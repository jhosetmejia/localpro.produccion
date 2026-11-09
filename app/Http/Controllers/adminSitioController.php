<?php

namespace App\Http\Controllers;

use App\Models\Tienda;
use App\Models\VisitaPagina;
use App\Models\VisitaSitio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Súper administrador: Visitas y uso de TODO LocalProo (panel y páginas públicas): quién está ahora, desde dónde,
 * en qué equipo, qué secciones usan, qué negocios están más activos y por dónde llega la gente.
 * La pestaña LocalProCut está en adminCutController.
 */
class adminSitioController extends Controller
{
    private const PERIODOS = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', 'todo' => 'Desde el inicio'];

    public function index(Request $request)
    {
        VisitaSitio::ubicarPendientes(150);
        $periodo = array_key_exists($request->query('periodo'), self::PERIODOS) ? $request->query('periodo') : 'hoy';
        $desde = match ($periodo) { 'hoy' => now()->startOfDay(), '7' => now()->subDays(7), '30' => now()->subDays(30), default => null };
        $base = fn () => VisitaSitio::query()->when($desde, fn ($q) => $q->where('created_at', '>=', $desde));
        $paginas = fn () => VisitaPagina::query()->when($desde, fn ($q) => $q->where('created_at', '>=', $desde));
        $grupo = fn (string $col, int $n = 8) => $base()->select($col . ' as k', DB::raw('count(*) as n'))->whereNotNull($col)->groupBy($col)->orderByDesc('n')->limit($n)->pluck('n', 'k');

        $dias = VisitaSitio::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('count(*) as n'), DB::raw('sum(case when user_id is null then 0 else 1 end) as c'))
            ->groupBy('d')->get()->keyBy('d');
        $serie = collect(range(29, 0))->mapWithKeys(function ($i) use ($dias) {
            $d = now()->subDays($i)->toDateString();
            return [$d => ['n' => (int) ($dias[$d]->n ?? 0), 'c' => (int) ($dias[$d]->c ?? 0)]];
        });

        // Secciones más usadas (por la primera parte del nombre de la ruta)
        $secciones = $paginas()->whereNotNull('ruta')->select(DB::raw("SUBSTRING_INDEX(ruta, '.', 1) as k"), DB::raw('count(*) as n'))
            ->groupBy('k')->orderByDesc('n')->limit(12)->pluck('n', 'k')
            ->mapWithKeys(fn ($n, $k) => [VisitaSitio::seccion($k) => $n]);
        $negocios = $base()->whereNotNull('tienda_id')->select('tienda_id', DB::raw('count(*) as visitas'), DB::raw('sum(paginas) as paginas'), DB::raw('max(ultima_vez) as ultima'))
            ->groupBy('tienda_id')->orderByDesc('paginas')->limit(12)->get();
        $nombres = Tienda::withoutGlobalScopes()->whereIn('id', $negocios->pluck('tienda_id'))->pluck('nombre', 'id');

        $b = $base();
        return view('admin.sitio', [
            'periodo' => $periodo, 'periodos' => self::PERIODOS,
            'k' => [
                'historico' => VisitaSitio::count(),
                'desde' => VisitaSitio::min('created_at'),
                'visitas' => (clone $b)->count(),
                'personas' => (clone $b)->distinct()->count('visitante'),
                'con_sesion' => (clone $b)->whereNotNull('user_id')->distinct()->count('user_id'),
                'negocios' => (clone $b)->whereNotNull('tienda_id')->distinct()->count('tienda_id'),
                'paginas' => $paginas()->count(),
                'nuevos' => (clone $b)->where('nuevo', true)->count(),
                'celular' => (clone $b)->where('dispositivo', 'Celular')->count(),
                'app' => (clone $b)->where('es_app', true)->count(),
                'hoy' => VisitaSitio::where('created_at', '>=', now()->startOfDay())->count(),
            ],
            'serie' => $serie,
            'secciones' => $secciones,
            'negocios' => $negocios, 'nombres' => $nombres,
            'grupos' => [
                'Ciudades' => (clone $b)->select(DB::raw("CONCAT(COALESCE(ciudad,'?'), ', ', COALESCE(pais,'?')) as k"), DB::raw('count(*) as n'))->where('geo_consultada', true)->groupBy('k')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
                'Llegaron desde' => $base()->select(DB::raw("COALESCE(origen, app_origen, referencia, 'Directo') as k"), DB::raw('count(*) as n'))->groupBy('k')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
                'Páginas de entrada' => $base()->whereNull('user_id')->select('entrada as k', DB::raw('count(*) as n'))->groupBy('entrada')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
                'Equipo' => $grupo('dispositivo'),
                'Sistema' => $grupo('sistema'),
                'Marca' => $grupo('marca'),
                'Navegador' => $grupo('navegador'),
                'Operador' => $grupo('operador'),
            ],
            'activos' => $this->activos(),
            'ultimas' => VisitaSitio::with(['paginasVistas' => fn ($q) => $q->orderBy('id'), 'user:id,name', 'tienda:id,nombre'])->latest('id')->limit(40)->get(),
        ]);
    }

    public function vivo()
    {
        VisitaSitio::ubicarPendientes(30);
        $activos = $this->activos();
        return response()->json([
            'html' => view('admin.sitio-activos', ['activos' => $activos])->render(),
            'ahora' => $activos->count(),
            'hoy' => VisitaSitio::where('created_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    // "Ahora mismo": abrieron una página en los últimos 5 minutos
    private function activos()
    {
        return VisitaSitio::with(['user:id,name', 'tienda:id,nombre'])->where('ultima_vez', '>=', now()->subMinutes(5))->orderByDesc('ultima_vez')->limit(80)->get();
    }
}
