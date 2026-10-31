<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\InventarioConteo;
use App\Models\InventarioConteoItem;
use App\Models\ProductoVariante;
use App\Support\CodigoBarras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Resumen de inventario (por talla, categoría y color) y conteo físico para comparar lo del sistema con lo de la bodega.
class inventarioConteoController extends Controller
{
    const ORDEN_TALLAS = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '2XL', '3XL', '4XL', '5XL', 'U', 'UNICA', 'ÚNICA'];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-inventario');
        $this->middleware('permission:ajustar-inventario')->only(['aplicar', 'cancelar']);
    }

    // Tallas y colores activos de productos activos que llevan control de inventario
    private function base(?int $categoriaId = null)
    {
        return ProductoVariante::query()
            ->join('productos as p', 'p.id', '=', 'producto_variantes.producto_id')
            ->where('producto_variantes.estado', 1)
            ->where('p.estado', 1)
            ->where('p.controla_inventario', 1)
            ->when($categoriaId, fn ($q) => $q->whereExists(fn ($e) => $e->from('categoria_producto')
                ->whereColumn('categoria_producto.producto_id', 'p.id')->where('categoria_producto.categoria_id', $categoriaId)));
    }

    private function categorias()
    {
        return Categoria::with('caracteristica:id,nombre')->get()
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->caracteristica?->nombre])
            ->filter(fn ($c) => $c['nombre'])->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public static function ordenTalla($a, $b): int
    {
        $a = mb_strtoupper((string) $a); $b = mb_strtoupper((string) $b);
        $ia = array_search($a, self::ORDEN_TALLAS, true); $ib = array_search($b, self::ORDEN_TALLAS, true);
        if ($ia !== false || $ib !== false) return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
        if (is_numeric($a) && is_numeric($b)) return (float) $a <=> (float) $b;
        if ($a === 'SIN TALLA') return 1;
        if ($b === 'SIN TALLA') return -1;
        return strnatcasecmp($a, $b);
    }

    public function resumen(Request $request)
    {
        $categorias = $this->categorias();
        $catId = $request->integer('categoria') ?: null;
        if ($catId && !$categorias->firstWhere('id', $catId)) $catId = null;

        $filas = $this->base($catId)->get([
            'producto_variantes.id', 'producto_variantes.talla', 'producto_variantes.color', 'producto_variantes.stock', 'p.id as pid',
            DB::raw('COALESCE(producto_variantes.precio, p.precio_venta, 0) as pv'),
            DB::raw('COALESCE(producto_variantes.costo, p.precio_costo, 0) as pc'),
        ]);

        // Nombres de categoría de cada producto (un producto puede estar en varias)
        $catsDe = DB::table('categoria_producto')
            ->join('categorias', 'categorias.id', '=', 'categoria_producto.categoria_id')
            ->join('caracteristicas', 'caracteristicas.id', '=', 'categorias.caracteristica_id')
            ->whereIn('categoria_producto.producto_id', $filas->pluck('pid')->unique())
            ->get(['categoria_producto.producto_id', 'caracteristicas.nombre'])
            ->groupBy('producto_id')->map(fn ($g) => $g->pluck('nombre')->unique()->values()->all());

        $agrupar = function ($claveDe) use ($filas) {
            $g = [];
            foreach ($filas as $f) {
                $u = max(0, (float) $f->stock);
                foreach ((array) $claveDe($f) as $k) {
                    $g[$k] ??= ['unidades' => 0, 'refs' => 0, 'productos' => [], 'venta' => 0, 'costo' => 0, 'agotadas' => 0];
                    $g[$k]['unidades'] += $u;
                    $g[$k]['refs']++;
                    $g[$k]['productos'][$f->pid] = 1;
                    $g[$k]['venta'] += $u * (float) $f->pv;
                    $g[$k]['costo'] += $u * (float) $f->pc;
                    if ($u <= 0) $g[$k]['agotadas']++;
                }
            }
            foreach ($g as &$x) $x['productos'] = count($x['productos']);
            return $g;
        };

        $porTalla = $agrupar(fn ($f) => trim((string) $f->talla) !== '' ? mb_strtoupper(trim($f->talla)) : 'SIN TALLA');
        uksort($porTalla, [self::class, 'ordenTalla']);
        $porColor = $agrupar(fn ($f) => trim((string) $f->color) !== '' ? mb_strtoupper(trim($f->color)) : 'SIN COLOR');
        uasort($porColor, fn ($a, $b) => $b['unidades'] <=> $a['unidades']);
        $porCategoria = $agrupar(fn ($f) => $catsDe[$f->pid] ?? ['Sin categoría']);
        uasort($porCategoria, fn ($a, $b) => $b['unidades'] <=> $a['unidades']);

        $total = [
            'unidades' => $filas->sum(fn ($f) => max(0, (float) $f->stock)),
            'productos' => $filas->pluck('pid')->unique()->count(),
            'refs' => $filas->count(),
            'venta' => $filas->sum(fn ($f) => max(0, (float) $f->stock) * (float) $f->pv),
            'costo' => $filas->sum(fn ($f) => max(0, (float) $f->stock) * (float) $f->pc),
            'negativos' => $filas->filter(fn ($f) => (float) $f->stock < 0)->count(),
        ];

        // Al tocar una talla: lista de productos (con color y cantidad) que tienen esa talla
        $talla = mb_strtoupper(trim((string) $request->get('talla')));
        if ($talla === '' || !isset($porTalla[$talla])) $talla = null;
        $detalleTalla = collect();
        if ($talla) {
            $deTalla = $filas->filter(fn ($f) => (trim((string) $f->talla) !== '' ? mb_strtoupper(trim($f->talla)) : 'SIN TALLA') === $talla);
            $nombres = DB::table('productos')->whereIn('id', $deTalla->pluck('pid')->unique())->pluck('nombre', 'id');
            $detalleTalla = $deTalla->groupBy('pid')->map(fn ($g, $pid) => [
                'id' => $pid,
                'nombre' => $nombres[$pid] ?? 'Producto',
                'unidades' => $g->sum(fn ($f) => max(0, (float) $f->stock)),
                'venta' => $g->sum(fn ($f) => max(0, (float) $f->stock) * (float) $f->pv),
                'colores' => $g->map(fn ($f) => [
                    'color' => trim((string) $f->color) !== '' ? mb_convert_case(trim($f->color), MB_CASE_TITLE) : 'Sin color',
                    'stock' => (float) $f->stock,
                ])->sortByDesc('stock')->values()->all(),
            ])->sortBy([['unidades', 'desc'], ['nombre', 'asc']])->values();
        }

        $abierto = InventarioConteo::where('estado', 'abierto')->latest()->first();

        return view('inventario.resumen', compact('categorias', 'catId', 'porTalla', 'porColor', 'porCategoria', 'total', 'abierto', 'talla', 'detalleTalla'));
    }

    public function index()
    {
        $conteos = InventarioConteo::with(['user:id,name', 'categoria.caracteristica:id,nombre'])
            ->withCount(['items', 'items as contados_count' => fn ($q) => $q->whereNotNull('contado')])
            ->latest()->paginate(20);
        $categorias = $this->categorias();

        return view('inventario.conteos', compact('conteos', 'categorias'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'categoria_id' => 'nullable|integer',
        ]);
        $catId = $datos['categoria_id'] ?? null;
        $cat = $catId ? $this->categorias()->firstWhere('id', $catId) : null;
        if ($catId && !$cat) $catId = null;

        $variantes = $this->base($catId)->get(['producto_variantes.id', 'producto_variantes.stock']);
        if ($variantes->isEmpty()) {
            return back()->with('error', 'No hay productos con inventario para contar' . ($cat ? ' en ' . $cat['nombre'] : '') . '.');
        }

        $conteo = DB::transaction(function () use ($datos, $cat, $catId, $variantes) {
            $conteo = InventarioConteo::create([
                'user_id' => auth()->id(),
                'nombre' => trim($datos['nombre'] ?? '') ?: 'Conteo ' . ($cat ? $cat['nombre'] . ' ' : '') . now()->format('d/m/Y'),
                'categoria_id' => $catId,
                'estado' => 'abierto',
            ]);
            foreach ($variantes->chunk(500) as $lote) {
                InventarioConteoItem::insert($lote->map(fn ($v) => [
                    'inventario_conteo_id' => $conteo->id,
                    'producto_variante_id' => $v->id,
                    'stock_sistema' => (float) $v->stock,
                ])->values()->all());
            }
            return $conteo;
        });

        return redirect()->route('inventario.conteos.show', $conteo)->with('success', 'Conteo abierto. Escanee o escriba lo que hay en físico.');
    }

    public function show(InventarioConteo $conteo)
    {
        $items = $conteo->items()
            ->with(['variante:id,producto_id,talla,color,codigo_barras', 'variante.producto:id,nombre,codigo,img_path'])
            ->get()
            ->filter(fn ($i) => $i->variante && $i->variante->producto)
            ->sortBy(fn ($i) => [$i->variante->producto->nombre, $i->variante->talla, $i->variante->color])
            ->values();

        $grupos = $items->groupBy(fn ($i) => $i->variante->producto_id)
            ->map(fn ($g) => $g->sort(fn ($a, $b) => self::ordenTalla($a->variante->talla, $b->variante->talla) ?: strnatcasecmp((string) $a->variante->color, (string) $b->variante->color))->values());

        $resumen = $this->numeros($conteo);

        return view('inventario.conteo', compact('conteo', 'grupos', 'resumen'));
    }

    private function numeros(InventarioConteo $conteo): array
    {
        $items = $conteo->items()
            ->join('producto_variantes as v', 'v.id', '=', 'inventario_conteo_items.producto_variante_id')
            ->join('productos as p', 'p.id', '=', 'v.producto_id')
            ->get(['inventario_conteo_items.stock_sistema', 'inventario_conteo_items.contado', DB::raw('COALESCE(v.costo, p.precio_costo, 0) as pc')]);
        $contados = $items->whereNotNull('contado');
        $falta = 0; $sobra = 0; $vFalta = 0; $vSobra = 0; $conDif = 0;
        foreach ($contados as $i) {
            $d = round((float) $i->contado - (float) $i->stock_sistema, 3);
            if ($d < 0) { $falta += -$d; $vFalta += -$d * (float) $i->pc; $conDif++; }
            if ($d > 0) { $sobra += $d; $vSobra += $d * (float) $i->pc; $conDif++; }
        }
        return [
            'refs' => $items->count(),
            'contadas' => $contados->count(),
            'sistema' => $items->sum(fn ($i) => max(0, (float) $i->stock_sistema)),
            'sistema_contadas' => $contados->sum(fn ($i) => max(0, (float) $i->stock_sistema)),
            'fisico' => $contados->sum('contado'),
            'faltan' => $falta, 'sobran' => $sobra, 'valor_faltan' => $vFalta, 'valor_sobran' => $vSobra, 'con_diferencia' => $conDif,
            'sin_contar_unidades' => $items->whereNull('contado')->sum(fn ($i) => max(0, (float) $i->stock_sistema)),
        ];
    }

    // Guarda una cantidad (escrita a mano) o suma 1 por cada prenda escaneada
    public function contar(Request $request, InventarioConteo $conteo)
    {
        if (!$conteo->abierto) return response()->json(['ok' => false, 'msg' => 'Este conteo ya está cerrado.'], 422);

        $datos = $request->validate([
            'item' => 'nullable|integer',
            'contado' => 'nullable|numeric|min:0|max:100000',
            'codigo' => 'nullable|string|max:80',
        ]);

        $item = null;
        if (!empty($datos['codigo'])) {
            $codigo = CodigoBarras::resolver(trim($datos['codigo']));
            $variante = ProductoVariante::with('producto:id,nombre')->where('codigo_barras', $codigo)->where('estado', 1)->first();
            if (!$variante) {
                // Etiqueta pegada a mercancía que todavía no se ha creado: se ofrece crearla con ese código
                $libre = CodigoBarras::libre($codigo);
                return response()->json([
                    'ok' => false,
                    'msg' => $libre ? 'La etiqueta ' . $codigo . ' es de mercancía nueva y todavía no tiene producto.' : 'No encontré ninguna prenda con el código ' . $codigo . '.',
                    'crear_url' => auth()->user()?->can('crear-producto') ? route('productos.create', ['barras' => $codigo]) : null,
                ]);
            }
            $item = $conteo->items()->where('producto_variante_id', $variante->id)->first();
            if (!$item) {
                // Si el conteo es de todo el inventario y la talla/color no estaba (creada después), se agrega
                if ($conteo->categoria_id) {
                    return response()->json(['ok' => false, 'msg' => $variante->producto->nombre . ' no es de la categoría de este conteo.']);
                }
                $item = $conteo->items()->create(['producto_variante_id' => $variante->id, 'stock_sistema' => (float) $variante->stock]);
            }
            DB::table('inventario_conteo_items')->where('id', $item->id)->update([
                'contado' => DB::raw('COALESCE(contado, 0) + 1'), 'user_id' => auth()->id(), 'contado_at' => now(),
            ]);
        } else {
            $item = $conteo->items()->whereKey($datos['item'] ?? 0)->firstOrFail();
            $item->update([
                'contado' => array_key_exists('contado', $datos) && $datos['contado'] !== null ? round((float) $datos['contado'], 3) : null,
                'user_id' => auth()->id(), 'contado_at' => now(),
            ]);
        }

        $item = $item->fresh(['variante.producto:id,nombre']);
        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id, 'contado' => $item->contado, 'sistema' => $item->stock_sistema,
                'nombre' => $item->variante->producto->nombre,
                'detalle' => trim($item->variante->talla . ' ' . $item->variante->color),
                'producto_id' => $item->variante->producto_id,
            ],
            'resumen' => $this->numeros($conteo),
        ]);
    }

    public function aplicar(Request $request, InventarioConteo $conteo)
    {
        if (!$conteo->abierto) return back()->with('error', 'Este conteo ya está cerrado.');
        $cero = $request->input('sin_contar') === 'cero';

        $res = DB::transaction(function () use ($conteo, $cero) {
            $r = ['ajustadas' => 0, 'faltan' => 0, 'sobran' => 0];
            $conteo->items()->with('variante')->lockForUpdate()->get()->each(function ($i) use ($conteo, $cero, &$r) {
                if (!$i->variante) return;
                $contado = $i->contado ?? ($cero ? 0.0 : null);
                if ($contado === null) return;
                $delta = round($contado - $i->stock_sistema, 3);
                if (abs($delta) < 0.0005) return;
                // Se suma la diferencia al stock actual: lo vendido mientras se contaba se respeta
                $i->variante->moverStock($delta, 'conteo', 'CONTEO-' . $conteo->id, 'Conteo físico: ' . $conteo->nombre);
                $r['ajustadas']++;
                if ($delta < 0) $r['faltan'] += -$delta; else $r['sobran'] += $delta;
            });
            $conteo->update([
                'estado' => 'aplicado', 'aplicado_por' => auth()->id(), 'aplicado_at' => now(),
                'resumen' => $this->numeros($conteo) + $r + ['sin_contar' => $cero ? 'cero' : 'dejar'],
            ]);
            return $r;
        });

        return redirect()->route('inventario.conteos.show', $conteo)->with('success', $res['ajustadas']
            ? 'Inventario ajustado: ' . $res['ajustadas'] . ' tallas/colores corregidas (' . cantidad($res['faltan']) . ' faltaban, ' . cantidad($res['sobran']) . ' sobraban).'
            : 'Conteo cerrado: todo coincidía con el sistema. 🎉');
    }

    public function cancelar(InventarioConteo $conteo)
    {
        if ($conteo->abierto) $conteo->update(['estado' => 'cancelado']);
        return redirect()->route('inventario.conteos.index')->with('success', 'Conteo cancelado. No se cambió el inventario.');
    }
}
