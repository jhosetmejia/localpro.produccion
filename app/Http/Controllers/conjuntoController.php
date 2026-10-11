<?php

namespace App\Http\Controllers;

use App\Models\Conjunto\Cargo;
use App\Models\Conjunto\Comunicado;
use App\Models\Conjunto\Gasto;
use App\Models\Conjunto\PagoConjunto;
use App\Models\Conjunto\Pqrs;
use App\Models\Conjunto\Reserva;
use App\Models\Conjunto\Unidad;
use App\Support\TiendaActual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Administración de conjuntos residenciales, torres y edificios:
 * unidades, cobro de administración, pagos y recibos, cartera, gastos, comunicados, PQRS y reservas.
 */
class conjuntoController extends Controller
{
    private function conj()
    {
        return TiendaActual::tienda();
    }

    // ===================== Inicio =====================
    public function inicio()
    {
        $mesIni = now()->startOfMonth();
        $pendientes = fn () => Cargo::whereIn('estado', ['pendiente', 'parcial']);
        $recaudoMes = (float) PagoConjunto::where('anulado', false)->where('fecha', '>=', $mesIni)->sum('valor');
        $gastosMes = (float) Gasto::where('fecha', '>=', $mesIni)->sum('valor');

        $morosos = Cargo::with('unidad')->whereIn('estado', ['pendiente', 'parcial'])->whereDate('vence', '<', today())
            ->selectRaw('unidad_id, SUM(valor - pagado) as deuda, MIN(vence) as desde')->groupBy('unidad_id')
            ->orderByDesc('deuda')->take(8)->get();

        return view('conjunto.inicio', [
            'conj' => $this->conj(),
            'kpi' => [
                'unidades' => Unidad::where('activa', true)->count(),
                'recaudoMes' => $recaudoMes,
                'gastosMes' => $gastosMes,
                'cartera' => (float) $pendientes()->selectRaw('COALESCE(SUM(valor - pagado),0) s')->value('s'),
                'enMora' => Cargo::whereIn('estado', ['pendiente', 'parcial'])->whereDate('vence', '<', today())->distinct()->count('unidad_id'),
                'facturadoMes' => (float) Cargo::where('periodo', now()->format('Y-m'))->where('estado', '<>', 'anulado')->sum('valor'),
                'cobroMesGenerado' => Cargo::where('periodo', now()->format('Y-m'))->where('tipo', 'administracion')->exists(),
                'pqrsAbiertas' => Pqrs::where('estado', '<>', 'cerrada')->count(),
            ],
            'morosos' => $morosos,
            'ultimosPagos' => PagoConjunto::with('unidad')->where('anulado', false)->latest('fecha')->latest('id')->take(6)->get(),
            'reservas' => Reserva::with('unidad')->where('estado', 'confirmada')->whereDate('fecha', '>=', today())->orderBy('fecha')->orderBy('hora_inicio')->take(5)->get(),
            'pqrs' => Pqrs::with('unidad')->where('estado', '<>', 'cerrada')->latest()->take(5)->get(),
        ]);
    }

    // ===================== Unidades =====================
    public function unidades(Request $request)
    {
        $q = trim((string) $request->q);
        $unidades = Unidad::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('numero', 'like', "%$q%")->orWhere('torre', 'like', "%$q%")->orWhere('propietario', 'like', "%$q%")->orWhere('residente', 'like', "%$q%")))
            ->orderBy('torre')->orderByRaw('LENGTH(numero), numero')->get();
        $saldos = Cargo::whereIn('estado', ['pendiente', 'parcial'])->groupBy('unidad_id')
            ->selectRaw('unidad_id, SUM(valor - pagado) s')->pluck('s', 'unidad_id');
        return view('conjunto.unidades', compact('unidades', 'saldos', 'q'));
    }

    public function unidadGuardar(Request $request, ?Unidad $unidad = null)
    {
        $d = $request->validate([
            'torre' => 'nullable|string|max:30',
            'numero' => 'required|string|max:20',
            'tipo' => ['required', Rule::in(array_keys(Unidad::TIPOS))],
            'coeficiente' => 'nullable|numeric|min:0|max:100',
            'cuota' => 'required|numeric|min:0|max:100000000',
            'propietario' => 'nullable|string|max:80',
            'propietario_tel' => 'nullable|string|max:20',
            'propietario_email' => 'nullable|email|max:120',
            'propietario_doc' => 'nullable|string|max:20',
            'residente' => 'nullable|string|max:80',
            'residente_tel' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:255',
        ], ['numero.required' => 'Escriba el número de la unidad.']);
        $d['torre'] = $d['torre'] ? trim($d['torre']) : null;
        $existe = Unidad::where('numero', $d['numero'])->where(fn ($w) => $d['torre'] ? $w->where('torre', $d['torre']) : $w->whereNull('torre'))
            ->when($unidad?->id, fn ($w, $id) => $w->where('id', '<>', $id))->exists();
        if ($existe) {
            return back()->withInput()->withErrors('Ya existe esa unidad' . ($d['torre'] ? ' en ' . $d['torre'] : '') . '.');
        }
        $d['activa'] = $request->boolean('activa', true);
        if ($unidad?->id) {
            $unidad->update($d);
            return redirect()->route('conjunto.unidad', $unidad)->with('success', 'Unidad actualizada.');
        }
        $u = Unidad::create($d);
        return redirect()->route('conjunto.unidad', $u)->with('success', 'Unidad ' . $u->nombre . ' creada.');
    }

    // Crear muchas unidades de una vez: torres × pisos × apartamentos por piso
    public function unidadesLote(Request $request)
    {
        $d = $request->validate([
            'torres' => 'nullable|string|max:255',
            'piso_desde' => 'required|integer|min:0|max:200',
            'piso_hasta' => 'required|integer|min:0|max:200|gte:piso_desde',
            'por_piso' => 'required|integer|min:1|max:50',
            'tipo' => ['required', Rule::in(array_keys(Unidad::TIPOS))],
            'cuota' => 'required|numeric|min:0|max:100000000',
        ]);
        $torres = array_values(array_filter(array_map('trim', explode(',', (string) $d['torres'])))) ?: [null];
        $total = count($torres) * ($d['piso_hasta'] - $d['piso_desde'] + 1) * $d['por_piso'];
        if ($total > 2000) {
            return back()->withErrors('Son demasiadas unidades de una vez (máximo 2.000).');
        }
        $creadas = 0;
        DB::transaction(function () use ($torres, $d, &$creadas) {
            foreach ($torres as $torre) {
                for ($p = $d['piso_desde']; $p <= $d['piso_hasta']; $p++) {
                    for ($n = 1; $n <= $d['por_piso']; $n++) {
                        $num = $p > 0 ? (string) ($p * 100 + $n) : (string) $n;
                        $q = Unidad::where('numero', $num);
                        $torre ? $q->where('torre', $torre) : $q->whereNull('torre');
                        if ($q->exists()) continue;
                        Unidad::create(['torre' => $torre, 'numero' => $num, 'tipo' => $d['tipo'], 'cuota' => $d['cuota'], 'activa' => true]);
                        $creadas++;
                    }
                }
            }
        });
        return redirect()->route('conjunto.unidades')->with('success', $creadas . ' unidades creadas.');
    }

    public function unidad(Unidad $unidad)
    {
        $cargos = $unidad->cargos()->where('estado', '<>', 'anulado')->orderByDesc('periodo')->orderByDesc('id')->take(36)->get();
        return view('conjunto.unidad', [
            'u' => $unidad,
            'cargos' => $cargos,
            'pagos' => $unidad->pagos()->latest('fecha')->latest('id')->take(24)->get(),
            'saldo' => $unidad->saldo(),
            'favor' => (float) $unidad->pagos()->where('anulado', false)->sum('saldo_favor'),
            'metodos' => \App\Models\MetodoPago::where('activo', true)->orderBy('orden')->pluck('nombre'),
            'conj' => $this->conj(),
        ]);
    }

    // ===================== Cobros =====================
    public function cobros(Request $request)
    {
        $periodo = $request->periodo ?: now()->format('Y-m');
        $cargos = Cargo::with('unidad')->where('periodo', $periodo)->where('estado', '<>', 'anulado')->orderBy('tipo')->orderBy('unidad_id')->get();
        $periodos = Cargo::select('periodo')->distinct()->orderByDesc('periodo')->limit(24)->pluck('periodo');
        return view('conjunto.cobros', [
            'periodo' => $periodo, 'cargos' => $cargos, 'periodos' => $periodos, 'conj' => $this->conj(),
            'unidades' => Unidad::where('activa', true)->orderBy('torre')->orderByRaw('LENGTH(numero), numero')->get(),
        ]);
    }

    // Genera la cuota de administración del mes para todas las unidades activas
    public function generarMes(Request $request)
    {
        $d = $request->validate([
            'periodo' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'vence' => 'nullable|date',
        ]);
        $mes = Carbon::createFromFormat('Y-m-d', $d['periodo'] . '-01');
        $vence = $d['vence'] ? Carbon::parse($d['vence']) : $mes->copy()->day(min(28, max(1, (int) $this->conj()->conj_dia_vence)));
        $concepto = 'Administración ' . ucfirst($mes->locale('es')->isoFormat('MMMM YYYY'));
        $n = 0;
        DB::transaction(function () use ($d, $vence, $concepto, &$n) {
            $ya = Cargo::where('periodo', $d['periodo'])->where('tipo', 'administracion')->where('estado', '<>', 'anulado')->pluck('unidad_id')->all();
            foreach (Unidad::where('activa', true)->where('cuota', '>', 0)->whereNotIn('id', $ya)->get() as $u) {
                Cargo::create(['unidad_id' => $u->id, 'periodo' => $d['periodo'], 'tipo' => 'administracion', 'concepto' => $concepto, 'valor' => $u->cuota, 'vence' => $vence, 'estado' => 'pendiente']);
                $this->aplicarSaldoFavor($u);
                $n++;
            }
        });
        return redirect()->route('conjunto.cobros', ['periodo' => $d['periodo']])
            ->with('success', $n ? "Cobro de $concepto generado para $n unidades." : 'Todas las unidades ya tenían el cobro de ese mes.');
    }

    // Cuota extraordinaria, multa o cobro especial (a todas las unidades o a una)
    public function cargoEspecial(Request $request)
    {
        $d = $request->validate([
            'tipo' => ['required', Rule::in(['extraordinaria', 'multa', 'interes', 'otro'])],
            'concepto' => 'required|string|max:120',
            'modo' => ['required', Rule::in(['igual', 'coeficiente', 'unidad'])],
            'valor' => 'required|numeric|min:1|max:1000000000',
            'unidad_id' => 'nullable|integer',
            'vence' => 'nullable|date',
        ], ['concepto.required' => 'Escriba el concepto del cobro.']);
        $periodo = now()->format('Y-m');
        $vence = $d['vence'] ?? today()->addDays(15)->toDateString();
        $n = 0;
        DB::transaction(function () use ($d, $periodo, $vence, &$n) {
            if ($d['modo'] === 'unidad') {
                $u = Unidad::findOrFail($d['unidad_id']);
                Cargo::create(['unidad_id' => $u->id, 'periodo' => $periodo, 'tipo' => $d['tipo'], 'concepto' => $d['concepto'], 'valor' => $d['valor'], 'vence' => $vence]);
                $this->aplicarSaldoFavor($u);
                $n = 1;
                return;
            }
            $unidades = Unidad::where('activa', true)->get();
            $sumaCoef = (float) $unidades->sum('coeficiente');
            foreach ($unidades as $u) {
                $valor = $d['modo'] === 'coeficiente'
                    ? ($sumaCoef > 0 ? round($d['valor'] * ((float) $u->coeficiente) / $sumaCoef, 0) : 0)
                    : (float) $d['valor'];
                if ($valor <= 0) continue;
                Cargo::create(['unidad_id' => $u->id, 'periodo' => $periodo, 'tipo' => $d['tipo'], 'concepto' => $d['concepto'], 'valor' => $valor, 'vence' => $vence]);
                $this->aplicarSaldoFavor($u);
                $n++;
            }
        });
        if ($d['modo'] === 'coeficiente' && $n === 0) {
            return back()->withErrors('Para repartir por coeficiente, primero ponga el coeficiente de cada unidad.');
        }
        return back()->with('success', "Cobro \"{$d['concepto']}\" creado para $n unidad(es).");
    }

    // Intereses de mora del mes sobre lo vencido (según el % configurado)
    public function generarIntereses()
    {
        $pct = (float) $this->conj()->conj_interes_mora;
        if ($pct <= 0) {
            return back()->withErrors('Primero configure el % de interés de mora en Configuración.');
        }
        $periodo = now()->format('Y-m');
        $concepto = 'Intereses de mora ' . ucfirst(now()->locale('es')->isoFormat('MMMM YYYY'));
        $n = 0;
        DB::transaction(function () use ($pct, $periodo, $concepto, &$n) {
            $ya = Cargo::where('periodo', $periodo)->where('tipo', 'interes')->where('estado', '<>', 'anulado')->pluck('unidad_id')->all();
            $deudas = Cargo::whereIn('estado', ['pendiente', 'parcial'])->where('tipo', '<>', 'interes')->whereDate('vence', '<', today())
                ->whereNotIn('unidad_id', $ya)->groupBy('unidad_id')->selectRaw('unidad_id, SUM(valor - pagado) d')->pluck('d', 'unidad_id');
            foreach ($deudas as $uid => $deuda) {
                $valor = round($deuda * $pct / 100, 0);
                if ($valor <= 0) continue;
                Cargo::create(['unidad_id' => $uid, 'periodo' => $periodo, 'tipo' => 'interes', 'concepto' => $concepto . ' (' . $pct . '%)', 'valor' => $valor, 'vence' => today()->endOfMonth()]);
                $n++;
            }
        });
        return back()->with('success', $n ? "Intereses generados para $n unidad(es) en mora." : 'No hay unidades en mora sin intereses este mes.');
    }

    public function cargoAnular(Cargo $cargo)
    {
        if ($cargo->pagado > 0) {
            return back()->withErrors('Ese cobro ya tiene pagos. Anule primero el recibo.');
        }
        $cargo->update(['estado' => 'anulado']);
        return back()->with('success', 'Cobro anulado.');
    }

    // ===================== Pagos =====================
    public function pagoGuardar(Request $request, Unidad $unidad)
    {
        $d = $request->validate([
            'valor' => 'required|numeric|min:1|max:1000000000',
            'fecha' => 'required|date|before_or_equal:today',
            'metodo' => 'required|string|max:40',
            'referencia' => 'nullable|string|max:60',
            'nota' => 'nullable|string|max:255',
        ], ['valor.required' => 'Escriba el valor recibido.']);

        $pago = DB::transaction(function () use ($d, $unidad) {
            $pago = PagoConjunto::create($d + [
                'unidad_id' => $unidad->id,
                'numero' => $this->conj()->siguienteRecibo(),
                'user_id' => Auth::id(),
                'saldo_favor' => (float) $d['valor'],
            ]);
            $this->aplicarSaldoFavor($unidad);
            return $pago->fresh();
        });

        return redirect()->route('conjunto.recibo', $pago)->with('success', 'Pago registrado: recibo ' . $pago->numero . '.');
    }

    public function pagos(Request $request)
    {
        $mes = $request->mes ?: now()->format('Y-m');
        $pagos = PagoConjunto::with('unidad')->whereYear('fecha', substr($mes, 0, 4))->whereMonth('fecha', substr($mes, 5, 2))
            ->latest('fecha')->latest('id')->get();
        return view('conjunto.pagos', ['pagos' => $pagos, 'mes' => $mes]);
    }

    public function recibo(PagoConjunto $pago)
    {
        return view('conjunto.recibo', ['p' => $pago->load(['unidad', 'cargos', 'user:id,name']), 'conj' => \App\Models\Negocio::datos()]);
    }

    public function pagoAnular(PagoConjunto $pago)
    {
        abort_if($pago->anulado, 422);
        DB::transaction(function () use ($pago) {
            foreach ($pago->cargos as $c) {
                $c->pagado = max(0, $c->pagado - (float) $c->pivot->valor);
                $c->estado = $c->pagado <= 0 ? 'pendiente' : 'parcial';
                $c->save();
            }
            $pago->cargos()->detach();
            $pago->update(['anulado' => true, 'saldo_favor' => 0]);
        });
        return back()->with('success', 'Recibo ' . $pago->numero . ' anulado.');
    }

    // Usa el dinero sobrante de los pagos para cubrir los cobros pendientes (del más antiguo al más nuevo)
    private function aplicarSaldoFavor(Unidad $u): void
    {
        $pagos = PagoConjunto::where('unidad_id', $u->id)->where('anulado', false)->where('saldo_favor', '>', 0)->orderBy('fecha')->orderBy('id')->lockForUpdate()->get();
        if ($pagos->isEmpty()) return;
        $cargos = Cargo::where('unidad_id', $u->id)->whereIn('estado', ['pendiente', 'parcial'])->orderBy('vence')->orderBy('id')->lockForUpdate()->get();
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
                DB::table('conjunto_pago_cargo')->insert(['pago_id' => $p->id, 'cargo_id' => $c->id, 'valor' => $aplica]);
            }
            $p->save();
        }
    }

    // ===================== Cartera =====================
    public function cartera()
    {
        $filas = Cargo::with('unidad')->whereIn('estado', ['pendiente', 'parcial'])
            ->selectRaw('unidad_id, SUM(valor - pagado) as deuda, MIN(vence) as desde, COUNT(*) as n,
                SUM(CASE WHEN vence < CURDATE() THEN valor - pagado ELSE 0 END) as vencida')
            ->groupBy('unidad_id')->orderByDesc('vencida')->orderByDesc('deuda')->get();
        return view('conjunto.cartera', ['filas' => $filas, 'conj' => $this->conj()]);
    }

    // ===================== Gastos =====================
    public function gastos(Request $request)
    {
        $mes = $request->mes ?: now()->format('Y-m');
        $gastos = Gasto::whereYear('fecha', substr($mes, 0, 4))->whereMonth('fecha', substr($mes, 5, 2))->latest('fecha')->get();
        $recaudo = (float) PagoConjunto::where('anulado', false)->whereYear('fecha', substr($mes, 0, 4))->whereMonth('fecha', substr($mes, 5, 2))->sum('valor');
        return view('conjunto.gastos', ['gastos' => $gastos, 'mes' => $mes, 'recaudo' => $recaudo, 'porCategoria' => $gastos->groupBy('categoria')->map->sum('valor')->sortDesc()]);
    }

    public function gastoGuardar(Request $request)
    {
        $d = $request->validate([
            'fecha' => 'required|date',
            'categoria' => 'required|string|max:40',
            'concepto' => 'required|string|max:160',
            'valor' => 'required|numeric|min:1|max:1000000000',
            'proveedor' => 'nullable|string|max:80',
        ]);
        Gasto::create($d + ['user_id' => Auth::id()]);
        return back()->with('success', 'Gasto registrado.');
    }

    public function gastoBorrar(Gasto $gasto)
    {
        $gasto->delete();
        return back()->with('success', 'Gasto eliminado.');
    }

    // ===================== Comunicados =====================
    public function comunicados()
    {
        return view('conjunto.comunicados', ['lista' => Comunicado::latest()->take(50)->get(), 'conj' => $this->conj(),
            'contactos' => Unidad::where('activa', true)->get()->map(fn ($u) => $u->contacto)->filter()->unique()->count()]);
    }

    public function comunicadoGuardar(Request $request)
    {
        $d = $request->validate(['titulo' => 'required|string|max:120', 'mensaje' => 'required|string|max:3000']);
        Comunicado::create($d + ['user_id' => Auth::id()]);
        return back()->with('success', 'Comunicado publicado. Compártalo por WhatsApp con el botón verde.');
    }

    // ===================== PQRS =====================
    public function pqrs(Request $request)
    {
        $estado = $request->estado ?: 'abiertas';
        $lista = Pqrs::with('unidad')->when($estado === 'abiertas', fn ($q) => $q->where('estado', '<>', 'cerrada'))
            ->when($estado === 'cerradas', fn ($q) => $q->where('estado', 'cerrada'))->latest()->get();
        return view('conjunto.pqrs', ['lista' => $lista, 'estado' => $estado,
            'unidades' => Unidad::where('activa', true)->orderBy('torre')->orderByRaw('LENGTH(numero), numero')->get()]);
    }

    public function pqrsGuardar(Request $request, ?Pqrs $pqrs = null)
    {
        if ($pqrs?->id) {
            $d = $request->validate(['estado' => ['required', Rule::in(array_keys(Pqrs::ESTADOS))], 'respuesta' => 'nullable|string|max:3000']);
            $pqrs->update($d);
            return back()->with('success', 'Solicitud actualizada.');
        }
        $d = $request->validate([
            'unidad_id' => 'nullable|integer',
            'tipo' => ['required', Rule::in(array_keys(Pqrs::TIPOS))],
            'asunto' => 'required|string|max:120',
            'detalle' => 'nullable|string|max:3000',
        ]);
        Pqrs::create($d + ['estado' => 'abierta']);
        return back()->with('success', 'Solicitud registrada.');
    }

    // ===================== Reservas =====================
    public function reservas()
    {
        return view('conjunto.reservas', [
            'lista' => Reserva::with('unidad')->whereDate('fecha', '>=', today()->subDays(1))->orderBy('fecha')->orderBy('hora_inicio')->get(),
            'zonas' => $this->conj()->zonas_comunes,
            'unidades' => Unidad::where('activa', true)->orderBy('torre')->orderByRaw('LENGTH(numero), numero')->get(),
        ]);
    }

    public function reservaGuardar(Request $request)
    {
        $d = $request->validate([
            'zona' => 'required|string|max:60',
            'unidad_id' => 'required|integer',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'nota' => 'nullable|string|max:255',
        ], ['hora_fin.after' => 'La hora final debe ser después de la inicial.']);
        Unidad::findOrFail($d['unidad_id']);
        $choca = Reserva::where('zona', $d['zona'])->whereDate('fecha', $d['fecha'])->where('estado', 'confirmada')
            ->where('hora_inicio', '<', $d['hora_fin'])->where('hora_fin', '>', $d['hora_inicio'])->exists();
        if ($choca) {
            return back()->withInput()->withErrors('Esa zona ya está reservada en ese horario.');
        }
        Reserva::create($d + ['estado' => 'confirmada']);
        return back()->with('success', 'Reserva confirmada.');
    }

    public function reservaCancelar(Reserva $reserva)
    {
        $reserva->update(['estado' => 'cancelada']);
        return back()->with('success', 'Reserva cancelada.');
    }

    // ===================== Configuración =====================
    public function config(Request $request)
    {
        $d = $request->validate([
            'conj_dia_vence' => 'required|integer|min:1|max:28',
            'conj_interes_mora' => 'nullable|numeric|min:0|max:5',
            'conj_zonas' => 'nullable|string|max:1000',
        ]);
        $d['conj_interes_mora'] = $d['conj_interes_mora'] ?? 0;
        $this->conj()->update($d);
        return back()->with('success', 'Configuración guardada.');
    }
}
