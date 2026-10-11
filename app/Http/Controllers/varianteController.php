<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Tallas, colores y cantidades de cada prenda
class varianteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-producto|ver-inventario', ['only' => ['index']]);
        $this->middleware('permission:editar-producto', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('permission:ajustar-inventario', ['only' => ['ajustar']]);
    }

    public function index(Producto $producto)
    {
        $producto->load(['variantes' => fn ($q) => $q->orderBy('talla')->orderBy('color')]);
        return view('inventario.variantes', compact('producto'));
    }

    // Crea varias combinaciones a la vez: tallas × colores, con la misma cantidad inicial
    public function store(Request $request, Producto $producto)
    {
        $datos = $request->validate([
            'tallas' => 'nullable|string|max:200',
            'colores' => 'nullable|string|max:300',
            'stock' => 'required|integer|min:0|max:100000',
            'precio' => 'nullable|numeric|min:0|max:999999999',
        ], [], ['stock' => 'cantidad inicial']);

        $tallas = $this->lista($datos['tallas'] ?? '');
        $colores = $this->lista($datos['colores'] ?? '');
        $tallas = $tallas ?: [null];
        $colores = $colores ?: [null];

        $creadas = 0;
        $omitidas = 0;
        DB::transaction(function () use ($producto, $tallas, $colores, $datos, &$creadas, &$omitidas) {
            foreach ($tallas as $t) {
                foreach ($colores as $c) {
                    $existe = $producto->variantes()->where('talla', $t)->where('color', $c)->exists();
                    if ($existe) {
                        $omitidas++;
                        continue;
                    }
                    $v = $producto->variantes()->create([
                        'talla' => $t,
                        'color' => $c,
                        'stock' => 0,
                        'precio' => $datos['precio'] ?? null,
                    ]);
                    if ((int) $datos['stock'] > 0) {
                        $v->moverStock((int) $datos['stock'], 'entrada', null, 'Inventario inicial');
                    }
                    $creadas++;
                }
            }
        });

        $msg = "Se agregaron $creadas combinaciones.";
        if ($omitidas) {
            $msg .= " $omitidas ya existían y no se repitieron.";
        }
        return back()->with('success', $msg);
    }

    public function update(Request $request, ProductoVariante $variante)
    {
        $datos = $request->validate([
            'precio' => 'nullable|numeric|min:0|max:999999999',
            'costo' => 'nullable|numeric|min:0|max:999999999',
            'codigo_barras' => ['nullable', 'string', 'max:60', Rule::unique('producto_variantes', 'codigo_barras')->where('tienda_id', \App\Support\TiendaActual::id())->ignore($variante->id)],
            'estado' => 'nullable|boolean',
        ]);
        $variante->update([
            'precio' => $datos['precio'] ?? null,
            'costo' => $datos['costo'] ?? null,
            'codigo_barras' => $datos['codigo_barras'] ?? null,
            'estado' => $request->boolean('estado', true) ? 1 : 0,
        ]);
        return back()->with('success', 'Talla/color actualizada.');
    }

    // Entrada (llegó mercancía), salida (daño, pérdida) o ajuste a una cantidad exacta (conteo físico)
    public function ajustar(Request $request, ProductoVariante $variante)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|integer|min:0|max:100000',
            'nota' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($variante, $datos) {
            $actual = ProductoVariante::whereKey($variante->id)->lockForUpdate()->value('stock');
            $delta = match ($datos['tipo']) {
                'entrada' => (int) $datos['cantidad'],
                'salida' => -(int) $datos['cantidad'],
                'ajuste' => (int) $datos['cantidad'] - (int) $actual,
            };
            if ($delta !== 0) {
                $variante->moverStock($delta, $datos['tipo'], null, $datos['nota'] ?? null);
            }
        });

        return back()->with('success', 'Inventario actualizado: ' . $variante->fresh()->stock . ' unidades.');
    }

    public function destroy(ProductoVariante $variante)
    {
        // Si ya tiene ventas se desactiva para no perder el historial
        if ($variante->stock == 0 && !\App\Models\PosVentaItem::where('producto_variante_id', $variante->id)->exists()) {
            $variante->delete();
            return back()->with('success', 'Talla/color eliminada.');
        }
        $variante->update(['estado' => 0]);
        return back()->with('success', 'Talla/color desactivada (tiene historial o unidades).');
    }

    private function lista(string $texto): array
    {
        return collect(preg_split('/[,;\n]+/', $texto))
            ->map(fn ($x) => mb_strtoupper(trim($x)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
