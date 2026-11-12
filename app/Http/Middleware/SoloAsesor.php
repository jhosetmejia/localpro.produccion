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
        // El súper administrador (sin usuario de asesor) puede ver las imágenes y videos para estados,
        // con los enlaces de LocalPro (código LOCALPRO)
        if (!$asesor && Auth::user()?->es_superadmin && $request->routeIs('asesor.estados')) {
            $virtual = new \App\Models\Asesor(['pais' => 'CO', 'codigo' => \App\Models\Asesor::CODIGO_LOCALPRO, 'activo' => true]);
            $virtual->setRelation('user', Auth::user());
            $request->attributes->set('asesor', $virtual);
            return $next($request);
        }
        if (!$asesor) {
            return redirect()->route('panel');
        }
        // Aspirante que todavía no aprueba el curso: va a su curso
        if ($asesor->estado === 'capacitacion') {
            return redirect()->route('curso.index');
        }
        if (!$asesor->activo) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors($asesor->estado === 'pendiente'
                ? 'Su solicitud todavía no ha sido aprobada. Pídale a quien lo invitó que la apruebe en su panel.'
                : 'Su usuario de asesor está inactivo. Comuníquese con LocalPro.');
        }
        // El curso es obligatorio para toda la red, también para quienes ya estaban activos
        if (!$asesor->curso_aprobado_at) {
            return redirect()->route('curso.index');
        }
        $request->attributes->set('asesor', $asesor);
        return $next($request);
    }
}
