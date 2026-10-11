<?php

namespace App\Http\Middleware;

use App\Support\TiendaActual;
use Closure;
use Illuminate\Http\Request;

// Pantallas de administración de conjuntos: solo si la cuenta es un conjunto y el usuario es dueño/administrador
class SoloConjunto
{
    public function handle(Request $request, Closure $next)
    {
        $t = TiendaActual::tienda();
        if (!$t || !$t->es_conjunto) {
            return redirect()->route('panel');
        }
        $u = $request->user();
        abort_unless($u->es_superadmin || $u->hasAnyRole(['dueno', 'administrador']), 403, 'Solo la administración del conjunto puede ver esta sección.');
        return $next($request);
    }
}
