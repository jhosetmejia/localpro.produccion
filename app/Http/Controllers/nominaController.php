<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\NominaElectronica;
use App\Models\NominaLiquidacion;
use App\Models\NominaPeriodo;
use App\Support\Nomina\Electronica;
use App\Support\Nomina\Liquidador;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Nómina del negocio: empleados, periodos de pago, desprendibles y (si el negocio lo elige) nómina electrónica DIAN
class nominaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('gestionar-nomina') || $u->can('editar-negocio')), 403);
            // Mientras el negocio no elija cómo maneja la nómina, solo puede ver la pregunta
            $modo = TiendaActual::tienda()?->nomina_modo;
            if (!in_array($modo, ['interna', 'electronica'], true) && !$request->routeIs('nomina.index', 'nomina.modo')) {
                return redirect()->route('nomina.index');
            }
            if ($modo !== 'electronica' && $request->routeIs('nomina.electronica*')) {
                return redirect()->route('nomina.index')->with('info', 'Active la nómina electrónica DIAN en la configuración de nómina para usar esta sección.');
            }
            return $next($request);
        });
    }

    private function tienda()
    {
        return TiendaActual::tienda();
    }

    // ---------- Inicio, pregunta inicial y configuración ----------

    public function index()
    {
        $t = $this->tienda();
        if (!in_array($t->nomina_modo, ['interna', 'electronica'], true)) {
            return view('nomina.pregunta', ['tienda' => $t]);
        }
        $periodos = NominaPeriodo::latest('fecha_fin')->latest('id')->paginate(12);
        $empleados = Empleado::where('activo', true)->count();
        $ultimo = NominaPeriodo::latest('fecha_fin')->first();
        return view('nomina.index', [
            'tienda' => $t, 'periodos' => $periodos, 'empleados' => $empleados,
            'sugerido' => $this->siguientePeriodo($ultimo, $t->nomina_periodicidad ?: 'mensual'),
            'anio' => Liquidador::anio((int) date('Y')),
        ]);
    }

    public function modo(Request $request)
    {
        $d = $request->validate(['nomina_modo' => 'required|in:no,interna,electronica']);
        $this->tienda()->forceFill($d)->save();
        return redirect()->route('nomina.index')->with('success', match ($d['nomina_modo']) {
            'no' => 'Listo. Si más adelante tiene empleados, vuelva a Nómina y actívela.',
            'interna' => 'Nómina activada sin envío a la DIAN. Registre sus empleados para empezar.',
            default => 'Nómina electrónica activada. Registre sus empleados; cada mes podrá enviar los documentos a la DIAN.',
        });
    }

    public function config(Request $request)
    {
        $d = $request->validate([
            'nomina_modo' => 'required|in:no,interna,electronica',
            'nomina_periodicidad' => 'required|in:mensual,quincenal',
            'nomina_prefijo' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
        ]);
        $d['nomina_exonerada'] = $request->boolean('nomina_exonerada');
        $d['nomina_prefijo'] = strtoupper($d['nomina_prefijo'] ?? '') ?: 'NE';
        $this->tienda()->forceFill($d)->save();
        return back()->with('success', 'Configuración de nómina guardada.');
    }

    // ---------- Empleados ----------

    public function empleados(Request $request)
    {
        $ver = $request->get('ver', 'activos');
        $empleados = Empleado::when($ver === 'activos', fn ($q) => $q->where('activo', true))
            ->when($ver === 'retirados', fn ($q) => $q->where('activo', false))
            ->orderBy('nombres')->get();
        return view('nomina.empleados', ['empleados' => $empleados, 'ver' => $ver, 'tienda' => $this->tienda(), 'anio' => Liquidador::anio((int) date('Y'))]);
    }

    public function empleadoForm(?Empleado $empleado = null)
    {
        $empleado = $empleado?->exists ? $empleado : null;
        return view('nomina.empleado', ['e' => $empleado ?? new Empleado(['tipo' => 'dependiente', 'tipo_contrato' => 'indefinido', 'riesgo_arl' => 1, 'tipo_documento' => '13', 'forma_pago' => 'transferencia', 'seguridad_social' => true, 'prestaciones' => true, 'enviar_dian' => true, 'activo' => true]), 'tienda' => $this->tienda(), 'anio' => Liquidador::anio((int) date('Y'))]);
    }

    public function empleadoGuardar(Request $request, ?Empleado $empleado = null)
    {
        $empleado = $empleado?->exists ? $empleado : null;
        $d = $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(Empleado::DOCUMENTOS))],
            'documento' => ['required', 'string', 'max:20', Rule::unique('empleados', 'documento')->where('tienda_id', TiendaActual::id())->ignore($empleado?->id)],
            'nombres' => 'required|string|max:60',
            'apellidos' => 'required|string|max:60',
            'email' => 'nullable|email|max:120',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:120',
            'municipio_dane' => 'nullable|digits:5',
            'cargo' => 'nullable|string|max:60',
            'tipo' => ['required', Rule::in(array_keys(Liquidador::TIPOS_TRABAJADOR))],
            'tipo_contrato' => ['required', Rule::in(array_keys(Empleado::CONTRATOS))],
            'salario' => 'required|numeric|min:0|max:999999999',
            'riesgo_arl' => 'required|integer|between:1,5',
            'aux_modo' => 'required|in:auto,si,no',
            'fecha_ingreso' => 'required|date',
            'fecha_retiro' => 'nullable|date|after_or_equal:fecha_ingreso',
            'eps' => 'nullable|string|max:60',
            'afp' => 'nullable|string|max:60',
            'caja' => 'nullable|string|max:60',
            'forma_pago' => 'required|in:transferencia,efectivo,cheque',
            'banco' => 'nullable|string|max:40',
            'tipo_cuenta' => 'nullable|in:ahorros,corriente',
            'numero_cuenta' => 'nullable|string|max:30',
        ], ['municipio_dane.digits' => 'El código DANE del municipio tiene 5 números (Pasto: 52001).']);
        foreach (['salario_integral', 'personalizado', 'seguridad_social', 'prestaciones', 'enviar_dian'] as $b) {
            $d[$b] = $request->boolean($b);
        }
        // Las opciones libres solo aplican en liquidación personalizada; la legal siempre lleva seguridad social y prestaciones
        if (!$d['personalizado']) {
            $d['seguridad_social'] = true;
            $d['prestaciones'] = true;
        }
        $d['aux_transporte'] = ['auto' => null, 'si' => true, 'no' => false][$d['aux_modo']];
        unset($d['aux_modo']);
        $d['activo'] = empty($d['fecha_retiro']) || Carbon::parse($d['fecha_retiro'])->gte(today());

        if ($empleado) {
            $empleado->update($d);
            $msg = 'Empleado actualizado.';
        } else {
            $empleado = Empleado::create($d);
            $msg = 'Empleado registrado.';
        }
        return redirect()->route('nomina.empleados')->with('success', $msg . ($empleado->personalizado ? ' Su liquidación es personalizada.' : ''));
    }

    // ---------- Periodos de pago ----------

    // Siguiente quincena o mes después del último periodo liquidado
    private function siguientePeriodo(?NominaPeriodo $ultimo, string $per): array
    {
        $ini = $ultimo ? $ultimo->fecha_fin->copy()->addDay() : today()->startOfMonth();
        if ($per === 'quincenal') {
            $fin = $ini->day <= 15 ? $ini->copy()->day(15) : $ini->copy()->endOfMonth();
            if ($ini->day > 1 && $ini->day <= 15) $fin = $ini->copy()->day(15);
        } else {
            $fin = $ini->copy()->endOfMonth();
        }
        return ['inicio' => $ini->format('Y-m-d'), 'fin' => $fin->format('Y-m-d'), 'pago' => $fin->format('Y-m-d')];
    }

    public function periodoCrear(Request $request)
    {
        $d = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'fecha_pago' => 'required|date',
            'nombre' => 'nullable|string|max:60',
        ]);
        $ini = Carbon::parse($d['fecha_inicio']);
        $fin = Carbon::parse($d['fecha_fin']);
        if ($ini->diffInDays($fin) > 31) {
            return back()->withErrors(['fecha_fin' => 'Un periodo de nómina no puede pasar de un mes.'])->withInput();
        }
        $cruce = NominaPeriodo::where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $ini)->first();
        if ($cruce) {
            return back()->withErrors(['fecha_inicio' => 'Esas fechas se cruzan con el periodo "' . $cruce->nombre . '".'])->withInput();
        }
        $empleados = Empleado::where('fecha_ingreso', '<=', $fin)
            ->where(fn ($q) => $q->whereNull('fecha_retiro')->orWhere('fecha_retiro', '>=', $ini))->get();
        if ($empleados->isEmpty()) {
            return back()->withErrors(['fecha_inicio' => 'No hay empleados vinculados en esas fechas. Registre sus empleados primero.'])->withInput();
        }
        $quincenal = Liquidador::dias360($ini->format('Y-m-d'), $fin->format('Y-m-d')) <= 16;
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $nombre = $d['nombre'] ?: (($quincenal ? ($ini->day <= 15 ? '1.ª quincena de ' : '2.ª quincena de ') : 'Nómina de ') . $meses[$fin->month] . ' ' . $fin->year);

        $periodo = DB::transaction(function () use ($d, $nombre, $quincenal, $empleados) {
            $p = NominaPeriodo::create([
                'nombre' => $nombre, 'periodicidad' => $quincenal ? 'quincenal' : 'mensual',
                'fecha_inicio' => $d['fecha_inicio'], 'fecha_fin' => $d['fecha_fin'], 'fecha_pago' => $d['fecha_pago'],
                'estado' => 'borrador', 'user_id' => auth()->id(),
            ]);
            foreach ($empleados as $e) {
                NominaLiquidacion::create(['nomina_periodo_id' => $p->id, 'empleado_id' => $e->id, 'salario' => $e->salario, 'novedades' => []]);
            }
            $p->recalcular();
            return $p;
        });
        return redirect()->route('nomina.periodo', $periodo)->with('success', 'Periodo creado con ' . $empleados->count() . ' empleado(s). Registre las novedades (horas extra, incapacidades, descuentos…) y liquide.');
    }

    public function periodo(NominaPeriodo $periodo)
    {
        $periodo->load('liquidaciones');
        return view('nomina.periodo', ['p' => $periodo, 'tienda' => $this->tienda()]);
    }

    public function periodoGuardar(Request $request, NominaPeriodo $periodo)
    {
        abort_unless($periodo->editable, 422, 'El periodo ya fue liquidado. Reábralo para cambiarlo.');
        $campos = array_merge(...array_map('array_keys', array_values(Liquidador::NOVEDADES)));
        $request->validate([
            'liq' => 'required|array',
            'liq.*.salario' => 'nullable|numeric|min:0|max:999999999',
            'liq.*.*' => 'nullable',
        ]);
        DB::transaction(function () use ($request, $periodo, $campos) {
            foreach ($periodo->liquidaciones()->get() as $l) {
                $in = $request->input('liq.' . $l->id);
                if (!is_array($in)) continue;
                $nov = [];
                foreach ($campos as $c) {
                    $val = (float) str_replace([',', ' '], ['.', ''], (string) ($in[$c] ?? 0));
                    if ($val > 0) $nov[$c] = min($val, 999999999);
                }
                if (!empty($in['liquidar_prima'])) $nov['liquidar_prima'] = 1;
                $data = ['novedades' => $nov];
                // El salario del periodo solo se cambia a mano en liquidación personalizada
                if ($l->empleado->personalizado && isset($in['salario']) && $in['salario'] !== '') $data['salario'] = (float) $in['salario'];
                $l->update($data);
            }
            $periodo->recalcular();
        });
        return back()->with('success', 'Novedades guardadas y nómina recalculada.');
    }

    public function periodoEstado(Request $request, NominaPeriodo $periodo)
    {
        $nuevo = $request->validate(['estado' => 'required|in:borrador,liquidada,pagada'])['estado'];
        if ($nuevo === 'borrador') {
            $mes = $periodo->fecha_fin->format('Y-m');
            $emitidos = NominaElectronica::where('mes', $mes)->whereIn('estado', ['aceptado', 'enviado', 'simulado'])->exists();
            $periodo->update(['estado' => 'borrador']);
            return back()->with('success', 'Periodo reabierto.' . ($emitidos ? ' Ojo: ya hay nómina electrónica de ' . $mes . '. Si cambia valores, envíe una nota de ajuste "reemplazar" en Nómina electrónica.' : ''));
        }
        if ($periodo->estado === 'borrador') $periodo->recalcular();
        $periodo->update(['estado' => $nuevo]);
        return back()->with('success', $nuevo === 'pagada' ? 'Periodo marcado como pagado.' : 'Periodo liquidado. Ya puede imprimir los desprendibles.');
    }

    public function periodoBorrar(NominaPeriodo $periodo)
    {
        abort_unless($periodo->editable, 422);
        $periodo->delete();
        return redirect()->route('nomina.index')->with('success', 'Periodo eliminado.');
    }

    public function desprendible(NominaPeriodo $periodo, ?NominaLiquidacion $liquidacion = null)
    {
        $liquidacion = $liquidacion?->exists ? $liquidacion : null;
        abort_if($liquidacion && (int) $liquidacion->nomina_periodo_id !== (int) $periodo->id, 404);
        $liqs = $liquidacion ? collect([$liquidacion->load('empleado')]) : $periodo->liquidaciones()->get();
        return view('nomina.desprendible', ['p' => $periodo, 'liqs' => $liqs, 'tienda' => $this->tienda()]);
    }

    // ---------- Nómina electrónica DIAN ----------

    public function electronica(Request $request)
    {
        $mes = preg_match('/^\d{4}-\d{2}$/', (string) $request->get('mes')) ? $request->get('mes') : now()->subMonthNoOverflow()->format('Y-m');
        $liqs = Electronica::liquidacionesDelMes($mes)->groupBy('empleado_id');
        $docs = NominaElectronica::with('empleado')->where('mes', $mes)->latest('id')->get();
        // Plazo: 10 primeros días hábiles del mes siguiente (aproximado sin festivos)
        $limite = Carbon::parse($mes . '-01')->addMonthNoOverflow()->startOfMonth();
        for ($h = 0; $h < 10; $limite->addDay()) {
            if (!$limite->isWeekend()) $h++;
            if ($h === 10) break;
        }
        $pendientes = $liqs->filter(fn ($g, $id) => $g->first()->empleado->enviar_dian && !Electronica::vigente($id, $mes));
        return view('nomina.electronica', [
            'mes' => $mes, 'liqs' => $liqs, 'docs' => $docs, 'limite' => $limite, 'pendientes' => $pendientes,
            'proveedor' => \App\Support\Dian\Facturador::nombreProveedor(), 'tienda' => $this->tienda(),
            'abiertos' => NominaPeriodo::where('estado', 'borrador')->where('fecha_fin', 'like', $mes . '-%')->count(),
        ]);
    }

    public function electronicaGenerar(Request $request)
    {
        $mes = $request->validate(['mes' => ['required', 'regex:/^\d{4}-\d{2}$/']])['mes'];
        [$n, $omit] = Electronica::generarMes($mes);
        $msg = $n ? "Se generaron y enviaron $n documento(s) de nómina electrónica." : 'No había documentos nuevos por enviar en ' . $mes . '.';
        if ($omit) $msg .= ' No se reportan: ' . implode(', ', $omit) . '.';
        return back()->with('success', $msg);
    }

    public function electronicaEnviar(NominaElectronica $documento)
    {
        abort_if($documento->estado === 'aceptado', 422);
        $documento->forceFill(['estado' => 'pendiente'])->save();
        $documento = Electronica::enviar($documento);
        $msg = $documento->numero . ': ' . $documento->estado_info[0] . ($documento->ultimo_error ? ' — ' . $documento->ultimo_error : '');
        return in_array($documento->estado, ['error', 'rechazado'], true) ? back()->withErrors(['doc' => $msg]) : back()->with('success', $msg);
    }

    public function electronicaAjuste(Request $request, NominaElectronica $documento)
    {
        $tipo = (int) $request->validate(['tipo_nota' => 'required|in:1,2'])['tipo_nota'];
        abort_unless(in_array($documento->estado, ['aceptado', 'simulado', 'enviado'], true), 422, 'Solo se ajustan documentos ya transmitidos.');
        $nota = Electronica::ajuste($documento, $tipo);
        return back()->with('success', 'Nota de ajuste ' . $nota->numero . ': ' . $nota->estado_info[0] . ($nota->ultimo_error ? ' — ' . $nota->ultimo_error : ''));
    }

    public function electronicaVer(NominaElectronica $documento)
    {
        return view('nomina.electronica-doc', ['doc' => $documento->load('empleado', 'referencia')]);
    }
}
