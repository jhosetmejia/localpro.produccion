<?php

namespace App\Http\Controllers;

use App\Models\Alquiler;
use App\Models\Caja;
use App\Models\Devolucion;
use App\Models\PosVenta;
use App\Models\PosVentaCorreccion;
use App\Models\Separado;
use App\Support\Dian\Impuestos;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corregir venta (error al digitar): el administrador baja la cantidad de productos que se registraron de más
 * y/o corrige el precio (ej. se vendió en $150.000 y se digitó $125.000),
 * aunque la caja de ese día ya esté cerrada. Todo cuadra solo:
 * - Los productos vuelven al inventario (movimiento "correccion").
 * - Se recalculan subtotal, descuento, total, IVA y ganancia de la venta (los reportes salen de ahí).
 * - Se baja el pago (por defecto el efectivo) y la caja de ese día recalcula total de ventas, efectivo esperado
 *   y diferencia (el faltante que dejó el error desaparece). No mueve plata de la caja de hoy.
 * - Queda el registro de quién corrigió, cuándo y por qué.
 */
class correccionVentaController extends Controller
{
    private function soloAdmin(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->es_superadmin || $u->hasAnyRole(['dueno', 'administrador'])), 403, 'Solo el dueño o el administrador puede corregir ventas.');
    }

    // Por qué no se puede corregir esta venta (null = sí se puede)
    public static function bloqueo(PosVenta $venta): ?string
    {
        if ($venta->estado !== 'pagada') return 'Esta venta está anulada.';
        $doc = $venta->documento;
        if ($doc && $doc->estado !== 'rechazado') return 'Esta venta salió como documento electrónico ante la DIAN: corríjala con una devolución (genera la nota crédito).';
        if (Devolucion::where('pos_venta_id', $venta->id)->exists()) return 'Esta venta ya tiene devoluciones: no se puede corregir.';
        if (Separado::where('pos_venta_id', $venta->id)->exists()) return 'Esta venta viene de un separado: no se puede corregir aquí.';
        if (class_exists(Alquiler::class) && Alquiler::where('pos_venta_id', $venta->id)->exists()) return 'Esta venta viene de un alquiler: no se puede corregir aquí.';
        return null;
    }

    public function create(PosVenta $venta)
    {
        $this->soloAdmin();
        $venta->load(['items', 'pagos.metodo', 'caja.user:id,name', 'user:id,name', 'correcciones.user:id,name']);
        return view('pos.corregir', ['venta' => $venta, 'bloqueo' => self::bloqueo($venta)]);
    }

    public function store(Request $request, PosVenta $venta)
    {
        $this->soloAdmin();
        $datos = $request->validate([
            'cantidades' => 'required|array',
            'cantidades.*' => 'nullable|numeric|min:0',
            'precios' => 'nullable|array',
            'precios.*' => 'nullable|string|max:20',
            'pago_id' => 'nullable|integer',
            'motivo' => 'required|string|min:3|max:255',
        ], ['motivo.required' => 'Escriba el motivo de la corrección (ej. "se digitaron 2 de más").']);

        $res = DB::transaction(function () use ($venta, $datos) {
            $venta = PosVenta::with(['items.variante', 'pagos.metodo', 'documento'])->whereKey($venta->id)->lockForUpdate()->first();
            if ($b = self::bloqueo($venta)) throw ValidationException::withMessages(['cantidades' => $b]);

            $caja = $venta->caja_id ? Caja::whereKey($venta->caja_id)->lockForUpdate()->first() : null;
            $esperadoAntes = $caja ? $caja->efectivoEsperado() : null;
            $totalAntes = (float) $venta->total;

            // 1) Productos: la cantidad solo se baja (digitada de más); el precio se puede subir o bajar
            $cambios = [];
            foreach ($venta->items as $item) {
                if (!array_key_exists($item->id, $datos['cantidades'])) continue;
                $nueva = round((float) $datos['cantidades'][$item->id], 3);
                $antes = (float) $item->cantidad;
                $precioAntes = (float) $item->precio;
                $txt = $datos['precios'][$item->id] ?? null;
                $precioNuevo = ($txt === null || trim($txt) === '') ? $precioAntes : (float) preg_replace('/\D/', '', $txt);
                if ($nueva > $antes + 0.0001) {
                    throw ValidationException::withMessages(['cantidades' => 'Solo se puede bajar la cantidad. Para vender más, haga una venta nueva.']);
                }
                if ($nueva > 0 && $precioNuevo <= 0) {
                    throw ValidationException::withMessages(['cantidades' => 'El precio real de "' . $item->descripcion . '" debe ser mayor a $0.']);
                }
                if (abs($nueva - $antes) < 0.0001 && abs($precioNuevo - $precioAntes) < 0.5) continue;
                $quita = round($antes - $nueva, 3);
                if ($quita > 0 && $item->variante && $item->variante->controla) {
                    $item->variante->moverStock($quita, 'correccion', $venta->numero, 'Corrección de venta por error al digitar');
                }
                $cambios[] = ['producto' => trim($item->descripcion . ' ' . ($item->talla ? 'T.' . $item->talla : '') . ' ' . ($item->color ?: '')),
                    'antes' => $antes, 'despues' => $nueva, 'precio' => $precioAntes, 'precio_despues' => $precioNuevo];
                if ($nueva <= 0) $item->delete();
                else $item->update(['cantidad' => $nueva, 'precio' => $precioNuevo, 'subtotal' => round($nueva * $precioNuevo, 2)]);
            }
            if (!$cambios) throw ValidationException::withMessages(['cantidades' => 'No cambió ninguna cantidad ni precio.']);

            $items = $venta->items()->get();
            $pagosDet = [];

            if ($items->isEmpty()) {
                // Se quitaron todos los productos: la venta queda anulada (la caja deja de contarla)
                $venta->update(['estado' => 'anulada', 'anulada_en' => now(), 'anulada_por' => Auth::id(), 'corregida_en' => now()]);
                $totalDespues = 0.0;
            } else {
                // 2) Totales e impuestos de la venta, igual que en la caja
                $subtotal = round($items->sum(fn ($i) => (float) $i->subtotal), 2);
                $descuento = min((float) $venta->descuento, $subtotal);
                $totalDespues = round($subtotal - $descuento, 2);
                $imp = Impuestos::calcular($items->map(fn ($i) => ['total' => (float) $i->subtotal, 'impuesto' => $i->impuesto ?: 'ninguno'])->all(), $descuento);
                foreach ($items->values() as $k => $i) {
                    $li = $imp['lineas'][$k];
                    $i->update(['base' => $li['base'], 'iva_valor' => $li['iva'], 'iva_tasa' => $li['tasa']]);
                }
                $venta->update(['subtotal' => $subtotal, 'descuento' => $descuento, 'total' => $totalDespues,
                    'base_gravable' => $imp['base'], 'impuestos' => $imp['impuestos'], 'impuestos_detalle' => $imp['detalle'] ?: null, 'corregida_en' => now()]);

                // 3) Pagos: primero el pago elegido, luego el efectivo, luego los demás
                $sobra = round((float) $venta->pagos->sum('monto') - $totalDespues, 2);
                $orden = $venta->pagos->sortBy(fn ($p) => [(int) $p->id !== (int) ($datos['pago_id'] ?? 0), !($p->metodo->es_efectivo ?? false), -(float) $p->monto])->values();
                // Se cobró más de lo registrado (precio mal digitado): la diferencia se suma a ese pago
                if ($sobra < -0.004 && ($p = $orden->first())) {
                    $nuevo = round((float) $p->monto - $sobra, 2);
                    $pagosDet[] = ['metodo' => $p->metodo->nombre ?? 'Pago', 'antes' => (float) $p->monto, 'despues' => $nuevo];
                    $extra = ($p->moneda ?? null) === 'VES' && (float) $p->monto > 0 ? ['monto_moneda' => round((float) $p->monto_moneda * $nuevo / (float) $p->monto, 2)] : [];
                    $p->update(['monto' => $nuevo] + $extra);
                    $sobra = 0;
                }
                foreach ($orden as $p) {
                    if ($sobra <= 0.004) break;
                    $resta = round(min($sobra, (float) $p->monto), 2);
                    $nuevo = round((float) $p->monto - $resta, 2);
                    $pagosDet[] = ['metodo' => $p->metodo->nombre ?? 'Pago', 'antes' => (float) $p->monto, 'despues' => $nuevo];
                    if ($nuevo <= 0.004) $p->delete();
                    else {
                        $extra = ($p->moneda ?? null) === 'VES' && (float) $p->monto > 0 ? ['monto_moneda' => round((float) $p->monto_moneda * $nuevo / (float) $p->monto, 2)] : [];
                        $p->update(['monto' => $nuevo] + $extra);
                    }
                    $sobra = round($sobra - $resta, 2);
                }
            }

            // 4) Caja de ese día: si ya está cerrada, se recalcula lo guardado en el cierre (el contado no cambia)
            $esperadoDespues = null;
            if ($caja) {
                $caja->refresh();
                $esperadoDespues = $caja->efectivoEsperado();
                if ($caja->estado === 'cerrada') {
                    $caja->update([
                        'total_ventas' => (float) $caja->ventas()->where('estado', 'pagada')->sum('total'),
                        'efectivo_esperado' => $esperadoDespues,
                        'diferencia' => $caja->diferencia !== null ? round((float) $caja->diferencia + ((float) $esperadoAntes - $esperadoDespues), 2) : null,
                    ]);
                }
            }

            PosVentaCorreccion::create([
                'tienda_id' => TiendaActual::id(), 'pos_venta_id' => $venta->id, 'caja_id' => $caja?->id, 'user_id' => Auth::id(),
                'total_antes' => $totalAntes, 'total_despues' => $totalDespues,
                'esperado_antes' => $esperadoAntes, 'esperado_despues' => $esperadoDespues,
                'detalle' => ['productos' => $cambios, 'pagos' => $pagosDet], 'motivo' => $datos['motivo'],
            ]);

            return ['menos' => $totalAntes - $totalDespues, 'total' => $totalDespues, 'caja' => $caja?->fresh(), 'anulada' => $items->isEmpty()];
        });

        $dif = (float) $res['menos'];
        $msg = '✅ Venta ' . $venta->numero . ' corregida: ahora vale ' . dinero($res['total'])
            . (abs($dif) < 0.01 ? '' : ' (' . ($dif > 0 ? dinero($dif) . ' menos' : dinero(-$dif) . ' más') . ')')
            . '. Inventario, reportes y ganancia ya están al día.';
        if ($res['anulada']) $msg = '✅ Se quitaron todos los productos: la venta ' . $venta->numero . ' quedó anulada y los productos volvieron al inventario.';
        if ($res['caja'] && $res['caja']->estado === 'cerrada') {
            $d = (float) $res['caja']->diferencia;
            $msg .= ' La caja de ese día ' . (abs($d) < 0.01 ? 'quedó cuadrada.' : ($d < 0 ? 'quedó con faltante de ' . dinero(abs($d)) . '.' : 'quedó con sobrante de ' . dinero($d) . '.'));
        }
        return redirect()->route('pos.ventas')->with('success', $msg);
    }
}
