<?php

namespace App\Http\Controllers;

use App\Support\CrearTienda;
use App\Support\Paises;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// Paso 1 del registro: datos del dueño. Crea su tienda y lo lleva al asistente de configuración.
class registroController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('panel');
        }
        return view('auth.registro', ['paises' => Paises::LISTA]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:120|unique:users,email',
            'telefono' => 'required|string|max:20',
            'pais' => ['required', Rule::in(array_keys(Paises::LISTA))],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Escriba su nombre.',
            'email.required' => 'Escriba su correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo. Intente ingresar.',
            'telefono.required' => 'Escriba su número de celular o WhatsApp.',
            'password.required' => 'Escriba una contraseña.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $primerNombre = strtok(trim($datos['name']), ' ');
        [, $user] = CrearTienda::crear(
            ['nombre' => 'Tienda de ' . $primerNombre, 'pais' => $datos['pais'], 'telefono' => $datos['telefono']],
            $datos
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('bienvenida.index');
    }
}
