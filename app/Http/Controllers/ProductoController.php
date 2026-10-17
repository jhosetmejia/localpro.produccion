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
        $this->middleware('permission:eliminar-producto', ['only' => ['destroy', 'eliminar']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = Producto::with(['categorias.caracteristica','marca.caracteristica','presentacione.caracteristica','variantes','fotos'])->latest()->get();
    
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
                'para' => $request->para ?: null,
                // Promoción: solo cuenta si el precio antes es mayor que el de venta
                'precio_antes' => $request->boolean('en_promo') && (float) $request->precio_antes > (float) $request->precio_venta ? $request->precio_antes : null,
                'precio_venta' => $request->precio_venta,
                'precio_costo' => $request->precio_costo ?: 0,
                'controla_inventario' => $request->boolean('controla_inventario'),
                'mostrar_sin_foto' => $request->boolean('mostrar_sin_foto'),
                'descripcion' => $request->descripcion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'img_path' => $name,
                'marca_id' => $request->marca_id,
                'presentacione_id' => $request->presentacione_id,
                'unidad' => \App\Support\TiendaActual::tienda()->mod_medida ? ($request->unidad ?: 'und') : 'und',
                'impuesto' => $request->impuesto ?: \App\Support\Dian\Facturador::impuestoPorDefecto(\App\Support\TiendaActual::tienda()),
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
                    $talla = \App\Support\Tallas::normalizar($fila['talla'] ?? '');
                    $color = \App\Support\Colores::normalizar($fila['color'] ?? '');
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
                $cant = $producto->es_fraccion ? round((float) $request->cantidad_inicial, 3) : (int) $request->cantidad_inicial;
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
            . ($controla ? ' (' . ($producto->es_fraccion ? cantidad($total, $producto->unidad) : $total . ' ' . ($total == 1 ? 'unidad' : 'unidades')) . ')' : '') . '.';
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
                'para' => $request->para ?: null,
                // Promoción: solo cuenta si el precio antes es mayor que el de venta
                'precio_antes' => $request->boolean('en_promo') && (float) $request->precio_antes > (float) $request->precio_venta ? $request->precio_antes : null,
                'precio_venta' => $request->precio_venta,
                'precio_costo' => $request->precio_costo ?: 0,
                'controla_inventario' => $request->boolean('controla_inventario'),
                'mostrar_sin_foto' => $request->boolean('mostrar_sin_foto'),
                'descripcion' => $request->descripcion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'img_path' => $name,
                'marca_id' => $request->marca_id,
                'presentacione_id' => $request->presentacione_id,
                'unidad' => \App\Support\TiendaActual::tienda()->mod_medida ? ($request->unidad ?: ($producto->unidad ?: 'und')) : ($producto->unidad ?: 'und'),
                'impuesto' => $request->impuesto ?: ($producto->impuesto ?: 'ninguno'),
            ]);

            $producto->save();

            //Tabla categoría producto
            // Solo categorías de esta tienda
            $categorias = Categoria::whereIn('id', (array) $request->get('categorias'))->pluck('id');
            if ($categorias->isEmpty()) {
                $categorias = collect([\App\Support\CrearTienda::categoria('General')->id]);
            }
            $producto->categorias()->sync($categorias);

            // Fotos por color
            foreach ((array) $request->input('foto_color_nombre', []) as $i => $color) {
                // Mismo texto que tiene la talla/color guardada, para que coincida en la tienda
                $color = trim((string) $color);
                if ($color === '' || !$producto->variantes()->where('color', $color)->exists()) continue;
                $actual = $producto->fotos()->where('color', $color)->first();
                if ($request->hasFile("foto_color.$i")) {
                    $nombre = $producto->handleUploadImage($request->file("foto_color.$i"));
                    if ($actual) {
                        Storage::disk('public')->delete('productos/' . $actual->path);
                        $actual->update(['path' => $nombre]);
                    } else {
                        $producto->fotos()->create(['color' => $color, 'path' => $nombre]);
                    }
                } elseif ($request->boolean("quitar_foto_color.$i") && $actual) {
                    Storage::disk('public')->delete('productos/' . $actual->path);
                    $actual->delete();
                }
            }

            // Cantidades escritas en el mismo formulario: solo las que la persona cambió.
            // Se deja la cantidad exacta que escribió (queda en movimientos como "ajuste").
            $ajustadas = 0;
            if ($producto->controla_inventario && $request->user()?->can('ajustar-inventario')) {
                $antes = (array) $request->input('stock_antes', []);
                foreach ((array) $request->input('stock', []) as $id => $nuevo) {
                    if ($nuevo === null || $nuevo === '' || !is_numeric($nuevo) || (float) $nuevo < 0 || (float) $nuevo > 100000) continue;
                    if (isset($antes[$id]) && abs((float) $antes[$id] - (float) $nuevo) < 0.0005) continue;
                    $v = $producto->variantes()->whereKey((int) $id)->lockForUpdate()->first();
                    if (!$v) continue;
                    $delta = round((float) $nuevo - (float) $v->stock, 3);
                    if (abs($delta) > 0.0005) { $v->moverStock($delta, 'ajuste', null, 'Ajuste al editar el producto'); $ajustadas++; }
                }
            }

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            return back()->withInput()->withErrors('No se pudo guardar el producto. Intente de nuevo.');
        }

        return redirect()->route('productos.index')->with('success', 'Producto editado' . ($ajustadas ? ' y cantidades actualizadas.' : '.'));
    }

    // Borrar el producto para siempre (solo dueño o administrador de la tienda).
    // Las ventas ya hechas se conservan con su descripción; se borran sus tallas, stock y fotos.
    public function eliminar(Producto $producto)
    {
        abort_unless(auth()->user()?->hasAnyRole(['dueno', 'administrador']), 403, 'Solo el dueño o el administrador pueden eliminar productos.');
        $nombre = $producto->nombre;
        $archivos = array_filter(array_merge([$producto->img_path], $producto->fotos()->pluck('path')->all()));
        DB::transaction(function () use ($producto) {
            $producto->categorias()->detach();
            $producto->fotos()->delete();
            $producto->variantes()->delete();
            $producto->delete();
        });
        foreach ($archivos as $a) Storage::disk('public')->delete('productos/' . $a);
        return redirect()->back(302, [], route('productos.index'))->with('success', 'Producto "' . $nombre . '" eliminado.');
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
            // Volver a mostrar un producto oculto ocupa cupo del plan
            if ($msg = \App\Support\TiendaActual::limitePlan('productos')) {
                return redirect()->back(302, [], route('productos.index'))->withErrors($msg);
            }
            Producto::where('id', $producto->id)
                ->update([
                    'estado' => 1
                ]);
            $message = 'Producto restaurado';
        }

        return redirect()->back(302, [], route('productos.index'))->with('success', $message);
    }
}
