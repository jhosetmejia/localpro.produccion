<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Freno a robots (y a programas con IA que llenan formularios solos) en registro y recuperar clave:
 *  - Campo trampa invisible: una persona no lo ve ni lo llena; un robot sí.
 *  - Tiempo mínimo: nadie llena el formulario en menos de 2 segundos.
 * Si el formulario es viejo (sin las marcas), deja pasar para no bloquear a nadie con la página abierta de antes.
 */
class AntiBot
{
    public function handle(Request $request, Closure $next)
    {
        if (trim((string) $request->input('web_empresa')) !== '') {
            \Illuminate\Support\Facades\Log::notice('AntiBot: campo trampa lleno', ['ip' => $request->ip(), 'ruta' => $request->path()]);
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors('No pudimos procesar el formulario. Intente de nuevo.');
        }
        if ($request->filled('_lp_t')) {
            try { $t = (int) decrypt($request->input('_lp_t')); } catch (\Throwable $e) { $t = 0; }
            if ($t && time() - $t < 2) {
                return back()->withInput($request->except('password', 'password_confirmation'))->withErrors('Muy rápido. Revise sus datos y vuelva a enviar.');
            }
        }
        return $next($request);
    }
}
