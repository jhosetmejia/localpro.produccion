<?php

namespace App\Http\Controllers;

use App\Models\Instituto\Cargo;
use App\Models\Instituto\Estudiante;
use App\Models\Instituto\Matricula;
use App\Models\Instituto\Pago;
use App\Models\Instituto\Programa;
use App\Models\MetodoPago;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Institutos técnicos y academias: programas, inscripciones (aspirantes), estudiantes, matrículas
 * de contado o a crédito (cuotas mensuales), pagos con recibo, cartera y formatos para imprimir.
 */
class institutoController extends Controller
{
    private function inst(): Tienda
    {
        return TiendaActual::tienda();
    }

    private function esAdmin(): bool
    {
        $u = Auth::user();
        return $u->es_superadmin || $u->hasAnyRole(['dueno', 'administrador']);
    }

    private function metodos()
    {
        $m = MetodoPago::where('activo', true)->orderBy('orden')->pluck('nombre');
        return $m->isNotEmpty() ? $m : collect(['Efectivo', 'Transferencia', 'Nequi', 'Tarjeta']);
    }

    // ===================== Resumen =====================
    public function inicio()
    {
        $mesIni = now()->startOfMonth();
        $pend = fn () => Cargo::whereIn('estado', ['pendiente', 'parcial']);
        return view('instituto.inicio', [
            'inst' => $this->inst(),
            'kpi' => [
                'programas' => Programa::where('activo', true)->count(),
                'matriculados' => Estudiante::where('estado', 'matriculado')->count(),
                'aspirantes' => Estudiante::where('estado', 'aspirante')->count(),
                'recaudoMes' => (float) Pago::where('anulado', false)->where('fecha', '>=', $mesIni)->sum('valor'),
                'pagosMes' => Pago::where('anulado', false)->where('fecha', '>=', $mesIni)->count(),
                'cartera' => (float) $pend()->selectRaw('COALESCE(SUM(valor - pagado),0) s')->value('s'),
                'vencido' => (float) $pend()->whereDate('vence', '<', today())->selectRaw('COALESCE(SUM(valor - pagado),0) s')->value('s'),
                'enMora' => $pend()->whereDate('vence', '<', today())->distinct()->count('estudiante_id'),
                'matriculasPeriodo' => Matricula::where('periodo', Matricula::periodoActual())->where('estado', 'activa')->count(),
            ],
            'proximas' => Cargo::with('estudiante')->whereIn('estado', ['pendiente', 'parcial'])
                ->whereBetween('vence', [today(), today()->addDays(7)])->orderBy('vence')->take(8)->get(),
            'ultimosPagos' => Pago::with('estudiante')->where('anulado', false)->latest('fecha')->latest('id')->take(6)->get(),
            'aspirantes' => Estudiante::with('programa')->where('estado', 'aspirante')->latest()->take(6)->get(),
        ]);
    }

    // ===================== Programas =====================
    public function programas()
    {
        $programas = Programa::withCount(['estudiantes as matriculados' => fn ($q) => $q->where('estado', 'matriculado')])
            ->orderByDesc('activo')->orderBy('nombre')->get();
        return view('instituto.programas', compact('programas'));
    }

    public function programaGuardar(Request $request, ?Programa $programa = null)
    {
        $d = $request->validate([
            'nombre' => 'required|string|max:120',
            'tipo' => 'nullable|string|max:40',
            'semestres' => 'required|integer|min:1|max:20',
            'valor_semestre' => 'required|numeric|min:0|max:1000000000',
            'valor_inscripcion' => 'nullable|numeric|min:0|max:1000000000',
            'modulos' => 'nullable|string|max:5000',
        ], ['nombre.required' => 'Escriba el nombre del programa.']);
        $d['tipo'] = $d['tipo'] ?: 'Técnico laboral por competencias';
        $d['valor_inscripcion'] = (float) ($d['valor_inscripcion'] ?? 0);
        $d['activo'] = $request->boolean('activo', true);
        if ($programa?->id) {
            $programa->update($d);
            return back()->with('success', 'Programa actualizado.');
        }
        Programa::create($d);
        return back()->with('success', 'Programa "' . $d['nombre'] . '" creado.');
    }

    public function programasEjemplo()
    {
        $n = 0;
        foreach (Programa::EJEMPLOS as $nom) {
            if (Programa::where('nombre', $nom)->exists()) continue;
            Programa::create(['nombre' => $nom, 'semestres' => 2, 'valor_semestre' => 0, 'valor_inscripcion' => 0, 'activo' => true]);
            $n++;
        }
        return back()->with('success', "$n programas cargados. Póngale a cada uno el valor del semestre y la duración.");
    }

    // ===================== Estudiantes e inscripciones =====================
    public function estudiantes(Request $request)
    {
        $q = trim((string) $request->q);
        $estado = array_key_exists($request->estado, Estudiante::ESTADOS) ? $request->estado : '';
        $programa = (int) $request->programa;
        $estudiantes = Estudiante::with('programa')
            ->when($estado !== '', fn ($w) => $w->where('estado', $estado))
            ->when($programa, fn ($w) => $w->where('programa_id', $programa))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombres', 'like', "%$q%")->orWhere('apellidos', 'like', "%$q%")
                ->orWhere('documento', 'like', "%$q%")->orWhere('celular', 'like', "%$q%")->orWhere('consecutivo', 'like', "%$q%")))
            ->orderBy('apellidos')->orderBy('nombres')->paginate(50)->withQueryString();
        $saldos = Cargo::whereIn('estado', ['pendiente', 'parcial'])->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->groupBy('estudiante_id')->selectRaw('estudiante_id, SUM(valor - pagado) s')->pluck('s', 'estudiante_id');
        $conteo = Estudiante::groupBy('estado')->selectRaw('estado, count(*) n')->pluck('n', 'estado');
        return view('instituto.estudiantes', [
            'estudiantes' => $estudiantes, 'saldos' => $saldos, 'q' => $q, 'estado' => $estado, 'programa' => $programa, 'conteo' => $conteo,
            'programas' => Programa::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function inscripcion(?Estudiante $estudiante = null)
    {
        return view('instituto.inscripcion', [
            'e' => $estudiante?->id ? $estudiante : new Estudiante(['tipo_doc' => 'CC', 'nacionalidad' => 'Colombiana', 'fecha_inscripcion' => today(), 'ciudad' => $this->inst()->ciudad, 'departamento' => $this->inst()->departamento]),
            'programas' => Programa::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function estudianteGuardar(Request $request, ?Estudiante $estudiante = null)
    {
        $txt = fn ($max) => "nullable|string|max:$max";
        $d = $request->validate([
            'programa_id' => ['nullable', 'integer', Rule::exists('inst_programas', 'id')->where('tienda_id', TiendaActual::id())],
            'jornada' => ['nullable', Rule::in(array_keys(Estudiante::JORNADAS))],
            'fecha_inscripcion' => 'nullable|date',
            'nombres' => 'required|string|max:80',
            'apellidos' => 'required|string|max:80',
            'tipo_doc' => ['required', Rule::in(array_keys(Estudiante::TIPOS_DOC))],
            'documento' => $txt(20), 'lugar_expedicion' => $txt(60),
            'fecha_nacimiento' => 'nullable|date|before:today', 'lugar_nacimiento' => $txt(60), 'nacionalidad' => $txt(40),
            'estado_civil' => $txt(20), 'estrato' => $txt(4), 'eps' => $txt(60),
            'direccion' => $txt(120), 'ciudad' => $txt(60), 'departamento' => $txt(60),
            'email' => 'nullable|email|max:120', 'telefono' => $txt(20), 'celular' => $txt(20), 'enfermedad' => $txt(120),
            'lab_entidad' => $txt(80), 'lab_cargo' => $txt(60), 'lab_telefono' => $txt(20),
            'bach_titulo' => $txt(80), 'bach_institucion' => $txt(120), 'bach_ciudad' => $txt(60), 'bach_fecha' => $txt(20),
            'noveno_institucion' => $txt(120), 'noveno_fecha' => $txt(20),
            'acu_parentesco' => $txt(30), 'acu_nombre' => $txt(100), 'acu_telefono' => $txt(20),
            'notas' => $txt(255),
            'estado' => ['nullable', Rule::in(array_keys(Estudiante::ESTADOS))],
        ], [
            'nombres.required' => 'Escriba los nombres del estudiante.',
            'apellidos.required' => 'Escriba los apellidos del estudiante.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);
        $d['autoriza_imagen'] = $request->boolean('autoriza_imagen');
        $d['autoriza_datos'] = $request->boolean('autoriza_datos');
        if (!empty($d['documento'])) {
            $d['documento'] = preg_replace('/[^0-9A-Za-z]/', '', $d['documento']);
            $repetido = Estudiante::where('documento', $d['documento'])->when($estudiante?->id, fn ($w, $id) => $w->where('id', '<>', $id))->first();
            if ($repetido) {
                return back()->withInput()->withErrors('Ya hay un estudiante con ese documento: ' . $repetido->nombre . '.');
            }
        }

        if ($estudiante?->id) {
            if (!$this->esAdmin() || empty($d['estado'])) unset($d['estado']);
            $estudiante->update($d);
            return redirect()->route('instituto.estudiante', $estudiante)->with('success', 'Datos actualizados.');
        }

        unset($d['estado']);
        $e = DB::transaction(function () use ($d, $request) {
            $t = Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $n = (int) $t->consecutivo_inscripcion + 1;
            $t->forceFill(['consecutivo_inscripcion' => $n])->save();
            $e = Estudiante::create($d + [
                'estado' => 'aspirante',
                'consecutivo' => str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                'fecha_inscripcion' => $d['fecha_inscripcion'] ?? today(),
                'user_id' => Auth::id(),
            ]);
            // Cobro de la inscripción (si el programa la tiene y se marcó cobrarla)
            $prog = $e->programa;
            if ($prog && $prog->valor_inscripcion > 0 && $request->boolean('cobrar_inscripcion', true)) {
                Cargo::create(['estudiante_id' => $e->id, 'tipo' => 'inscripcion', 'concepto' => 'Inscripción · ' . $prog->nombre, 'valor' => $prog->valor_inscripcion, 'vence' => today()]);
            }
            return $e;
        });
        return redirect()->route('instituto.estudiante', $e)->with('success', 'Inscripción No. ' . $e->consecutivo . ' guardada. Cuando pague y entregue documentos, matricúlelo.');
    }

    public function estudiante(Estudiante $estudiante)
    {
        return view('instituto.estudiante', [
            'e' => $estudiante->load('programa'),
            'matriculas' => $estudiante->matriculas()->get(),
            'cargos' => $estudiante->cargos()->where('estado', '<>', 'anulado')->orderBy('vence')->orderBy('id')->get(),
            'pagos' => $estudiante->pagos()->latest('fecha')->latest('id')->take(30)->get(),
            'saldo' => $estudiante->saldo(),
            'favor' => (float) $estudiante->pagos()->where('anulado', false)->sum('saldo_favor'),
            'metodos' => $this->metodos(),
            'inst' => $this->inst(),
            'admin' => $this->esAdmin(),
        ]);
    }

    public function estadoGuardar(Request $request, Estudiante $estudiante)
    {
        abort_unless($this->esAdmin(), 403);
        $d = $request->validate(['estado' => ['required', Rule::in(array_keys(Estudiante::ESTADOS))]]);
        $estudiante->update($d);
        return back()->with('success', 'Estado: ' . Estudiante::ESTADOS[$d['estado']] . '.');
    }

    public function formatoInscripcion(Estudiante $estudiante)
    {
        return view('instituto.formato-inscripcion', [
            'e' => $estudiante->load('programa'), 'inst' => $this->inst(),
            'programas' => Programa::where('activo', true)->orderBy('id')->get(['id', 'nombre']),
        ]);
    }

    // ===================== Matrículas =====================
    public function matricula(Estudiante $estudiante)
    {
        $ultima = $estudiante->matriculas()->where('estado', 'activa')->first();
        return view('instituto.matricula', [
            'e' => $estudiante,
            'programas' => Programa::where('activo', true)->orderBy('nombre')->get(),
            'ultima' => $ultima,
            'inst' => $this->inst(),
        ]);
    }

    public function matriculaGuardar(Request $request, Estudiante $estudiante)
    {
        $d = $request->validate([
            'programa_id' => ['required', 'integer', Rule::exists('inst_programas', 'id')->where('tienda_id', TiendaActual::id())],
            'periodo' => ['required', 'regex:/^\d{4}-[12]$/'],
            'semestre' => 'required|integer|min:1|max:20',
            'jornada' => ['required', Rule::in(array_keys(Estudiante::JORNADAS))],
            'modulos_pendientes' => 'nullable|string|max:2000',
            'modalidad' => ['required', Rule::in(['contado', 'credito'])],
            'valor_semestre' => 'required|numeric|min:0|max:1000000000',
            'cuota_inicial' => 'nullable|numeric|min:0|max:1000000000',
            'num_cuotas' => 'nullable|integer|min:1|max:24',
            'fecha' => 'required|date',
        ], [
            'programa_id.required' => 'Elija el programa.',
            'jornada.required' => 'Elija la jornada.',
        ]);
        $prog = Programa::findOrFail($d['programa_id']);
        $total = (float) $d['valor_semestre'];
        $inicial = $d['modalidad'] === 'credito' ? min($total, (float) ($d['cuota_inicial'] ?? 0)) : $total;
        $n = $d['modalidad'] === 'credito' ? (int) ($d['num_cuotas'] ?? 0) : 0;
        if ($d['modalidad'] === 'credito' && $n < 1 && $inicial < $total) {
            return back()->withInput()->withErrors('Escriba en cuántas cuotas paga el resto.');
        }
        $resto = round($total - $inicial, 0);
        $cuota = $n > 0 ? floor($resto / $n) : 0;
        $ya = Matricula::where('estudiante_id', $estudiante->id)->where('periodo', $d['periodo'])->where('programa_id', $prog->id)->where('estado', 'activa')->exists();
        if ($ya) {
            return back()->withInput()->withErrors('El estudiante ya tiene matrícula activa en ese programa para el periodo ' . $d['periodo'] . '.');
        }

        $m = DB::transaction(function () use ($d, $prog, $estudiante, $total, $inicial, $n, $resto, $cuota) {
            $fecha = Carbon::parse($d['fecha']);
            $m = Matricula::create([
                'estudiante_id' => $estudiante->id, 'programa_id' => $prog->id, 'programa_nombre' => $prog->nombre,
                'periodo' => $d['periodo'], 'semestre' => $d['semestre'], 'jornada' => $d['jornada'],
                'modulos_pendientes' => $d['modulos_pendientes'] ?? null, 'modalidad' => $d['modalidad'],
                'valor_semestre' => $total, 'cuota_inicial' => $d['modalidad'] === 'credito' ? $inicial : 0,
                'num_cuotas' => $n, 'valor_cuota' => $cuota, 'fecha' => $fecha, 'estado' => 'activa', 'user_id' => Auth::id(),
            ]);
            $base = 'Semestre ' . $d['semestre'] . ' · ' . $prog->nombre . ' (' . $m->periodo_texto . ')';
            if ($d['modalidad'] === 'contado') {
                if ($total > 0) Cargo::create(['estudiante_id' => $estudiante->id, 'matricula_id' => $m->id, 'tipo' => 'matricula', 'concepto' => 'Matrícula de contado · ' . $base, 'valor' => $total, 'vence' => $fecha]);
            } else {
                if ($inicial > 0) Cargo::create(['estudiante_id' => $estudiante->id, 'matricula_id' => $m->id, 'tipo' => 'cuota_inicial', 'concepto' => 'Cuota inicial · ' . $base, 'valor' => $inicial, 'vence' => $fecha]);
                $dia = max(1, min(28, (int) ($this->inst()->inst_dia_pago ?: 5)));
                for ($i = 1; $i <= $n; $i++) {
                    $valor = $i === $n ? $resto - $cuota * ($n - 1) : $cuota; // la última cuota ajusta los pesos que sobran
                    $vence = $fecha->copy()->startOfMonth()->addMonthsNoOverflow($i)->day($dia);
                    Cargo::create(['estudiante_id' => $estudiante->id, 'matricula_id' => $m->id, 'tipo' => 'cuota', 'concepto' => "Cuota $i de $n · " . $base, 'valor' => $valor, 'vence' => $vence]);
                }
            }
            $estudiante->update(['estado' => 'matriculado', 'programa_id' => $prog->id, 'jornada' => $d['jornada']]);
            $this->aplicarSaldoFavor($estudiante);
            return $m;
        });

        return redirect()->route('instituto.estudiante', $estudiante)->with('success', 'Matrícula guardada: ' . $prog->nombre . ', semestre ' . $m->semestre . ' (' . $m->periodo_texto . ').');
    }

    public function formatoMatricula(Matricula $matricula)
    {
        return view('instituto.formato-matricula', [
            'm' => $matricula->load('estudiante'), 'e' => $matricula->estudiante, 'inst' => $this->inst(),
            'programas' => Programa::where('activo', true)->orderBy('id')->get(['id', 'nombre']),
        ]);
    }

    public function matriculaAnular(Matricula $matricula)
    {
        abort_unless($this->esAdmin(), 403);
        if ($matricula->cargos()->where('pagado', '>', 0)->exists()) {
            return back()->withErrors('Esa matrícula ya tiene pagos. Anule primero los recibos.');
        }
        DB::transaction(function () use ($matricula) {
            $matricula->cargos()->update(['estado' => 'anulado']);
            $matricula->update(['estado' => 'anulada']);
            $e = $matricula->estudiante;
            if (!$e->matriculas()->where('estado', 'activa')->exists() && $e->estado === 'matriculado') $e->update(['estado' => 'aspirante']);
        });
        return back()->with('success', 'Matrícula anulada.');
    }

    // ===================== Cobros sueltos =====================
    public function cargoGuardar(Request $request, Estudiante $estudiante)
    {
        $d = $request->validate([
            'concepto' => 'required|string|max:150',
            'valor' => 'required|numeric|min:1|max:1000000000',
            'vence' => 'nullable|date',
        ], ['concepto.required' => 'Escriba el concepto (ej. carné, uniforme, certificado).']);
        DB::transaction(function () use ($d, $estudiante) {
            Cargo::create(['estudiante_id' => $estudiante->id, 'tipo' => 'otro', 'concepto' => $d['concepto'], 'valor' => $d['valor'], 'vence' => $d['vence'] ?? today()]);
            $this->aplicarSaldoFavor($estudiante);
        });
        return back()->with('success', 'Cobro agregado.');
    }

    public function cargoAnular(Cargo $cargo)
    {
        abort_unless($this->esAdmin(), 403);
        if ($cargo->pagado > 0) {
            return back()->withErrors('Ese cobro ya tiene pagos. Anule primero el recibo.');
        }
        $cargo->update(['estado' => 'anulado']);
        return back()->with('success', 'Cobro anulado.');
    }

    // ===================== Pagos =====================
    public function pagoGuardar(Request $request, Estudiante $estudiante)
    {
        $d = $request->validate([
            'valor' => 'required|numeric|min:1|max:1000000000',
            'fecha' => 'required|date|before_or_equal:today',
            'metodo' => 'required|string|max:40',
            'referencia' => 'nullable|string|max:60',
            'nota' => 'nullable|string|max:255',
        ], ['valor.required' => 'Escriba el valor recibido.', 'fecha.before_or_equal' => 'La fecha del pago no puede ser futura.']);

        $pago = DB::transaction(function () use ($d, $estudiante) {
            $pago = Pago::create($d + [
                'estudiante_id' => $estudiante->id,
                'numero' => $this->inst()->siguienteRecibo(),
                'user_id' => Auth::id(),
                'saldo_favor' => (float) $d['valor'],
            ]);
            $this->aplicarSaldoFavor($estudiante);
            return $pago->fresh();
        });
        return redirect()->route('instituto.recibo', $pago)->with('success', 'Pago registrado: recibo ' . $pago->numero . '.');
    }

    public function pagos(Request $request)
    {
        $mes = preg_match('/^\d{4}-\d{2}$/', (string) $request->mes) ? $request->mes : now()->format('Y-m');
        $pagos = Pago::with('estudiante')->whereYear('fecha', substr($mes, 0, 4))->whereMonth('fecha', substr($mes, 5, 2))
            ->latest('fecha')->latest('id')->get();
        $porMetodo = $pagos->where('anulado', false)->groupBy('metodo')->map->sum('valor')->sortDesc();
        return view('instituto.pagos', compact('pagos', 'mes', 'porMetodo'));
    }

    public function recibo(Pago $pago)
    {
        return view('instituto.recibo', ['p' => $pago->load(['estudiante.programa', 'cargos', 'user:id,name']), 'inst' => $this->inst(), 'admin' => $this->esAdmin()]);
    }

    public function pagoAnular(Pago $pago)
    {
        abort_unless($this->esAdmin(), 403);
        abort_if($pago->anulado, 422);
        DB::transaction(function () use ($pago) {
            foreach ($pago->cargos as $c) {
                $c->pagado = max(0, round($c->pagado - (float) $c->pivot->valor, 2));
                $c->estado = $c->pagado <= 0 ? 'pendiente' : 'parcial';
                $c->save();
            }
            $pago->cargos()->detach();
            $pago->update(['anulado' => true, 'saldo_favor' => 0]);
        });
        return back()->with('success', 'Recibo ' . $pago->numero . ' anulado.');
    }

    // ===================== Cartera =====================
    public function cartera(Request $request)
    {
        $soloVencido = $request->boolean('vencido', true);
        $filas = Cargo::with('estudiante.programa')->whereIn('estado', ['pendiente', 'parcial'])
            ->when($soloVencido, fn ($q) => $q->whereDate('vence', '<', today()))
            ->groupBy('estudiante_id')
            ->selectRaw('estudiante_id, SUM(valor - pagado) deuda, MIN(vence) desde, COUNT(*) n')
            ->orderByDesc('deuda')->get();
        return view('instituto.cartera', ['filas' => $filas, 'inst' => $this->inst(), 'soloVencido' => $soloVencido]);
    }

    public function config(Request $request)
    {
        abort_unless($this->esAdmin(), 403);
        $d = $request->validate(['inst_dia_pago' => 'required|integer|min:1|max:28']);
        $this->inst()->forceFill($d)->save();
        return back()->with('success', 'Las cuotas nuevas vencerán el día ' . $d['inst_dia_pago'] . ' de cada mes.');
    }

    // Usa el dinero de los pagos para cubrir los cobros pendientes, del que vence primero al último
    private function aplicarSaldoFavor(Estudiante $e): void
    {
        $pagos = Pago::where('estudiante_id', $e->id)->where('anulado', false)->where('saldo_favor', '>', 0)->orderBy('fecha')->orderBy('id')->lockForUpdate()->get();
        if ($pagos->isEmpty()) return;
        $cargos = Cargo::where('estudiante_id', $e->id)->whereIn('estado', ['pendiente', 'parcial'])->orderBy('vence')->orderBy('id')->lockForUpdate()->get();
        foreach ($pagos as $p) {
            foreach ($cargos as $c) {
                if ($p->saldo_favor <= 0) break;
                $falta = round($c->valor - $c->pagado, 2);
                if ($falta <= 0) continue;
                $aplica = min($falta, $p->saldo_favor);
                $c->pagado = round($c->pagado + $aplica, 2);
                $c->estado = $c->pagado >= $c->valor ? 'pagado' : 'parcial';
                $c->save();
                $p->saldo_favor = round($p->saldo_favor - $aplica, 2);
                DB::table('inst_pago_cargo')->insert(['pago_id' => $p->id, 'cargo_id' => $c->id, 'valor' => $aplica]);
            }
            $p->save();
        }
    }
}
