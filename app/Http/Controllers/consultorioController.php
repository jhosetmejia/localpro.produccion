<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Atencion;
use App\Models\AtencionNota;
use App\Models\Cita;
use App\Models\ClinicaAcceso;
use App\Models\Paciente;
use App\Models\User;
use App\Support\Consultorio;
use App\Support\TiendaActual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Consultorio particular: pacientes, agenda de citas e historia clínica (Res. 1995/1999).
// - "agenda-consultorio": recepción (pacientes y citas, sin ver la parte clínica).
// - "historia-clinica": profesional de salud (ve y escribe historias).
class consultorioController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            $t = TiendaActual::tienda();
            if (!$t || !$t->mod_consultorio) {
                return redirect('/')->with('info', 'Su negocio no tiene activado Consultorio. Actívelo en Datos del negocio → "Módulos de su negocio".');
            }
            abort_unless($u && ($u->es_superadmin || $u->can('agenda-consultorio') || $u->can('historia-clinica')), 403);
            return $next($request);
        });
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->es_superadmin || $request->user()->can('historia-clinica'), 403, 'Solo los profesionales de salud ven la historia clínica.');
            return $next($request);
        })->only(['atencionNueva', 'atencion', 'atencionGuardar', 'atencionCerrar', 'atencionNota', 'imprimir', 'historiaCompleta', 'cie']);
    }

    private function clinico(): bool
    {
        $u = auth()->user();
        return $u->es_superadmin || $u->can('historia-clinica');
    }

    private function ajuste(string $k, $def = null)
    {
        return Ajuste::valor('t' . TiendaActual::id() . '_' . $k, $def);
    }

    private function profesionales()
    {
        $tid = TiendaActual::id();
        return User::where('tienda_id', $tid)->get()->filter(fn ($u) => $u->can('historia-clinica'))->values();
    }

    // ================= Inicio =================
    public function inicio()
    {
        $hoy = Cita::with(['paciente', 'profesional:id,name'])->whereDate('fecha', today())->orderBy('hora')->get();
        $kpi = [
            'pacientes' => Paciente::count(),
            'citas_hoy' => $hoy->whereIn('estado', ['programada', 'confirmada'])->count(),
            'atendidas_hoy' => $hoy->where('estado', 'atendida')->count(),
            'atenciones_mes' => Atencion::whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'semana' => Cita::whereBetween('fecha', [today(), today()->addDays(6)])->whereIn('estado', ['programada', 'confirmada'])->count(),
            'abiertas' => $this->clinico() ? Atencion::whereNull('cerrada_at')->where('profesional_id', auth()->id())->count() : 0,
        ];
        $recientes = Paciente::latest()->take(6)->get();
        $sinPerfil = $this->clinico() && !auth()->user()->registro_profesional;
        return view('consultorio.inicio', compact('hoy', 'kpi', 'recientes', 'sinPerfil'));
    }

    // ================= Agenda =================
    public function agenda(Request $request)
    {
        $fecha = rescue(fn () => Carbon::parse($request->get('fecha', today()->toDateString())), today(), false)->startOfDay();
        $desde = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $dias = collect(range(0, 6))->map(fn ($i) => $desde->copy()->addDays($i));
        $citas = Cita::with(['paciente', 'profesional:id,name'])
            ->whereBetween('fecha', [$desde->toDateString(), $desde->copy()->addDays(6)->toDateString()])
            ->orderBy('hora')->get()->groupBy(fn ($c) => $c->fecha->toDateString());
        $profesionales = $this->profesionales();
        $duracion = (int) $this->ajuste('cita_duracion', 30);
        $paciente = $request->integer('paciente') ? Paciente::find($request->integer('paciente')) : null;
        return view('consultorio.agenda', compact('fecha', 'desde', 'dias', 'citas', 'profesionales', 'duracion', 'paciente'));
    }

    public function citaGuardar(Request $request, ?Cita $cita = null)
    {
        $datos = $request->validate([
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('tienda_id', TiendaActual::id())],
            'profesional_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tienda_id', TiendaActual::id())],
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'duracion' => 'nullable|integer|min:5|max:480',
            'motivo' => 'nullable|string|max:160',
            'valor' => 'nullable|numeric|min:0|max:999999999',
            'nota' => 'nullable|string|max:255',
        ]);
        $datos['duracion'] = $datos['duracion'] ?? (int) $this->ajuste('cita_duracion', 30);

        // Que el profesional no tenga dos citas cruzadas
        if (!empty($datos['profesional_id'])) {
            $ini = Carbon::parse($datos['fecha'] . ' ' . $datos['hora']);
            $fin = $ini->copy()->addMinutes($datos['duracion']);
            $cruce = Cita::where('profesional_id', $datos['profesional_id'])->whereDate('fecha', $datos['fecha'])
                ->whereIn('estado', ['programada', 'confirmada'])->when($cita?->id, fn ($q) => $q->where('id', '<>', $cita->id))->get()
                ->first(function ($c) use ($ini, $fin) {
                    $ci = Carbon::parse($c->fecha->toDateString() . ' ' . $c->hora);
                    return $ci < $fin && $ci->copy()->addMinutes($c->duracion) > $ini;
                });
            if ($cruce) return back()->withInput()->withErrors('Ese profesional ya tiene una cita a las ' . $cruce->hora_corta . ' (' . $cruce->paciente?->nombre_completo . ').');
        }

        if ($cita && $cita->exists) {
            $cita->update($datos);
            $msg = 'Cita actualizada.';
        } else {
            $cita = Cita::create($datos + ['estado' => 'programada', 'user_id' => auth()->id()]);
            $msg = 'Cita agendada para el ' . $cita->fecha->translatedFormat('l j \d\e F') . ' a las ' . $cita->hora_corta . '.';
        }
        return redirect()->route('consultorio.agenda', ['fecha' => $cita->fecha->toDateString()])->with('success', $msg);
    }

    public function citaEstado(Request $request, Cita $cita)
    {
        $request->validate(['estado' => ['required', Rule::in(array_keys(Cita::ESTADOS))]]);
        $cita->update(['estado' => $request->estado]);
        return back()->with('success', 'Cita marcada como ' . mb_strtolower(Cita::ESTADOS[$request->estado][0]) . '.');
    }

    // ================= Pacientes =================
    public function pacientes(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $pacientes = Paciente::withCount('atenciones')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombres', 'like', "%$q%")->orWhere('apellidos', 'like', "%$q%")
                ->orWhere('documento', 'like', "%$q%")->orWhere('telefono', 'like', "%$q%")
                ->orWhereRaw("CONCAT(nombres, ' ', COALESCE(apellidos, '')) like ?", ["%$q%"])))
            ->orderBy('nombres')->orderBy('apellidos')->paginate(30)->withQueryString();
        if ($request->wantsJson()) {
            return response()->json($pacientes->getCollection()->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre_completo, 'documento' => trim($p->tipo_documento . ' ' . $p->documento), 'telefono' => $p->telefono]));
        }
        return view('consultorio.pacientes', compact('pacientes', 'q'));
    }

    public function pacienteForm(?Paciente $paciente = null)
    {
        $paciente = $paciente && $paciente->exists ? $paciente : null;
        return view('consultorio.paciente-form', ['p' => $paciente, 'clinico' => $this->clinico()]);
    }

    public function pacienteGuardar(Request $request, ?Paciente $paciente = null)
    {
        $paciente = $paciente && $paciente->exists ? $paciente : null;
        $datos = $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(Paciente::DOCUMENTOS))],
            'documento' => ['nullable', 'string', 'max:30', Rule::unique('pacientes', 'documento')->where('tienda_id', TiendaActual::id())->ignore($paciente?->id)],
            'nombres' => 'required|string|max:80',
            'apellidos' => 'nullable|string|max:80',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'sexo' => 'nullable|in:F,M,I',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:120',
            'direccion' => 'nullable|string|max:160',
            'ciudad' => 'nullable|string|max:80',
            'aseguradora' => 'nullable|string|max:80',
            'ocupacion' => 'nullable|string|max:80',
            'acompanante' => 'nullable|string|max:160',
            'alergias' => 'nullable|string|max:2000',
            'antecedentes' => 'nullable|array',
            'antecedentes.*' => 'nullable|string|max:3000',
            'notas' => 'nullable|string|max:3000',
            'autoriza_datos' => 'nullable|boolean',
        ], ['documento.unique' => 'Ya hay un paciente con ese documento.']);

        // La parte clínica solo la cambia un profesional
        if (!$this->clinico()) {
            unset($datos['alergias'], $datos['antecedentes'], $datos['notas']);
        } else {
            $datos['antecedentes'] = array_filter(array_intersect_key($datos['antecedentes'] ?? [], Paciente::ANTECEDENTES), fn ($v) => trim((string) $v) !== '');
        }
        if ($request->boolean('autoriza_datos') && !$paciente?->autoriza_datos_at) $datos['autoriza_datos_at'] = now();
        unset($datos['autoriza_datos']);

        if ($paciente) {
            $paciente->update($datos);
            ClinicaAcceso::anotar('editar', $paciente->id);
        } else {
            $paciente = Paciente::create($datos);
            ClinicaAcceso::anotar('crear', $paciente->id);
        }
        if ($request->input('despues') === 'cita') {
            return redirect()->route('consultorio.agenda', ['paciente' => $paciente->id])->with('success', 'Paciente guardado. Ahora agéndele la cita.');
        }
        return redirect()->route('consultorio.paciente', $paciente)->with('success', 'Paciente guardado.');
    }

    public function paciente(Paciente $paciente)
    {
        $clinico = $this->clinico();
        $atenciones = $clinico ? $paciente->atenciones()->with('profesional:id,name')->orderByDesc('fecha')->get() : collect();
        $citas = $paciente->citas()->with('profesional:id,name')->orderByDesc('fecha')->orderByDesc('hora')->take(20)->get();
        $accesos = auth()->user()->can('editar-negocio')
            ? ClinicaAcceso::with('user:id,name')->where('paciente_id', $paciente->id)->latest('created_at')->take(15)->get() : collect();
        if ($clinico) ClinicaAcceso::anotar('ver', $paciente->id);
        return view('consultorio.paciente', compact('paciente', 'clinico', 'atenciones', 'citas', 'accesos'));
    }

    public function pacienteBorrar(Paciente $paciente)
    {
        abort_unless(auth()->user()->can('editar-negocio'), 403);
        if ($paciente->atenciones()->exists()) {
            return back()->with('error', 'Este paciente tiene historia clínica: por ley no se puede borrar.');
        }
        $paciente->citas()->delete();
        $paciente->delete();
        return redirect()->route('consultorio.pacientes')->with('success', 'Paciente eliminado.');
    }

    // ================= Historia clínica =================
    public function atencionNueva(Request $request, Paciente $paciente)
    {
        $cita = $request->integer('cita') ? Cita::where('paciente_id', $paciente->id)->find($request->integer('cita')) : null;
        // Si ya tiene una atención abierta de este profesional para el mismo paciente, se sigue esa
        $abierta = Atencion::where('paciente_id', $paciente->id)->where('profesional_id', auth()->id())->whereNull('cerrada_at')->latest()->first();
        if ($abierta) return redirect()->route('consultorio.atencion', $abierta)->with('info', 'Siga con la atención que tenía abierta.');

        $u = auth()->user();
        $plantilla = $request->get('plantilla', $this->ajuste('hc_plantilla', 'general'));
        if (!array_key_exists($plantilla, Consultorio::plantillas())) $plantilla = 'general';

        $atencion = DB::transaction(function () use ($paciente, $cita, $u, $plantilla) {
            $a = Atencion::create([
                'paciente_id' => $paciente->id, 'profesional_id' => $u->id, 'cita_id' => $cita?->id,
                'numero' => TiendaActual::tienda()->siguienteAtencion(), 'plantilla' => $plantilla, 'fecha' => now(),
                'contenido' => ['motivo' => $cita?->motivo], 'diagnosticos' => [],
                'profesional_nombre' => $u->name, 'profesional_registro' => $u->registro_profesional, 'profesional_especialidad' => $u->especialidad,
            ]);
            if ($cita) $cita->update(['atencion_id' => $a->id]);
            return $a;
        });
        ClinicaAcceso::anotar('crear', $paciente->id, $atencion->id);
        return redirect()->route('consultorio.atencion', $atencion);
    }

    public function atencion(Atencion $atencion)
    {
        $atencion->load(['paciente', 'notas', 'profesional:id,name']);
        $puedeEditar = !$atencion->cerrada && $atencion->profesional_id === auth()->id();
        $anteriores = Atencion::where('paciente_id', $atencion->paciente_id)->where('id', '<>', $atencion->id)->whereNotNull('cerrada_at')
            ->orderByDesc('fecha')->take(5)->get();
        ClinicaAcceso::anotar('ver', $atencion->paciente_id, $atencion->id);
        return view('consultorio.atencion', ['a' => $atencion, 'p' => $atencion->paciente, 'puedeEditar' => $puedeEditar,
            'plantilla' => Consultorio::plantilla($atencion->plantilla), 'anteriores' => $anteriores]);
    }

    public function atencionGuardar(Request $request, Atencion $atencion)
    {
        if ($atencion->cerrada || $atencion->profesional_id !== auth()->id()) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'msg' => 'Esta atención ya está cerrada o es de otro profesional.'], 422)
                : back()->with('error', 'Esta atención ya está cerrada o es de otro profesional: agregue una nota aclaratoria.');
        }
        $request->validate([
            'plantilla' => ['nullable', Rule::in(array_keys(Consultorio::plantillas()))],
            'c' => 'nullable|array', 'c.*' => 'nullable|string|max:10000',
            's' => 'nullable|array', 's.*' => 'nullable|string|max:30',
            'dx' => 'nullable|array|max:10', 'dx.*.codigo' => 'nullable|string|max:10', 'dx.*.texto' => 'nullable|string|max:200', 'dx.*.tipo' => 'nullable|in:impresion,confirmado',
            'formula' => 'nullable|string|max:10000',
            'incapacidad_dias' => 'nullable|integer|min:0|max:180',
            'incapacidad_desde' => 'nullable|date',
        ]);
        $contenido = array_filter((array) $request->input('c', []), fn ($v) => trim((string) $v) !== '');
        $signos = array_filter((array) $request->input('s', []), fn ($v) => trim((string) $v) !== '');
        if ($signos) $contenido['signos'] = $signos;
        $dx = collect($request->input('dx', []))->map(fn ($d) => ['codigo' => mb_strtoupper(trim($d['codigo'] ?? '')), 'texto' => trim($d['texto'] ?? ''), 'tipo' => $d['tipo'] ?? 'impresion'])
            ->filter(fn ($d) => $d['codigo'] !== '' || $d['texto'] !== '')->values()->all();

        $atencion->update([
            'plantilla' => $request->input('plantilla', $atencion->plantilla),
            'contenido' => $contenido, 'diagnosticos' => $dx,
            'formula' => trim((string) $request->input('formula')) ?: null,
            'incapacidad_dias' => $request->integer('incapacidad_dias') ?: null,
            'incapacidad_desde' => $request->integer('incapacidad_dias') ? ($request->input('incapacidad_desde') ?: today()) : null,
        ]);

        if ($request->wantsJson()) return response()->json(['ok' => true, 'hora' => now()->format('h:i a')]);
        if ($request->input('cerrar')) return $this->atencionCerrar($atencion->fresh());
        return back()->with('success', 'Borrador guardado.');
    }

    public function atencionCerrar(Atencion $atencion)
    {
        if ($atencion->cerrada) return redirect()->route('consultorio.atencion', $atencion);
        abort_unless($atencion->profesional_id === auth()->id(), 403, 'Solo quien hizo la atención la puede cerrar.');
        $c = $atencion->contenido ?? [];
        if (trim((string) ($c['motivo'] ?? '')) === '' && empty($atencion->diagnosticos)) {
            return back()->with('error', 'Antes de cerrar escriba al menos el motivo de consulta o un diagnóstico.');
        }
        $u = auth()->user();
        $atencion->forceFill([
            'cerrada_at' => now(),
            'profesional_nombre' => $u->name, 'profesional_registro' => $u->registro_profesional, 'profesional_especialidad' => $u->especialidad,
        ])->save();
        $atencion->forceFill(['huella' => $atencion->calcularHuella()])->save();
        if ($atencion->cita_id) Cita::whereKey($atencion->cita_id)->update(['estado' => 'atendida']);
        ClinicaAcceso::anotar('cerrar', $atencion->paciente_id, $atencion->id);
        return redirect()->route('consultorio.atencion', $atencion)->with('success', 'Atención cerrada y firmada. Desde ahora solo se puede agregar notas aclaratorias.');
    }

    public function atencionNota(Request $request, Atencion $atencion)
    {
        $request->validate(['texto' => 'required|string|min:3|max:5000']);
        if (!$atencion->cerrada) return back()->with('error', 'La atención sigue abierta: corrija directamente el texto.');
        AtencionNota::create(['atencion_id' => $atencion->id, 'user_id' => auth()->id(), 'autor' => auth()->user()->name, 'texto' => trim($request->texto)]);
        ClinicaAcceso::anotar('nota', $atencion->paciente_id, $atencion->id);
        return back()->with('success', 'Nota aclaratoria agregada.');
    }

    // doc: historia | formula | incapacidad
    public function imprimir(Request $request, Atencion $atencion)
    {
        $doc = in_array($request->get('doc'), ['historia', 'formula', 'incapacidad'], true) ? $request->get('doc') : 'historia';
        $atencion->load(['paciente', 'notas']);
        ClinicaAcceso::anotar('imprimir', $atencion->paciente_id, $atencion->id);
        return view('consultorio.imprimir', ['doc' => $doc, 'atenciones' => collect([$atencion]), 'p' => $atencion->paciente, 'tienda' => TiendaActual::tienda(), 'firmas' => $this->firmas(collect([$atencion]))]);
    }

    public function historiaCompleta(Paciente $paciente)
    {
        $atenciones = $paciente->atenciones()->with('notas')->whereNotNull('cerrada_at')->orderBy('fecha')->get();
        ClinicaAcceso::anotar('imprimir', $paciente->id);
        return view('consultorio.imprimir', ['doc' => 'completa', 'atenciones' => $atenciones, 'p' => $paciente, 'tienda' => TiendaActual::tienda(), 'firmas' => $this->firmas($atenciones)]);
    }

    // Firmas de los profesionales como imagen embebida (no quedan públicas en internet)
    private function firmas($atenciones): array
    {
        $f = [];
        foreach (User::whereIn('id', $atenciones->pluck('profesional_id')->unique())->get(['id', 'firma_path']) as $u) {
            if ($u->firma_path && Storage::disk('local')->exists($u->firma_path)) {
                $f[$u->id] = 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($u->firma_path));
            }
        }
        return $f;
    }

    public function cie(Request $request)
    {
        return response()->json(Consultorio::buscarCie((string) $request->get('q')));
    }

    // ================= Configuración =================
    public function configuracion()
    {
        return view('consultorio.configuracion', [
            'u' => auth()->user(), 'clinico' => $this->clinico(),
            'plantilla' => $this->ajuste('hc_plantilla', 'general'), 'duracion' => (int) $this->ajuste('cita_duracion', 30),
            'firma' => $this->firmas(collect([(object) ['profesional_id' => auth()->id()]]))[auth()->id()] ?? null,
        ]);
    }

    public function configuracionGuardar(Request $request)
    {
        $request->validate([
            'registro_profesional' => 'nullable|string|max:60',
            'especialidad' => 'nullable|string|max:80',
            'firma' => 'nullable|image|max:2048',
            'plantilla' => ['nullable', Rule::in(array_keys(Consultorio::plantillas()))],
            'cita_duracion' => 'nullable|integer|min:5|max:240',
        ]);
        $u = auth()->user();
        if ($this->clinico()) {
            $cambios = ['registro_profesional' => $request->input('registro_profesional'), 'especialidad' => $request->input('especialidad')];
            if ($request->hasFile('firma')) {
                if ($u->firma_path) Storage::disk('local')->delete($u->firma_path);
                $cambios['firma_path'] = $request->file('firma')->store('firmas', 'local');
            }
            if ($request->boolean('quitar_firma') && $u->firma_path) {
                Storage::disk('local')->delete($u->firma_path);
                $cambios['firma_path'] = null;
            }
            $u->forceFill($cambios)->save();
        }
        if ($u->can('editar-negocio')) {
            $tid = TiendaActual::id();
            if ($request->filled('plantilla')) Ajuste::guardar('t' . $tid . '_hc_plantilla', $request->plantilla);
            if ($request->filled('cita_duracion')) Ajuste::guardar('t' . $tid . '_cita_duracion', (int) $request->cita_duracion);
        }
        return back()->with('success', 'Configuración guardada.');
    }
}
