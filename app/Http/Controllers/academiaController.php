<?php

namespace App\Http\Controllers;

use App\Models\AcademiaAvance;
use App\Models\Asesor;
use App\Models\User;
use App\Support\Academia;
use Illuminate\Http\Request;

// "Aprende LocalPro": cursos por módulos para el dueño, su equipo y los asesores
class academiaController extends Controller
{
    private function avances(): array
    {
        return AcademiaAvance::where('user_id', auth()->id())->get()->keyBy('curso')->all();
    }

    public static function porcentaje(array $curso, ?AcademiaAvance $a): int
    {
        $total = count($curso['lecciones']) + 1; // + la prueba
        $hechas = count(array_intersect(array_keys($curso['lecciones']), (array) ($a?->lecciones ?? []))) + ($a?->aprobado_at ? 1 : 0);
        return (int) round($hechas / $total * 100);
    }

    public function index()
    {
        $cursos = Academia::paraMi();
        $avances = $this->avances();

        // El dueño ve cómo va su equipo
        $equipo = collect();
        $u = auth()->user();
        if ($u->tienda_id && Academia::esJefe()) {
            $equipo = User::where('tienda_id', $u->tienda_id)->where('id', '<>', $u->id)->orderBy('name')->get(['id', 'name'])
                ->map(function ($p) {
                    $av = AcademiaAvance::where('user_id', $p->id)->get();
                    return ['nombre' => $p->name, 'aprobados' => $av->whereNotNull('aprobado_at')->count(), 'empezados' => $av->count()];
                });
        }

        // Asesores: cómo va su línea (los que entraron con su código y los de su equipo). El administrador ve a toda la red.
        $linea = collect();
        $cursosAsesor = array_keys(Academia::cursosAsesor());
        $ids = null;
        if ($u->es_superadmin) $ids = Asesor::where('estado', 'activo')->pluck('id')->all();
        elseif ($u->asesor) $ids = array_values(array_diff(array_keys($u->asesor->arbolVisible()), [$u->asesor->id]));
        if ($ids) {
            $gente = Asesor::with('user:id,name,telefono')->whereIn('id', $ids)->where('estado', '<>', 'rechazado')->get();
            $av = AcademiaAvance::whereIn('user_id', $gente->pluck('user_id'))->whereIn('curso', $cursosAsesor)->get()->groupBy('user_id');
            $linea = $gente->map(function ($a) use ($av, $cursosAsesor) {
                $suyos = $av->get($a->user_id, collect());
                return [
                    'nombre' => $a->nombre, 'codigo' => $a->codigo, 'cargo' => $a->cargo_nombre,
                    'aprobados' => $suyos->whereNotNull('aprobado_at')->count(), 'total' => count($cursosAsesor),
                    'empezo' => $suyos->isNotEmpty(), 'ultimo' => $suyos->max('updated_at'),
                    'tel' => $a->user?->telefono ? numero_whatsapp($a->user->telefono) : null,
                ];
            })->sortByDesc(fn ($x) => [$x['aprobados'], $x['empezo']])->values();
        }

        return view('aprende.index', [
            'cursos' => $cursos, 'avances' => $avances, 'equipo' => $equipo, 'linea' => $linea,
            'totalCursos' => count(Academia::cursos()),
            'aprobados' => collect($avances)->whereNotNull('aprobado_at')->filter(fn ($a) => isset($cursos[$a->curso]))->count(),
        ]);
    }

    public function curso(string $slug)
    {
        $curso = Academia::paraMi()[$slug] ?? abort(404);
        $a = AcademiaAvance::firstOrNew(['user_id' => auth()->id(), 'curso' => $slug]);
        return view('aprende.curso', [
            'slug' => $slug, 'curso' => $curso, 'avance' => $a,
            'hechas' => (array) ($a->lecciones ?? []),
            'tieneTienda' => (bool) auth()->user()->tienda_id,
            'resultado' => session('resultado'),
        ]);
    }

    // "Entendido": marca la lección como vista
    public function leccion(string $slug, int $n)
    {
        $curso = Academia::paraMi()[$slug] ?? abort(404);
        abort_unless(isset($curso['lecciones'][$n]), 404);
        $a = AcademiaAvance::firstOrNew(['user_id' => auth()->id(), 'curso' => $slug]);
        $a->lecciones = array_values(array_unique(array_merge((array) ($a->lecciones ?? []), [$n])));
        $a->save();
        $sig = $n + 1;
        return redirect()->to(route('aprende.curso', $slug) . (isset($curso['lecciones'][$sig]) ? '#l' . $sig : '#prueba'));
    }

    // Prueba fácil: se aprueba con 2 de 3; intentos ilimitados
    public function prueba(Request $request, string $slug)
    {
        $curso = Academia::paraMi()[$slug] ?? abort(404);
        $resp = (array) $request->input('r', []);
        $bien = 0;
        $malas = [];
        foreach ($curso['prueba'] as $i => $p) {
            if ((string) ($resp[$i] ?? '') === (string) $p['r']) $bien++; else $malas[] = $i;
        }
        $nota = (int) round($bien / max(1, count($curso['prueba'])) * 100);
        $a = AcademiaAvance::firstOrNew(['user_id' => auth()->id(), 'curso' => $slug]);
        $a->lecciones = array_keys($curso['lecciones']);
        $aprobo = $nota >= 66;
        if ($aprobo) {
            $a->nota = max((int) $a->nota, $nota);
            $a->aprobado_at = $a->aprobado_at ?: now();
        }
        $a->save();
        return redirect()->to(route('aprende.curso', $slug) . '#prueba')->with('resultado', ['aprobo' => $aprobo, 'nota' => $nota, 'malas' => $malas]);
    }
}
