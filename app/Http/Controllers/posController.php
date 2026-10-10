<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class posController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('facturaPublica');
        $this->middleware('permission:usar-pos', ['only' => ['index', 'buscar', 'store']]);
        $this->middleware('permission:usar-pos|ver-cajas', ['only' => ['ticket', 'ventas']]);
        $this->middleware('permission:anular-venta-pos', ['only' => ['anular']]);
    }

    public function index()
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir')->with('success', 'Abra su caja para empezar a vender.');
        }
        return view('pos.index', [
            'caja' => $caja,
            'metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']),
            'negocio' => Negocio::datos(),
        ]);
    }

    // Busca prendas por nombre, código del producto o código de barras de la talla/color
    public function buscar(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $variantes = ProductoVariante::query()
            ->with('producto:id,nombre,codigo,precio_venta,img_path')
            ->where('estado', 1)
            ->whereHas('producto', fn ($p) => $p->where('estado', 1))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('codigo_barras', $q)
                        ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%"));
                });
            })
            ->orderByDesc('stock')
            ->limit(120)
            ->get();

        // Agrupadas por producto para elegir talla y color en el POS
        $productos = $variantes->groupBy('producto_id')->map(function ($grupo) {
            $p = $grupo->first()->producto;
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo' => $p->codigo,
                'precio' => (float) $p->precio_venta,
                'img' => $p->img_path ? asset('storage/productos/' . $p->img_path) : null,
                'stock' => (int) $grupo->sum('stock'),
                'variantes' => $grupo->sortBy(['talla', 'color'])->values()->map(fn ($v) => [
                    'id' => $v->id,
                    'talla' => $v->talla,
                    'color' => $v->color,
                    'etiqueta' => $v->etiqueta,
                    'stock' => (int) $v->stock,
                    'precio' => $v->precio_final,
                    'codigo_barras' => $v->codigo_barras,
                ]),
            ];
        })->values();

        // Si escanearon un código de barras exacto, el POS lo agrega directo
        $exacta = $q !== '' ? $variantes->firstWhere('codigo_barras', $q) : null;

        return response()->json([
            'productos' => $productos,
            'exacta' => $exacta ? $exacta->id : null,
        ]);
    }

    public function store(Request $request)
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return response()->json(['message' => 'Su caja está cerrada. Ábrala para vender.'], 422);
        }

        $datos = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*.variante_id' => 'required|integer|exists:producto_variantes,id',
            'items.*.cantidad' => 'required|integer|min:1|max:1000',
            'descuento' => 'nullable|numeric|min:0|max:999999999',
            'pagos' => 'required|array|min:1|max:6',
            'pagos.*.metodo_id' => 'required|integer|exists:metodos_pago,id',
            'pagos.*.monto' => 'required|numeric|min:0|max:999999999',
            'pagos.*.referencia' => 'nullable|string|max:60',
            'cliente_nombre' => 'nullable|string|max:80',
            'cliente_telefono' => 'nullable|string|max:20',
        ], [
            'items.required' => 'Agregue al menos una prenda.',
            'pagos.required' => 'Indique cómo pagó el cliente.',
        ]);

        $venta = DB::transaction(function () use ($datos, $caja) {
            // Bloquea las tallas/colores vendidos para que dos cajas no vendan la misma unidad
            $ids = collect($datos['items'])->pluck('variante_id')->unique();
            $variantes = ProductoVariante::with('producto:id,nombre,precio_venta')
                ->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $pedidas = collect($datos['items'])->groupBy('variante_id')->map->sum('cantidad');
            foreach ($pedidas as $id => $cant) {
                $v = $variantes[$id];
                if ($v->stock < $cant) {
                    throw ValidationException::withMessages([
                        'items' => "No hay suficiente {$v->producto->nombre} ({$v->etiqueta}). Disponibles: {$v->stock}.",
                    ]);
                }
            }

            // Precio oficial de cada talla/color (no se confía en el que envía el navegador)
            $items = collect($datos['items'])->map(fn ($i) => array_merge($i, ['precio' => $variantes[$i['variante_id']]->precio_final]));
            $subtotal = $items->sum(fn ($i) => round($i['cantidad'] * $i['precio'], 2));
            $descuento = min((float) ($datos['descuento'] ?? 0), $subtotal);
            $total = round($subtotal - $descuento, 2);

            $metodos = MetodoPago::whereIn('id', collect($datos['pagos'])->pluck('metodo_id'))->get()->keyBy('id');
            $pagos = collect($datos['pagos'])->filter(fn ($p) => $p['monto'] > 0)->values();
            $pagado = round($pagos->sum('monto'), 2);
            if ($pagado + 0.001 < $total) {
                throw ValidationException::withMessages(['pagos' => 'El pago no alcanza: faltan $' . number_format($total - $pagado, 0, ',', '.')]);
            }
            // Solo el efectivo puede dar cambio
            $cambio = round($pagado - $total, 2);
            $efectivoRecibido = $pagos->filter(fn ($p) => $metodos[$p['metodo_id']]->es_efectivo)->sum('monto');
            if ($cambio > 0 && $cambio - $efectivoRecibido > 0.001) {
                throw ValidationException::withMessages(['pagos' => 'Los pagos que no son en efectivo no pueden superar el total.']);
            }

            $venta = PosVenta::create([
                'numero' => 'TMP-' . uniqid(),
                'caja_id' => $caja->id,
                'user_id' => Auth::id(),
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
                'cliente_telefono' => $datos['cliente_telefono'] ?? null,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'cambio' => max($cambio, 0),
                'estado' => 'pagada',
            ]);
            $venta->update(['numero' => 'POS-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($items as $i) {
                $v = $variantes[$i['variante_id']];
                $venta->items()->create([
                    'producto_variante_id' => $v->id,
                    'descripcion' => $v->producto->nombre,
                    'talla' => $v->talla,
                    'color' => $v->color,
                    'cantidad' => $i['cantidad'],
                    'precio' => $i['precio'],
                    'subtotal' => round($i['cantidad'] * $i['precio'], 2),
                ]);
                $v->moverStock(-(int) $i['cantidad'], 'venta', $venta->numero);
            }

            // El cambio se descuenta del efectivo para que la caja cuadre
            $cambioPendiente = max($cambio, 0);
            foreach ($pagos as $p) {
                $monto = (float) $p['monto'];
                if ($cambioPendiente > 0 && $metodos[$p['metodo_id']]->es_efectivo) {
                    $resta = min($cambioPendiente, $monto);
                    $monto -= $resta;
                    $cambioPendiente -= $resta;
                }
                if ($monto > 0) {
                    $venta->pagos()->create([
                        'metodo_pago_id' => $p['metodo_id'],
                        'monto' => round($monto, 2),
                        'referencia' => $p['referencia'] ?? null,
                    ]);
                }
            }

            return $venta;
        });

        return response()->json([
            'id' => $venta->id,
            'numero' => $venta->numero,
            'total' => (float) $venta->total,
            'cambio' => (float) $venta->cambio,
            'ticket_url' => route('pos.ticket', $venta),
            'factura_url' => $venta->url_factura,
            'whatsapp' => $venta->whatsapp_numero,
        ]);
    }

    // Tiquete para impresora térmica de 80 mm
    public function ticket(PosVenta $venta)
    {
        $this->autorizar($venta);
        return view('pos.ticket', [
            'venta' => $venta->load(['items', 'pagos.metodo', 'user:id,name']),
            'negocio' => Negocio::datos(),
        ]);
    }

    // Factura que ve el cliente desde el enlace de WhatsApp (enlace firmado, sin iniciar sesión)
    public function facturaPublica(Request $request, PosVenta $venta)
    {
        abort_unless($request->hasValidSignature(), 403, 'El enlace de la factura no es válido.');
        return view('pos.factura', [
            'venta' => $venta->load(['items', 'pagos.metodo', 'user:id,name']),
            'negocio' => Negocio::datos(),
        ]);
    }

    public function ventas(Request $request)
    {
        $todas = Auth::user()->can('ver-cajas-todas');
        $ventas = PosVenta::with(['user:id,name', 'pagos.metodo'])
            ->when(!$todas, fn ($q) => $q->where('user_id', Auth::id()))
            ->when($request->get('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('numero', 'like', "%$t%")->orWhere('cliente_nombre', 'like', "%$t%")->orWhere('cliente_telefono', 'like', "%$t%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();
        return view('pos.ventas', compact('ventas'));
    }

    // Anula la venta y devuelve las prendas al inventario
    public function anular(PosVenta $venta)
    {
        DB::transaction(function () use ($venta) {
            $venta = PosVenta::whereKey($venta->id)->lockForUpdate()->first();
            if ($venta->estado !== 'pagada') {
                return;
            }
            abort_if($venta->caja->estado !== 'abierta', 422, 'Solo se pueden anular ventas de una caja abierta.');
            foreach ($venta->items as $item) {
                if ($item->variante) {
                    $item->variante->moverStock((int) $item->cantidad, 'anulacion', $venta->numero);
                }
            }
            $venta->update(['estado' => 'anulada', 'anulada_en' => now(), 'anulada_por' => Auth::id()]);
        });
        return back()->with('success', 'Venta anulada y prendas devueltas al inventario.');
    }

    private function autorizar(PosVenta $venta): void
    {
        abort_unless((int) $venta->user_id === (int) Auth::id() || Auth::user()->can('ver-cajas-todas'), 403);
    }
}
