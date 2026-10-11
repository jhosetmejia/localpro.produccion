<?php

namespace App\Http\Middleware;

use App\Support\TiendaActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Las pantallas del negocio necesitan una tienda activa con su configuración terminada.
 */
class RequiereTienda
{
    public function handle(Request $request, Closure $next, string $modo = 'completa')
    {
        $user = Auth::user();
        $tienda = TiendaActual::tienda();

        if (!$tienda) {
            return $user && $user->es_superadmin
                ? redirect()->route('admin.tiendas.index')
                : redirect()->route('login')->withErrors('Su usuario no está asociado a ninguna tienda.');
        }

        if (!$tienda->activa && !$user->es_superadmin) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors('La tienda ' . $tienda->nombre . ' está suspendida. Comuníquese con LocalPro.');
        }

        // Plan pagado vencido: solo puede ver "Mi plan" para renovarlo
        if ($tienda->plan_vencido && !$user->es_superadmin && !$request->routeIs('plan.*')) {
            return redirect()->route('plan.index');
        }

        // Mientras no termine los pasos de configuración, el dueño sigue en el asistente
        if ($modo === 'completa' && !$tienda->onboarding_completo) {
            return redirect()->route('bienvenida.index');
        }

        return $next($request);
    }
}
