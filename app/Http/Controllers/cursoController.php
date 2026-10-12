<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Support\CursoAsesor;
use App\Support\RedComercial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Curso básico del aspirante a asesor: lecciones → examen fácil → certificado → queda como asesor activo
class cursoController extends Controller
{
    private function yo(): ?Asesor
    {
        return auth()->user()?->asesor;
    }

    private function hechas(Asesor $a): array
    {
        return array_map('intval', $a->curso_modulos ?? []);
    }

    public function index()
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        $a->load('reclutador.user:id,name');

        return view('curso.index', [
            'yo' => $a,
            'lecciones' => CursoAsesor::lecciones(),
            'hechas' => $this->hechas($a),
            'intentos' => DB::table('asesor_examenes')->where('asesor_id', $a->id)->count(),
            'minima' => CursoAsesor::notaMinima(),
            'preguntas' => CursoAsesor::cantidadPreguntas(),
        ]);
    }

    public function leccion(int $n)
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        $lecciones = CursoAsesor::lecciones();
        abort_unless(isset($lecciones[$n]), 404);
        $hechas = $this->hechas($a);
        // Se avanza en orden: no puede abrir una lección sin haber visto la anterior
        if ($n > 1 && !in_array($n - 1, $hechas, true) && !$a->curso_aprobado_at) {
            return redirect()->route('curso.leccion', $n - 1);
        }

        return view('curso.leccion', [
            'yo' => $a, 'n' => $n, 'l' => $lecciones[$n], 'total' => count($lecciones), 'hechas' => $hechas,
        ]);
    }

    // "Entendido": marca la lección y pasa a la siguiente (o al examen)
    public function completar(int $n)
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        $lecciones = CursoAsesor::lecciones();
        abort_unless(isset($lecciones[$n]), 404);

        $hechas = $this->hechas($a);
        if (!in_array($n, $hechas, true)) {
            $hechas[] = $n;
            sort($hechas);
            $a->update(['curso_modulos' => $hechas]);
        }
        return isset($lecciones[$n + 1])
            ? redirect()->route('curso.leccion', $n + 1)
            : redirect()->route('curso.examen');
    }

    public function examen(Request $request)
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        if ($a->curso_aprobado_at) return redirect()->route('curso.certificado');
        if (count(array_intersect(array_keys(CursoAsesor::lecciones()), $this->hechas($a))) < count(CursoAsesor::lecciones())) {
            return redirect()->route('curso.index')->with('error', 'Primero vea las lecciones del curso.');
        }

        $ids = CursoAsesor::armarExamen();
        $request->session()->put('curso_examen', $ids);
        $banco = CursoAsesor::preguntas();

        return view('curso.examen', [
            'yo' => $a,
            'preguntas' => array_map(fn ($id) => ['id' => $id] + array_combine(['p', 'o', 'c'], $banco[$id]), $ids),
            'minima' => CursoAsesor::notaMinima(),
        ]);
    }

    public function calificar(Request $request)
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        if ($a->curso_aprobado_at) return redirect()->route('curso.certificado');

        $ids = $request->session()->pull('curso_examen');
        if (!$ids) return redirect()->route('curso.examen');

        $banco = CursoAsesor::preguntas();
        $resp = (array) $request->input('r', []);
        $correctas = 0;
        $detalle = [];
        foreach ($ids as $id) {
            $elegida = isset($resp[$id]) ? (int) $resp[$id] : null;
            $ok = $elegida !== null && $elegida === $banco[$id][2];
            $correctas += $ok ? 1 : 0;
            $detalle[] = ['p' => $id, 'r' => $elegida, 'ok' => $ok];
        }
        $total = count($ids);
        $nota = (int) round($correctas / max(1, $total) * 100);
        $aprobado = $nota >= CursoAsesor::notaMinima();

        DB::table('asesor_examenes')->insert([
            'asesor_id' => $a->id, 'nota' => $nota, 'correctas' => $correctas, 'total' => $total,
            'aprobado' => $aprobado, 'respuestas' => json_encode($detalle), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $a->update(['curso_nota' => max((int) $a->curso_nota, $nota)]);

        if (!$aprobado) {
            return redirect()->route('curso.index')->with('resultado', [
                'aprobado' => false, 'correctas' => $correctas, 'total' => $total,
                'falladas' => collect($detalle)->where('ok', false)->map(fn ($d) => $banco[$d['p']][0] . ' → ' . $banco[$d['p']][1][$banco[$d['p']][2]])->values()->all(),
            ]);
        }

        DB::transaction(function () use ($a, $nota) {
            $a->refresh();
            $datos = ['curso_aprobado_at' => now(), 'certificado' => $a->certificado ?: self::nuevoCertificado()];
            // Si venía como aspirante, queda como asesor activo de una vez (quien lo invitó puede desactivarlo después)
            if (in_array($a->estado, ['capacitacion', 'pendiente'], true)) {
                $invita = $a->reclutador;
                $datos += [
                    'estado' => 'activo', 'activo' => true, 'aprobado_at' => now(),
                    'padre_id' => $invita ? RedComercial::ubicar($a, $invita) : $a->padre_id,
                ];
            }
            $a->update($datos);
            RedComercial::historial($a, 'aprobado', 'Aprobó el curso de asesores con ' . $nota . '% · certificado ' . $a->certificado);
        });

        return redirect()->route('curso.certificado')->with('felicitar', true);
    }

    public function certificado()
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        if (!$a->curso_aprobado_at) return redirect()->route('curso.index');
        return view('curso.certificado', ['a' => $a->load('user'), 'propio' => true, 'felicitar' => session('felicitar')]);
    }

    // Verificación pública del certificado (para quien lo vea en una hoja de vida)
    public function verificar(string $codigo)
    {
        $a = Asesor::with('user')->where('certificado', strtoupper($codigo))->whereNotNull('curso_aprobado_at')->first();
        abort_unless($a, 404, 'Este certificado no existe.');
        return view('curso.certificado', ['a' => $a, 'propio' => false, 'felicitar' => false]);
    }

    private static function nuevoCertificado(): string
    {
        $max = (int) Asesor::where('certificado', 'like', 'CERT-%')->selectRaw('MAX(CAST(SUBSTRING(certificado, 6) AS UNSIGNED)) m')->value('m');
        do {
            $c = 'CERT-' . str_pad((string) ++$max, 5, '0', STR_PAD_LEFT);
        } while (Asesor::where('certificado', $c)->exists());
        return $c;
    }
}
