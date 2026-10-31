<?php

namespace App\Http\Middleware;

use App\Http\Controllers\giro360Controller;
use Closure;
use Illuminate\Http\Request;

// Subir o quitar logos y canciones del 360: el súper administrador (para todos) o un negocio que alquila plataforma 360 (para su cuenta)
class Puede360
{
    public function handle(Request $request, Closure $next)
    {
        if (!giro360Controller::puedeSubir()) {
            if ($request->expectsJson()) return response()->json(['message' => 'Active "Alquilo plataforma 360" en Datos del negocio para guardar logos y canciones en su cuenta.'], 403);
            abort(403, 'Active "Alquilo plataforma 360" en Datos del negocio.');
        }
        return $next($request);
    }
}
