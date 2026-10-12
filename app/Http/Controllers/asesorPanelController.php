<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Models\Comision;
use App\Models\Tienda;
use Illuminate\Http\Request;

// Panel de la red comercial (asesores y líderes). No tiene caja ni ventas: solo sus negocios, su red y sus comisiones.
class asesorPanelController extends Controller
{
    public function index(Request $request)
    {
        $yo = $request->attributes->get('asesor')->load('user', 'padre.user');
        $ids = $yo->idsRed();
        $comisiones = Comision::where('asesor_id', $yo->id);
        $negocios = Tienda::with('plan')->where('asesor_id', $yo->id)->latest()->get();

        // ----- Mis ganancias del mes (motivación: meta = salario mínimo) -----
        $cfg = \App\Support\Comisiones::valores();
        $porVenta = (float) $cfg['nueva']['venta'];
        $salario = (float) \App\Models\Ajuste::valor('red_salario_minimo', 1750000);
        $mes = (float) (clone $comisiones)->where('estado', '<>', 'anulada')->where('created_at', '>=', now()->startOfMonth())->sum('monto');
        $ventasMes = (int) (clone $comisiones)->where('estado', '<>', 'anulada')->where('concepto', 'venta')->where('tipo', 'nueva')->where('created_at', '>=', now()->startOfMonth())->count();
        $diasMes = now()->daysInMonth; $hoy = now()->day;
        $faltan = max(0, $salario - $mes);
        $ganancias = [
            'mes' => $mes,
            'ventasMes' => $ventasMes,
            'meta' => $salario,
            'avance' => $salario > 0 ? min(100, round($mes / $salario * 100)) : 0,
            'faltan' => $faltan,
            'ventasFaltan' => $porVenta > 0 ? (int) ceil($faltan / $porVenta) : 0,
            'porVenta' => $porVenta,
            'proyeccion' => $hoy > 0 ? round($mes / $hoy * $diasMes, -3) : 0,
            'diasRestantes' => $diasMes - $hoy,
            'anio' => (float) (clone $comisiones)->where('estado', '<>', 'anulada')->where('created_at', '>=', now()->startOfYear())->sum('monto'),
        ];

        // Lo que ya ganó por cada negocio, y quiénes ya pagaron (los pagos de prueba no cuentan)
        $porNegocio = Comision::where('asesor_id', $yo->id)->where('estado', '<>', 'anulada')
            ->selectRaw("tienda_id, SUM(monto) total, SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) pendiente")
            ->groupBy('tienda_id')->get()->keyBy('tienda_id');
        $real = fn ($q) => $q->where('estado', 'aprobado')->where('monto', '>', 0)
            ->where('referencia', 'not like', \App\Models\PagoSuscripcion::PREFIJO_PRUEBA . '%');
        $pagaron = $real(\App\Models\PagoSuscripcion::whereIn('tienda_id', $negocios->pluck('id')))->pluck('tienda_id')->unique()->flip();

        // Pagos de los últimos 7 días de SUS negocios, con lo que usted ganó en cada uno
        $recientes = $real(\App\Models\PagoSuscripcion::with(['tienda:id,nombre,asesor_id', 'plan:id,nombre'])
            ->whereIn('tienda_id', $negocios->pluck('id'))
            ->where('updated_at', '>=', now()->subDays(7)))->latest('updated_at')->take(5)->get()
            ->each(fn ($p) => $p->mi_comision = (float) Comision::where('pago_suscripcion_id', $p->id)->where('asesor_id', $yo->id)->where('estado', '<>', 'anulada')->sum('monto'));

        return view('asesor.index', [
            'ganancias' => $ganancias,
            'porNegocio' => $porNegocio,
            'pagaron' => $pagaron,
            'recientes' => $recientes,
            'yo' => $yo,
            'negocios' => $negocios,
            'resumen' => [
                'pendiente' => (float) (clone $comisiones)->where('estado', 'pendiente')->sum('monto'),
                'pagada' => (float) (clone $comisiones)->where('estado', 'pagada')->sum('monto'),
                'mes' => (float) (clone $comisiones)->where('estado', '<>', 'anulada')->where('created_at', '>=', now()->startOfMonth())->sum('monto'),
                'equipo' => count($ids) - 1,
                'negociosRed' => Tienda::whereIn('asesor_id', $ids)->count(),
            ],
            'porVencer' => Tienda::where('asesor_id', $yo->id)->whereNotNull('plan_vence')
                ->whereBetween('plan_vence', [today()->subDays(15), today()->addDays(30)])->orderBy('plan_vence')->get(),
            'ultimas' => Comision::with('tienda:id,nombre')->where('asesor_id', $yo->id)->latest()->take(8)->get(),
            'aspirantes' => Asesor::with('user:id,name,email,telefono')->where('reclutado_por', $yo->id)->where('estado', 'pendiente')->latest()->get(),
            'enCurso' => Asesor::with('user:id,name,telefono')->where('reclutado_por', $yo->id)->where('estado', 'capacitacion')->latest()->get(),
            'sumados' => Asesor::with('user:id,name,telefono')->where('reclutado_por', $yo->id)->where('estado', 'activo')->latest()->take(30)->get(),
            'progreso' => \App\Support\RedComercial::progreso($yo),
        ]);
    }

    public function red(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        $ids = $yo->idsRed();
        $gente = Asesor::with('user:id,name,telefono')->whereIn('id', $ids)->get();
        $negocios = Tienda::with('plan:id,nombre')->whereIn('asesor_id', $ids)->orderBy('nombre')->get()->groupBy('asesor_id');

        return view('asesor.red', [
            'yo' => $yo,
            'raices' => $gente->where('id', $yo->id),
            'hijos' => $gente->where('id', '<>', $yo->id)->groupBy('padre_id'),
            'negocios' => $negocios,
            'total' => $negocios->flatten()->count(),
        ]);
    }

    public function comisiones(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        $lista = Comision::with('tienda:id,nombre')->where('asesor_id', $yo->id)
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->latest()->paginate(30)->withQueryString();
        return view('asesor.comisiones', ['yo' => $yo, 'lista' => $lista, 'estado' => $request->estado]);
    }
}
