<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// El negocio crea y edita las formas de pago que acepta
class metodoPagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-metodo-pago', ['only' => ['index']]);
        $this->middleware('permission:crear-metodo-pago', ['only' => ['store']]);
        $this->middleware('permission:editar-metodo-pago', ['only' => ['update']]);
    }

    public function index()
    {
        $metodos = MetodoPago::when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'interno'), fn ($q) => $q->where('interno', false))->orderBy('orden')->orderBy('nombre')->get();
        return view('metodo-pago.index', compact('metodos'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('metodos_pago', 'nombre')->where('tienda_id', \App\Support\TiendaActual::id())],
            'orden' => 'nullable|integer|min:0|max:999',
            'moneda' => 'nullable|in:VES',
        ]);
        MetodoPago::create([
            'moneda' => self::moneda($request),
            'nombre' => $datos['nombre'],
            'orden' => $datos['orden'] ?? 0,
            'es_efectivo' => $request->boolean('es_efectivo'),
            'activo' => true,
        ]);
        return back()->with('success', 'Método de pago creado.');
    }

    public function update(Request $request, MetodoPago $metodo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('metodos_pago', 'nombre')->where('tienda_id', \App\Support\TiendaActual::id())->ignore($metodo->id)],
            'orden' => 'nullable|integer|min:0|max:999',
            'medio_dian' => ['nullable', Rule::in(array_keys(\App\Support\Dian\Catalogos::MEDIOS_PAGO))],
            'moneda' => 'nullable|in:VES',
        ]);
        $metodo->update([
            'moneda' => self::moneda($request, $metodo),
            'nombre' => $datos['nombre'],
            'medio_dian' => $datos['medio_dian'] ?? $metodo->medio_dian,
            'orden' => $datos['orden'] ?? 0,
            'es_efectivo' => $request->boolean('es_efectivo'),
            'activo' => $request->boolean('activo'),
        ]);
        return back()->with('success', 'Método de pago actualizado.');
    }

    // Solo las tiendas de Venezuela tienen métodos en bolívares
    private static function moneda(Request $request, ?MetodoPago $m = null): ?string
    {
        if (\App\Support\TiendaActual::tienda()?->pais !== 'VE') return $m?->moneda;
        return $request->input('moneda') === 'VES' ? 'VES' : null;
    }
}
