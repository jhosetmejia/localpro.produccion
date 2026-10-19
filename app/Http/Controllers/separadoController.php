<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Models\ProductoVariante;
use App\Models\Separado;
use App\Models\SeparadoAbono;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

// Separados con abonos: el cliente separa la prenda, abona por partes y al completar se la lleva
class separadoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('usar-separados') || $u->can('usar-pos')), 403);
            return $next($request);
        })->except('publico');
    }

    public function index(Request $request)
    {
        $filtro = $request->get('ver', 'activos');
        $q = trim((string) $request->get('q'));
        $lista = Separado::with('items')
            ->when($filtro === 'activos', fn ($w) => $w->where('estado', 'activo'))
            ->when($filtro === 'listos', fn ($w) => $w->where('estado', 'activo')->whereColumn('abonado', '>=', 'total'))
            ->when($filtro === 'vencidos', fn ($w) => $w->where('estado', 'activo')->whereDate('vence_en', '<', today()))
            ->when($filtro === 'entregados', fn ($w) => $w->where('estado', 'entregado'))
            ->when($filtro === 'cancelados', fn ($w) => $w->where('estado', 'cancelado'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('numero', 'like', "%$q%")->orWhere('cliente_nombre', 'like', "%$q%")->orWhere('cliente_telefono', 'like', "%$q%")))
            ->orderByRaw("estado = 'activo' desc")->latest()->paginate(30)->withQueryString();

        $activos = Separado::where('estado', 'activo');
        return view('separado.index', [
            'lista' => $lista, 'filtro' => $filtro, 'q' => $q,
            'kpi' => [
                'activos' => (clone $activos)->count(),
                'porCobrar' => (float) (clone $activos)->sum(DB::raw('GREATEST(total - abonado, 0)')),
                'abonado' => (float) (clone $activos)->sum('abonado'),
                'listos' => (clone $activos)->whereColumn('abonado', '>=', 'total')->count(),
                'vencidos' => (clone $activos)->whereDate('vence_en', '<', today())->count(),
                'abonosHoy' => (float) SeparadoAbono::where('anulado', false)->whereDate('created_at', today())->sum('monto'),
            ],
        ]);
    }

    public function create()
    {
        if (!Caja::abiertaDe(Auth::id())) {
            return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para recibir el abono del separado.');
        }
        return view('separado.create', ['metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo'])]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variante_id' => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:9999',
            'cliente_id' => 'nullable|integer',
            'cliente_nombre' => 'required|string|min:2|max:80',
            'cliente_telefono' => 'nullable|string|max:20',
            'descuento' => 'nullable|numeric|min:0',
            'abono' => 'required|numeric|min:0',
            'metodo_id' => 'nullable|integer',
            'vence_dias' => 'nullable|integer|min:0|max:365',
            'nota' => 'nullable|string|max:255',
        ], ['items.required' => 'Agregue al menos una prenda.', 'cliente_nombre.required' => 'Escriba el nombre del cliente.']);

        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) throw ValidationException::withMessages(['abono' => 'Abra su caja para recibir el abono.']);

        $sep = DB::transaction(function () use ($d, $caja) {
            Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $ids = collect($d['items'])->pluck('variante_id')->unique();
            $vars = ProductoVariante::with('producto:id,nombre,precio_venta,precio_costo,controla_inventario,unidad')->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            if ($vars->count() !== $ids->count()) throw ValidationException::withMessages(['items' => 'Alguna prenda ya no está disponible. Recargue la página.']);

            $pedidas = collect($d['items'])->groupBy('variante_id')->map(fn ($g) => round($g->sum(fn ($i) => (float) $i['cantidad']), 3));
            foreach ($pedidas as $id => $cant) {
                $v = $vars[$id];
                if ($v->controla && (float) $v->stock + 0.0005 < $cant) {
                    throw ValidationException::withMessages(['items' => "No hay suficiente {$v->producto->nombre} ({$v->etiqueta}). Disponibles: " . cantidad($v->stock) . '.']);
                }
            }
            $subtotal = collect($d['items'])->sum(fn ($i) => round((float) $i['cantidad'] * $vars[$i['variante_id']]->precio_final, 2));
            $total = round($subtotal - min((float) ($d['descuento'] ?? 0), $subtotal), 2);
            $abono = round((float) $d['abono'], 2);
            if ($abono > $total) throw ValidationException::withMessages(['abono' => 'El abono no puede ser mayor que el total (' . dinero($total) . '). Si paga todo, mejor véndalo en la caja.']);
            $metodo = null;
            if ($abono > 0) {
                $metodo = MetodoPago::activos()->whereKey($d['metodo_id'] ?? 0)->first();
                if (!$metodo) throw ValidationException::withMessages(['metodo_id' => 'Elija cómo pagó el abono.']);
            }
            $tel = preg_replace('/\D/', '', (string) ($d['cliente_telefono'] ?? ''));

            $sep = Separado::create([
                'numero' => 'SEP-' . str_pad((string) (Separado::count() + 1), 6, '0', STR_PAD_LEFT),
                'cliente_id' => \App\Models\Cliente::whereKey($d['cliente_id'] ?? 0)->value('id'),
                'cliente_nombre' => trim($d['cliente_nombre']),
                'cliente_telefono' => $tel ?: null,
                'user_id' => Auth::id(),
                'total' => $total,
                'abonado' => 0,
                'estado' => 'activo',
                'vence_en' => ($d['vence_dias'] ?? 30) > 0 ? today()->addDays((int) ($d['vence_dias'] ?? 30)) : null,
                'nota' => $d['nota'] ?? null,
            ]);
            foreach ($d['items'] as $i) {
                $v = $vars[$i['variante_id']];
                $cant = round((float) $i['cantidad'], 3);
                $sep->items()->create([
                    'producto_variante_id' => $v->id, 'descripcion' => $v->producto->nombre, 'talla' => $v->talla, 'color' => $v->color,
                    'cantidad' => $cant, 'precio' => $v->precio_final, 'subtotal' => round($cant * $v->precio_final, 2),
                ]);
                // La prenda sale del inventario disponible: queda guardada en bodega para el cliente
                if ($v->controla) $v->moverStock(-$cant, 'separado', $sep->numero);
            }
            if ($abono > 0) self::registrarAbono($sep, $abono, $metodo->id, $caja->id, 'Abono inicial');
            return $sep;
        });

        return redirect()->route('separados.show', $sep)->with('success', 'Separado ' . $sep->numero . ' creado. La prenda queda guardada para ' . $sep->cliente_nombre . '.')->with('abono_nuevo', $sep->abonos()->latest('id')->value('id'));
    }

    public function show(Separado $separado)
    {
        return view('separado.show', [
            's' => $separado->load(['items.variante.producto.fotos', 'abonos.metodo', 'abonos.user:id,name', 'user:id,name', 'venta']),
            'metodos' => MetodoPago::activos()->get(['id', 'nombre']),
            'caja' => Caja::abiertaDe(Auth::id()),
            'publico' => self::urlPublica($separado),
        ]);
    }

    public function abonar(Request $request, Separado $separado)
    {
        $d = $request->validate(['monto' => 'required|numeric|min:0.01', 'metodo_id' => 'required|integer', 'nota' => 'nullable|string|max:255'],
            ['monto.required' => 'Escriba cuánto abona.', 'metodo_id.required' => 'Elija cómo pagó.']);
        if ($separado->estado !== 'activo') return back()->withErrors('Este separado ya no está activo.');
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para recibir el abono.');
        $metodo = MetodoPago::activos()->whereKey($d['metodo_id'])->first();
        if (!$metodo) return back()->withErrors('Elija un método de pago válido.');

        $abono = DB::transaction(function () use ($separado, $d, $metodo, $caja) {
            $s = Separado::whereKey($separado->id)->lockForUpdate()->first();
            $monto = round((float) $d['monto'], 2);
            if ($monto > $s->saldo + 0.001) throw ValidationException::withMessages(['monto' => 'Solo le falta ' . dinero($s->saldo) . '. No puede abonar más.']);
            return self::registrarAbono($s, $monto, $metodo->id, $caja->id, $d['nota'] ?? null);
        });
        $separado->refresh();
        $msg = $separado->pagado ? '¡Separado pagado completo! Ya puede entregarle la prenda.' : 'Abono ' . $abono->numero . ' registrado. Le falta ' . dinero($separado->saldo) . '.';
        return redirect()->route('separados.show', $separado)->with('success', $msg)->with('abono_nuevo', $abono->id);
    }

    private static function registrarAbono(Separado $s, float $monto, int $metodoId, ?int $cajaId, ?string $nota): SeparadoAbono
    {
        $s->abonado = round($s->abonado + $monto, 2);
        $s->save();
        return SeparadoAbono::create([
            'separado_id' => $s->id,
            'numero' => 'AB-' . str_pad((string) (SeparadoAbono::count() + 1), 6, '0', STR_PAD_LEFT),
            'caja_id' => $cajaId, 'user_id' => Auth::id(), 'metodo_pago_id' => $metodoId,
            'monto' => $monto, 'saldo_despues' => $s->saldo, 'nota' => $nota,
        ]);
    }

    // Recibo del abono para impresora térmica de 80 mm
    public function recibo(SeparadoAbono $abono)
    {
        $s = $abono->separado()->with('items')->first();
        return view('separado.recibo', ['a' => $abono->load('metodo', 'user:id,name'), 's' => $s, 'negocio' => Negocio::datos(), 'wa' => self::mensajeAbono($s, $abono)]);
    }

    // Al pagar todo: se entrega la prenda y queda registrada como venta (el dinero ya entró con los abonos)
    public function entregar(Separado $separado)
    {
        if ($separado->estado !== 'activo') return back()->withErrors('Este separado ya no está activo.');
        if (!$separado->pagado) return back()->withErrors('Aún le falta ' . dinero($separado->saldo) . '. Solo se entrega cuando esté pagado completo.');
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para registrar la entrega.');

        $venta = DB::transaction(function () use ($separado, $caja) {
            $s = Separado::with('items.variante.producto')->whereKey($separado->id)->lockForUpdate()->first();
            $venta = PosVenta::create([
                'numero' => TiendaActual::tienda()->siguienteNumeroPos(),
                'caja_id' => $caja->id, 'user_id' => Auth::id(), 'cliente_id' => $s->cliente_id,
                'cliente_nombre' => $s->cliente_nombre, 'cliente_telefono' => $s->cliente_telefono,
                'subtotal' => $s->items->sum('subtotal'), 'descuento' => max(0, round($s->items->sum('subtotal') - $s->total, 2)),
                'total' => $s->total, 'cambio' => 0, 'estado' => 'pagada',
            ]);
            foreach ($s->items as $i) {
                $venta->items()->create([
                    'producto_variante_id' => $i->producto_variante_id, 'descripcion' => $i->descripcion . ' (separado ' . $s->numero . ')',
                    'talla' => $i->talla, 'color' => $i->color, 'cantidad' => $i->cantidad, 'precio' => $i->precio,
                    'costo' => $i->variante?->costo_final ?? 0, 'subtotal' => $i->subtotal,
                ]);
            }
            $venta->pagos()->create(['metodo_pago_id' => MetodoPago::pagadoConAbonos()->id, 'monto' => $s->total, 'referencia' => $s->numero]);
            $s->update(['estado' => 'entregado', 'entregado_en' => now(), 'pos_venta_id' => $venta->id]);
            return $venta;
        });
        return redirect()->route('separados.show', $separado)->with('success', '¡Entregado! Quedó registrado como la venta ' . $venta->numero . '.');
    }

    public function cancelar(Request $request, Separado $separado)
    {
        $d = $request->validate(['motivo' => 'nullable|string|max:255', 'devolver' => 'nullable|boolean', 'metodo_id' => 'nullable|integer']);
        if ($separado->estado !== 'activo') return back()->withErrors('Este separado ya no está activo.');
        $devolver = (bool) ($d['devolver'] ?? false) && $separado->abonado > 0;
        $caja = Caja::abiertaDe(Auth::id());
        if ($devolver && !$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para devolver el dinero.');

        DB::transaction(function () use ($separado, $d, $devolver, $caja) {
            $s = Separado::with('items.variante')->whereKey($separado->id)->lockForUpdate()->first();
            // La prenda vuelve al inventario disponible
            foreach ($s->items as $i) {
                if ($i->variante && $i->variante->controla) $i->variante->moverStock((float) $i->cantidad, 'sep_cancelado', $s->numero);
            }
            if ($devolver) {
                $metodo = MetodoPago::activos()->whereKey($d['metodo_id'] ?? 0)->first() ?? MetodoPago::activos()->where('es_efectivo', true)->first();
                SeparadoAbono::create([
                    'separado_id' => $s->id, 'numero' => 'AB-' . str_pad((string) (SeparadoAbono::count() + 1), 6, '0', STR_PAD_LEFT),
                    'caja_id' => $caja->id, 'user_id' => Auth::id(), 'metodo_pago_id' => $metodo->id,
                    'monto' => -$s->abonado, 'saldo_despues' => 0, 'nota' => 'Devolución por cancelación',
                ]);
            }
            $s->update([
                'estado' => 'cancelado', 'cancelado_en' => now(),
                'motivo_cancelacion' => trim(($d['motivo'] ?? '') . ($s->abonado > 0 ? ($devolver ? ' · Se devolvieron ' . dinero($s->abonado) : ' · Los abonos (' . dinero($s->abonado) . ') no se devolvieron') : '')),
            ]);
        });
        return redirect()->route('separados.show', $separado)->with('success', 'Separado cancelado. La prenda volvió al inventario.');
    }

    // Página pública (enlace firmado) para que el cliente vea su separado y sus abonos
    public function publico(Separado $separado)
    {
        $tienda = Tienda::find($separado->tienda_id);
        \App\Support\TiendaActual::set($tienda?->id); // valores en la moneda de esa tienda (ej. dólares en Venezuela)
        return view('separado.publico', ['s' => $separado->load(['items', 'abonos' => fn ($q) => $q->where('anulado', false)]), 'tienda' => $tienda]);
    }

    public static function urlPublica(Separado $s): string
    {
        return url(URL::signedRoute('separados.publico', ['separado' => $s->id], null, false));
    }

    public static function mensajeAbono(Separado $s, SeparadoAbono $a): string
    {
        $prendas = $s->items->map(fn ($i) => $i->descripcion . ($i->talla ? ' T' . $i->talla : ''))->implode(', ');
        $neg = Negocio::datos()->nombre;
        $txt = 'Hola ' . strtok($s->cliente_nombre, ' ') . ' 👋 En ' . $neg . ' recibimos su abono de *' . dinero($a->monto) . '* (recibo ' . $a->numero . ') para su separado ' . $s->numero . ': ' . $prendas . ".\n\n"
            . 'Total: ' . dinero($s->total) . "\nAbonado: " . dinero($s->abonado) . "\n";
        $txt .= $s->pagado ? "✅ ¡Ya está pagado completo! Puede pasar por su prenda.\n" : '*Le falta: ' . dinero($s->saldo) . "*\n" . ($s->vence_en ? 'Tiene hasta el ' . $s->vence_en->format('d/m/Y') . " para completar el pago.\n" : '');
        return $txt . "\nVea su recibo y sus abonos aquí: " . self::urlPublica($s);
    }
}
