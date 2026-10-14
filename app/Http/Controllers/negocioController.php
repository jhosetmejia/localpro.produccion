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
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', \Illuminate\Validation\Rule::unique('tiendas', 'slug')->ignore($negocio->id)],
            'catalogo_activo' => 'nullable|boolean',
            'mod_medida' => 'nullable|boolean',
            'mod_vencimiento' => 'nullable|boolean',
            'mod_recetas' => 'nullable|boolean',
            'mod_alquiler' => 'nullable|boolean',
            'pais' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Paises::LISTA))],
            'tipo_negocio' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Tienda::TIPOS))],
            'nit' => 'nullable|string|max:30',
            'regimen' => 'nullable|string|max:40',
            'resolucion_numero' => 'nullable|string|max:40',
            'resolucion_fecha' => 'nullable|date',
            'resolucion_vigencia' => 'nullable|date',
            'resolucion_prefijo' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'resolucion_desde' => 'nullable|integer|min:1',
            'resolucion_hasta' => 'nullable|integer|min:1|gte:resolucion_desde',
            'direccion' => 'nullable|string|max:120',

            'telefono' => ['nullable', 'string', 'max:30', \App\Support\Validar::celular($request->input('pais'))],
            'email' => 'nullable|email|max:120',
            'mensaje_tiquete' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ] + \App\Support\Ubicaciones::reglas($request->input('pais'), $request->input('departamento')), \App\Support\Ubicaciones::mensajes());

        if (array_key_exists('telefono', $datos)) {
            $datos['telefono'] = \App\Support\Validar::limpiarTel($datos['telefono'], $request->input('pais') ?: null) ?: null;
        }

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
        $datos['catalogo_activo'] = $request->boolean('catalogo_activo');
        $datos['cliente_obligatorio'] = $request->boolean('cliente_obligatorio');
        foreach (['mod_medida', 'mod_vencimiento', 'mod_recetas', 'mod_alquiler'] as $m) {
            $datos[$m] = $request->boolean($m);
        }
        $datos['usa_tallas'] = \App\Models\Tienda::TIPOS[$datos['tipo_negocio']][2];

        $negocio->update($datos);
        return back()->with('success', 'Datos del negocio guardados.');
    }
}
