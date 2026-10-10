<?php

namespace App\Http\Middleware;

use App\Support\Paises;
use App\Support\TiendaActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Define la tienda de la petición: la del usuario, o la que el superadministrador está viendo.
 * Va antes de SubstituteBindings para que ni siquiera por URL se puedan abrir datos de otra tienda.
 */
class ResolverTienda
{
    public function handle(Request $request, Closure $next)
    {
        TiendaActual::set(null);

        if ($user = Auth::user()) {
            $id = $user->tienda_id;
            if ($user->es_superadmin && $request->session()->has('tienda_visitada')) {
                $id = (int) $request->session()->get('tienda_visitada');
            }
            TiendaActual::set($id ?: null);

            // Horas y fechas en la zona horaria del país de la tienda
            if ($t = TiendaActual::tienda()) {
                $zona = Paises::datos($t->pais)['zona'];
                config(['app.timezone' => $zona]);
                date_default_timezone_set($zona);
            }
        }

        return $next($request);
    }
}
