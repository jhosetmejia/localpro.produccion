<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Entrada;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Support\TiendaActual;
use Illuminate\Http\Request;

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

    // Todas las etiquetas de la tienda, filtradas como quiera el dueño: categoría, para quién, con existencias
    public function todas(Request $request)
    {
        $f = [
            'categoria' => (int) $request->query('categoria', 0),
            'para' => (string) $request->query('para', ''),
            'q' => trim((string) $request->query('q', '')),
            'stock' => $request->query('stock', '1') === '1',
            'cant' => $request->query('cant', 'una') === 'stock' ? 'stock' : 'una',
        ];
        $variantes = ProductoVariante::query()
            ->with('producto.categorias.caracteristica')
            ->where('producto_variantes.estado', 1)
            ->whereHas('producto', function ($p) use ($f) {
                $p->where('estado', 1)
                    ->when($f['categoria'], fn ($x) => $x->whereHas('categorias', fn ($c) => $c->where('categorias.id', $f['categoria'])))
                    ->when(in_array($f['para'], ['mujer', 'hombre', 'ninos', 'todos'], true), fn ($x) => $x->where('para', $f['para']))
                    ->when($f['q'] !== '', fn ($x) => $x->where(fn ($y) => $y->where('nombre', 'like', '%' . $f['q'] . '%')->orWhere('codigo', 'like', '%' . $f['q'] . '%')));
            })
            ->when($f['stock'], fn ($w) => $w->where(fn ($x) => $x->where('producto_variantes.stock', '>', 0)->orWhereHas('producto', fn ($p) => $p->where('controla_inventario', false))))
            ->join('productos', 'productos.id', '=', 'producto_variantes.producto_id')
            ->orderBy('productos.nombre')->orderBy('producto_variantes.talla')->orderBy('producto_variantes.color')
            ->select('producto_variantes.*')
            ->limit(1500)->get();

        $items = $variantes->map(fn ($v) => ['v' => $this->conCodigo($v), 'cant' => $f['cant'] === 'stock' ? max(1, (int) $v->stock) : 1]);
        $categorias = Categoria::with('caracteristica')->get()->filter(fn ($c) => $c->caracteristica)->sortBy(fn ($c) => mb_strtolower($c->caracteristica->nombre))->values();

        return view('etiqueta.index', [
            'items' => $items, 'titulo' => 'Todas las etiquetas', 'negocio' => Negocio::datos(),
            'todas' => true, 'f' => $f, 'categorias' => $categorias, 'limite' => $variantes->count() >= 1500,
        ]);
    }

    private function vista($items, string $titulo)
    {
        return view('etiqueta.index', ['items' => $items, 'titulo' => $titulo, 'negocio' => Negocio::datos()]);
    }

    // Código de barras basado en el código del producto (P-0113 → P011301…), ver CodigoBarras
    private function conCodigo(ProductoVariante $v): ProductoVariante
    {
        \App\Support\CodigoBarras::asegurar($v);
        return $v;
    }
}
