<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Devolucion;
use App\Models\MetodoPago;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cambios y devoluciones de ventas del POS.
 * - Reembolso: se devuelve el dinero desde la caja abierta del vendedor.
 * - Cambio: queda un saldo a favor (nota crédito NC-…) para usar en otra compra.
 */
class devolucionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:devolver-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:devolver-venta|usar-pos', ['only' => ['show', 'saldo']]);
    }

    public function create(PosVenta $venta)
    {
        if (!Caja::abiertaDe(Auth::id())) {
            return redirect()->route('cajas.abrir')->with('success', 'Abra su caja para poder hacer devoluciones.');
        }
        abort_if($venta->estado !== 'pagada', 422, 'Esta venta está anulada.');

        $venta->load(['items', 'pagos.metodo']);
        $devuelto = $this->devueltoPorItem($venta);
        return view('devolucion.create', [
            'venta' => $venta,
            'devuelto' => $devuelto,
            'metodos' => MetodoPago::activos()->get(),
        ]);
    }

    public function store(Request $request, PosVenta $venta)
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir');
        }

        $datos = $request->validate([
            'tipo' => 'required|in:reembolso,cambio',
            'metodo_pago_id' => 'required_if:tipo,reembolso|nullable|integer',
            'motivo' => 'nullable|string|max:255',
            'cantidades' => 'required|array',
            'cantidades.*' => 'nullable|numeric|min:0|max:100000',
        ], ['metodo_pago_id.required_if' => 'Elija cómo le devuelve el dinero al cliente.']);

        $dev = DB::transaction(function () use ($venta, $caja, $datos) {
            $venta = PosVenta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
            if ($venta->estado !== 'pagada') {
                throw ValidationException::withMessages(['cantidades' => 'Esta venta está anulada.']);
            }
            $venta->load('items.variante.producto');
            $devuelto = $this->devueltoPorItem($venta);

            $lineas = [];
            foreach ($venta->items as $item) {
                $cant = round((float) ($datos['cantidades'][$item->id] ?? 0), 3);
                if ($cant <= 0) {
                    continue;
                }
                $max = round((float) $item->cantidad - (float) ($devuelto[$item->id] ?? 0), 3);
                if ($cant > $max + 0.0005) {
                    throw ValidationException::withMessages(['cantidades' => "De {$item->descripcion} solo se pueden devolver {$max}."]);
                }
                $lineas[] = [$item, $cant];
            }
            if (!$lineas) {
                throw ValidationException::withMessages(['cantidades' => 'Indique cuántas unidades devuelve el cliente.']);
            }

            // Si la venta tuvo descuento, se devuelve proporcional a lo que realmente pagó
            $factor = $venta->subtotal > 0 ? (($venta->subtotal - $venta->descuento) / $venta->subtotal) : 1;
            $total = round(collect($lineas)->sum(fn ($l) => $l[1] * $l[0]->precio) * $factor, 2);

            $metodo = null;
            if ($datos['tipo'] === 'reembolso') {
                $metodo = MetodoPago::activos()->whereKey($datos['metodo_pago_id'])->first();
                if (!$metodo) {
                    throw ValidationException::withMessages(['metodo_pago_id' => 'Método de pago no válido.']);
                }
                if ($metodo->es_efectivo && $caja->efectivoEsperado() < $total) {
                    throw ValidationException::withMessages(['metodo_pago_id' => 'No hay suficiente efectivo en la caja para devolver ' . dinero($total) . '.']);
                }
            }

            $dev = Devolucion::create([
                'codigo' => TiendaActual::tienda()->siguienteNumeroNc(),
                'pos_venta_id' => $venta->id,
                'caja_id' => $caja->id,
                'user_id' => Auth::id(),
                'tipo' => $datos['tipo'],
                'metodo_pago_id' => $metodo?->id,
                'total' => $total,
                'saldo_disponible' => $datos['tipo'] === 'cambio' ? $total : 0,
                'motivo' => $datos['motivo'] ?? null,
            ]);

            foreach ($lineas as [$item, $cant]) {
                $dev->items()->create([
                    'pos_venta_item_id' => $item->id,
                    'producto_variante_id' => $item->producto_variante_id,
                    'cantidad' => $cant,
                    'precio' => $item->precio,
                    'subtotal' => round($cant * $item->precio * $factor, 2),
                ]);
                // La prenda vuelve al inventario
                if ($item->variante && $item->variante->controla) {
                    $item->variante->moverStock($cant, 'devolucion', $dev->codigo, $venta->numero);
                }
            }

            // Venta facturada electrónicamente: la devolución va a la DIAN como nota crédito parcial
            \App\Support\Dian\Facturador::enviarDespues(\App\Support\Dian\Facturador::notaCredito(
                $venta, 'Devolución ' . $dev->codigo . ($dev->motivo ? ': ' . $dev->motivo : ''), '1', $dev,
                collect($lineas)->map(fn ($l) => ['item_id' => $l[0]->id, 'cantidad' => $l[1]])->all(), $total
            ));

            return $dev;
        });

        return redirect()->route('devoluciones.show', $dev)->with('success', $dev->tipo === 'cambio'
            ? 'Cambio registrado. El cliente tiene ' . dinero($dev->total) . ' de saldo a favor con el código ' . $dev->codigo . '.'
            : 'Devolución registrada. Entregue ' . dinero($dev->total) . ' al cliente.');
    }

    // Comprobante de la devolución / nota crédito (80 mm)
    public function show(Devolucion $devolucion)
    {
        return view('devolucion.show', [
            'dev' => $devolucion->load(['items.ventaItem', 'venta', 'metodo', 'user:id,name']),
            'negocio' => Negocio::datos(),
        ]);
    }

    // El POS consulta el saldo a favor de una nota crédito
    public function saldo(Request $request)
    {
        $codigo = strtoupper(trim((string) $request->get('codigo')));
        $dev = Devolucion::where('codigo', $codigo)->where('tipo', 'cambio')->first();
        if (!$dev || $dev->saldo_disponible <= 0) {
            return response()->json(['message' => $dev ? 'Esa nota crédito ya fue usada.' : 'No existe esa nota crédito.'], 404);
        }
        return response()->json(['codigo' => $dev->codigo, 'saldo' => (float) $dev->saldo_disponible, 'metodo_id' => MetodoPago::saldoAFavor()->id]);
    }

    private function devueltoPorItem(PosVenta $venta): array
    {
        return DB::table('devolucion_items as di')
            ->join('devoluciones as d', 'd.id', '=', 'di.devolucion_id')
            ->where('d.pos_venta_id', $venta->id)
            ->groupBy('di.pos_venta_item_id')
            ->select('di.pos_venta_item_id', DB::raw('SUM(di.cantidad) as c'))
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->pos_venta_item_id => (int) $r->c])
            ->all();
    }
}
