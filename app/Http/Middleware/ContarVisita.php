<?php

namespace App\Http\Middleware;

use App\Models\VisitaPagina;
use App\Models\VisitaSitio;
use App\Support\AgenteUsuario;
use App\Support\TiendaActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Cuenta las visitas de todo LocalProo: cada página HTML que se abre (panel y páginas públicas).
 * Se guarda DESPUÉS de mandar la página (terminate), así no la demora; si algo falla, no pasa nada.
 * Una visita = el mismo navegador sin 30 minutos de pausa. Ver Admin → Visitas y uso.
 */
class ContarVisita
{
    private const COOKIE = 'lp_v';
    private const NO_CONTAR = ['admin.sitio.vivo', 'admin.cut.vivo', 'opinion.mias', 'cut.visita', 'cut.evento', 'opinion.guardar', 'opinion.error'];

    public function handle(Request $request, Closure $next)
    {
        try {
            if ($this->contable($request) && !preg_match('/^[a-z0-9]{16,32}$/', (string) $request->cookie(self::COOKIE))) {
                $id = Str::lower(Str::random(24));
                $request->attributes->set('lp_v_nuevo', $id);
                Cookie::queue(cookie(self::COOKIE, $id, 60 * 24 * 730, null, null, null, true, false, 'Lax'));
            }
        } catch (\Throwable $e) { /* el contador nunca debe dañar una página */ }
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            if (!$this->contable($request) || app()->runningInConsole()) return;
            $status = $response->getStatusCode();
            if ($status >= 300) return;
            $tipo = (string) $response->headers->get('Content-Type');
            if ($tipo !== '' && !str_contains($tipo, 'text/html')) return;
            $ua = (string) $request->userAgent();
            $ag = AgenteUsuario::analizar($ua);
            if ($ag['es_bot']) return;

            $nuevoId = $request->attributes->get('lp_v_nuevo');
            $vis = $nuevoId ?: (string) $request->cookie(self::COOKIE);
            if (!preg_match('/^[a-z0-9]{16,32}$/', $vis)) return;
            $ahora = Carbon::now('America/Lima');
            $ruta = optional($request->route())->getName();
            $pagina = Str::limit('/' . ltrim($request->path(), '/'), 190, '');
            $user = $request->user();

            $clave = 'visita_sitio_' . $vis;
            $idVisita = Cache::get($clave);
            $v = $idVisita ? VisitaSitio::find($idVisita) : null;
            if (!$v) {
                $ref = (string) $request->headers->get('referer');
                $host = $ref ? parse_url($ref, PHP_URL_HOST) : null;
                $propio = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
                $propio = preg_replace('/^www\./', '', $propio);
                $origen = $request->query('utm_source') ?: ($request->query('ref') ?: $request->query('desde'));
                $v = VisitaSitio::create([
                    'visitante' => $vis,
                    'nuevo' => (bool) $nuevoId || !VisitaSitio::where('visitante', $vis)->exists(),
                    'user_id' => $user?->id,
                    'tienda_id' => $user ? TiendaActual::id() : null,
                    'ip' => $request->ip(),
                    'dispositivo' => $ag['dispositivo'], 'sistema' => $ag['sistema'], 'navegador' => $ag['navegador'],
                    'app_origen' => $ag['app'], 'marca' => $ag['marca_equipo'],
                    'es_app' => $request->query('app') === '1',
                    'referencia' => $host && !str_ends_with($host, $propio) ? Str::limit($host, 110, '') : null,
                    'origen' => is_string($origen) ? (Str::limit(Str::slug($origen), 58, '') ?: null) : null,
                    'entrada' => $pagina,
                    'paginas' => 0,
                    'ultima_vez' => $ahora, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
            $cambios = ['ultima_vez' => $ahora, 'updated_at' => $ahora, 'paginas' => $v->paginas + 1, 'ultima_ruta' => $ruta ? Str::limit($ruta, 78, '') : null, 'ultima_pagina' => $pagina];
            if ($user && !$v->user_id) { $cambios['user_id'] = $user->id; $cambios['tienda_id'] = TiendaActual::id(); }
            if ($request->query('app') === '1') $cambios['es_app'] = true;
            VisitaSitio::whereKey($v->id)->update($cambios);
            VisitaPagina::create(['visita_id' => $v->id, 'ruta' => $ruta ? Str::limit($ruta, 78, '') : null, 'pagina' => $pagina, 'created_at' => $ahora]);
            Cache::put($clave, $v->id, now()->addMinutes(30));
        } catch (\Throwable $e) {
            // El contador nunca debe dañar una página
        }
    }

    /** Latido (cada 2 min desde el panel o el editor abierto): sigue contando como "ahora mismo" sin abrir páginas. */
    public static function latido(Request $request)
    {
        try {
            $vis = (string) $request->cookie(self::COOKIE);
            $id = preg_match('/^[a-z0-9]{16,32}$/', $vis) ? Cache::get('visita_sitio_' . $vis) : null;
            if ($id) {
                VisitaSitio::whereKey($id)->update(['ultima_vez' => Carbon::now('America/Lima')]);
                Cache::put('visita_sitio_' . $vis, $id, now()->addMinutes(30));
            }
        } catch (\Throwable $e) { /* nada */ }
        return response()->noContent();
    }

    private function contable(Request $request): bool
    {
        if (!$request->isMethod('GET') || $request->ajax() || $request->expectsJson() || preg_match('/prefetch|prerender/i', (string) ($request->header('Purpose') . $request->header('Sec-Purpose') . $request->header('X-Moz')))) return false;
        $ruta = optional($request->route())->getName();
        if ($ruta && in_array($ruta, self::NO_CONTAR, true)) return false;
        return !preg_match('#^(storage|build|js|css|img|icons|fonts|estado-|sw\.js|manifest|.*\.(png|jpe?g|webp|gif|svg|ico|txt|xml|json|webmanifest|map)$)#i', ltrim($request->path(), '/'));
    }
}
