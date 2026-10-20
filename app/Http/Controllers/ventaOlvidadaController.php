<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\PosVenta;
use App\Models\PosVentaCorreccion;
use App\Models\ProductoVariante;
use App\Models\Tienda;
use App\Support\Dian\Impuestos;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registrar venta olvidada: una venta que se hizo pero no se registró ese día se carga después,
 * con la fecha y en la caja de ese día (aunque ya esté cerrada). Solo dueño o administrador.
 * - Descuenta el inventario y la venta sale en los reportes del día correcto (y en las ventas del vendedor de esa caja).
 * - La caja de ese día recalcula total de ventas, efectivo esperado y diferencia: si esa plata quedó
 *   como sobrante en el cierre, la caja queda cuadrada. No toca la caja de hoy.
 * - Queda registro de quién la cargó, cuándo y por qué.
 */
class ventaOlvidadaController extends Controller
{
    public const DIAS = 15; // hasta cuántos días atrás se puede registrar

    private function soloAdmin(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->es_superadmin || $u->hasAnyRole(['dueno', 'administrador'])), 403, 'Solo el dueño o el administrador puede registrar ventas olvidadas.');
    }

    // Con POS electrónico o factura electrónica no se puede: la DIAN no deja emitir documentos con fecha pasada
    private function bloqueo(): ?string
    {
        $t = TiendaActual::tienda();
        if (\App\Support\Dian\Facturador::tipoDocumento($t, false) !== 'tiquete') {
            return 'Su negocio emite POS electrónico ante la DIAN, y la DIAN no permite documentos con fecha pasada. Registre la venta hoy en la caja.';
        }
        return null;
    }

    private function cajas()
    {
        return Caja::with('user:id,name')->where('abierta_en', '>=', now()->subDays(self::DIAS)->startOfDay())
            ->orderByDesc('abierta_en')->limit(60)->get();
    }

    private function metodos()
    {
        return MetodoPago::where('activo', true)->where('es_credito', false)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'interno'), fn ($q) => $q->where('interno', false))
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'moneda'), fn ($q) => $q->where(fn ($w) => $w->whereNull('moneda')->orWhere('moneda', '<>', 'VES')))
            ->orderBy('orden')->orderBy('nombre')->get();
    }

    public function create()
    {
        $this->soloAdmin();
        $cajas = $this->cajas();
        // Por defecto: la última caja que no es de hoy
        $sugerida = $cajas->first(fn ($c) => !$c->abierta_en->isToday()) ?? $cajas->first();
        return view('pos.olvidada', ['cajas' => $cajas, 'sugerida' => $sugerida, 'metodos' => $this->metodos(), 'bloqueo' => $this->bloqueo()]);
    }

    public function store(Request $request)
    {
        $this->soloAdmin();
        if ($b = $this->bloqueo()) return back()->withErrors($b);
        $datos = $request->validate([
            'caja_id' => 'required|integer',
            'hora' => 'nullable|date_format:H:i',
            'items' => 'required|array|min:1|max:100',
            'items.*.variante_id' => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:100000',
            'items.*.precio' => 'required|numeric|min:0|max:999999999',
            'metodo_id' => 'required|integer',
            'cliente_nombre' => 'nullable|string|max:80',
            'motivo' => 'required|string|min:3|max:255',
        ], [
            'items.required' => 'Agregue al menos un producto.',
            'motivo.required' => 'Escriba el motivo (ej. "no se registró en la caja").',
        ]);

        $res = DB::transaction(function () use ($datos) {
            $caja = Caja::whereKey($datos['caja_id'])->lockForUpdate()->firstOrFail();
            abort_unless($caja->abierta_en && $caja->abierta_en->gte(now()->subDays(self::DIAS)->startOfDay()), 422, 'Esa caja es muy antigua.');
            $metodo = $this->metodos()->firstWhere('id', (int) $datos['metodo_id']);
            if (!$metodo) throw ValidationException::withMessages(['metodo_id' => 'Elija un método de pago válido.']);

            // Fecha de la venta: el día de esa caja, a la hora indicada (dentro del turno)
            $fin = $caja->cerrada_en ?? now();
            $fecha = $datos['hora'] ? Carbon::parse($caja->abierta_en->toDateString() . ' ' . $datos['hora']) : $fin->copy()->subMinute();
            if ($fecha->lt($caja->abierta_en)) $fecha = $caja->abierta_en->copy()->addMinute();
            if ($fecha->gt($fin)) $fecha = $fin->copy()->subMinute();

            $variantes = ProductoVariante::with('producto')->whereIn('id', collect($datos['items'])->pluck('variante_id'))->get()->keyBy('id');
            $items = collect($datos['items'])->map(function ($i) use ($variantes) {
                $v = $variantes[$i['variante_id']] ?? null;
                if (!$v) throw ValidationException::withMessages(['items' => 'Algún producto ya no existe. Recargue la página.']);
                return ['v' => $v, 'cantidad' => round((float) $i['cantidad'], 3), 'precio' => round((float) $i['precio'], 2)];
            });
            $subtotal = round($items->sum(fn ($i) => $i['cantidad'] * $i['precio']), 2);
            $imp = Impuestos::calcular($items->map(fn ($i) => ['total' => round($i['cantidad'] * $i['precio'], 2), 'impuesto' => $i['v']->producto->impuesto ?? 'ninguno'])->all(), 0);

            $esperadoAntes = $caja->efectivoEsperado();
            $tienda = Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $venta = PosVenta::create([
                'numero' => $tienda->siguienteNumeroPos(),
                'tipo_doc' => 'tiquete',
                'base_gravable' => $imp['base'], 'impuestos' => $imp['impuestos'], 'impuestos_detalle' => $imp['detalle'] ?: null,
                'caja_id' => $caja->id,
                'user_id' => $caja->user_id, // cuenta como venta del vendedor de esa caja
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
                'subtotal' => $subtotal, 'descuento' => 0, 'total' => $subtotal, 'cambio' => 0,
                'estado' => 'pagada',
                'registrada_despues_en' => now(),
            ]);
            $venta->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

            foreach ($items->values() as $k => $i) {
                $v = $i['v'];
                $li = $imp['lineas'][$k];
                $venta->items()->create([
                    'impuesto' => $li['impuesto'], 'iva_tasa' => $li['tasa'], 'base' => $li['base'], 'iva_valor' => $li['iva'],
                    'producto_variante_id' => $v->id, 'descripcion' => $v->producto->nombre, 'talla' => $v->talla, 'color' => $v->color,
                    'cantidad' => $i['cantidad'], 'precio' => $i['precio'], 'costo' => $v->costo_final,
                    'subtotal' => round($i['cantidad'] * $i['precio'], 2),
                ]);
                if ($v->controla) $v->moverStock(-$i['cantidad'], 'venta', $venta->numero, 'Venta olvidada registrada después');
            }
            if ($subtotal > 0) $venta->pagos()->create(['metodo_pago_id' => $metodo->id, 'monto' => $subtotal]);

            // Caja de ese día: si ya está cerrada, se recalcula lo guardado en el cierre (el contado no cambia)
            $caja->refresh();
            $esperadoDespues = $caja->efectivoEsperado();
            if ($caja->estado === 'cerrada') {
                $caja->update([
                    'total_ventas' => (float) $caja->ventas()->where('estado', 'pagada')->sum('total'),
                    'efectivo_esperado' => $esperadoDespues,
                    'diferencia' => $caja->diferencia !== null ? round((float) $caja->diferencia + ($esperadoAntes - $esperadoDespues), 2) : null,
                ]);
            }

            PosVentaCorreccion::create([
                'tienda_id' => TiendaActual::id(), 'pos_venta_id' => $venta->id, 'caja_id' => $caja->id, 'user_id' => Auth::id(),
                'total_antes' => 0, 'total_despues' => $subtotal, 'esperado_antes' => $esperadoAntes, 'esperado_despues' => $esperadoDespues,
                'detalle' => ['tipo' => 'olvidada', 'productos' => $items->map(fn ($i) => ['producto' => trim($i['v']->producto->nombre . ' ' . ($i['v']->talla ? 'T.' . $i['v']->talla : '') . ' ' . ($i['v']->color ?: '')), 'antes' => 0, 'despues' => $i['cantidad'], 'precio' => $i['precio']])->values()->all(), 'pagos' => [['metodo' => $metodo->nombre, 'antes' => 0, 'despues' => $subtotal]]],
                'motivo' => $datos['motivo'],
            ]);

            return ['venta' => $venta, 'caja' => $caja->fresh()];
        });

        $msg = '✅ Venta olvidada ' . $res['venta']->numero . ' registrada el ' . $res['venta']->created_at->format('d/m/Y h:i a') . ' por ' . dinero($res['venta']->total) . '. El inventario y los reportes de ese día ya están al día.';
        if ($res['caja']->estado === 'cerrada') {
            $d = (float) $res['caja']->diferencia;
            $msg .= ' La caja de ese día ' . (abs($d) < 0.01 ? 'quedó cuadrada.' : ($d < 0 ? 'quedó con faltante de ' . dinero(abs($d)) . '.' : 'quedó con sobrante de ' . dinero($d) . '.'));
        }
        return redirect()->route('pos.ventas')->with('success', $msg);
    }
}
