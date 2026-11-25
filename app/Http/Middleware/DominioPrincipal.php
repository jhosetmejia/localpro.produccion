<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

// Todo funciona en un solo dominio (APP_URL). El www (y cualquier dominio viejo de VIEJOS) redirigen allá.
class DominioPrincipal
{
    private const VIEJOS = [];

    public function handle(Request $request, Closure $next)
    {
        $principal = app()->environment('production') ? config('app.dominio') : parse_url((string) config('app.url'), PHP_URL_HOST);
        $host = strtolower($request->getHost());

        // La tienda en línea y el marketplace también abren con www (botones del panel), así salen en el navegador y no en la
        // app instalada. Los enlaces internos se quedan en www; el canonical apunta al dominio sin www.
        if ($principal && $host === 'www.' . $principal && $request->is('t', 't/*', 'marketplace', 'marketplace/*')) {
            URL::forceRootUrl('https://www.' . $principal);
            return $next($request);
        }

        // 2026-11-25 (Jhoset): LP Chat vive en www.localproo.com/chat para que se instale como app APARTE (si el celular ya
        // tiene la app de LocalProo, Chrome no deja instalar otra en el mismo dominio). Los links viejos (localproo.com/chat/...)
        // pasan solos a www. Las llamadas entre servidores (/chat/api/...) y los envíos (POST) se quedan donde llegan.
        if ($principal && $host === 'www.' . $principal && $request->is('chat', 'chat/*')) {
            URL::forceRootUrl('https://www.' . $principal);
            return $next($request);
        }
        if ($principal && $host === $principal && $request->isMethod('GET') && $request->is('chat', 'chat/*') && !$request->is('chat/api/*')) {
            return redirect()->to('https://www.' . $principal . $request->getRequestUri(), 302);
        }

        if ($principal && $host !== $principal
            && (in_array($host, self::VIEJOS, true) || $host === 'www.' . $principal)
            && !$request->is('.well-known/*', 'webhooks/*')) {
            return redirect()->to('https://' . $principal . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
