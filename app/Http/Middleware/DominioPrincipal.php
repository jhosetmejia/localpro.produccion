<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Todo funciona en un solo dominio (APP_URL). El dominio de pruebas y el www redirigen allá.
class DominioPrincipal
{
    private const VIEJOS = ['localpro.narinoexposhow.com'];

    public function handle(Request $request, Closure $next)
    {
        $principal = parse_url((string) config('app.url'), PHP_URL_HOST);
        $host = strtolower($request->getHost());

        if ($principal && $host !== $principal
            && (in_array($host, self::VIEJOS, true) || $host === 'www.' . $principal)
            && !$request->is('.well-known/*', 'webhooks/*')) {
            return redirect()->to('https://' . $principal . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
