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

    // El número tal como se usa en WhatsApp (solo dígitos y con indicativo), para saber si dos clientes son la misma persona
    private function numero(?string $tel): string
    {
        return (string) numero_whatsapp($tel);
    }

    // Números que ya no se pueden dar: ya contactados por alguien, o asignados hoy a otra persona
    private function numerosOcupados(): array
    {
        $hoy = now()->toDateString();
        $ocupados = [];
        Cliente::query()
            ->where(fn ($q) => $q->whereNotNull('contactado_at')->orWhere('asignado_fecha', $hoy))
            ->with('persona:id,telefono')->select(['id', 'persona_id'])
            ->chunkById(1000, function ($lote) use (&$ocupados) {
                foreach ($lote as $c) {
                    $n = $this->numero($c->persona?->telefono);
                    if ($n !== '') $ocupados[$n] = true;
                }
            });
        return $ocupados;
    }

    // Reparte contactos sin repetir número: una persona se contacta UNA sola vez, aunque esté cargada dos veces en la base
    private function asignar(int $userId, int $cuantos): int
    {
        if ($cuantos <= 0) return 0;
        return DB::transaction(function () use ($userId, $cuantos) {
            $ocupados = $this->numerosOcupados();
            $elegidos = [];
            $desde = 0;
            while (count($elegidos) < $cuantos) {
                $lote = $this->libres()->with('persona:id,telefono')->where('id', '>', $desde)
                    ->orderBy('id')->limit(300)->lockForUpdate()->get(['id', 'persona_id']);
                if ($lote->isEmpty()) break;
                foreach ($lote as $c) {
                    $desde = $c->id;
                    $n = $this->numero($c->persona?->telefono);
                    if ($n === '' || isset($ocupados[$n])) continue;
                    $ocupados[$n] = true;
                    $elegidos[] = $c->id;
                    if (count($elegidos) >= $cuantos) break;
                }
            }
            if (!$elegidos) return 0;
            Cliente::whereIn('id', $elegidos)->update(['asignado_a' => $userId, 'asignado_fecha' => now()->toDateString()]);
            return count($elegidos);
        });
    }

    // Los clientes de la base que tienen el mismo número (la misma persona cargada varias veces)
    private function mismoNumero(Cliente $cliente)
    {
        $n = $this->numero($cliente->persona?->telefono);
        if ($n === '') return collect([$cliente->id]);
        $digitos = substr($n, -7);
        return Cliente::with('persona:id,telefono')
            ->whereHas('persona', fn ($p) => $p->where('telefono', 'like', '%' . $digitos . '%'))
            ->get(['id', 'persona_id'])
            ->filter(fn ($c) => $this->numero($c->persona?->telefono) === $n)
            ->pluck('id')->push($cliente->id)->unique();
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

    // Marcar un contacto: enviado, numero_malo o no_interesa (o deshacer).
    // Se marca también cualquier otro cliente con el mismo número, para que nadie más del equipo le vuelva a escribir.
    public function marcar(Request $request, Cliente $cliente)
    {
        abort_unless((int) $cliente->asignado_a === (int) auth()->id() || $this->esJefe(), 403);
        $estado = $request->input('estado');
        $cliente->loadMissing('persona');
        $ids = $this->mismoNumero($cliente);

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
        if ($estado === 'enviado') {
            $previo = Cliente::with('contactadoPor:id,name')->whereIn('id', $ids)->whereNotNull('contactado_at')
                ->where('id', '<>', $cliente->id)->first();
            if ($previo && (int) $previo->contactado_por !== (int) auth()->id()) {
                Cliente::whereIn('id', $ids)->whereNull('contactado_at')->update([
                    'contactado_at' => $previo->contactado_at, 'contactado_por' => $previo->contactado_por, 'contacto_estado' => $previo->contacto_estado,
                ]);
                return response()->json(['ok' => false, 'repetido' => true,
                    'mensaje' => 'A este número ya le escribió ' . ($previo->contactadoPor->name ?? 'otra persona del equipo') . '. No le escriba otra vez.'], 409);
            }
        }

        Cliente::whereIn('id', $ids)->update(['contactado_at' => now(), 'contactado_por' => auth()->id(), 'contacto_estado' => $estado]);
        return response()->json(['ok' => true]);
    }

    // Revisar antes de abrir WhatsApp que nadie más le haya escrito ya a ese número
    public function revisar(Cliente $cliente)
    {
        abort_unless((int) $cliente->asignado_a === (int) auth()->id() || $this->esJefe(), 403);
        $cliente->loadMissing('persona');
        $previo = Cliente::with('contactadoPor:id,name')->whereIn('id', $this->mismoNumero($cliente))->whereNotNull('contactado_at')->first();
        return response()->json([
            'libre' => !$previo,
            'mensaje' => $previo ? 'A este número ya le escribió ' . ($previo->contactadoPor->name ?? 'otra persona del equipo') . ' el ' . $previo->contactado_at->format('d/m/Y') . '. No le escriba otra vez.' : null,
        ]);
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
