<?php

namespace App\Http\Controllers;

use App\Models\Alquiler;
use App\Models\AlquilerAbono;
use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Models\Tienda;
use App\Support\Dian\Facturador;
use App\Support\Dian\Impuestos;
use App\Support\TiendaActual;
use App\Support\Validar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Alquileres: el negocio pone en alquiler un servicio con fecha, hora, lugar, responsable y valor
class alquilerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('usar-alquileres') || $u->can('usar-pos')), 403);
            // Solo para negocios que manejan alquiler (se activa en Datos del negocio → Módulos)
            if (!TiendaActual::tienda()?->mod_alquiler) {
                return redirect('/')->with('info', 'Su negocio no tiene activado Alquiler. Si lo maneja, actívelo en Datos del negocio → "Módulos de su negocio".');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filtro = $request->get('ver', 'proximos');
        $q = trim((string) $request->get('q'));
        $lista = Alquiler::with(['venta', 'abonos.metodo'])
            ->when($filtro === 'proximos', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', '>=', today()))
            ->when($filtro === 'hoy', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', today()))
            ->when($filtro === 'pendientes', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', '<', today()))
            ->when($filtro === 'abonando', fn ($w) => $w->where('estado', 'programado')->where('abonado', '>', 0))
            ->when($filtro === 'alquilados', fn ($w) => $w->where('estado', '!=', 'cancelado')->where('entrega', 'alquilado'))
            ->when($filtro === 'realizados', fn ($w) => $w->where('estado', 'realizado'))
            ->when($filtro === 'cancelados', fn ($w) => $w->where('estado', 'cancelado'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('numero', 'like', "%$q%")->orWhere('que_alquila', 'like', "%$q%")->orWhere('lugar', 'like', "%$q%")->orWhere('responsable_telefono', 'like', "%$q%")))
            ->when(in_array($filtro, ['proximos', 'hoy'], true), fn ($w) => $w->orderBy('fecha')->orderBy('hora'), fn ($w) => $w->orderByDesc('fecha')->orderByDesc('hora'))
            ->paginate(30)->withQueryString();

        $prog = Alquiler::where('estado', 'programado');
        return view('alquiler.index', [
            'lista' => $lista, 'filtro' => $filtro, 'q' => $q,
            'kpi' => [
                'proximos' => (clone $prog)->whereDate('fecha', '>=', today())->count(),
                'valorProximos' => (float) (clone $prog)->whereDate('fecha', '>=', today())->sum('valor'),
                'hoy' => (clone $prog)->whereDate('fecha', today())->count(),
                'pendientes' => (clone $prog)->whereDate('fecha', '<', today())->count(),
                'abonando' => (clone $prog)->where('abonado', '>', 0)->count(),
                'saldoAbonando' => (float) (clone $prog)->where('abonado', '>', 0)->sum(DB::raw('GREATEST(valor - abonado, 0)')),
                'alquilados' => Alquiler::where('estado', '!=', 'cancelado')->where('entrega', 'alquilado')->count(),
                'vencidosSinDevolver' => Alquiler::where('estado', '!=', 'cancelado')->where('entrega', 'alquilado')->whereDate('fecha', '<', today())->count(),
                'mes' => (float) Alquiler::where('estado', 'realizado')->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month)->sum('valor'),
                'gananciaMes' => (float) Alquiler::where('estado', 'realizado')->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month)
                    ->sum(DB::raw('valor - CASE WHEN con_comision = 1 THEN comision ELSE 0 END')),
            ],
            'metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']),
            'cajaAbierta' => (bool) Caja::abiertaDe(Auth::id()),
            'ventaNueva' => session('alq_venta') ? PosVenta::find(session('alq_venta')) : null,
            'abonoNuevo' => session('alq_abono') ? AlquilerAbono::with('alquiler', 'metodo')->find(session('alq_abono')) : null,
            'negocio' => Negocio::datos(),
        ]);
    }

    public function create()
    {
        return view('alquiler.form', ['alquiler' => new Alquiler(['fecha' => today(), 'hora' => '08:00'])] + $this->datosPago());
    }

    public function store(Request $request)
    {
        $d = $this->validar($request);
        $pago = $this->validarPago($request, (float) $d['valor'], 0);
        $d['numero'] = Alquiler::siguienteNumero();
        $d['user_id'] = Auth::id();
        $d['estado'] = 'programado';
        $a = Alquiler::create($d);
        if ($pago) {
            [$a, $abono, $venta] = $this->registrarPago($a, $pago['caja'], $pago['metodo'], $pago['monto'], null, $a->numero);
            return $this->respuestaPago($a, $abono, $venta, 'Alquiler ' . $a->numero . ' creado. ');
        }
        return redirect()->route('alquileres.index', ['ver' => $a->fecha->lt(today()) ? 'pendientes' : 'proximos'])
            ->with('success', 'Alquiler ' . $a->numero . ' creado: ' . $a->que_alquila . ' el ' . $a->fecha->format('d/m/Y') . ', ' . $a->horario_texto . '. Queda sin pagar: cobre desde la lista cuando le paguen.');
    }

    // Dueño / administrador: puede editar (y cambiar el precio de) un alquiler ya cobrado
    public static function esAdmin(): bool
    {
        $u = Auth::user();
        return $u && ($u->es_superadmin || $u->hasRole('dueno') || $u->hasRole('administrador'));
    }

    public function edit(Alquiler $alquiler)
    {
        if ($alquiler->cobrado && !self::esAdmin()) return redirect()->route('alquileres.index')->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró (venta ' . $alquiler->venta?->numero . '); solo el administrador lo puede editar.');
        return view('alquiler.form', ['alquiler' => $alquiler] + $this->datosPago());
    }

    public function update(Request $request, Alquiler $alquiler)
    {
        if ($alquiler->cobrado && !self::esAdmin()) return redirect()->route('alquileres.index')->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró; solo el administrador lo puede editar.');
        $d = $this->validar($request);
        $pago = ($alquiler->cobrado || $alquiler->estado === 'cancelado') ? null : $this->validarPago($request, (float) $d['valor'], (float) $alquiler->abonado);
        $extra = '';

        if ($alquiler->cobrado) {
            $venta = $alquiler->venta;
            $cambiaPrecio = abs($d['valor'] - $alquiler->valor) > 0.001 || ($d['con_comision'] ? $d['comision'] : 0) != ($alquiler->con_comision ? $alquiler->comision : 0);
            if ($cambiaPrecio && $venta) {
                if ($venta->estado !== 'pagada') {
                    // La venta se anuló: el precio se cambia solo en el alquiler
                } elseif ($venta->tipo_doc !== 'tiquete') {
                    throw ValidationException::withMessages(['valor' => 'La venta ' . $venta->numero . ' salió como documento electrónico ante la DIAN y su precio no se puede cambiar. Anúlela en Ventas (sale nota crédito) y vuelva a cobrar el alquiler.']);
                } elseif ($alquiler->abonos()->exists()) {
                    throw ValidationException::withMessages(['valor' => 'Este alquiler se pagó con abonos; el precio ya no se puede cambiar. Si hubo un error, anule la venta ' . $venta->numero . ' en Ventas.']);
                } elseif ($venta->caja?->estado !== 'abierta') {
                    throw ValidationException::withMessages(['valor' => 'La caja donde se cobró (venta ' . $venta->numero . ') ya está cerrada; el precio no se puede cambiar.']);
                } else {
                    DB::transaction(function () use ($venta, $d) {
                        $tienda = Tienda::find(TiendaActual::id());
                        $item = $venta->items()->first();
                        $imp = Impuestos::calcular([['total' => (float) $d['valor'], 'impuesto' => $item?->impuesto ?? Facturador::impuestoPorDefecto($tienda)]], 0);
                        $li = $imp['lineas'][0];
                        $venta->update(['subtotal' => $d['valor'], 'total' => $d['valor'], 'base_gravable' => $imp['base'], 'impuestos' => $imp['impuestos'], 'impuestos_detalle' => $imp['detalle'] ?: null]);
                        $item?->update(['precio' => $d['valor'], 'subtotal' => $d['valor'], 'costo' => $d['con_comision'] ? $d['comision'] : 0,
                            'impuesto' => $li['impuesto'], 'iva_tasa' => $li['tasa'], 'base' => $li['base'], 'iva_valor' => $li['iva']]);
                        $venta->pagos()->first()?->update(['monto' => $d['valor']]);
                    });
                    $extra = ' La venta ' . $venta->numero . ' quedó por ' . dinero($d['valor']) . '.';
                }
            }
            $d['abonado'] = $d['valor'];
        } elseif ($d['valor'] + 0.001 < $alquiler->abonado) {
            throw ValidationException::withMessages(['valor' => 'Ya abonaron ' . dinero($alquiler->abonado) . '. El valor no puede quedar por debajo de eso.']);
        }
        $alquiler->update($d);
        if ($pago) {
            [$a, $abono, $venta] = $this->registrarPago($alquiler, $pago['caja'], $pago['metodo'], $pago['monto'], null, $alquiler->numero);
            return $this->respuestaPago($a, $abono, $venta, 'Alquiler ' . $a->numero . ' actualizado. ');
        }
        return redirect()->route('alquileres.index', ['ver' => $alquiler->estado === 'programado' ? ($alquiler->fecha->lt(today()) ? 'pendientes' : 'proximos') : $alquiler->estado . 's'])
            ->with('success', 'Alquiler ' . $alquiler->numero . ' actualizado.' . $extra);
    }

    public function estado(Request $request, Alquiler $alquiler)
    {
        $nuevo = $request->validate(['estado' => 'required|in:programado,cancelado', 'devolver' => 'nullable|boolean'])['estado'];
        if ($alquiler->cobrado) {
            return back()->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró con la venta ' . $alquiler->venta?->numero . '. Si hubo un error, anule esa venta en Ventas.');
        }
        $devolver = $nuevo === 'cancelado' && $request->boolean('devolver') && $alquiler->abonado > 0;
        $caja = Caja::abiertaDe(Auth::id());
        if ($devolver && !$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para devolver los abonos del alquiler ' . $alquiler->numero . '.');

        DB::transaction(function () use ($alquiler, $nuevo, $devolver, $caja) {
            $a = Alquiler::whereKey($alquiler->id)->lockForUpdate()->first();
            if ($devolver) {
                $metodo = MetodoPago::activos()->where('es_efectivo', true)->first() ?? MetodoPago::activos()->first();
                AlquilerAbono::create([
                    'alquiler_id' => $a->id, 'numero' => AlquilerAbono::siguienteNumero(), 'caja_id' => $caja->id, 'user_id' => Auth::id(),
                    'metodo_pago_id' => $metodo->id, 'monto' => -$a->abonado, 'saldo_despues' => $a->valor, 'nota' => 'Devolución por cancelación',
                ]);
                $a->abonado = 0;
            }
            $a->estado = $nuevo;
            $a->save();
        });
        if ($devolver) return back()->with('success', 'El alquiler ' . $alquiler->numero . ' quedó cancelado y se devolvieron ' . dinero($alquiler->abonado) . ' de su caja.');
        $txt = ['programado' => 'vuelve a quedar programado', 'realizado' => 'quedó como realizado', 'cancelado' => 'quedó cancelado'][$nuevo];
        return back()->with('success', 'El alquiler ' . $alquiler->numero . ' ' . $txt . '.');
    }

    // Pagó todo de una vez: queda como venta en la caja (con su factura) con el método con que pagaron.
    public function cobrar(Request $request, Alquiler $alquiler)
    {
        $d = $request->validate(['metodo_id' => 'required|integer', 'referencia' => 'nullable|string|max:60'], ['metodo_id.required' => 'Elija cómo le pagaron.']);
        $request->merge(['monto' => $alquiler->saldo]);
        return $this->abonar($request, $alquiler);
    }

    // Abono: el cliente paga una parte (o lo que falta). Cada abono entra a la caja.
    // Si con este pago queda pagado completo, se factura: el alquiler queda como venta y pasa a "Cobrado".
    public function abonar(Request $request, Alquiler $alquiler)
    {
        $d = $request->validate(['monto' => 'required|numeric|min:1', 'metodo_id' => 'required|integer', 'nota' => 'nullable|string|max:255', 'referencia' => 'nullable|string|max:60'],
            ['monto.required' => 'Escriba cuánto paga.', 'metodo_id.required' => 'Elija cómo le pagaron.']);
        if ($alquiler->cobrado) return back()->withErrors('Este alquiler ya está pagado y facturado.');
        if ($alquiler->estado === 'cancelado') return back()->withErrors('Este alquiler está cancelado.');
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para recibir el pago del alquiler ' . $alquiler->numero . '.');
        $metodo = MetodoPago::activos()->whereKey($d['metodo_id'])->first();
        if (!$metodo) return back()->withErrors('Ese método de pago no está disponible.');

        [$a, $abono, $venta] = $this->registrarPago($alquiler, $caja, $metodo, (float) $d['monto'], $d['nota'] ?? null, $d['referencia'] ?? null);
        return $this->respuestaPago($a, $abono, $venta);
    }

    // Registra un pago (completo o abono) en la caja. Si con él queda pagado, se factura.
    // Devuelve [alquiler, abono|null, venta|null]
    private function registrarPago(Alquiler $alquiler, Caja $caja, MetodoPago $metodo, float $monto, ?string $nota, ?string $referencia): array
    {
        return DB::transaction(function () use ($alquiler, $caja, $metodo, $monto, $nota, $referencia) {
            $a = Alquiler::whereKey($alquiler->id)->lockForUpdate()->first();
            if ($a->pos_venta_id) return [$a, null, PosVenta::find($a->pos_venta_id)];
            $monto = round($monto, 2);
            if ($monto > $a->saldo + 0.001) {
                throw ValidationException::withMessages(['monto' => 'Solo falta ' . dinero($a->saldo) . '. No puede pagar más que eso.']);
            }

            // Paga todo de una sola vez y sin abonos antes: venta normal con el método real
            if ($a->abonado <= 0 && $monto + 0.001 >= $a->valor) {
                $a->abonado = $a->valor;
                $venta = $this->facturar($a, $caja, $metodo, $referencia ?: $a->numero);
                return [$a, null, $venta];
            }

            $a->abonado = round($a->abonado + $monto, 2);
            $a->save();
            $abono = AlquilerAbono::create([
                'alquiler_id' => $a->id, 'numero' => AlquilerAbono::siguienteNumero(), 'caja_id' => $caja->id, 'user_id' => Auth::id(),
                'metodo_pago_id' => $metodo->id, 'monto' => $monto, 'saldo_despues' => $a->saldo, 'nota' => $nota,
            ]);
            // Con este abono quedó pagado: se factura (el dinero ya entró con los abonos)
            $venta = $a->pagado ? $this->facturar($a, $caja, MetodoPago::pagadoConAbonos(), $a->numero) : null;
            return [$a, $abono, $venta];
        });
    }

    private function respuestaPago(Alquiler $a, ?AlquilerAbono $abono, ?PosVenta $venta, string $antes = '')
    {
        $r = redirect()->route('alquileres.index', ['ver' => $venta ? 'realizados' : 'abonando']);
        if ($venta) {
            return $r->with('success', $antes . 'Alquiler ' . $a->numero . ' pagado completo' . ($abono ? ' con el abono ' . $abono->numero : '') . ': quedó como la venta ' . $venta->numero . ' en su caja.'
                . ($a->con_comision ? ' Ganancia del local: ' . dinero($a->ganancia) . ' (comisión ' . dinero($a->comision) . ').' : ''))
                ->with('alq_venta', $venta->id);
        }
        return $r->with('success', $antes . 'Abono ' . $abono->numero . ' de ' . dinero($abono->monto) . ' al alquiler ' . $a->numero . '. Lleva ' . dinero($a->abonado) . ', le falta ' . dinero($a->saldo) . '.')
            ->with('alq_abono', $abono->id);
    }

    // Formulario: métodos de pago y si hay caja abierta para recibir el pago ahí mismo
    private function datosPago(): array
    {
        return ['metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']), 'cajaAbierta' => (bool) Caja::abiertaDe(Auth::id())];
    }

    // Lo que eligió en "¿Cómo paga?": después, completo o abono. Devuelve null si paga después.
    private function validarPago(Request $request, float $valor, float $yaAbonado): ?array
    {
        $d = $request->validate([
            'pago' => 'nullable|in:despues,completo,abono',
            'pago_monto' => 'nullable|required_if:pago,abono|numeric|min:1',
            'pago_metodo' => 'nullable|required_if:pago,completo,abono|integer',
        ], [
            'pago_monto.required_if' => 'Escriba cuánto abona.',
            'pago_metodo.required_if' => 'Elija cómo le pagaron.',
        ]);
        $tipo = $d['pago'] ?? 'despues';
        if ($tipo === 'despues') return null;
        $falta = round($valor - $yaAbonado, 2);
        if ($falta <= 0) return null;
        $monto = $tipo === 'completo' ? $falta : round((float) $d['pago_monto'], 2);
        if ($monto > $falta + 0.001) {
            throw ValidationException::withMessages(['pago_monto' => 'El abono no puede ser mayor a lo que falta (' . dinero($falta) . '). Si paga todo, elija "Paga completo".']);
        }
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) throw ValidationException::withMessages(['pago' => 'Para recibir el pago primero abra su caja (Mi caja → Abrir caja), o elija "Paga después".']);
        $metodo = MetodoPago::activos()->whereKey($d['pago_metodo'])->first();
        if (!$metodo) throw ValidationException::withMessages(['pago_metodo' => 'Ese método de pago no está disponible.']);
        return ['caja' => $caja, 'metodo' => $metodo, 'monto' => $monto];
    }

    // Crea la venta (factura / tiquete) del alquiler. El costo es la comisión: ganancia = valor − comisión.
    private function facturar(Alquiler $a, Caja $caja, MetodoPago $metodo, string $referencia): PosVenta
    {
        $tienda = Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
        $tipoDoc = Facturador::tipoDocumento($tienda, false);
        Facturador::asegurarNumeracion($tienda, $tipoDoc);
        $impuesto = Facturador::impuestoPorDefecto($tienda);
        $imp = Impuestos::calcular([['total' => (float) $a->valor, 'impuesto' => $impuesto]], 0);
        $li = $imp['lineas'][0];

        $venta = PosVenta::create([
            'numero' => $tienda->siguienteNumeroPos(),
            'tipo_doc' => $tipoDoc,
            'base_gravable' => $imp['base'],
            'impuestos' => $imp['impuestos'],
            'impuestos_detalle' => $imp['detalle'] ?: null,
            'adquiriente' => $tipoDoc !== 'tiquete' ? Facturador::adquiriente(null) : null,
            'caja_id' => $caja->id,
            'user_id' => Auth::id(),
            'cliente_nombre' => 'Alquiler ' . $a->numero,
            'cliente_telefono' => $a->responsable_telefono,
            'subtotal' => $a->valor,
            'descuento' => 0,
            'total' => $a->valor,
            'cambio' => 0,
            'estado' => 'pagada',
        ]);
        $venta->items()->create([
            'descripcion' => mb_substr('Alquiler ' . $a->numero . ': ' . $a->que_alquila . ' · ' . $a->fecha->format('d/m/Y') . ' ' . str_replace([' am', ' pm'], ['am', 'pm'], $a->horario_texto), 0, 120),
            'cantidad' => 1,
            'precio' => $a->valor,
            'costo' => $a->con_comision ? $a->comision : 0,
            'subtotal' => $a->valor,
            'impuesto' => $li['impuesto'], 'iva_tasa' => $li['tasa'], 'base' => $li['base'], 'iva_valor' => $li['iva'],
        ]);
        $venta->pagos()->create(['metodo_pago_id' => $metodo->id, 'monto' => $a->valor, 'referencia' => $referencia]);
        Facturador::enviarDespues(Facturador::registrarVenta($venta));

        $a->update(['estado' => 'realizado', 'pos_venta_id' => $venta->id, 'cobrado_en' => now(), 'abonado' => $a->valor]);
        return $venta;
    }

    // Entrega y devolución de lo alquilado: por entregar → alquilado (lo tiene el cliente) → devuelto (en orden o con novedad)
    public function entrega(Request $request, Alquiler $alquiler)
    {
        $d = $request->validate([
            'accion' => 'required|in:entregar,devolver,deshacer',
            'devolucion_estado' => 'nullable|required_if:accion,devolver|in:en_orden,con_novedad',
            'devolucion_nota' => 'nullable|required_if:devolucion_estado,con_novedad|string|max:255',
        ], ['devolucion_nota.required_if' => 'Escriba qué novedad tuvo (daño, faltante…).']);
        if ($alquiler->estado === 'cancelado') return back()->withErrors('Este alquiler está cancelado.');

        if ($d['accion'] === 'entregar') {
            $alquiler->update(['entrega' => 'alquilado', 'entregado_en' => now(), 'devuelto_en' => null, 'devolucion_estado' => null, 'devolucion_nota' => null]);
            $msg = 'Alquiler ' . $alquiler->numero . ': quedó en manos del cliente (alquilado).' . ($alquiler->pagado ? '' : ' Ojo: aún le falta pagar ' . dinero($alquiler->saldo) . '.');
        } elseif ($d['accion'] === 'devolver') {
            $alquiler->update(['entrega' => 'devuelto', 'devuelto_en' => now(), 'entregado_en' => $alquiler->entregado_en ?? now(), 'devolucion_estado' => $d['devolucion_estado'], 'devolucion_nota' => $d['devolucion_nota'] ?? null]);
            $msg = 'Alquiler ' . $alquiler->numero . ': ' . ($d['devolucion_estado'] === 'en_orden' ? 'devuelto en orden.' : 'devuelto con novedad.') . ($alquiler->pagado ? '' : ' Aún le falta pagar ' . dinero($alquiler->saldo) . '.');
        } else {
            $alquiler->update(['entrega' => 'pendiente', 'entregado_en' => null, 'devuelto_en' => null, 'devolucion_estado' => null, 'devolucion_nota' => null]);
            $msg = 'Alquiler ' . $alquiler->numero . ' vuelve a quedar por entregar.';
        }
        return back()->with('success', $msg);
    }

    public function destroy(Alquiler $alquiler)
    {
        if ($alquiler->cobrado) return back()->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró; no se puede eliminar. Si hubo un error, anule la venta ' . $alquiler->venta?->numero . '.');
        if ($alquiler->abonos()->exists()) return back()->withErrors('El alquiler ' . $alquiler->numero . ' tiene abonos; no se puede eliminar. Cancélelo y devuelva los abonos si es necesario.');
        $num = $alquiler->numero;
        $alquiler->delete();
        return redirect()->route('alquileres.index')->with('success', 'Alquiler ' . $num . ' eliminado.');
    }

    private function validar(Request $request): array
    {
        $d = $request->validate([
            'que_alquila' => 'required|string|min:2|max:120',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'lugar' => 'required|string|min:2|max:150',
            'responsable_telefono' => ['required', 'string', 'max:20', Validar::celular()],
            'con_encargado' => 'nullable|boolean',
            'encargado_nombre' => 'nullable|required_if:con_encargado,1|string|min:2|max:80',
            'encargado_telefono' => ['nullable', 'string', 'max:20', Validar::celular()],
            'nota' => 'nullable|string|max:255',
            'valor' => 'required|numeric|min:0|max:999999999999',
            'con_comision' => 'nullable|boolean',
            'comision' => 'nullable|required_if:con_comision,1|numeric|min:0|lte:valor',
        ], [
            'comision.required_if' => 'Escriba el valor de la comisión.',
            'comision.lte' => 'La comisión no puede ser mayor que el valor del alquiler.',
            'que_alquila.required' => 'Escriba qué se alquila.',
            'fecha.required' => 'Elija la fecha del alquiler.',
            'hora.required' => 'Elija la hora de inicio del servicio.',
            'lugar.required' => 'Escriba el lugar.',
            'responsable_telefono.required' => 'Escriba el celular de quien contrata.',
            'encargado_nombre.required_if' => 'Escriba el nombre del encargado de su negocio, o apague "Encargado de nuestro lado".',
            'valor.required' => 'Escriba el valor del alquiler.',
        ]);
        $d['responsable_telefono'] = Validar::limpiarTel($d['responsable_telefono']);
        // Encargado de nuestro lado (opcional): quien presta el servicio
        if ($request->boolean('con_encargado')) {
            $d['encargado_nombre'] = trim((string) ($d['encargado_nombre'] ?? '')) ?: null;
            $d['encargado_telefono'] = !empty($d['encargado_telefono']) ? Validar::limpiarTel($d['encargado_telefono']) : null;
        } else {
            $d['encargado_nombre'] = null;
            $d['encargado_telefono'] = null;
        }
        unset($d['con_encargado']);
        $d['con_comision'] = $request->boolean('con_comision');
        $d['comision'] = $d['con_comision'] ? (float) ($d['comision'] ?? 0) : 0;
        return $d;
    }
}
