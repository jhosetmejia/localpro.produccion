<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
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
        return (bool) auth()->user()?->hasAnyRole(['dueno', 'administrador']);
    }

    // Los que se le pueden asignar: con celular, sin contactar y sin asignar hoy a otra persona
    private function libres()
    {
        $hoy = now()->toDateString();
        return Cliente::query()
            ->whereNull('contactado_at')
            ->where(fn ($q) => $q->whereNull('asignado_fecha')->orWhere('asignado_fecha', '<', $hoy))
            ->whereHas('persona', fn ($p) => $p->where('estado', 1)->whereNotNull('telefono')->where('telefono', '<>', ''));
    }

    private function asignar(int $userId, int $cuantos): int
    {
        if ($cuantos <= 0) return 0;
        return DB::transaction(function () use ($userId, $cuantos) {
            $ids = $this->libres()->orderBy('id')->limit($cuantos)->lockForUpdate()->pluck('id');
            if ($ids->isEmpty()) return 0;
            Cliente::whereIn('id', $ids)->update(['asignado_a' => $userId, 'asignado_fecha' => now()->toDateString()]);
            return $ids->count();
        });
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

        $lista = Cliente::with('persona')->where('asignado_a', $yo)->where('asignado_fecha', $hoy)
            ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get();

        $equipo = collect();
        if ($this->esJefe()) {
            $equipo = User::where('tienda_id', TiendaActual::id())->orderBy('name')->get(['id', 'name'])->map(function ($u) use ($hoy) {
                $q = Cliente::where('asignado_a', $u->id)->where('asignado_fecha', $hoy);
                return ['nombre' => $u->name, 'asignados' => (clone $q)->count(), 'hechos' => (clone $q)->whereNotNull('contactado_at')->count(),
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
        ]);
    }

    // Marcar un contacto: enviado, numero_malo o no_interesa (o deshacer)
    public function marcar(Request $request, Cliente $cliente)
    {
        abort_unless((int) $cliente->asignado_a === (int) auth()->id() || $this->esJefe(), 403);
        $estado = $request->input('estado');
        if ($estado === 'deshacer') {
            $cliente->forceFill(['contactado_at' => null, 'contactado_por' => null, 'contacto_estado' => null])->save();
        } else {
            abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);
            $cliente->forceFill(['contactado_at' => now(), 'contactado_por' => auth()->id(), 'contacto_estado' => $estado])->save();
        }
        return response()->json(['ok' => true]);
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
