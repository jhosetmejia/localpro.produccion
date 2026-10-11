<?php

namespace App\Http\Controllers;

use App\Support\TiendaActual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Reportes de ventas, ganancia, productos, vendedores y métodos de pago, con exportación a Excel (CSV)
class reporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-reportes');
    }

    public function index(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);
        return view('reporte.index', $this->datos($desde, $hasta) + [
            'desde' => $desde, 'hasta' => $hasta, 'periodo' => $request->get('periodo', 'mes'),
        ]);
    }

    public function exportar(Request $request, string $tipo)
    {
        [$desde, $hasta] = $this->rango($request);
        $t = TiendaActual::id();
        $nombre = "localpro-{$tipo}-{$desde->format('Ymd')}-{$hasta->format('Ymd')}.csv";

        if ($tipo === 'ventas') {
            $cabecera = ['Número', 'Fecha', 'Hora', 'Vendedor', 'Cliente', 'Teléfono', 'Subtotal', 'Descuento', 'Total', 'Costo', 'Ganancia', 'Pagos', 'Estado'];
            $filas = DB::table('pos_ventas as v')
                ->leftJoin('users as u', 'u.id', '=', 'v.user_id')
                ->where('v.tienda_id', $t)->whereBetween('v.created_at', [$desde, $hasta])
                ->orderBy('v.created_at')
                ->select('v.*', 'u.name as vendedor')
                ->get()
                ->map(function ($v) {
                    $costo = (float) DB::table('pos_venta_items')->where('pos_venta_id', $v->id)->selectRaw('COALESCE(SUM(costo*cantidad),0) c')->value('c');
                    $pagos = DB::table('pos_venta_pagos as p')->join('metodos_pago as m', 'm.id', '=', 'p.metodo_pago_id')
                        ->where('p.pos_venta_id', $v->id)->get()->map(fn ($p) => $p->nombre . ' ' . $this->num($p->monto))->implode(' + ');
                    $c = Carbon::parse($v->created_at);
                    return [$v->numero, $c->format('d/m/Y'), $c->format('H:i'), $v->vendedor, $v->cliente_nombre, $v->cliente_telefono,
                        $this->num($v->subtotal), $this->num($v->descuento), $this->num($v->total), $this->num($costo), $this->num($v->total - $costo), $pagos, $v->estado];
                });
        } elseif ($tipo === 'productos') {
            $cabecera = ['Producto', 'Talla', 'Color', 'Unidades vendidas', 'Vendido', 'Costo', 'Ganancia'];
            $filas = $this->productos($desde, $hasta, 100000)->map(fn ($p) => [
                $p->descripcion, $p->talla, $p->color, $p->unidades, $this->num($p->monto), $this->num($p->costo), $this->num($p->monto - $p->costo),
            ]);
        } elseif ($tipo === 'inventario') {
            $cabecera = ['Código', 'Producto', 'Talla', 'Color', 'Código de barras', 'Stock', 'Precio', 'Costo', 'Valor a costo'];
            $filas = DB::table('producto_variantes as v')->join('productos as p', 'p.id', '=', 'v.producto_id')
                ->where('v.tienda_id', $t)->where('v.estado', 1)->orderBy('p.nombre')->orderBy('v.talla')
                ->select('p.codigo', 'p.nombre', 'v.talla', 'v.color', 'v.codigo_barras', 'v.stock', DB::raw('COALESCE(v.precio, p.precio_venta) as precio'), DB::raw('COALESCE(v.costo, p.precio_costo) as costo'))
                ->get()->map(fn ($r) => [$r->codigo, $r->nombre, $r->talla, $r->color, $r->codigo_barras, $r->stock, $this->num($r->precio), $this->num($r->costo), $this->num($r->stock * $r->costo)]);
        } else {
            abort(404);
        }

        return response()->streamDownload(function () use ($cabecera, $filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // para que Excel muestre bien las tildes
            fputcsv($out, $cabecera, ';');
            foreach ($filas as $f) {
                fputcsv($out, $f, ';');
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function datos(Carbon $desde, Carbon $hasta): array
    {
        $t = TiendaActual::id();
        $ventas = DB::table('pos_ventas')->where('tienda_id', $t)->where('estado', 'pagada')->whereBetween('created_at', [$desde, $hasta]);
        $ids = (clone $ventas)->pluck('id');

        $bruto = (float) (clone $ventas)->sum('total');
        $descuentos = (float) (clone $ventas)->sum('descuento');
        $num = $ids->count();
        $costo = (float) DB::table('pos_venta_items')->whereIn('pos_venta_id', $ids)->selectRaw('COALESCE(SUM(costo*cantidad),0) c')->value('c');

        $devs = DB::table('devoluciones')->where('tienda_id', $t)->whereBetween('created_at', [$desde, $hasta]);
        $devuelto = (float) (clone $devs)->sum('total');
        $costoDevuelto = (float) DB::table('devolucion_items as di')->join('pos_venta_items as i', 'i.id', '=', 'di.pos_venta_item_id')
            ->whereIn('di.devolucion_id', (clone $devs)->pluck('id'))->selectRaw('COALESCE(SUM(i.costo*di.cantidad),0) c')->value('c');

        $neto = $bruto - $devuelto;
        $ganancia = $neto - ($costo - $costoDevuelto);

        // Ventas por día
        $porDia = (clone $ventas)->selectRaw('DATE(created_at) d, SUM(total) t, COUNT(*) n')->groupBy('d')->orderBy('d')->get()->keyBy('d');
        $dias = [];
        for ($d = $desde->copy()->startOfDay(); $d->lte($hasta) && count($dias) < 62; $d->addDay()) {
            $k = $d->format('Y-m-d');
            $dias[] = ['label' => $d->format('d/m'), 'total' => (float) ($porDia[$k]->t ?? 0), 'n' => (int) ($porDia[$k]->n ?? 0)];
        }

        $porVendedor = DB::table('pos_ventas as v')->join('users as u', 'u.id', '=', 'v.user_id')
            ->where('v.tienda_id', $t)->where('v.estado', 'pagada')->whereBetween('v.created_at', [$desde, $hasta])
            ->groupBy('u.id', 'u.name')->select('u.name', DB::raw('SUM(v.total) t'), DB::raw('COUNT(*) n'))->orderByDesc('t')->get();

        $porMetodo = DB::table('pos_venta_pagos as p')->join('metodos_pago as m', 'm.id', '=', 'p.metodo_pago_id')
            ->whereIn('p.pos_venta_id', $ids)->groupBy('m.id', 'm.nombre')->select('m.nombre', DB::raw('SUM(p.monto) t'))->orderByDesc('t')->get();

        $porHora = (clone $ventas)->selectRaw('HOUR(created_at) h, COUNT(*) n')->groupBy('h')->pluck('n', 'h');

        return [
            'resumen' => compact('bruto', 'descuentos', 'devuelto', 'neto', 'costo', 'ganancia', 'num') + ['promedio' => $num ? $bruto / $num : 0, 'margen' => $neto > 0 ? $ganancia / $neto * 100 : 0],
            'dias' => $dias,
            'top' => $this->productos($desde, $hasta, 10),
            'porVendedor' => $porVendedor,
            'porMetodo' => $porMetodo,
            'horaPico' => $porHora->count() ? $porHora->sortDesc()->keys()->first() : null,
        ];
    }

    private function productos(Carbon $desde, Carbon $hasta, int $limite)
    {
        return DB::table('pos_venta_items as i')->join('pos_ventas as v', 'v.id', '=', 'i.pos_venta_id')
            ->where('v.tienda_id', TiendaActual::id())->where('v.estado', 'pagada')->whereBetween('v.created_at', [$desde, $hasta])
            ->groupBy('i.descripcion', 'i.talla', 'i.color')
            ->select('i.descripcion', 'i.talla', 'i.color', DB::raw('SUM(i.cantidad) unidades'), DB::raw('SUM(i.subtotal) monto'), DB::raw('SUM(i.costo*i.cantidad) costo'))
            ->orderByDesc('unidades')->limit($limite)->get();
    }

    private function rango(Request $request): array
    {
        $hoy = Carbon::today();
        [$desde, $hasta] = match ($request->get('periodo', 'mes')) {
            'hoy' => [$hoy->copy(), $hoy->copy()],
            'ayer' => [$hoy->copy()->subDay(), $hoy->copy()->subDay()],
            'semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()],
            'mes_pasado' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            'rango' => [
                Carbon::parse($request->get('desde', $hoy->format('Y-m-d'))),
                Carbon::parse($request->get('hasta', $hoy->format('Y-m-d'))),
            ],
            default => [$hoy->copy()->startOfMonth(), $hoy->copy()],
        };
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return [$desde->startOfDay(), $hasta->endOfDay()];
    }

    private function num($v): string
    {
        $m = moneda();
        return number_format((float) $v, $m['decimales'], $m['decimal'], '');
    }
}
