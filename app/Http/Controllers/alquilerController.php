<?php

namespace App\Http\Controllers;

use App\Models\Alquiler;
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

// Alquileres: el negocio pone en alquiler un servicio con fecha, hora, lugar, responsable y valor
class alquilerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('usar-alquileres') || $u->can('usar-pos')), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filtro = $request->get('ver', 'proximos');
        $q = trim((string) $request->get('q'));
        $lista = Alquiler::with('venta')
            ->when($filtro === 'proximos', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', '>=', today()))
            ->when($filtro === 'hoy', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', today()))
            ->when($filtro === 'pendientes', fn ($w) => $w->where('estado', 'programado')->whereDate('fecha', '<', today()))
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
                'mes' => (float) Alquiler::where('estado', 'realizado')->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month)->sum('valor'),
                'gananciaMes' => (float) Alquiler::where('estado', 'realizado')->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month)
                    ->sum(DB::raw('valor - CASE WHEN con_comision = 1 THEN comision ELSE 0 END')),
            ],
            'metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']),
            'cajaAbierta' => (bool) Caja::abiertaDe(Auth::id()),
            'ventaNueva' => session('alq_venta') ? PosVenta::find(session('alq_venta')) : null,
            'negocio' => Negocio::datos(),
        ]);
    }

    public function create()
    {
        return view('alquiler.form', ['alquiler' => new Alquiler(['fecha' => today(), 'hora' => '08:00'])]);
    }

    public function store(Request $request)
    {
        $d = $this->validar($request);
        $d['numero'] = Alquiler::siguienteNumero();
        $d['user_id'] = Auth::id();
        $d['estado'] = 'programado';
        $a = Alquiler::create($d);
        return redirect()->route('alquileres.index', ['ver' => $a->fecha->lt(today()) ? 'pendientes' : 'proximos'])
            ->with('success', 'Alquiler ' . $a->numero . ' creado: ' . $a->que_alquila . ' el ' . $a->fecha->format('d/m/Y') . ' a las ' . $a->hora_texto . '.');
    }

    public function edit(Alquiler $alquiler)
    {
        if ($alquiler->cobrado) return redirect()->route('alquileres.index')->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró (venta ' . $alquiler->venta?->numero . '); no se puede editar.');
        return view('alquiler.form', ['alquiler' => $alquiler]);
    }

    public function update(Request $request, Alquiler $alquiler)
    {
        if ($alquiler->cobrado) return redirect()->route('alquileres.index')->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró; no se puede editar.');
        $alquiler->update($this->validar($request));
        return redirect()->route('alquileres.index', ['ver' => $alquiler->estado === 'programado' ? ($alquiler->fecha->lt(today()) ? 'pendientes' : 'proximos') : $alquiler->estado . 's'])
            ->with('success', 'Alquiler ' . $alquiler->numero . ' actualizado.');
    }

    public function estado(Request $request, Alquiler $alquiler)
    {
        $nuevo = $request->validate(['estado' => 'required|in:programado,cancelado'])['estado'];
        if ($alquiler->cobrado) {
            return back()->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró con la venta ' . $alquiler->venta?->numero . '. Si hubo un error, anule esa venta en Ventas.');
        }
        $alquiler->update(['estado' => $nuevo]);
        $txt = ['programado' => 'vuelve a quedar programado', 'realizado' => 'quedó como realizado', 'cancelado' => 'quedó cancelado'][$nuevo];
        return back()->with('success', 'El alquiler ' . $alquiler->numero . ' ' . $txt . '.');
    }

    // Cobrar: queda registrado como venta en la caja (con su factura / tiquete) y el alquiler pasa a realizado.
    // El costo de la venta es la comisión, así la ganancia del local = valor del alquiler − comisión.
    public function cobrar(Request $request, Alquiler $alquiler)
    {
        $d = $request->validate(['metodo_id' => 'required|integer', 'referencia' => 'nullable|string|max:60'], ['metodo_id.required' => 'Elija cómo le pagaron.']);
        if ($alquiler->cobrado) return back()->withErrors('Este alquiler ya se cobró.');
        if ($alquiler->estado === 'cancelado') return back()->withErrors('Este alquiler está cancelado.');
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) return redirect()->route('cajas.abrir')->with('info', 'Abra su caja para cobrar el alquiler ' . $alquiler->numero . '.');
        $metodo = MetodoPago::activos()->whereKey($d['metodo_id'])->first();
        if (!$metodo) return back()->withErrors('Ese método de pago no está disponible.');

        $venta = DB::transaction(function () use ($alquiler, $caja, $metodo, $d) {
            $a = Alquiler::whereKey($alquiler->id)->lockForUpdate()->first();
            if ($a->pos_venta_id) return PosVenta::find($a->pos_venta_id);

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
                'descripcion' => mb_substr('Alquiler ' . $a->numero . ': ' . $a->que_alquila, 0, 120),
                'cantidad' => 1,
                'precio' => $a->valor,
                'costo' => $a->con_comision ? $a->comision : 0,
                'subtotal' => $a->valor,
                'impuesto' => $li['impuesto'], 'iva_tasa' => $li['tasa'], 'base' => $li['base'], 'iva_valor' => $li['iva'],
            ]);
            $venta->pagos()->create(['metodo_pago_id' => $metodo->id, 'monto' => $a->valor, 'referencia' => $d['referencia'] ?? $a->numero]);
            Facturador::enviarDespues(Facturador::registrarVenta($venta));

            $a->update(['estado' => 'realizado', 'pos_venta_id' => $venta->id, 'cobrado_en' => now()]);
            return $venta;
        });

        return redirect()->route('alquileres.index', ['ver' => 'realizados'])
            ->with('success', 'Alquiler ' . $alquiler->numero . ' cobrado: quedó como la venta ' . $venta->numero . ' en su caja.' . ($alquiler->con_comision ? ' Ganancia del local: ' . dinero($alquiler->ganancia) . ' (comisión ' . dinero($alquiler->comision) . ').' : ''))
            ->with('alq_venta', $venta->id);
    }

    public function destroy(Alquiler $alquiler)
    {
        if ($alquiler->cobrado) return back()->withErrors('El alquiler ' . $alquiler->numero . ' ya se cobró; no se puede eliminar. Si hubo un error, anule la venta ' . $alquiler->venta?->numero . '.');
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
            'lugar' => 'required|string|min:2|max:150',
            'responsable_telefono' => ['required', 'string', 'max:20', Validar::celular()],
            'nota' => 'nullable|string|max:255',
            'valor' => 'required|numeric|min:0|max:999999999999',
            'con_comision' => 'nullable|boolean',
            'comision' => 'nullable|required_if:con_comision,1|numeric|min:0|lte:valor',
        ], [
            'comision.required_if' => 'Escriba el valor de la comisión.',
            'comision.lte' => 'La comisión no puede ser mayor que el valor del alquiler.',
            'que_alquila.required' => 'Escriba qué se alquila.',
            'fecha.required' => 'Elija la fecha del alquiler.',
            'hora.required' => 'Elija la hora del alquiler.',
            'lugar.required' => 'Escriba el lugar.',
            'responsable_telefono.required' => 'Escriba el número del responsable.',
            'valor.required' => 'Escriba el valor del alquiler.',
        ]);
        $d['responsable_telefono'] = Validar::limpiarTel($d['responsable_telefono']);
        $d['con_comision'] = $request->boolean('con_comision');
        $d['comision'] = $d['con_comision'] ? (float) ($d['comision'] ?? 0) : 0;
        return $d;
    }
}
