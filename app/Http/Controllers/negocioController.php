<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Datos del negocio y logo que salen en el tiquete y la factura
class negocioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:editar-negocio');
    }

    public function edit()
    {
        return view('negocio.edit', ['negocio' => Negocio::datos(), 'paises' => \App\Support\Paises::LISTA, 'tipos' => \App\Models\Tienda::TIPOS]);
    }

    public function update(Request $request)
    {
        $negocio = Negocio::datos();
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
            'pais' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Paises::LISTA))],
            'tipo_negocio' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Tienda::TIPOS))],
            'nit' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:120',
            'ciudad' => 'nullable|string|max:60',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'mensaje_tiquete' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($negocio->logo_path) {
                Storage::disk('public')->delete($negocio->logo_path);
            }
            $datos['logo_path'] = $request->file('logo')->store('negocio', 'public');
        }
        if ($request->boolean('quitar_logo') && $negocio->logo_path) {
            Storage::disk('public')->delete($negocio->logo_path);
            $datos['logo_path'] = null;
        }
        unset($datos['logo']);
        $datos['usa_tallas'] = \App\Models\Tienda::TIPOS[$datos['tipo_negocio']][2];

        $negocio->update($datos);
        return back()->with('success', 'Datos del negocio guardados.');
    }
}
