<?php

namespace App\Http\Middleware;

use App\Support\EspacioContable;
use Closure;
use Illuminate\Http\Request;

/**
 * Contabilidad: la usa el negocio (con su tienda) o el asesor de la red desde su panel, aunque no tenga negocio.
 */
class RequiereEspacioContable
{
    public function handle(Request $request, Closure $next)
    {
        if (EspacioContable::esAsesor()) {
            return $next($request);
        }
        return app(RequiereTienda::class)->handle($request, $next);
    }
}
