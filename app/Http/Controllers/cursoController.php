<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Support\CursoAsesor;
use App\Support\RedComercial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Curso básico obligatorio para toda la red: lecciones → examen fácil → certificado.
// Aspirante nuevo: al aprobar queda como asesor activo. Asesor que ya estaba activo: al aprobar vuelve a su panel.
// Administrador: lo recorre completo en modo vista (no se guarda en la base y el certificado es de muestra).
class cursoController extends Controller
{
    private function yo(): ?Asesor
    {
        $u = auth()->user();
        if ($u?->asesor) {
            return $u->asesor;
        }
        if ($u?->es_superadmin) {
            $a = new Asesor([
                'estado' => 'activo',
                'curso_modulos' => session('curso_admin.modulos', []),
                'curso_nota' => session('curso_admin.nota'),
                'curso_aprobado_at' => session('curso_admin.aprobado'),
                'certificado' => 'CERT-MUESTRA',
            ]);
            $a->setRelation('user', $u);
            $a->setRelation('reclutador', null);
            $a->vistaAdmin = true;
            return $a;
        }
        return null;
    }

    private function esAdmin(Asesor $a): bool
    {
        return !empty($a->vistaAdmin);
    }

    private function panel(Asesor $a): string
    {
        return $this->esAdmin($a) ? route('admin.red.index') : route('asesor.index');
    }

    // Guarda el avance: en la base para la red, en la sesión para el administrador
    private function guardar(Asesor $a, array $datos): void
    {
        if ($this->esAdmin($a)) {
            $mapa = ['curso_modulos' => 'modulos', 'curso_nota' => 'nota', 'curso_aprobado_at' => 'aprobado'];
            foreach ($datos as $k => $v) {
                if (isset($mapa[$k])) session()->put('curso_admin.' . $mapa[$k], $v);
            }
            $a->forceFill($datos);
            return;
        }
        $a->update($datos);
    }

    private function hechas(Asesor $a): array
    {
        return array_map('intval', $a->curso_modulos ?? []);
    }

    private function comunes(Asesor $a): array
    {
        return ['yo' => $a, 'admin' => $this->esAdmin($a), 'panel' => $this->panel($a)];
    }

    public function index()
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        if (!$this->esAdmin($a)) $a->load('reclutador.user:id,name');

        return view('curso.index', $this->comunes($a) + [
            'lecciones' => CursoAsesor::lecciones(),
            'hechas' => $this->hechas($a),
            'intentos' => $a->id ? DB::table('asesor_examenes')->where('asesor_id', $a->id)->count() : (int) session('curso_admin.intentos', 0),
            'minima' => CursoAsesor::notaMinima(),
            'preguntas' => CursoAsesor::cantidadPreguntas(),
        ]);
    }

    // Solo para el administrador: borrar su avance de muestra y empezar de nuevo
    public function reiniciar()
    {
        abort_unless(auth()->user()?->es_superadmin, 403);
        session()->forget('curso_admin');
        return redirect()->route('curso.index');
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

        $tarea = null;
        if (($lecciones[$n]['tarea'] ?? null) === 'contactos') {
            $tarea = ['hecha' => $this->esAdmin($a) || self::tareaHecha($a), 'r' => $a->id ? asesorContactosController::resumen($a->id) : ['total' => 0, 'contactados' => 0, 'hoy' => 0]];
        }
        return view('curso.leccion', $this->comunes($a) + [
            'n' => $n, 'l' => $lecciones[$n], 'total' => count($lecciones), 'hechas' => $hechas, 'tarea' => $tarea,
        ]);
    }

    public static function tareaHecha(Asesor $a): bool
    {
        if (!$a->id) return true;
        $r = asesorContactosController::resumen($a->id);
        return $r['total'] > 0 && $r['contactados'] >= CursoAsesor::TAREA_MINIMO;
    }

    // "Entendido": marca la lección y pasa a la siguiente (o al examen)
    public function completar(int $n)
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        $lecciones = CursoAsesor::lecciones();
        abort_unless(isset($lecciones[$n]), 404);

        // Lección con tarea: solo se pasa cuando la hizo de verdad (el administrador en vista no la necesita)
        if (($lecciones[$n]['tarea'] ?? null) === 'contactos' && !$this->esAdmin($a) && !self::tareaHecha($a)) {
            return redirect()->route('curso.leccion', $n)->with('tarea', 'Primero haga la tarea: suba sus contactos y escríbale a ' . CursoAsesor::TAREA_MINIMO . ' personas.');
        }
        $hechas = $this->hechas($a);
        if (!in_array($n, $hechas, true)) {
            $hechas[] = $n;
            sort($hechas);
            $this->guardar($a, ['curso_modulos' => $hechas]);
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

        return view('curso.examen', $this->comunes($a) + [
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

        if ($this->esAdmin($a)) {
            session()->put('curso_admin.intentos', (int) session('curso_admin.intentos', 0) + 1);
        } else {
            DB::table('asesor_examenes')->insert([
                'asesor_id' => $a->id, 'nota' => $nota, 'correctas' => $correctas, 'total' => $total,
                'aprobado' => $aprobado, 'respuestas' => json_encode($detalle), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->guardar($a, ['curso_nota' => max((int) $a->curso_nota, $nota)]);

        if (!$aprobado) {
            return redirect()->route('curso.index')->with('resultado', [
                'aprobado' => false, 'correctas' => $correctas, 'total' => $total,
                'falladas' => collect($detalle)->where('ok', false)->map(fn ($d) => $banco[$d['p']][0] . ' → ' . $banco[$d['p']][1][$banco[$d['p']][2]])->values()->all(),
            ]);
        }

        if ($this->esAdmin($a)) {
            $this->guardar($a, ['curso_aprobado_at' => now()]);
            return redirect()->route('curso.certificado')->with('felicitar', true);
        }

        DB::transaction(function () use ($a, $nota) {
            $a->refresh();
            $datos = ['curso_aprobado_at' => now(), 'certificado' => $a->certificado ?: self::nuevoCertificado()];
            // Aspirante: queda como asesor activo de una vez (quien lo invitó puede desactivarlo después)
            if (in_array($a->estado, ['capacitacion', 'pendiente'], true)) {
                $invita = $a->reclutador;
                $datos += [
                    'estado' => 'activo', 'activo' => true, 'aprobado_at' => now(),
                    'padre_id' => $invita ? RedComercial::ubicar($a, $invita) : $a->padre_id,
                ];
            }
            $a->update($datos);
            RedComercial::historial($a, 'curso', 'Aprobó el curso de asesores con ' . $nota . '% · certificado ' . $a->certificado);
        });

        return redirect()->route('curso.certificado')->with('felicitar', true);
    }

    public function certificado()
    {
        $a = $this->yo();
        if (!$a) return redirect()->route('panel');
        if (!$a->curso_aprobado_at) return redirect()->route('curso.index');
        if (!$this->esAdmin($a)) $a->load('user');
        return view('curso.certificado', $this->comunes($a) + ['a' => $a, 'propio' => true, 'felicitar' => session('felicitar')]);
    }

    // Verificación pública del certificado (para quien lo vea en una hoja de vida)
    public function verificar(string $codigo)
    {
        $a = Asesor::with('user')->where('certificado', strtoupper($codigo))->whereNotNull('curso_aprobado_at')->first();
        abort_unless($a, 404, 'Este certificado no existe.');
        return view('curso.certificado', ['a' => $a, 'propio' => false, 'felicitar' => false, 'admin' => false, 'panel' => url('/')]);
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
