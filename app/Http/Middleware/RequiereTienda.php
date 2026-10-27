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
            if ($user && $user->es_superadmin) {
                return redirect()->route('admin.inicio');
            }
            if ($user && $user->asesor) {
                return redirect()->route('asesor.index');
            }
            return redirect()->route('login')->withErrors('Su usuario no está asociado a ninguna tienda.');
        }

        if (!$tienda->activa && !$user->es_superadmin) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors('La tienda ' . $tienda->nombre . ' está suspendida. Comuníquese con LocalPro.');
        }

        // Prueba o plan vencido: pantalla "Pague su plan" (solo puede ver eso y "Mi plan" para pagar)
        if ($tienda->plan_vencido && !$user->es_superadmin && !$request->routeIs('plan.*')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Su plan venció. Active su plan para seguir usando LocalProo.'], 402)
                : redirect()->route('plan.vencido');
        }

        // Mientras no termine los pasos de configuración, el dueño sigue en el asistente
        if ($modo === 'completa' && !$tienda->onboarding_completo) {
            return redirect()->route('bienvenida.index');
        }

        // Tiendas creadas sin ciudad o sin teléfono: el dueño debe completar los datos del negocio antes de seguir
        if ($modo === 'completa' && !$user->es_superadmin && $request->isMethod('GET') && !$request->expectsJson()
            && !$request->routeIs('negocio.*', 'plan.*', 'logout') && $user->can('editar-negocio')
            && (blank($tienda->departamento) || blank($tienda->ciudad) || blank($tienda->telefono))) {
            return redirect()->route('negocio.edit')
                ->with('error', 'Complete los datos de su negocio (departamento, ciudad y teléfono) para seguir usando LocalProo.');
        }

        return $next($request);
    }
}
