<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Panel de la red comercial: solo asesores y líderes activos
class SoloAsesor
{
    public function handle(Request $request, Closure $next)
    {
        $asesor = Auth::user()?->asesor;
        if (!$asesor) {
            return redirect()->route('panel');
        }
        if (!$asesor->activo) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors($asesor->estado === 'pendiente'
                ? 'Su solicitud todavía no ha sido aprobada. Pídale a quien lo invitó que la apruebe en su panel.'
                : 'Su usuario de asesor está inactivo. Comuníquese con LocalPro.');
        }
        $request->attributes->set('asesor', $asesor);
        return $next($request);
    }
}
