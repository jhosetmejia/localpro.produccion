<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use App\Support\ContactarBase;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contactar clientes nuevos: cada día a cada persona del equipo se le asignan contactos de la base de datos
 * (los que tienen celular y aún no se han contactado) para escribirles por WhatsApp con un mensaje listo.
 */
class contactarController extends Controller
{
    public const MENSAJE = "¡Hola {nombre}! 👋✨\n\nTe saluda *{tienda}* 🛍️\nTe cuento que ya tenemos nuestra *tienda en línea* con el catálogo nuevo 😍\n\n👉 Míralo aquí: {catalogo}\n\nPuedes pedir directo por WhatsApp y te lo apartamos. ¡Te esperamos! 💚";

    public function __construct()
    {
        $this->middleware('permission:ver-cliente|crear-cliente|editar-cliente');
    }

    private function esJefe(): bool
    {
        $u = auth()->user();
        return (bool) ($u?->es_superadmin || $u?->hasAnyRole(['dueno', 'administrador']));
    }

    // La lógica de reparto vive en App\Support\ContactarBase (la usan también los asesores con quienes se comparte la base)
    private function libres() { return ContactarBase::libres(); }
    private function asignar(int $userId, int $cuantos): int { return ContactarBase::asignar($userId, $cuantos); }

    /**
     * Con quién se comparte la base cuando el dueño marca "Compartir mi base de datos con mis asesores":
     * - Negocio del súper admin o venta directa de LocalPro → todos los asesores de LocalPro.
     * - Dueño que es de la red (asesor o líder) → la gente de su red.
     * - Negocio que trajo un asesor → ese asesor y la gente de su red.
     */
    private function compartirCon(): ?array
    {
        if (!$this->esJefe()) return null;
        $u = auth()->user();
        $t = TiendaActual::tienda();
        $duenoSuper = $t && User::where('tienda_id', $t->id)->where('es_superadmin', true)->exists();
        if ($u->es_superadmin || $duenoSuper || $t?->venta_directa_id) return ['asesor_id' => null, 'texto' => 'todos los asesores de LocalPro'];
        if ($a = $u->asesor) return ['asesor_id' => $a->id, 'texto' => 'los asesores de su red'];
        if ($t?->asesor_id) return ['asesor_id' => (int) $t->asesor_id, 'texto' => 'su asesor de LocalPro y el equipo de su asesor'];
        return null;
    }

    // Activar o quitar "Compartir mi base de datos con mis asesores"
    public function compartir(Request $request)
    {
        $c = $this->compartirCon();
        abort_unless($c, 403, 'Su negocio todavía no tiene asesores con quién compartir la base.');
        $t = TiendaActual::tienda();
        $t->compartir_asesores = $request->boolean('compartir_asesores');
        $t->compartir_asesor_id = $t->compartir_asesores ? $c['asesor_id'] : null;
        $t->save();
        return back()->with('success', $t->compartir_asesores
            ? '👥 Listo: su base de datos quedó compartida con ' . $c['texto'] . '. A cada número se le escribe una sola vez.'
            : 'Su base de datos ya no se comparte con los asesores.');
    }

    public function index()
    {
        $tienda = TiendaActual::tienda();
        $yo = auth()->id();
        $hoy = now()->toDateString();
        $cuota = max(1, (int) ($tienda->contactos_diarios ?: 20));

        // Los de hoy de esta persona; si le faltan para completar su cuota, se le reparten
        $deHoy = Cliente::where('asignado_a', $yo)->where('asignado_fecha', $hoy)->count();
        if ($deHoy < $cuota) $this->asignar($yo, $cuota - $deHoy);

        $lista = Cliente::with('persona', 'contactadoPor:id,name')->where('asignado_a', $yo)->where('asignado_fecha', $hoy)
            ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get();

        $equipo = collect();
        if ($this->esJefe()) {
            // Gente del equipo + asesores a quienes se les compartió la base y ya tienen contactos de aquí
            $deFuera = Cliente::where('asignado_fecha', $hoy)->distinct()->pluck('asignado_a')
                ->merge(Cliente::whereNotNull('contactado_por')->distinct()->pluck('contactado_por'))->filter()->unique();
            $equipo = User::with('asesor:id,user_id')->where(fn ($q) => $q->where('tienda_id', TiendaActual::id())->orWhereIn('id', $deFuera))
                ->orderBy('name')->get(['id', 'name', 'tienda_id'])->map(function ($u) use ($hoy) {
                $q = Cliente::where('asignado_a', $u->id)->where('asignado_fecha', $hoy);
                return ['nombre' => $u->name, 'asesor' => (int) $u->tienda_id !== (int) TiendaActual::id() && $u->asesor, 'asignados' => (clone $q)->count(), 'hechos' => (clone $q)->whereNotNull('contactado_at')->count(),
                    'total' => Cliente::where('contactado_por', $u->id)->count()];
            })->filter(fn ($e) => $e['asignados'] || $e['total'])->values();
        }

        return view('cliente.contactar', [
            'tienda' => $tienda,
            'lista' => $lista,
            'cuota' => $cuota,
            'mensaje' => $tienda->mensaje_contacto ?: self::MENSAJE,
            'catalogo' => $tienda->catalogo_activo && $tienda->slug ? $tienda->url_catalogo : null,
            'esJefe' => $this->esJefe(),
            'equipo' => $equipo,
            'pendientesBase' => $this->libres()->count(),
            'contactadosTotal' => Cliente::whereNotNull('contactado_at')->count(),
            'compartir' => $this->compartirCon(),
        ]);
    }

    // Marcar un contacto: enviado, numero_malo o no_interesa (o deshacer).
    // Se marca también cualquier otro cliente con el mismo número, para que nadie más del equipo le vuelva a escribir.
    public function marcar(Request $request, Cliente $cliente)
    {
        abort_unless((int) $cliente->asignado_a === (int) auth()->id() || $this->esJefe(), 403);
        $estado = $request->input('estado');
        $cliente->loadMissing('persona');
        $ids = ContactarBase::mismoNumero($cliente);

        if ($estado === 'deshacer') {
            // Quitar la marca: el jefe siempre; la persona que lo marcó, solo en los primeros 30 minutos (por si se equivocó)
            $propio = (int) $cliente->contactado_por === (int) auth()->id() && $cliente->contactado_at && $cliente->contactado_at->gt(now()->subMinutes(30));
            if (!$this->esJefe() && !$propio) {
                return response()->json(['ok' => false, 'mensaje' => 'Este contacto ya quedó como contactado. Solo el dueño o el administrador puede quitar la marca.'], 403);
            }
            Cliente::whereIn('id', $ids)->update(['contactado_at' => null, 'contactado_por' => null, 'contacto_estado' => null]);
            return response()->json(['ok' => true]);
        }

        abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);

        // Si otra persona ya le escribió (el mismo número cargado dos veces), no se le vuelve a escribir
        [$ok, $mensaje, $repetido] = ContactarBase::marcar($cliente, (int) auth()->id(), $estado);
        return $ok ? response()->json(['ok' => true]) : response()->json(['ok' => false, 'repetido' => $repetido, 'mensaje' => $mensaje], 409);
    }

    // Revisar antes de abrir WhatsApp que nadie más le haya escrito ya a ese número
    public function revisar(Cliente $cliente)
    {
        abort_unless((int) $cliente->asignado_a === (int) auth()->id() || $this->esJefe(), 403);
        $mensaje = ContactarBase::yaEscrito($cliente);
        return response()->json(['libre' => !$mensaje, 'mensaje' => $mensaje]);
    }

    // Pedir más contactos hoy (cuando ya terminó los suyos)
    public function mas()
    {
        $n = $this->asignar(auth()->id(), max(1, (int) (TiendaActual::tienda()->contactos_diarios ?: 20)));
        return back()->with($n ? 'success' : 'error', $n ? "Le asignamos $n contactos más." : 'Ya no hay más contactos por escribir. ¡Suba más contactos a su base de datos!');
    }

    // El dueño o administrador cambia el mensaje y cuántos contactos al día por persona
    public function ajustes(Request $request)
    {
        abort_unless($this->esJefe(), 403);
        $d = $request->validate([
            'mensaje' => 'required|string|max:1000',
            'contactos_diarios' => 'required|integer|min:1|max:200',
        ], ['mensaje.required' => 'Escriba el mensaje.']);
        $t = TiendaActual::tienda();
        $t->mensaje_contacto = trim($d['mensaje']) === trim(self::MENSAJE) ? null : $d['mensaje'];
        $t->contactos_diarios = $d['contactos_diarios'];
        $t->save();
        return back()->with('success', 'Mensaje y cantidad diaria guardados.');
    }
}
