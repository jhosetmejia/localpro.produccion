<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\ProductoVariante;
use App\Models\Proveedore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Entradas de mercancía: lo que llega del proveedor suma al inventario y actualiza el costo
class entradaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-entradas', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-entradas', ['only' => ['create', 'store']]);
    }

    public function index()
    {
        $entradas = Entrada::with(['proveedor.persona', 'user:id,name'])->withCount('items')->latest('fecha')->latest('id')->paginate(25);
        return view('entrada.index', compact('entradas'));
    }

    public function create()
    {
        $proveedores = Proveedore::with('persona')->get()->sortBy(fn ($p) => $p->persona->razon_social ?? '');
        return view('entrada.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedore_id' => 'nullable|integer',
            'fecha' => 'required|date',
            'numero_factura' => 'nullable|string|max:40',
            'nota' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:300',
            'items.*.variante_id' => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:100000',
            'items.*.costo' => 'required|numeric|min:0|max:999999999',
        ], ['items.required' => 'Agregue al menos un producto a la entrada.']);

        if (!empty($datos['proveedore_id']) && !Proveedore::whereKey($datos['proveedore_id'])->exists()) {
            throw ValidationException::withMessages(['proveedore_id' => 'El proveedor no existe.']);
        }

        $entrada = DB::transaction(function () use ($datos) {
            $ids = collect($datos['items'])->pluck('variante_id')->unique();
            $variantes = ProductoVariante::with('producto:id,nombre,controla_inventario')->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            if ($variantes->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'Algún producto ya no existe. Recargue la página.']);
            }

            $entrada = Entrada::create([
                'proveedore_id' => $datos['proveedore_id'] ?? null,
                'user_id' => Auth::id(),
                'fecha' => $datos['fecha'],
                'numero_factura' => $datos['numero_factura'] ?? null,
                'nota' => $datos['nota'] ?? null,
                'total' => 0,
            ]);
            $ref = 'ENT-' . str_pad((string) $entrada->id, 5, '0', STR_PAD_LEFT);

            $total = 0;
            foreach ($datos['items'] as $i) {
                $v = $variantes[$i['variante_id']];
                $sub = round($i['cantidad'] * $i['costo'], 2);
                $total += $sub;
                $entrada->items()->create([
                    'producto_variante_id' => $v->id,
                    'descripcion' => $v->producto->nombre . ($v->etiqueta !== 'Única' ? ' · ' . $v->etiqueta : ''),
                    'cantidad' => $i['cantidad'],
                    'costo' => $i['costo'],
                    'subtotal' => $sub,
                ]);
                // El último costo de compra queda como costo de esa talla/color
                $v->update(['costo' => $i['costo']]);
                if ($v->controla) {
                    $v->moverStock(round((float) $i['cantidad'], 3), 'entrada', $ref, !empty($datos['numero_factura']) ? 'Factura ' . $datos['numero_factura'] : null);
                }
            }
            $entrada->update(['total' => $total]);
            return $entrada;
        });

        return redirect()->route('entradas.show', $entrada)->with('success', 'Entrada registrada: el inventario y los costos quedaron actualizados.');
    }

    public function show(Entrada $entrada)
    {
        $entrada->load(['items', 'proveedor.persona', 'user:id,name']);
        return view('entrada.show', compact('entrada'));
    }
}
