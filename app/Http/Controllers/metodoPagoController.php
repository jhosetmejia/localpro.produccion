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
        $metodos = MetodoPago::orderBy('orden')->orderBy('nombre')->get();
        return view('metodo-pago.index', compact('metodos'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:40|unique:metodos_pago,nombre',
            'orden' => 'nullable|integer|min:0|max:999',
        ]);
        MetodoPago::create([
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
            'nombre' => ['required', 'string', 'max:40', Rule::unique('metodos_pago', 'nombre')->ignore($metodo->id)],
            'orden' => 'nullable|integer|min:0|max:999',
        ]);
        $metodo->update([
            'nombre' => $datos['nombre'],
            'orden' => $datos['orden'] ?? 0,
            'es_efectivo' => $request->boolean('es_efectivo'),
            'activo' => $request->boolean('activo'),
        ]);
        return back()->with('success', 'Método de pago actualizado.');
    }
}
