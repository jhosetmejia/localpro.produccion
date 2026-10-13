<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaracteristicaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Caracteristica;
use App\Models\Categoria;
use Exception;
use Illuminate\Support\Facades\DB;

class categoriaController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:ver-categoria|crear-categoria|editar-categoria|eliminar-categoria', ['only' => ['index']]);
        $this->middleware('permission:crear-categoria', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-categoria', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-categoria', ['only' => ['destroy', 'eliminar']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::with('caracteristica')->withCount('productos')->get()
            ->sortBy(fn ($c) => [$c->caracteristica->estado == 1 ? 0 : 1, mb_strtolower($c->caracteristica->nombre)])
            ->values();

        return view('categoria.index', ['categorias' => $categorias]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categoria.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCaracteristicaRequest $request)
    {
        $categoria = DB::transaction(function () use ($request) {
            $caracteristica = Caracteristica::create($request->validated());
            return $caracteristica->categoria()->create(['caracteristica_id' => $caracteristica->id]);
        });

        // Desde el formulario de producto se crea sin salir de la página
        if ($request->expectsJson()) {
            return response()->json(['id' => $categoria->id, 'nombre' => $request->validated()['nombre']]);
        }

        return redirect()->route('categorias.index')->with('success', 'Categoría registrada');
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
    public function edit(Categoria $categoria)
    {
        return view('categoria.edit', ['categoria' => $categoria]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        Caracteristica::where('id', $categoria->caracteristica->id)
            ->update($request->validated());

        return redirect()->route('categorias.index')->with('success', 'Categoría editada');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $message = '';
        $categoria = Categoria::findOrFail($id);
        if ($categoria->caracteristica->estado == 1) {
            Caracteristica::where('id', $categoria->caracteristica->id)
                ->update([
                    'estado' => 0
                ]);
            $message = 'Categoría eliminada';
        } else {
            Caracteristica::where('id', $categoria->caracteristica->id)
                ->update([
                    'estado' => 1
                ]);
            $message = 'Categoría restaurada';
        }

        return redirect()->route('categorias.index')->with('success', $message);
    }

    /**
     * Eliminar del todo (no solo ocultar). Los productos no se borran: si se quedan sin categoría pasan a "General".
     */
    public function eliminar(Categoria $categoria)
    {
        $categoria->load('caracteristica');
        $nombre = $categoria->caracteristica->nombre ?? 'la categoría';
        if (mb_strtolower($nombre) === 'general' && $categoria->productos()->exists()) {
            return back()->withErrors('La categoría General no se puede eliminar mientras tenga productos: es donde quedan los productos sin categoría.');
        }

        $movidos = DB::transaction(function () use ($categoria) {
            $productos = $categoria->productos()->withCount('categorias')->get();
            $sinOtra = $productos->filter(fn ($p) => $p->categorias_count <= 1);
            $movidos = 0;
            if ($sinOtra->isNotEmpty()) {
                $general = \App\Support\CrearTienda::categoria('General');
                if ($general->id !== $categoria->id) {
                    foreach ($sinOtra as $p) {
                        $p->categorias()->syncWithoutDetaching([$general->id]);
                        $movidos++;
                    }
                }
            }
            $categoria->productos()->detach();
            $car = $categoria->caracteristica;
            $categoria->delete();
            $car?->delete();
            return $movidos;
        });

        return redirect()->route('categorias.index')->with('success', 'Categoría ' . $nombre . ' eliminada.' . ($movidos ? ' Sus ' . $movidos . ' producto(s) quedaron en "General".' : ''));
    }
}
