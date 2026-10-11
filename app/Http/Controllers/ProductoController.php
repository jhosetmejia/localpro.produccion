<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Presentacione;
use App\Models\Producto;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-producto|crear-producto|editar-producto|eliminar-producto', ['only' => ['index']]);
        $this->middleware('permission:crear-producto', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-producto', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-producto', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = Producto::with(['categorias.caracteristica','marca.caracteristica','presentacione.caracteristica','variantes'])->latest()->get();
    
        return view('producto.index',compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if ($msg = \App\Support\TiendaActual::limitePlan('productos')) {
            return redirect()->route('productos.index')->withErrors($msg);
        }
        // Marcas y presentaciones no se usan en las tiendas
        $marcas = collect();
        $presentaciones = collect();

        $categorias = Categoria::join('caracteristicas as c', 'categorias.caracteristica_id', '=', 'c.id')
            ->select('categorias.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        return view('producto.create', compact('marcas', 'presentaciones', 'categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductoRequest $request)
    {
        if ($msg = \App\Support\TiendaActual::limitePlan('productos')) {
            return back()->withInput()->withErrors($msg);
        }
        //dd($request);
        try {
            DB::beginTransaction();
            //Tabla producto
            $producto = new Producto();
            if ($request->hasFile('img_path')) {
                $name = $producto->handleUploadImage($request->file('img_path'));
            } else {
                $name = null;
            }

            $producto->fill([
                'codigo' => $request->codigo ?: \App\Support\CrearTienda::siguienteCodigo(),
                'nombre' => $request->nombre,
                'precio_venta' => $request->precio_venta,
                'precio_costo' => $request->precio_costo ?: 0,
                'controla_inventario' => $request->boolean('controla_inventario'),
                'descripcion' => $request->descripcion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'img_path' => $name,
                'marca_id' => $request->marca_id,
                'presentacione_id' => $request->presentacione_id
            ]);

            $producto->save();

            //Tabla categoría producto
            // Solo categorías de esta tienda
            $categorias = Categoria::whereIn('id', (array) $request->get('categorias'))->pluck('id');
            if ($categorias->isEmpty()) {
                $categorias = collect([\App\Support\CrearTienda::categoria('General')->id]);
            }
            $producto->categorias()->attach($categorias);

            // Cantidades: por talla/color (moda) o una sola cantidad
            $controla = $producto->controla_inventario;
            $creadas = 0;
            $total = 0;
            if (\App\Support\TiendaActual::tienda()->usa_tallas && $request->boolean('con_tallas')) {
                $vistas = [];
                foreach ((array) $request->input('variantes', []) as $fila) {
                    $talla = mb_strtoupper(trim((string) ($fila['talla'] ?? ''))) ?: null;
                    $color = mb_strtoupper(trim((string) ($fila['color'] ?? ''))) ?: null;
                    $clave = $talla . '|' . $color;
                    if ($clave === '|' || isset($vistas[$clave])) {
                        continue;
                    }
                    $vistas[$clave] = true;
                    $v = $producto->variantes()->create(['talla' => $talla, 'color' => $color, 'stock' => 0]);
                    $cant = (int) ($fila['cantidad'] ?? 0);
                    if ($cant > 0 && $controla) {
                        $v->moverStock($cant, 'entrada', null, 'Inventario inicial');
                        $total += $cant;
                    }
                    $creadas++;
                }
            }
            if ($creadas === 0) {
                $v = $producto->variantes()->create(['talla' => null, 'color' => null, 'stock' => 0]);
                $cant = (int) $request->cantidad_inicial;
                if ($cant > 0 && $controla) {
                    $v->moverStock($cant, 'entrada', null, 'Inventario inicial');
                    $total += $cant;
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            return back()->withInput()->withErrors('No se pudo guardar el producto. Intente de nuevo.');
        }

        $msg = 'Producto "' . $producto->nombre . '" guardado y listo para vender'
            . ($controla ? ' (' . $total . ' ' . ($total === 1 ? 'unidad' : 'unidades') . ')' : '') . '.';
        return redirect()->route('productos.index')->with('success', $msg);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        // Marcas y presentaciones no se usan en las tiendas
        $marcas = collect();
        $presentaciones = collect();

        $categorias = Categoria::join('caracteristicas as c', 'categorias.caracteristica_id', '=', 'c.id')
            ->select('categorias.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        return view('producto.edit',compact('producto','marcas','presentaciones','categorias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        try{
            DB::beginTransaction();

            if ($request->hasFile('img_path')) {
                $name = $producto->handleUploadImage($request->file('img_path'));

                //Eliminar si existiese una imagen
                if(Storage::disk('public')->exists('productos/'.$producto->img_path)){
                    Storage::disk('public')->delete('productos/'.$producto->img_path);
                }

            } else {
                $name = $producto->img_path;
            }

            $producto->fill([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'precio_venta' => $request->precio_venta,
                'precio_costo' => $request->precio_costo ?: 0,
                'controla_inventario' => $request->boolean('controla_inventario'),
                'descripcion' => $request->descripcion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'img_path' => $name,
                'marca_id' => $request->marca_id,
                'presentacione_id' => $request->presentacione_id
            ]);

            $producto->save();

            //Tabla categoría producto
            // Solo categorías de esta tienda
            $categorias = Categoria::whereIn('id', (array) $request->get('categorias'))->pluck('id');
            if ($categorias->isEmpty()) {
                $categorias = collect([\App\Support\CrearTienda::categoria('General')->id]);
            }
            $producto->categorias()->sync($categorias);

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
        }

        return redirect()->route('productos.index')->with('success','Producto editado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $message = '';
        $producto = Producto::findOrFail($id);
        if ($producto->estado == 1) {
            Producto::where('id', $producto->id)
                ->update([
                    'estado' => 0
                ]);
            $message = 'Producto eliminado';
        } else {
            Producto::where('id', $producto->id)
                ->update([
                    'estado' => 1
                ]);
            $message = 'Producto restaurado';
        }

        return redirect()->route('productos.index')->with('success', $message);
    }
}
