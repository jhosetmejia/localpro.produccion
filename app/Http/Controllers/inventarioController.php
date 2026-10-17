<?php

namespace App\Http\Controllers;

use App\Models\InventarioMovimiento;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;

class inventarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-inventario');
    }

    public function index(Request $request)
    {
        $q = \App\Support\CodigoBarras::resolver(trim((string) $request->get('q')));
        $filtro = $request->get('filtro', 'todos');

        $variantes = ProductoVariante::query()
            ->with('producto:id,nombre,codigo,precio_venta')
            ->where('producto_variantes.estado', 1)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('producto_variantes.talla', 'like', "%$q%")
                        ->orWhere('producto_variantes.color', 'like', "%$q%")
                        ->orWhere('codigo_barras', $q)
                        ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%"));
                });
            })
            ->when($filtro === 'bajo', fn ($w) => $w->whereBetween('producto_variantes.stock', [1, 5]))
            ->when($filtro === 'agotado', fn ($w) => $w->where('producto_variantes.stock', '<=', 0))
            ->join('productos', 'productos.id', '=', 'producto_variantes.producto_id')
            ->orderBy('productos.nombre')->orderBy('producto_variantes.talla')->orderBy('producto_variantes.color')
            ->select('producto_variantes.*')
            ->paginate(40)
            ->withQueryString();

        $resumen = [
            'unidades' => (int) ProductoVariante::where('estado', 1)->sum('stock'),
            'referencias' => ProductoVariante::where('estado', 1)->count(),
            'bajo' => ProductoVariante::where('estado', 1)->whereBetween('stock', [1, 5])->count(),
            'agotado' => ProductoVariante::where('estado', 1)->where('stock', '<=', 0)->count(),
        ];

        return view('inventario.index', compact('variantes', 'resumen', 'q', 'filtro'));
    }

    public function movimientos(Request $request)
    {
        $movimientos = InventarioMovimiento::with(['variante.producto:id,nombre', 'user:id,name'])
            ->when($request->get('variante'), fn ($w, $v) => $w->where('producto_variante_id', $v))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('inventario.movimientos', compact('movimientos'));
    }
}
