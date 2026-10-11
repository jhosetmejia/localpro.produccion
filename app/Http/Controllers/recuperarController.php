<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

// "¿Olvidó su contraseña?": enlace por correo para crear una nueva
class recuperarController extends Controller
{
    public function formulario()
    {
        return view('auth.recuperar');
    }

    public function enviar(Request $request)
    {
        $request->validate(['email' => 'required|email'], ['email.required' => 'Escriba su correo.', 'email.email' => 'El correo no es válido.']);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de recuperación: ' . $e->getMessage());
            return back()->withInput()->withErrors('No pudimos enviar el correo en este momento. Escríbale al administrador de LocalPro para que le ayude.');
        }

        // Mismo mensaje exista o no la cuenta, para no revelar qué correos están registrados
        return back()->with('ok', 'Si ese correo tiene una cuenta, le enviamos un enlace para crear una contraseña nueva. Revise también la carpeta de spam.');
    }

    public function formularioNueva(Request $request, string $token)
    {
        return view('auth.restablecer', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function guardarNueva(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('registro_ok', 'Listo, ya puede ingresar con su nueva contraseña.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors('El enlace no es válido o ya venció. Pida uno nuevo.');
    }
}
