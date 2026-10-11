<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Tienda;
use App\Support\TiendaActual;

// Catálogo público de cada tienda: sus clientes ven productos y piden por WhatsApp
class catalogoController extends Controller
{
    public function show(string $slug)
    {
        $tienda = Tienda::where('slug', $slug)->firstOrFail();
        abort_unless($tienda->catalogo_activo && $tienda->activa && $tienda->onboarding_completo, 404);
        TiendaActual::set($tienda->id);

        $productos = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica'])
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                $vars = $p->variantes->filter(fn ($v) => !$p->controla_inventario || $v->stock > 0)->values();
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'precio' => (float) $p->precio_venta,
                    'img' => $p->img_path ? asset('storage/productos/' . rawurlencode($p->img_path)) : null,
                    'categorias' => $p->categorias->map(fn ($c) => $c->caracteristica->nombre ?? null)->filter()->values(),
                    'disponible' => $vars->isNotEmpty(),
                    'variantes' => $vars->map(fn ($v) => ['id' => $v->id, 'etiqueta' => $v->etiqueta, 'talla' => $v->talla, 'color' => $v->color, 'precio' => (float) ($v->precio ?? $p->precio_venta)]),
                ];
            })
            ->sortByDesc('disponible')->values();

        $categorias = $productos->pluck('categorias')->flatten()->unique()->reject(fn ($c) => $c === 'General')->values();

        return view('catalogo.show', compact('tienda', 'productos', 'categorias'));
    }
}
