<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Support\Comisiones;

// Solo para la red comercial (asesores, líderes y administrador): simulador para reclutar: simulador de ganancias con los valores reales de comisión
class unirseController extends Controller
{
    public function index()
    {
        $u = auth()->user();
        if (!$u->es_superadmin && !($u->asesor && $u->asesor->activo)) {
            return redirect()->route('panel');
        }
        $tel = preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp'));
        if ($tel !== '' && strlen($tel) <= 10) {
            $tel = '57' . ltrim($tel, '0');
        }
        return view('asesores.simulador', [
            'valores' => Comisiones::valores(),
            'whatsapp' => $tel ?: null,
        ]);
    }
}
