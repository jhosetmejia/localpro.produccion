<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\TiendaActual;

// "Mi carta" (restaurantes y cafeterías): carta en línea para el QR de las mesas y carta para imprimir en PDF/JPG con 3 plantillas
class miCartaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio');
    }

    public function index()
    {
        $t = TiendaActual::tienda();
        return view('negocio.carta', [
            'tienda' => $t,
            'listo' => (bool) ($t->catalogo_activo && $t->slug && $t->activa && $t->onboarding_completo),
            'hayProductos' => Producto::where('estado', 1)->exists(),
        ]);
    }
}
