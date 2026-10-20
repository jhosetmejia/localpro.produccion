<?php

namespace App\Http\Middleware;

use App\Support\TiendaActual;
use Closure;
use Illuminate\Http\Request;

// Pantallas del instituto / academia: solo si la cuenta es de ese tipo
class SoloInstituto
{
    public function handle(Request $request, Closure $next)
    {
        $t = TiendaActual::tienda();
        if (!$t || !$t->es_instituto) {
            return redirect()->route('panel');
        }
        return $next($request);
    }
}
