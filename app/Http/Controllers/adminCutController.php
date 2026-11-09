<?php

namespace App\Http\Controllers;

use App\Models\CutEvento;
use App\Models\CutVisita;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Súper administrador: LocalProCut en números (como "Activos ahora" de ECO): visitas desde que se creó el contador,
 * quién está ahora, desde qué ciudad y equipo, qué hace, y cuánta gente comparte, instala la app y se registra.
 */
class adminCutController extends Controller
{
    private const PERIODOS = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', 'todo' => 'Desde el inicio'];

    public function index(Request $request)
    {
        CutVisita::ubicarPendientes(150);
        $periodo = array_key_exists($request->query('periodo'), self::PERIODOS) ? $request->query('periodo') : '30';
        $desde = match ($periodo) { 'hoy' => now()->startOfDay(), '7' => now()->subDays(7), '30' => now()->subDays(30), default => null };
        $base = fn () => CutVisita::query()->when($desde, fn ($q) => $q->where('created_at', '>=', $desde));

        $grupo = fn (string $col, int $n = 8) => $base()->select($col . ' as k', DB::raw('count(*) as n'))->whereNotNull($col)
            ->groupBy($col)->orderByDesc('n')->limit($n)->pluck('n', 'k');

        $dias = CutVisita::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('count(*) as n'), DB::raw('count(distinct visitante) as p'))
            ->groupBy('d')->pluck('n', 'd');
        $serie = collect(range(29, 0))->mapWithKeys(fn ($i) => [now()->subDays($i)->toDateString() => (int) ($dias[now()->subDays($i)->toDateString()] ?? 0)]);

        $b = $base();
        $total = (clone $b)->count();
        $embudo = [
            'Abrieron el editor' => $total,
            'Agregaron videos' => (clone $b)->where('videos', '>', 0)->count(),
            'Exportaron un video' => (clone $b)->where('exportados', '>', 0)->count(),
            'Compartieron' => (clone $b)->where('compartidos', '>', 0)->count(),
            'Tocaron Registrarse' => (clone $b)->where('clic_registro', true)->count(),
        ];
        $registros = Tienda::withoutGlobalScopes()->where('origen', 'like', 'cut%')->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))->count();

        return view('admin.cut', [
            'periodo' => $periodo,
            'periodos' => self::PERIODOS,
            'k' => [
                'historico' => CutVisita::count(),
                'desde' => CutVisita::min('created_at'),
                'personas_historico' => CutVisita::distinct()->count('visitante'),
                'visitas' => $total,
                'personas' => (clone $b)->distinct()->count('visitante'),
                'vuelven' => (clone $b)->where('nuevo', false)->distinct()->count('visitante'),
                'hoy' => CutVisita::where('created_at', '>=', now()->startOfDay())->count(),
                'videos' => (int) (clone $b)->sum('exportados'),
                'compartidos' => (int) (clone $b)->sum('compartidos'),
                'invitados' => (clone $b)->whereNotNull('invito')->count(),
                'instalaron' => (clone $b)->where('instalo', true)->distinct()->count('visitante'),
                'en_app' => (clone $b)->where('es_app', true)->count(),
                'clic_app' => (clone $b)->where('clic_app', true)->count(),
                'registros' => $registros,
                'minutos' => (int) round((clone $b)->avg('segundos') / 60),
            ],
            'serie' => $serie,
            'embudo' => $embudo + ['Se registraron' => $registros],
            'grupos' => [
                'Ciudades' => $base()->select(DB::raw("CONCAT(COALESCE(ciudad,'?'), ', ', COALESCE(pais,'?')) as k"), DB::raw('count(*) as n'))->where('geo_consultada', true)->groupBy('k')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
                'Países' => $grupo('pais'),
                'Equipo' => $grupo('dispositivo'),
                'Sistema' => $grupo('sistema'),
                'Marca' => $grupo('marca'),
                'Navegador' => $grupo('navegador'),
                'Llegaron desde' => $base()->select(DB::raw("COALESCE(origen, app_origen, referencia, 'directo') as k"), DB::raw('count(*) as n'))->groupBy('k')->orderByDesc('n')->limit(10)->pluck('n', 'k'),
                'Operador' => $grupo('operador'),
            ],
            'activos' => $this->activos(),
            'ultimas' => CutVisita::with(['eventos' => fn ($q) => $q->orderBy('id'), 'user:id,name'])->latest('id')->limit(40)->get(),
        ]);
    }

    // "Ahora mismo": se refresca solo cada 15 s
    public function vivo()
    {
        CutVisita::ubicarPendientes(30);
        return response()->json([
            'html' => view('admin.cut-activos', ['activos' => $this->activos()])->render(),
            'hoy' => CutVisita::where('created_at', '>=', now()->startOfDay())->count(),
            'historico' => CutVisita::count(),
        ]);
    }

    private function activos()
    {
        return CutVisita::with('user:id,name')->where('ultima_vez', '>=', now()->subMinutes(3))->orderByDesc('ultima_vez')->limit(60)->get();
    }

    public static function nombreEvento(?string $t): string
    {
        return CutEvento::NOMBRES[$t] ?? (string) $t;
    }
}
