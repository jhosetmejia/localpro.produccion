<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Sitio de pruebas (APP_ENV=staging): pide usuario y clave del navegador antes de abrir cualquier página,
// para que no lo vean clientes ni Google. En producción no hace nada.
class ProtegerPruebas
{
    public function handle(Request $request, Closure $next)
    {
        $clave = (string) config('app.clave_pruebas');
        if (!app()->environment('staging') || $clave === '') {
            return $next($request);
        }
        if ($request->getUser() === 'localpro' && hash_equals($clave, (string) $request->getPassword())) {
            $res = $next($request);
            $res->headers->set('X-Robots-Tag', 'noindex, nofollow');
            return $res;
        }
        return response('Sitio de pruebas de LocalPro: escriba el usuario y la clave.', 401, [
            'WWW-Authenticate' => 'Basic realm="LocalPro pruebas", charset="UTF-8"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
