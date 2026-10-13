<?php

namespace App\Http\Controllers;

use App\Models\Alquiler;
use App\Support\Validar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $lista = Alquiler::query()
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
            ],
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
        return view('alquiler.form', ['alquiler' => $alquiler]);
    }

    public function update(Request $request, Alquiler $alquiler)
    {
        $alquiler->update($this->validar($request));
        return redirect()->route('alquileres.index', ['ver' => $alquiler->estado === 'programado' ? ($alquiler->fecha->lt(today()) ? 'pendientes' : 'proximos') : $alquiler->estado . 's'])
            ->with('success', 'Alquiler ' . $alquiler->numero . ' actualizado.');
    }

    public function estado(Request $request, Alquiler $alquiler)
    {
        $nuevo = $request->validate(['estado' => 'required|in:programado,realizado,cancelado'])['estado'];
        $alquiler->update(['estado' => $nuevo]);
        $txt = ['programado' => 'vuelve a quedar programado', 'realizado' => 'quedó como realizado', 'cancelado' => 'quedó cancelado'][$nuevo];
        return back()->with('success', 'El alquiler ' . $alquiler->numero . ' ' . $txt . '.');
    }

    public function destroy(Alquiler $alquiler)
    {
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
        ], [
            'que_alquila.required' => 'Escriba qué se alquila.',
            'fecha.required' => 'Elija la fecha del alquiler.',
            'hora.required' => 'Elija la hora del alquiler.',
            'lugar.required' => 'Escriba el lugar.',
            'responsable_telefono.required' => 'Escriba el número del responsable.',
            'valor.required' => 'Escriba el valor del alquiler.',
        ]);
        $d['responsable_telefono'] = Validar::limpiarTel($d['responsable_telefono']);
        return $d;
    }
}
