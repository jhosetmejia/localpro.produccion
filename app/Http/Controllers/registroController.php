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
        return view('auth.registro', ['paises' => Paises::LISTA, 'codigoAsesor' => session('codigo_asesor', request()->cookie('lp_asesor'))]);
    }

    // Enlace del asesor: guarda su código (60 días) y abre el registro
    public function referido(string $codigo)
    {
        $asesor = \App\Models\Asesor::where('codigo', strtoupper($codigo))->where('activo', true)->first();
        if (!$asesor) {
            return redirect()->route('registro');
        }
        session(['codigo_asesor' => $asesor->codigo]);
        return redirect()->route('registro')->withCookie(cookie('lp_asesor', $asesor->codigo, 60 * 24 * 60));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:120|unique:users,email',
            'telefono' => 'required|string|max:20',
            'pais' => ['required', Rule::in(array_keys(Paises::LISTA))],
            'password' => 'required|string|min:8|confirmed',
            'codigo_asesor' => 'nullable|string|max:20',
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

        // Si llegó por un asesor (enlace o código), el negocio queda en su red
        $codigo = strtoupper(trim((string) ($datos['codigo_asesor'] ?? '') ?: (string) session('codigo_asesor', $request->cookie('lp_asesor'))));
        if ($codigo !== '' && ($asesor = \App\Models\Asesor::where('codigo', $codigo)->where('activo', true)->first())) {
            $user->tienda->forceFill(['asesor_id' => $asesor->id])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('bienvenida.index');
    }
}
