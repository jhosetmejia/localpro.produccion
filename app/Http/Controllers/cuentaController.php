<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// "Mi cuenta": cualquier usuario (dueño, vendedor, asesor o administrador) cambia su nombre, teléfono y clave
class cuentaController extends Controller
{
    public function edit()
    {
        return view('cuenta.edit', ['u' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $datos = $request->validate([
            'name' => 'required|string|max:80',
            'telefono' => 'nullable|string|max:20',
            'clave_actual' => 'nullable|required_with:clave|string',
            'clave' => 'nullable|string|min:8|confirmed',
        ], [
            'clave_actual.required_with' => 'Escriba su clave actual para poder cambiarla.',
            'clave.min' => 'La clave nueva debe tener mínimo 8 caracteres.',
            'clave.confirmed' => 'Las claves nuevas no coinciden.',
        ]);

        if (!empty($datos['clave'])) {
            if (!Hash::check($datos['clave_actual'], $u->password)) {
                return back()->withErrors(['clave_actual' => 'La clave actual no es correcta.'])->withInput();
            }
            $u->password = Hash::make($datos['clave']);
        }
        $u->name = $datos['name'];
        $u->telefono = $datos['telefono'] ?? null;
        $u->save();

        return back()->with('success', !empty($datos['clave']) ? 'Datos y clave actualizados.' : 'Datos actualizados.');
    }
}
