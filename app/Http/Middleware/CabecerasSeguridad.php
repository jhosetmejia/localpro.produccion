<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Cabeceras de seguridad para todas las respuestas: evitan que otra página meta LocalProo en un iframe (clickjacking),
// que el navegador "adivine" tipos de archivo, y que se filtren direcciones completas a otros sitios.
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next)
    {
        $r = $next($request);
        if (!method_exists($r, 'headers')) return $r;
        $h = $r->headers;
        if (!$h->has('X-Frame-Options')) $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');
        if ($request->isSecure()) $h->set('Strict-Transport-Security', 'max-age=15552000; includeSubDomains');
        $h->remove('X-Powered-By');
        return $r;
    }
}
