<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Caja;
use App\Models\ClinicaAcceso;
use App\Models\Consentimiento;
use App\Models\Ajuste;
use App\Models\Cita;
use App\Models\LaboratorioOrden;
use App\Models\OrtodonciaControl;
use App\Models\Periodontograma;
use App\Models\ProcedimientoInsumo;
use App\Models\ProductoVariante;
use App\Models\PosVenta;
use App\Models\MetodoPago;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\PacienteAdjunto;
use App\Models\Producto;
use App\Models\TratamientoAbono;
use App\Models\TratamientoItem;
use App\Models\TratamientoPlan;
use App\Models\User;
use App\Support\Odontologia;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Consultorio odontológico: odontograma, planes de tratamiento con abonos a la caja,
// consentimientos informados firmados en pantalla y radiografías / fotos del paciente.
// - "historia-clinica" (odontólogo): odontograma, planes, consentimientos y adjuntos.
// - "agenda-consultorio" (recepción / auxiliar): ve los planes, recibe abonos, imprime presupuestos y recibos.
class odontologiaController extends Controller
{
    private const SOLO_CLINICO = ['odontograma', 'odontogramaGuardar', 'odontogramaPlan', 'planCrear', 'itemGuardar', 'itemBorrar', 'itemHecho', 'planGuardar',
        'consentimientoNuevo', 'consentimientoGuardar', 'consentimiento', 'adjuntoSubir', 'adjunto', 'adjuntoBorrar',
        'controlGuardar', 'periodontograma', 'periodontogramaGuardar'];
    // Solo el dueño o administrador (editar-negocio)
    private const SOLO_DUENO = ['insumos', 'insumoGuardar', 'insumoBorrar', 'comisiones', 'comisionesGuardar', 'indicadores'];

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
            abort_unless($request->user()->es_superadmin || $request->user()->can('historia-clinica'), 403, 'Solo el odontólogo(a) ve y cambia la parte clínica.');
            return $next($request);
        })->only(self::SOLO_CLINICO);
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->es_superadmin || $request->user()->can('editar-negocio'), 403, 'Solo el dueño o el administrador ve esta parte.');
            return $next($request);
        })->only(self::SOLO_DUENO);
    }

    private function clinico(): bool
    {
        $u = auth()->user();
        return $u->es_superadmin || $u->can('historia-clinica');
    }

    // ================= Odontograma =================
    public function odontograma(Request $request, Paciente $paciente)
    {
        $versiones = $paciente->odontogramas()->get(['id', 'created_at', 'profesional_nombre', 'atencion_id', 'denticion']);
        $ver = $request->integer('version') ? $paciente->odontogramas()->find($request->integer('version')) : null;
        $actual = $ver ?: $paciente->odontogramas()->first();
        $atencion = $request->integer('atencion') ? Atencion::where('paciente_id', $paciente->id)->find($request->integer('atencion')) : null;
        $denticion = $actual?->denticion ?? ($paciente->fecha_nacimiento && $paciente->fecha_nacimiento->age < 6 ? 'temporal'
            : ($paciente->fecha_nacimiento && $paciente->fecha_nacimiento->age < 12 ? 'mixta' : 'permanente'));
        ClinicaAcceso::anotar('ver', $paciente->id, $atencion?->id);
        return view('odontologia.odontograma', [
            'p' => $paciente, 'versiones' => $versiones, 'actual' => $actual, 'soloVer' => (bool) $ver && $ver->id !== $versiones->first()?->id,
            'atencion' => $atencion, 'denticion' => $denticion,
            'pendientes' => $actual ? Odontologia::pendientes($actual->piezas ?? []) : [],
            'historia' => Odontologia::historiaPiezas($paciente),
        ]);
    }

    public function odontogramaGuardar(Request $request, Paciente $paciente)
    {
        $request->validate([
            'piezas' => 'nullable|string|max:60000',
            'denticion' => ['required', Rule::in(array_keys(Odontologia::DENTICIONES))],
            'notas' => 'nullable|string|max:3000',
            'atencion_id' => ['nullable', 'integer', Rule::exists('atenciones', 'id')->where('tienda_id', TiendaActual::id())->where('paciente_id', $paciente->id)],
        ]);
        $piezas = Odontologia::limpiarPiezas(json_decode((string) $request->input('piezas'), true) ?: []);
        $u = auth()->user();
        $o = Odontograma::create([
            'paciente_id' => $paciente->id, 'atencion_id' => $request->input('atencion_id'), 'user_id' => $u->id, 'profesional_nombre' => $u->name,
            'denticion' => $request->denticion, 'piezas' => $piezas, 'notas' => trim((string) $request->input('notas')) ?: null,
        ]);
        ClinicaAcceso::anotar('editar', $paciente->id, $o->atencion_id);
        $n = count(Odontologia::pendientes($piezas));
        $msg = 'Odontograma guardado' . ($n ? ': hay ' . $n . ' procedimiento(s) por hacer. Toque "Pasar al plan de tratamiento" para armar el presupuesto.' : '.');
        return redirect()->route('odonto.odontograma', [$paciente, 'atencion' => $o->atencion_id])->with('success', $msg);
    }

    // Arma un plan de tratamiento con lo que está en rojo en el odontograma, con los precios de sus servicios
    public function odontogramaPlan(Odontograma $odontograma)
    {
        $pend = Odontologia::pendientes($odontograma->piezas ?? []);
        if (!$pend) return back()->with('info', 'El odontograma no tiene nada por hacer (en rojo).');
        $servicios = Producto::where('estado', 1)->get(['id', 'nombre', 'precio_venta']);
        $plan = DB::transaction(function () use ($odontograma, $pend, $servicios) {
            $plan = $this->nuevoPlan($odontograma->paciente_id, 'Plan según odontograma del ' . $odontograma->created_at->format('d/m/Y'));
            foreach ($pend as $i => $x) {
                $serv = $this->buscarServicio($servicios, $x['procedimiento']);
                TratamientoItem::create([
                    'plan_id' => $plan->id, 'pieza' => $x['pieza'], 'caras' => $x['caras'], 'producto_id' => $serv?->id,
                    'descripcion' => $serv?->nombre ?? $x['procedimiento'], 'cantidad' => $x['cantidad'] ?? 1, 'valor' => (float) ($serv?->precio_venta ?? 0), 'orden' => $i + 1,
                ]);
            }
            return $plan;
        });
        return redirect()->route('odonto.plan', $plan)->with('success', 'Plan ' . $plan->numero . ' creado con ' . count($pend) . ' procedimiento(s). Revise los valores y muéstreselo al paciente.');
    }

    private function buscarServicio($servicios, string $proc)
    {
        $clave = mb_strtolower(explode(' ', $proc)[0]);
        return $servicios->first(fn ($s) => mb_strtolower($s->nombre) === mb_strtolower($proc))
            ?? $servicios->first(fn ($s) => str_starts_with(mb_strtolower($s->nombre), $clave));
    }

    private function nuevoPlan(int $pacienteId, string $nombre): TratamientoPlan
    {
        return TratamientoPlan::create([
            'paciente_id' => $pacienteId, 'numero' => TratamientoPlan::siguienteNumero(), 'nombre' => $nombre,
            'estado' => 'propuesto', 'profesional_id' => auth()->id(), 'user_id' => auth()->id(),
        ]);
    }

    // ================= Planes de tratamiento =================
    public function tratamientos(Request $request)
    {
        $estado = $request->get('estado', 'activos');
        $q = trim((string) $request->get('q'));
        $planes = TratamientoPlan::with(['paciente', 'items'])
            ->when($estado === 'activos', fn ($w) => $w->whereIn('estado', ['propuesto', 'aprobado']))
            ->when(isset(TratamientoPlan::ESTADOS[$estado]), fn ($w) => $w->where('estado', $estado))
            ->when($q !== '', fn ($w) => $w->whereHas('paciente', fn ($x) => $x->where('nombres', 'like', "%$q%")->orWhere('apellidos', 'like', "%$q%")->orWhere('documento', 'like', "%$q%")))
            ->latest('id')->paginate(30)->withQueryString();
        $activos = TratamientoPlan::with('items')->where('estado', 'aprobado')->get();
        $kpi = [
            'por_cobrar' => round($activos->sum(fn ($p) => $p->saldo), 2),
            'en_curso' => $activos->count(),
            'presupuestos' => TratamientoPlan::where('estado', 'propuesto')->count(),
            'abonos_mes' => (float) TratamientoAbono::where('anulado', false)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto'),
        ];
        return view('odontologia.tratamientos', compact('planes', 'kpi', 'estado', 'q'));
    }

    public function planCrear(Request $request, Paciente $paciente)
    {
        $request->validate(['nombre' => 'nullable|string|max:120', 'tipo' => 'nullable|in:general,ortodoncia']);
        $orto = $request->input('tipo') === 'ortodoncia';
        $plan = $this->nuevoPlan($paciente->id, trim((string) $request->input('nombre')) ?: ($orto ? 'Tratamiento de ortodoncia' : 'Plan de tratamiento'));
        if ($orto) {
            $plan->update(['tipo' => 'ortodoncia', 'inicio' => today(), 'cuotas' => 24]);
            return redirect()->route('odonto.plan', $plan)->with('success', 'Ortodoncia ' . $plan->numero . ' creada. Ponga la cuota inicial, la cuota mensual y el número de meses, y agregue los procedimientos (instalación de brackets, retenedores…).');
        }
        return redirect()->route('odonto.plan', $plan)->with('success', 'Plan ' . $plan->numero . ' creado. Agregue los procedimientos.');
    }

    public function plan(TratamientoPlan $plan)
    {
        $plan->load(['paciente', 'items', 'abonos.metodo', 'abonos.user:id,name', 'profesional:id,name', 'controles']);
        return view('odontologia.plan', [
            'plan' => $plan, 'p' => $plan->paciente, 'clinico' => $this->clinico(),
            'servicios' => Producto::where('estado', 1)->orderByDesc('es_servicio')->orderBy('nombre')->get(['id', 'nombre', 'precio_venta', 'es_servicio']),
            'metodos' => MetodoPago::activos()->get(['id', 'nombre']),
            'caja' => Caja::abiertaDe(auth()->id()),
            'piezas' => Odontologia::piezasValidas(),
            'abonoNuevo' => session('abono_nuevo') ? TratamientoAbono::find(session('abono_nuevo')) : null,
        ]);
    }

    private function abierto(TratamientoPlan $plan): void
    {
        if (in_array($plan->estado, ['terminado', 'cancelado'], true)) {
            throw ValidationException::withMessages(['plan' => 'Este plan ya está ' . mb_strtolower(TratamientoPlan::ESTADOS[$plan->estado][0]) . ': no se puede cambiar.']);
        }
    }

    public function itemGuardar(Request $request, TratamientoPlan $plan, ?TratamientoItem $item = null)
    {
        $this->abierto($plan);
        $d = $request->validate([
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')->where('tienda_id', TiendaActual::id())],
            'descripcion' => 'nullable|string|max:160',
            'pieza' => ['nullable', Rule::in(Odontologia::piezasValidas())],
            'caras' => 'nullable|string|max:10',
            'cantidad' => 'nullable|integer|min:1|max:99',
            'valor' => 'nullable|numeric|min:0|max:999999999',
        ]);
        $prod = !empty($d['producto_id']) ? Producto::find($d['producto_id']) : null;
        $desc = trim((string) ($d['descripcion'] ?? '')) ?: $prod?->nombre;
        if (!$desc) throw ValidationException::withMessages(['descripcion' => 'Elija el servicio o escriba el procedimiento.']);
        $caras = strtoupper(preg_replace('/[^VLMDOvlmdo]/', '', (string) ($d['caras'] ?? '')));
        $datos = [
            'producto_id' => $prod?->id, 'descripcion' => $desc, 'pieza' => $d['pieza'] ?? null, 'caras' => $caras ?: null,
            'cantidad' => $d['cantidad'] ?? 1, 'valor' => $d['valor'] ?? (float) ($prod?->precio_venta ?? 0),
        ];
        if ($item && $item->exists) {
            abort_unless($item->plan_id === $plan->id, 404);
            $item->update($datos);
        } else {
            TratamientoItem::create($datos + ['plan_id' => $plan->id, 'orden' => (int) $plan->items()->max('orden') + 1]);
        }
        $this->revisarTotal($plan->fresh('items'));
        return back()->with('success', 'Procedimiento guardado.');
    }

    // El total del plan no puede quedar por debajo de lo que el paciente ya abonó
    private function revisarTotal(TratamientoPlan $plan): void
    {
        if ($plan->total + 0.001 < (float) $plan->abonado) {
            throw ValidationException::withMessages(['valor' => 'El total del plan (' . dinero($plan->total) . ') quedaría menor que lo abonado (' . dinero($plan->abonado) . ').']);
        }
    }

    public function itemBorrar(TratamientoPlan $plan, TratamientoItem $item)
    {
        abort_unless($item->plan_id === $plan->id, 404);
        $this->abierto($plan);
        if ($item->estado === 'hecho') return back()->with('error', 'Ese procedimiento ya se hizo: no se puede quitar.');
        DB::transaction(function () use ($plan, $item) {
            $item->delete();
            $this->revisarTotal($plan->fresh('items'));
        });
        return back()->with('success', 'Procedimiento quitado del plan.');
    }

    public function itemHecho(TratamientoPlan $plan, TratamientoItem $item)
    {
        abort_unless($item->plan_id === $plan->id, 404);
        $this->abierto($plan);
        if ($item->estado === 'hecho') {
            DB::transaction(function () use ($item, $plan) {
                $item->update(['estado' => 'pendiente', 'hecho_at' => null, 'hecho_por' => null, 'hecho_user_id' => null]);
                Odontologia::moverInsumos($item, false, $plan->numero);
            });
            return back()->with('success', 'Procedimiento marcado otra vez como pendiente.' . ($item->producto_id ? ' Si se habían descontado insumos, volvieron al inventario.' : ''));
        }
        $insumos = DB::transaction(function () use ($item, $plan) {
            $item->update(['estado' => 'hecho', 'hecho_at' => now(), 'hecho_por' => auth()->user()->name, 'hecho_user_id' => auth()->id()]);
            return Odontologia::moverInsumos($item, true, $plan->numero);
        });
        if ($plan->estado === 'propuesto') $plan->update(['estado' => 'aprobado', 'aprobado_at' => $plan->aprobado_at ?? now()]);
        $plan->load('items');
        $msg = 'Procedimiento marcado como hecho.' . ($insumos ? ' Se descontaron ' . $insumos . ' insumo(s) del inventario.' : '');
        if ($plan->items->every(fn ($i) => $i->estado === 'hecho')) $msg .= ' ¡Ya se hizo todo el plan! Si está pagado, márquelo como terminado.';
        return back()->with('success', $msg);
    }

    // Nombre, notas, descuento y cambio de estado (aprobar, terminar, cancelar, volver a presupuesto)
    public function planGuardar(Request $request, TratamientoPlan $plan)
    {
        $d = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'notas' => 'nullable|string|max:3000',
            'descuento' => 'nullable|numeric|min:0|max:999999999',
            'accion' => ['nullable', Rule::in(['aprobar', 'terminar', 'cancelar', 'reabrir'])],
            'aprobado_nota' => 'nullable|string|max:160',
            'cuota_inicial' => 'nullable|numeric|min:0|max:999999999',
            'cuota_mensual' => 'nullable|numeric|min:0|max:999999999',
            'cuotas' => 'nullable|integer|min:0|max:120',
            'inicio' => 'nullable|date',
        ]);
        $accion = $d['accion'] ?? null;
        if (!$accion) {
            DB::transaction(function () use ($plan, $d) {
                $this->abierto($plan);
                $plan->fill(['nombre' => trim((string) ($d['nombre'] ?? '')) ?: $plan->nombre, 'notas' => $d['notas'] ?? null, 'descuento' => (float) ($d['descuento'] ?? 0)]);
                if ($plan->es_ortodoncia) {
                    $plan->fill(['cuota_inicial' => (float) ($d['cuota_inicial'] ?? 0), 'cuota_mensual' => (float) ($d['cuota_mensual'] ?? 0),
                        'cuotas' => (int) ($d['cuotas'] ?? 0), 'inicio' => $d['inicio'] ?? $plan->inicio ?? today()]);
                    // El valor del tratamiento queda como un renglón propio (orden 0): cuota inicial + cuotas mensuales
                    $valor = round($plan->cuota_inicial + $plan->cuotas * $plan->cuota_mensual, 2);
                    $fila = $plan->items()->where('orden', 0)->first();
                    if ($valor > 0) {
                        $desc = 'Ortodoncia: cuota inicial ' . dinero($plan->cuota_inicial) . ' + ' . $plan->cuotas . ' cuotas de ' . dinero($plan->cuota_mensual);
                        $fila ? $fila->update(['descripcion' => mb_substr($desc, 0, 160), 'valor' => $valor, 'cantidad' => 1])
                            : TratamientoItem::create(['plan_id' => $plan->id, 'descripcion' => mb_substr($desc, 0, 160), 'valor' => $valor, 'cantidad' => 1, 'orden' => 0]);
                    } elseif ($fila && $fila->estado !== 'hecho') {
                        $fila->delete();
                    }
                    $plan->unsetRelation('items');
                }
                if ($plan->descuento > $plan->subtotal) throw ValidationException::withMessages(['descuento' => 'El descuento no puede ser mayor que el valor del plan.']);
                $this->revisarTotal($plan);
                $plan->save();
            });
            return back()->with('success', 'Plan guardado.');
        }
        if ($accion === 'aprobar') {
            $this->abierto($plan);
            if ($plan->items()->doesntExist()) return back()->with('error', 'Agregue al menos un procedimiento antes de aprobar el plan.');
            $plan->update(['estado' => 'aprobado', 'aprobado_at' => now(), 'aprobado_nota' => trim((string) ($d['aprobado_nota'] ?? '')) ?: 'Aprobado por el paciente en consulta']);
            return back()->with('success', 'Plan aprobado por el paciente. Ya puede recibir abonos y marcar los procedimientos a medida que los haga.');
        }
        if ($accion === 'terminar') {
            $this->abierto($plan);
            $plan->update(['estado' => 'terminado']);
            return back()->with('success', 'Plan terminado.' . ($plan->saldo > 0 ? ' Ojo: quedó un saldo de ' . dinero($plan->saldo) . ' por cobrar.' : ''));
        }
        if ($accion === 'cancelar') {
            $this->abierto($plan);
            if ($plan->abonado > 0) return back()->with('error', 'Este plan tiene abonos por ' . dinero($plan->abonado) . ': márquelo como terminado o devuelva el dinero antes de cancelarlo.');
            $plan->update(['estado' => 'cancelado']);
            return back()->with('success', 'Plan cancelado.');
        }
        // reabrir: un plan terminado o cancelado vuelve a quedar en tratamiento
        $plan->update(['estado' => $plan->aprobado_at ? 'aprobado' : 'propuesto']);
        return back()->with('success', 'Plan abierto otra vez.');
    }

    public function abono(Request $request, TratamientoPlan $plan)
    {
        $d = $request->validate([
            'monto' => 'required|numeric|min:1|max:999999999',
            'metodo_id' => ['required', 'integer'],
            'nota' => 'nullable|string|max:255',
        ], ['monto.required' => 'Escriba cuánto abona el paciente.', 'metodo_id.required' => 'Elija cómo pagó.']);
        if ($plan->estado === 'cancelado') return back()->with('error', 'El plan está cancelado.');
        $caja = Caja::abiertaDe(auth()->id());
        if (!$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para recibir el abono del tratamiento.');
        $metodo = MetodoPago::activos()->whereKey($d['metodo_id'])->first();
        if (!$metodo) throw ValidationException::withMessages(['metodo_id' => 'Elija cómo pagó el abono.']);

        $abono = DB::transaction(function () use ($plan, $d, $metodo, $caja) {
            $p = TratamientoPlan::whereKey($plan->id)->lockForUpdate()->first();
            $p->load('items');
            $monto = round((float) $d['monto'], 2);
            if ($monto > $p->saldo + 0.001) {
                throw ValidationException::withMessages(['monto' => 'El abono es mayor que el saldo pendiente (' . dinero($p->saldo) . ').']);
            }
            $p->abonado = round($p->abonado + $monto, 2);
            if ($p->estado === 'propuesto') {
                $p->estado = 'aprobado';
                $p->aprobado_at ??= now();
                $p->aprobado_nota ??= 'Aprobado con el primer abono';
            }
            $p->save();
            return TratamientoAbono::create([
                'plan_id' => $p->id, 'numero' => TratamientoAbono::siguienteNumero(), 'caja_id' => $caja->id, 'user_id' => auth()->id(),
                'metodo_pago_id' => $metodo->id, 'monto' => $monto, 'saldo_despues' => $p->saldo, 'nota' => $d['nota'] ?? null,
            ]);
        });
        $plan->refresh()->load('items');
        $msg = $plan->saldo <= 0 ? '¡Tratamiento pagado completo!' : 'Abono ' . $abono->numero . ' recibido. Saldo: ' . dinero($plan->saldo) . '.';
        return redirect()->route('odonto.plan', $plan)->with('success', $msg)->with('abono_nuevo', $abono->id);
    }

    public function recibo(TratamientoAbono $abono)
    {
        $plan = $abono->plan()->with(['paciente', 'items'])->first();
        return view('odontologia.recibo', ['a' => $abono->load('metodo', 'user:id,name'), 'plan' => $plan, 'tienda' => TiendaActual::tienda(), 'wa' => self::mensajeAbono($plan, $abono)]);
    }

    public static function mensajeAbono(TratamientoPlan $plan, TratamientoAbono $a): string
    {
        $t = TiendaActual::tienda();
        return 'Hola ' . $plan->paciente?->nombres . ', le confirmamos su abono de ' . dinero($a->monto) . ' a su tratamiento ' . $plan->numero
            . ' en ' . $t?->nombre . '. Saldo pendiente: ' . dinero($a->saldo_despues) . '. ¡Gracias por su confianza!';
    }

    public function presupuesto(TratamientoPlan $plan)
    {
        $plan->load(['paciente', 'items', 'abonos', 'profesional:id,name,registro_profesional,especialidad,firma_path']);
        $firma = null;
        $u = $plan->profesional;
        if ($u?->firma_path && Storage::disk('local')->exists($u->firma_path)) {
            $firma = 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($u->firma_path));
        }
        $t = TiendaActual::tienda();
        $wa = 'Hola ' . $plan->paciente?->nombres . ', le compartimos su plan de tratamiento ' . $plan->numero . ' de ' . $t?->nombre . ': '
            . $plan->items->map(fn ($i) => '• ' . ($i->pieza ? 'Pieza ' . $i->pieza . ': ' : '') . $i->descripcion . ' ' . dinero($i->total))->implode("\n")
            . "\nTotal: " . dinero($plan->total) . ($plan->abonado > 0 ? ' · Abonado: ' . dinero($plan->abonado) . ' · Saldo: ' . dinero($plan->saldo) : '');
        return view('odontologia.presupuesto', ['plan' => $plan, 'p' => $plan->paciente, 'tienda' => $t, 'firma' => $firma, 'wa' => $wa]);
    }

    // ================= Consentimientos =================
    public function consentimientoNuevo(Request $request, Paciente $paciente)
    {
        $plantillas = Odontologia::consentimientos();
        $clave = array_key_exists($request->get('plantilla'), $plantillas) ? $request->get('plantilla')
            : ($paciente->fecha_nacimiento && $paciente->fecha_nacimiento->age < 18 ? 'menor' : 'general');
        $u = auth()->user();
        $t = TiendaActual::tienda();
        $procedimiento = trim((string) $request->get('procedimiento')) ?: $plantillas[$clave][1];
        $texto = Odontologia::textoConsentimiento($clave, [
            'paciente' => $paciente->nombre_completo, 'documento' => trim($paciente->tipo_documento . ' ' . $paciente->documento) ?: '________',
            'procedimiento' => $procedimiento, 'profesional' => $u->name, 'consultorio' => $t->nombre,
            'fecha' => now()->translatedFormat('j \d\e F \d\e Y'), 'ciudad' => $t->ciudad ?: '________', 'firmante' => '________',
        ]);
        return view('odontologia.consentimiento-nuevo', ['p' => $paciente, 'plantillas' => $plantillas, 'clave' => $clave, 'texto' => $texto, 'procedimiento' => $procedimiento]);
    }

    public function consentimientoGuardar(Request $request, Paciente $paciente)
    {
        $d = $request->validate([
            'plantilla' => ['required', Rule::in(array_keys(Odontologia::consentimientos()))],
            'procedimiento' => 'nullable|string|max:200',
            'texto' => 'required|string|min:40|max:20000',
            'firmante_nombre' => 'required|string|max:160',
            'firmante_documento' => 'nullable|string|max:40',
            'firmante_relacion' => 'required|string|max:40',
            'firma' => 'required|string|max:900000',
        ], ['firma.required' => 'Falta la firma del paciente.', 'firmante_nombre.required' => 'Escriba el nombre de quien firma.']);
        if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $d['firma'], $m) || strlen($m[1]) < 800) {
            throw ValidationException::withMessages(['firma' => 'Falta la firma: pídale al paciente que firme en el recuadro.']);
        }
        $png = base64_decode($m[1], true);
        if ($png === false || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") throw ValidationException::withMessages(['firma' => 'La firma no se pudo leer. Intente de nuevo.']);

        $u = auth()->user();
        $c = DB::transaction(function () use ($paciente, $d, $png, $u, $request) {
            $path = 'consentimientos/' . TiendaActual::id() . '/' . $paciente->id . '-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.png';
            Storage::disk('local')->put($path, $png);
            $c = Consentimiento::create([
                'paciente_id' => $paciente->id, 'plantilla' => $d['plantilla'], 'titulo' => Odontologia::consentimientos()[$d['plantilla']][0],
                'procedimiento' => $d['procedimiento'] ?? null, 'texto' => $d['texto'], 'firmante_nombre' => $d['firmante_nombre'],
                'firmante_documento' => $d['firmante_documento'] ?? null, 'firmante_relacion' => $d['firmante_relacion'], 'firma_path' => $path,
                'firmado_at' => now(), 'ip' => $request->ip(), 'profesional_id' => $u->id, 'profesional_nombre' => $u->name, 'profesional_registro' => $u->registro_profesional,
            ]);
            $c->forceFill(['huella' => $c->calcularHuella()])->save();
            return $c;
        });
        ClinicaAcceso::anotar('crear', $paciente->id);
        return redirect()->route('odonto.consentimiento', $c)->with('success', 'Consentimiento firmado y guardado. Imprímalo o guárdelo en PDF si el paciente quiere una copia.');
    }

    public function consentimiento(Consentimiento $consentimiento)
    {
        $img = fn ($path) => $path && Storage::disk('local')->exists($path) ? 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($path)) : null;
        $prof = $consentimiento->profesional_id ? User::find($consentimiento->profesional_id, ['id', 'firma_path']) : null;
        ClinicaAcceso::anotar('imprimir', $consentimiento->paciente_id);
        return view('odontologia.consentimiento', [
            'c' => $consentimiento, 'p' => $consentimiento->paciente, 'tienda' => TiendaActual::tienda(),
            'firmaPaciente' => $img($consentimiento->firma_path), 'firmaProfesional' => $img($prof?->firma_path),
        ]);
    }

    // ================= Radiografías, fotos y documentos =================
    public function adjuntoSubir(Request $request, Paciente $paciente)
    {
        $request->validate([
            'archivos' => 'required|array|max:10',
            'archivos.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'tipo' => ['required', Rule::in(array_keys(PacienteAdjunto::TIPOS))],
            'titulo' => 'nullable|string|max:120',
        ], ['archivos.required' => 'Elija las fotos o radiografías.', 'archivos.*.mimes' => 'Solo fotos (JPG, PNG, WEBP) o PDF.', 'archivos.*.max' => 'Cada archivo puede pesar hasta 10 MB.']);
        $n = 0;
        foreach ($request->file('archivos') as $f) {
            $path = $f->store('pacientes/' . TiendaActual::id() . '/' . $paciente->id, 'local');
            PacienteAdjunto::create([
                'paciente_id' => $paciente->id, 'tipo' => $request->tipo, 'titulo' => trim((string) $request->input('titulo')) ?: null,
                'path' => $path, 'mime' => $f->getMimeType(), 'tamano' => (int) $f->getSize(), 'user_id' => auth()->id(),
            ]);
            $n++;
        }
        ClinicaAcceso::anotar('editar', $paciente->id);
        return back()->with('success', $n === 1 ? 'Archivo guardado en la historia del paciente.' : $n . ' archivos guardados en la historia del paciente.');
    }

    public function adjunto(PacienteAdjunto $adjunto)
    {
        abort_unless(Storage::disk('local')->exists($adjunto->path), 404);
        ClinicaAcceso::anotar('ver', $adjunto->paciente_id);
        return response()->file(Storage::disk('local')->path($adjunto->path), [
            'Content-Type' => $adjunto->mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // Solo quien lo subió (el mismo día) o el dueño: lo demás queda como parte de la historia
    public function adjuntoBorrar(PacienteAdjunto $adjunto)
    {
        $u = auth()->user();
        abort_unless($u->can('editar-negocio') || ($adjunto->user_id === $u->id && $adjunto->created_at?->isToday()), 403, 'Solo quien lo subió (el mismo día) o el dueño lo puede quitar.');
        Storage::disk('local')->delete($adjunto->path);
        $adjunto->delete();
        ClinicaAcceso::anotar('editar', $adjunto->paciente_id);
        return back()->with('success', 'Archivo quitado.');
    }

    // ================= Ortodoncia: controles mensuales =================
    public function controlGuardar(Request $request, TratamientoPlan $plan)
    {
        abort_unless($plan->es_ortodoncia, 404);
        $d = $request->validate([
            'fecha' => 'required|date|before_or_equal:today',
            'arco_sup' => 'nullable|string|max:60', 'arco_inf' => 'nullable|string|max:60',
            'ligaduras' => 'nullable|string|max:60', 'elasticos' => 'nullable|string|max:80',
            'notas' => 'nullable|string|max:3000', 'proximo' => 'nullable|date|after:fecha',
        ]);
        OrtodonciaControl::create($d + ['plan_id' => $plan->id, 'paciente_id' => $plan->paciente_id, 'user_id' => auth()->id(), 'profesional_nombre' => auth()->user()->name]);
        ClinicaAcceso::anotar('editar', $plan->paciente_id);
        $msg = 'Control de ortodoncia guardado.';
        if (!empty($d['proximo'])) $msg .= ' Agende la próxima cita para el ' . \Carbon\Carbon::parse($d['proximo'])->format('d/m/Y') . '.';
        return back()->with('success', $msg);
    }

    // ================= Periodontograma =================
    public function periodontograma(Request $request, Paciente $paciente)
    {
        $versiones = Periodontograma::where('paciente_id', $paciente->id)->orderByDesc('id')->get(['id', 'created_at', 'profesional_nombre', 'resumen']);
        $actual = $request->integer('version') ? Periodontograma::where('paciente_id', $paciente->id)->find($request->integer('version')) : Periodontograma::where('paciente_id', $paciente->id)->latest('id')->first();
        ClinicaAcceso::anotar('ver', $paciente->id);
        return view('odontologia.periodontograma', ['p' => $paciente, 'versiones' => $versiones, 'actual' => $actual,
            'soloVer' => $actual && $versiones->first() && $actual->id !== $versiones->first()->id]);
    }

    public function periodontogramaGuardar(Request $request, Paciente $paciente)
    {
        $request->validate(['datos' => 'nullable|string|max:80000', 'notas' => 'nullable|string|max:3000']);
        $datos = Odontologia::limpiarPerio(json_decode((string) $request->input('datos'), true) ?: []);
        if (!$datos) return back()->with('error', 'El periodontograma está vacío: escriba al menos una profundidad.');
        Periodontograma::create(['paciente_id' => $paciente->id, 'user_id' => auth()->id(), 'profesional_nombre' => auth()->user()->name,
            'datos' => $datos, 'resumen' => Odontologia::resumenPerio($datos), 'notas' => trim((string) $request->input('notas')) ?: null]);
        ClinicaAcceso::anotar('editar', $paciente->id);
        return redirect()->route('odonto.perio', $paciente)->with('success', 'Periodontograma guardado.');
    }

    // ================= Laboratorio dental =================
    public function laboratorio(Request $request)
    {
        $estado = $request->get('estado', 'pendientes');
        $ordenes = LaboratorioOrden::with('paciente')
            ->when($estado === 'pendientes', fn ($q) => $q->whereIn('estado', ['enviado', 'devuelto', 'recibido']))
            ->when(isset(LaboratorioOrden::ESTADOS[$estado]), fn ($q) => $q->where('estado', $estado))
            ->orderByRaw("CASE WHEN estado IN ('enviado','devuelto') THEN 0 WHEN estado = 'recibido' THEN 1 ELSE 2 END")->orderBy('entrega')->orderByDesc('id')
            ->paginate(40)->withQueryString();
        $paciente = $request->integer('paciente') ? Paciente::find($request->integer('paciente')) : null;
        $planes = $paciente ? TratamientoPlan::where('paciente_id', $paciente->id)->whereIn('estado', ['propuesto', 'aprobado'])->get(['id', 'numero', 'nombre']) : collect();
        $kpi = [
            'en_lab' => LaboratorioOrden::whereIn('estado', ['enviado', 'devuelto'])->count(),
            'atrasados' => LaboratorioOrden::whereIn('estado', ['enviado', 'devuelto'])->whereDate('entrega', '<', today())->count(),
            'costo_mes' => (float) LaboratorioOrden::whereBetween('enviado', [now()->startOfMonth(), now()->endOfMonth()])->sum('costo'),
        ];
        $laboratorios = LaboratorioOrden::distinct()->orderBy('laboratorio')->pluck('laboratorio');
        return view('odontologia.laboratorio', compact('ordenes', 'estado', 'paciente', 'planes', 'kpi', 'laboratorios'));
    }

    public function laboratorioGuardar(Request $request)
    {
        $d = $request->validate([
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('tienda_id', TiendaActual::id())],
            'plan_id' => ['nullable', 'integer', Rule::exists('tratamiento_planes', 'id')->where('tienda_id', TiendaActual::id())],
            'laboratorio' => 'required|string|max:120', 'trabajo' => 'required|string|max:160',
            'pieza' => 'nullable|string|max:30', 'color' => 'nullable|string|max:30', 'material' => 'nullable|string|max:80',
            'enviado' => 'required|date', 'entrega' => 'nullable|date|after_or_equal:enviado',
            'costo' => 'nullable|numeric|min:0|max:999999999', 'notas' => 'nullable|string|max:500',
        ], ['paciente_id.required' => 'Busque y elija el paciente.', 'trabajo.required' => 'Escriba qué trabajo se envía (ej. corona en zirconio).']);
        $o = LaboratorioOrden::create($d + ['numero' => LaboratorioOrden::siguienteNumero(), 'estado' => 'enviado', 'costo' => (float) ($d['costo'] ?? 0), 'user_id' => auth()->id()]);
        return redirect()->route('odonto.laboratorio')->with('success', 'Orden ' . $o->numero . ' registrada: ' . $o->trabajo . ' en ' . $o->laboratorio . '.');
    }

    public function laboratorioEstado(Request $request, LaboratorioOrden $orden)
    {
        $request->validate(['estado' => ['required', Rule::in(array_keys(LaboratorioOrden::ESTADOS))], 'notas' => 'nullable|string|max:500']);
        $cambios = ['estado' => $request->estado];
        if ($request->estado === 'recibido' && !$orden->recibido) $cambios['recibido'] = today();
        if ($request->estado === 'devuelto') {
            $cambios['recibido'] = null;
            if ($request->filled('notas')) $cambios['notas'] = mb_substr(trim(($orden->notas ? $orden->notas . ' · ' : '') . 'Devuelto ' . today()->format('d/m') . ': ' . $request->notas), 0, 500);
        }
        $orden->update($cambios);
        return back()->with('success', 'Orden ' . $orden->numero . ': ' . mb_strtolower(LaboratorioOrden::ESTADOS[$request->estado][0]) . '.');
    }

    // ================= Insumos por procedimiento =================
    public function insumos()
    {
        $servicios = Producto::where('estado', 1)->where('es_servicio', true)->orderBy('nombre')->get(['id', 'nombre']);
        $insumos = ProductoVariante::with('producto:id,nombre,es_servicio,controla_inventario')
            ->whereHas('producto', fn ($q) => $q->where('estado', 1)->where('es_servicio', false))->get()
            ->sortBy(fn ($v) => $v->producto?->nombre)->values();
        $config = ProcedimientoInsumo::with(['variante.producto:id,nombre'])->get()->groupBy('producto_id');
        return view('odontologia.insumos', compact('servicios', 'insumos', 'config'));
    }

    public function insumoGuardar(Request $request)
    {
        $d = $request->validate([
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('tienda_id', TiendaActual::id())],
            'producto_variante_id' => ['required', 'integer'],
            'cantidad' => 'required|numeric|min:0.001|max:9999',
        ], ['producto_variante_id.required' => 'Elija el insumo que se gasta.']);
        $v = ProductoVariante::whereKey($d['producto_variante_id'])->whereHas('producto')->first();
        abort_unless($v, 422, 'Ese insumo no existe en su inventario.');
        ProcedimientoInsumo::updateOrCreate(['producto_id' => $d['producto_id'], 'producto_variante_id' => $v->id], ['cantidad' => $d['cantidad']]);
        return back()->with('success', 'Insumo guardado: cada vez que se marque hecho este procedimiento se descuenta del inventario.');
    }

    public function insumoBorrar(ProcedimientoInsumo $insumo)
    {
        $insumo->delete();
        return back()->with('success', 'Insumo quitado del procedimiento.');
    }

    // ================= Comisiones por profesional =================
    private function porcentajes(): array
    {
        return json_decode((string) Ajuste::valor('t' . TiendaActual::id() . '_comisiones', '{}'), true) ?: [];
    }

    public function comisiones(Request $request)
    {
        $mes = rescue(fn () => \Carbon\Carbon::createFromFormat('Y-m', (string) $request->get('mes', now()->format('Y-m')))->startOfMonth(), now()->startOfMonth(), false);
        $desde = $mes->copy()->startOfMonth(); $hasta = $mes->copy()->endOfMonth();
        $items = TratamientoItem::with('plan.paciente')->where('estado', 'hecho')->whereBetween('hecho_at', [$desde, $hasta])->get();
        $pct = $this->porcentajes();
        $profes = User::where('tienda_id', TiendaActual::id())->get()->filter(fn ($u) => $u->can('historia-clinica'))->keyBy('id');
        $filas = $items->groupBy(fn ($i) => (int) $i->hecho_user_id)->map(function ($g, $uid) use ($pct, $profes) {
            $prod = round((float) $g->sum(fn ($i) => $i->total), 2);
            $p = (float) ($pct[$uid] ?? 0);
            return ['id' => $uid, 'nombre' => $profes[$uid]->name ?? ($g->first()->hecho_por ?: 'Sin profesional'), 'procedimientos' => $g->count(),
                'produccion' => $prod, 'porcentaje' => $p, 'comision' => round($prod * $p / 100, 2), 'detalle' => $g->sortBy('hecho_at')->values()];
        })->sortByDesc('produccion')->values();
        $atenciones = \App\Models\Atencion::whereNotNull('cerrada_at')->whereBetween('fecha', [$desde, $hasta])->selectRaw('profesional_id, COUNT(*) n')->groupBy('profesional_id')->pluck('n', 'profesional_id');
        return view('odontologia.comisiones', compact('mes', 'filas', 'profes', 'pct', 'atenciones'));
    }

    public function comisionesGuardar(Request $request)
    {
        $request->validate(['pct' => 'array', 'pct.*' => 'nullable|numeric|min:0|max:100']);
        $ids = User::where('tienda_id', TiendaActual::id())->pluck('id')->map(fn ($x) => (string) $x)->all();
        $pct = array_filter(array_intersect_key((array) $request->input('pct', []), array_flip($ids)), fn ($v) => $v !== null && $v !== '');
        Ajuste::guardar('t' . TiendaActual::id() . '_comisiones', json_encode(array_map('floatval', $pct)));
        return back()->with('success', 'Porcentajes de comisión guardados.');
    }

    // ================= Indicadores del consultorio y pacientes para recordar control =================
    public function indicadores(Request $request)
    {
        $mes = rescue(fn () => \Carbon\Carbon::createFromFormat('Y-m', (string) $request->get('mes', now()->format('Y-m')))->startOfMonth(), now()->startOfMonth(), false);
        $desde = $mes->copy()->startOfMonth(); $hasta = $mes->copy()->endOfMonth();
        $citas = Cita::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->get(['estado', 'origen']);
        $tot = $citas->count();
        $abonos = (float) TratamientoAbono::where('anulado', false)->whereBetween('created_at', [$desde, $hasta])->sum('monto');
        $ventas = (float) PosVenta::where('estado', 'pagada')->whereBetween('created_at', [$desde, $hasta])->sum('total');
        $planes = TratamientoPlan::whereBetween('created_at', [$desde, $hasta])->get(['estado', 'aprobado_at']);
        $produccion = TratamientoItem::where('estado', 'hecho')->whereBetween('hecho_at', [$desde, $hasta])->get()
            ->groupBy(fn ($i) => $i->descripcion)->map(fn ($g, $k) => ['nombre' => $k, 'n' => $g->sum('cantidad'), 'valor' => round($g->sum(fn ($i) => $i->total), 2)])
            ->sortByDesc('valor')->take(10)->values();
        $kpi = [
            'ingresos' => round($abonos + $ventas, 2), 'abonos' => $abonos, 'ventas' => $ventas,
            'nuevos' => Paciente::whereBetween('created_at', [$desde, $hasta])->count(),
            'citas' => $tot, 'atendidas' => $citas->where('estado', 'atendida')->count(),
            'no_asistio' => $tot ? round($citas->where('estado', 'no_asistio')->count() * 100 / $tot) : 0,
            'en_linea' => $citas->where('origen', 'en_linea')->count(),
            'presupuestos' => $planes->count(), 'aprobados' => $planes->whereNotNull('aprobado_at')->count(),
            'por_cobrar' => round(TratamientoPlan::with('items')->where('estado', 'aprobado')->get()->sum(fn ($p) => $p->saldo), 2),
            'orto_atrasados' => TratamientoPlan::with('items')->where('tipo', 'ortodoncia')->where('estado', 'aprobado')->get()->filter(fn ($p) => $p->atraso > 0)->count(),
        ];
        // Últimos 6 meses de ingresos para la gráfica
        $serie = collect(range(5, 0))->map(function ($i) use ($mes) {
            $m = $mes->copy()->subMonths($i);
            $a = (float) TratamientoAbono::where('anulado', false)->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('monto');
            $v = (float) PosVenta::where('estado', 'pagada')->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('total');
            return ['mes' => ucfirst($m->translatedFormat('M')), 'valor' => round($a + $v)];
        });
        // Pacientes para recordar control: su última cita atendida fue hace más de 6 meses y no tienen cita próxima
        $meses = max(3, min(24, $request->integer('meses', 6)));
        $ultimas = Cita::where('estado', 'atendida')->selectRaw('paciente_id, MAX(fecha) f')->groupBy('paciente_id')->pluck('f', 'paciente_id');
        $conProxima = Cita::whereIn('estado', ['programada', 'confirmada'])->whereDate('fecha', '>=', today())->pluck('paciente_id')->flip();
        $idsRecall = $ultimas->filter(fn ($f, $pid) => \Carbon\Carbon::parse($f)->lt(today()->subMonths($meses)) && !isset($conProxima[$pid]))->keys();
        $recall = Paciente::whereIn('id', $idsRecall)->get()->map(fn ($p) => ['p' => $p, 'ultima' => \Carbon\Carbon::parse($ultimas[$p->id])])->sortBy('ultima')->take(60)->values();
        $ortoAtrasados = TratamientoPlan::with(['items', 'paciente'])->where('tipo', 'ortodoncia')->where('estado', 'aprobado')->get()->filter(fn ($p) => $p->atraso > 0)->values();
        return view('odontologia.indicadores', compact('mes', 'kpi', 'serie', 'produccion', 'recall', 'meses', 'ortoAtrasados'));
    }
}

