<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PosVenta;
use App\Models\Producto;
use App\Models\Tienda;
use App\Support\TiendaActual;

// Ejemplos para los asesores: cómo queda la tienda en línea, la página de enlaces, la factura… de negocios de muestra.
// Las tiendas de muestra se eligen en Admin → Planes y cobros (códigos LCT separados por coma).
class asesorEjemplosController extends Controller
{
    public const AJUSTE = 'ejemplos_tiendas';

    public function index()
    {
        $u = auth()->user();
        if (!$u->es_superadmin && !($u->asesor && $u->asesor->activo)) {
            return redirect()->route('panel');
        }

        $codigos = collect(explode(',', (string) Ajuste::valor(self::AJUSTE, 'LCT-00003')))
            ->map(fn ($c) => strtoupper(trim($c)))->filter()->unique()->values();

        $ejemplos = Tienda::whereIn('codigo', $codigos)->get()
            ->sortBy(fn ($t) => $codigos->search($t->codigo))
            ->map(function (Tienda $t) {
                return TiendaActual::como($t->id, function () use ($t) {
                    $venta = PosVenta::where('estado', 'pagada')->latest()->first();
                    $listo = $t->slug && $t->onboarding_completo;
                    return [
                        'tienda' => $t,
                        'tipo' => Tienda::TIPOS[$t->tipo_negocio] ?? null,
                        'catalogo' => $listo && $t->catalogo_activo ? $t->url_catalogo_navegador : null,
                        'enlaces' => $listo && $t->enlaces_activo ? route('enlaces.publico', $t->slug) : null,
                        'factura' => $venta?->url_factura,
                        'productos' => Producto::enCatalogo()->count(),
                    ];
                });
            })->values();

        return view('asesores.ejemplos', ['ejemplos' => $ejemplos, 'esAdmin' => (bool) $u->es_superadmin]);
    }
}
