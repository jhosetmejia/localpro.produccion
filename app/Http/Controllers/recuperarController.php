<?php

namespace App\Http\Controllers;

use App\Mail\CodigoRecuperarMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// "¿Olvidó su contraseña?": código de 6 números al correo para crear una nueva
class recuperarController extends Controller
{
    const MINUTOS = 15;     // vigencia del código
    const INTENTOS = 5;     // intentos fallidos antes de anular el código
    const ESPERA = 60;      // segundos entre un envío y otro

    public function formulario()
    {
        return view('auth.recuperar');
    }

    public function enviar(Request $request)
    {
        $request->validate(['email' => 'required|email'], ['email.required' => 'Escriba su correo.', 'email.email' => 'El correo no es válido.']);
        $email = Str::lower(trim($request->email));

        $actual = DB::table('password_resets')->where('email', $email)->first();
        if ($actual && $actual->created_at && Carbon::parse($actual->created_at)->diffInSeconds(now()) < self::ESPERA) {
            session(['recuperar_email' => $email]);
            return redirect()->route('password.codigo')->with('ok', 'Ya le enviamos un código hace un momento. Revise su correo (también spam) o espere un minuto para pedir otro.');
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $codigo = (string) random_int(100000, 999999);
            DB::table('password_resets')->where('email', $email)->delete();
            DB::table('password_resets')->insert(['email' => $email, 'token' => Hash::make($codigo), 'created_at' => now()]);
            Cache::forget('recuperar_intentos:' . $email);

            try {
                Mail::to($user->email)->send(new CodigoRecuperarMail($user, $codigo, self::MINUTOS));
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar el código de recuperación: ' . $e->getMessage());
                DB::table('password_resets')->where('email', $email)->delete();
                return back()->withInput()->withErrors('No pudimos enviar el correo en este momento. Escríbale al administrador de LocalPro para que le ayude.');
            }
        }

        // Mismo mensaje exista o no la cuenta, para no revelar qué correos están registrados
        session(['recuperar_email' => $email]);
        return redirect()->route('password.codigo')->with('ok', 'Si ese correo tiene una cuenta, le enviamos un código de 6 números. Revise también la carpeta de spam.');
    }

    public function formularioCodigo(Request $request)
    {
        // Desde el botón del correo llega ?email= (por si lo abre en otro celular o navegador)
        if (filter_var($request->query('email'), FILTER_VALIDATE_EMAIL)) {
            session(['recuperar_email' => Str::lower(trim($request->query('email')))]);
        }
        $email = session('recuperar_email');
        if (!$email) {
            return redirect()->route('password.request');
        }
        return view('auth.restablecer', ['email' => $email, 'minutos' => self::MINUTOS]);
    }

    public function guardarNueva(Request $request)
    {
        $request->merge(['codigo' => preg_replace('/\D/', '', (string) $request->codigo)]);
        $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ], [
            'codigo.required' => 'Escriba el código que le llegó al correo.',
            'codigo.digits' => 'El código tiene 6 números.',
            'password.required' => 'Escriba su contraseña nueva.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $email = Str::lower(trim($request->email));
        session(['recuperar_email' => $email]);
        $fila = DB::table('password_resets')->where('email', $email)->first();
        $llave = 'recuperar_intentos:' . $email;

        if (!$fila || !$fila->created_at || Carbon::parse($fila->created_at)->addMinutes(self::MINUTOS)->isPast()) {
            return back()->withErrors('El código ya venció o no existe. Pida uno nuevo.');
        }

        if (!Hash::check($request->codigo, $fila->token)) {
            $intentos = Cache::increment($llave);
            Cache::put($llave, $intentos, now()->addMinutes(self::MINUTOS));
            if ($intentos >= self::INTENTOS) {
                DB::table('password_resets')->where('email', $email)->delete();
                Cache::forget($llave);
                return back()->withErrors('Escribió mal el código varias veces y se anuló por seguridad. Pida uno nuevo.');
            }
            return back()->withErrors('El código no es correcto. Le quedan ' . (self::INTENTOS - $intentos) . ' intentos.');
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            return back()->withErrors('El código ya venció o no existe. Pida uno nuevo.');
        }

        $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
        event(new PasswordReset($user));
        DB::table('password_resets')->where('email', $email)->delete();
        Cache::forget($llave);
        session()->forget('recuperar_email');

        return redirect()->route('login')->withInput(['email' => $user->email])
            ->with('registro_ok', 'Listo, ya puede ingresar con su nueva contraseña.');
    }

    // Enlaces viejos de recuperación (antes se mandaba enlace): ahora se pide un código
    public function enlaceViejo()
    {
        return redirect()->route('password.request')->withErrors('Ahora la contraseña se recupera con un código. Escriba su correo y le enviamos uno.');
    }
}
