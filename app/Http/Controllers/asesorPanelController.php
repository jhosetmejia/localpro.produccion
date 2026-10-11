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

        return view('asesor.index', [
            'yo' => $yo,
            'negocios' => Tienda::with('plan')->where('asesor_id', $yo->id)->latest()->get(),
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
