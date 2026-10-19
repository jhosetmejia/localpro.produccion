<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\CodigoSuelto;
use App\Models\Entrada;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Support\CodigoBarras;
use App\Support\TiendaActual;
use Illuminate\Http\Request;

// Etiquetas con código de barras para pegar en las prendas y vender con lector
class etiquetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-producto');
        $this->middleware('permission:editar-producto', ['only' => ['generar']]);
    }

    /* ---------- Etiquetas en blanco para mercancía nueva ---------- */

    // Lotes de etiquetas sin producto: se imprimen, se pegan en la mercancía y al crear el producto se escanean
    public function nuevas()
    {
        $lotes = CodigoSuelto::whereNotNull('lote')
            ->selectRaw('lote, COUNT(*) as total, SUM(producto_variante_id IS NOT NULL) as usadas, MIN(created_at) as fecha')
            ->groupBy('lote')->orderByDesc('lote')->limit(60)->get();
        $libres = CodigoSuelto::whereNotNull('lote')->whereNull('producto_variante_id')->count();
        return view('etiqueta.nuevas', compact('lotes', 'libres'));
    }

    public function generar(Request $request)
    {
        $datos = $request->validate([
            'cantidad' => 'required|integer|min:1|max:1000',
            'copias' => 'nullable|integer|min:1|max:50',
        ], [], ['cantidad' => 'cantidad de códigos', 'copias' => 'copias de cada código']);
        $lote = CodigoBarras::generarLote((int) $datos['cantidad']);
        return redirect()->route('etiquetas.lote', ['lote' => $lote, 'copias' => (int) ($datos['copias'] ?? 1)]);
    }

    // Imprime un lote (por defecto solo las que aún no se han usado)
    public function lote(Request $request, string $lote)
    {
        $todas = $request->query('ver') === 'todas';
        $copias = max(1, min(50, (int) $request->query('copias', 1)));
        $codigos = CodigoSuelto::where('lote', $lote)->when(!$todas, fn ($q) => $q->whereNull('producto_variante_id'))->orderBy('id')->pluck('codigo');
        abort_if(!$todas && $codigos->isEmpty() && !CodigoSuelto::where('lote', $lote)->exists(), 404);
        $items = $codigos->map(fn ($c) => ['codigo' => $c, 'cant' => $copias]);
        return view('etiqueta.index', [
            'items' => $items, 'sueltas' => true, 'lote' => $lote, 'verTodas' => $todas,
            'titulo' => 'Mercancía nueva · lote ' . $this->nombreLote($lote), 'negocio' => Negocio::datos(),
        ]);
    }

    public static function nombreLote(string $lote): string
    {
        try { return \Carbon\Carbon::createFromFormat('ymdHis', $lote)->format('d/m/Y g:i a'); } catch (\Throwable $e) { return $lote; }
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
