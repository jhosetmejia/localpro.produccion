<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Support\TiendaActual;

// Etiquetas con código de barras para pegar en las prendas y vender con lector
class etiquetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-producto');
    }

    public function producto(Producto $producto)
    {
        $variantes = $producto->variantes()->where('estado', 1)->orderBy('talla')->orderBy('color')->get();
        $items = $variantes->map(fn ($v) => ['v' => $this->conCodigo($v)->setRelation('producto', $producto), 'cant' => max(1, (int) $v->stock)]);
        return $this->vista($items, $producto->nombre);
    }

    public function entrada(Entrada $entrada)
    {
        $entrada->load('items.variante.producto');
        $items = $entrada->items->filter(fn ($i) => $i->variante)->map(fn ($i) => ['v' => $this->conCodigo($i->variante), 'cant' => (int) $i->cantidad])->values();
        return $this->vista($items, 'Entrada ENT-' . str_pad($entrada->id, 5, '0', STR_PAD_LEFT));
    }

    private function vista($items, string $titulo)
    {
        return view('etiqueta.index', ['items' => $items, 'titulo' => $titulo, 'negocio' => Negocio::datos()]);
    }

    // Si la talla/color no tiene código de barras, se le asigna uno único: tienda + referencia
    private function conCodigo(ProductoVariante $v): ProductoVariante
    {
        if (!$v->codigo_barras) {
            $v->codigo_barras = str_pad((string) TiendaActual::id(), 3, '0', STR_PAD_LEFT) . str_pad((string) $v->id, 7, '0', STR_PAD_LEFT);
            $v->save();
        }
        return $v;
    }
}
