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
        // Enlace por tipo de negocio (ej. /contadores → ?tipo=contable): la cuenta nace con ese tipo y su herramienta activa
        $tipo = request()->query('tipo');
        if ($tipo && isset(\App\Models\Tienda::TIPOS[$tipo])) {
            session(['tipo_registro' => $tipo]);
        }
        return view('auth.registro', ['paises' => Paises::LISTA, 'codigoAsesor' => session('codigo_asesor', request()->cookie('lp_asesor')), 'tipoRegistro' => session('tipo_registro')]);
    }

    // Enlace del asesor: guarda su código (60 días) y abre el registro
    public function referido(string $codigo)
    {
        $asesor = \App\Models\Asesor::porCodigo($codigo)->aceptaNegocios()->first();
        $guardar = $asesor?->codigo ?? (\App\Models\Asesor::esCodigoLocalpro($codigo) ? \App\Models\Asesor::CODIGO_LOCALPRO : null);
        if (!$guardar) {
            return redirect()->route('registro');
        }
        session(['codigo_asesor' => $guardar]);
        return redirect()->route('registro')->withCookie(cookie('lp_asesor', $guardar, 60 * 24 * 60));
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
            'acepto' => 'accepted',
        ], [
            'acepto.accepted' => 'Para crear la cuenta debe aceptar las condiciones de uso.',
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
        $tipo = session('tipo_registro');
        $tipo = $tipo && isset(\App\Models\Tienda::TIPOS[$tipo]) ? $tipo : null;
        [, $user] = CrearTienda::crear(
            ['nombre' => ($tipo === 'contable' ? 'Oficina contable de ' : 'Tienda de ') . $primerNombre, 'pais' => $datos['pais'], 'telefono' => $datos['telefono']] + ($tipo ? ['tipo_negocio' => $tipo] : []),
            $datos
        );
        if ($tipo) {
            // Deja activos los módulos de ese tipo (para el contador: Contabilidad) desde el primer momento
            $mods = array_fill_keys(\App\Models\Tienda::MODULOS_POR_TIPO[$tipo] ?? [], true);
            if ($mods) $user->tienda->forceFill($mods)->save();
            $request->session()->forget('tipo_registro');
        }

        // Si llegó por un asesor (enlace o código), el negocio queda en su red
        $codigo = strtoupper(trim((string) ($datos['codigo_asesor'] ?? '') ?: (string) session('codigo_asesor', $request->cookie('lp_asesor'))));
        $asesor = $codigo !== '' ? \App\Models\Asesor::porCodigo($codigo)->aceptaNegocios()->first() : null;
        // Código LOCALPRO (súper administrador): el negocio queda directo con LocalPro, sin asesor
        if ($asesor || \App\Models\Asesor::esCodigoLocalpro($codigo)) {
            // Los negocios que llegan por un asesor (o por LocalPro) arrancan con prueba gratis de 3 meses; luego pagan su plan anual
            $prueba = \App\Models\Plan::where('nombre', 'Prueba 3 meses')->first();
            $user->tienda->forceFill(['asesor_id' => $asesor?->id, 'venta_directa_id' => null] + ($prueba ? ['plan_id' => $prueba->id, 'plan_vence' => today()->addDays(($prueba->dias ?: 90) - 1)] : []))->save();
        }

        $user->forceFill(['terminos_aceptados_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('bienvenida.index');
    }
}
