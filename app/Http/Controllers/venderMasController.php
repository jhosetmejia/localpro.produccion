<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\Route;

// "Vender más": todas las herramientas para conseguir clientes en un solo lugar
class venderMasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-cliente|crear-cliente|editar-cliente');
    }

    public function index()
    {
        $t = TiendaActual::tienda();
        $conCel = Cliente::whereHas('persona', fn ($p) => $p->where('estado', 1)->whereNotNull('telefono')->where('telefono', '<>', ''));
        return view('vender.index', [
            'tienda' => $t,
            'datos' => [
                'base' => (clone $conCel)->count(),
                'contactados' => Cliente::whereNotNull('contactado_at')->count(),
                'porContactar' => (clone $conCel)->whereNull('contactado_at')->count(),
                'hoy' => Cliente::whereDate('contactado_at', today())->count(),
                'visitas' => (int) ($t->catalogo_visitas ?? 0),
            ],
            'hayRedes' => Route::has('redes.index'),
            'hayEnlaces' => Route::has('enlaces.index'),
        ]);
    }
}
