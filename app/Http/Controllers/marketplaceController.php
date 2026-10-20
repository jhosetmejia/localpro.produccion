<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Tienda;
use App\Support\Paises;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// LocalProo Marketplace: todas las tiendas en línea de LocalPro en un solo lugar (tipo Mercado Libre).
// El cliente busca un producto y ve resultados de todas las tiendas, o explora las tiendas por tipo y ciudad.
// Cada producto lleva a su página en la tienda del negocio, donde compra por WhatsApp.
class marketplaceController extends Controller
{
    public const POR_PAGINA = 48;

    // Tiendas que se muestran: tienda en línea activa, negocio activo, registro terminado y datos sin depurar
    private function tiendasVisibles()
    {
        return Tienda::query()
            ->where('tiendas.catalogo_activo', true)
            ->where('tiendas.estado', 'activa')
            ->where('tiendas.onboarding_completo', true)
            ->whereNull('tiendas.datos_depurados_at')
            ->whereNotIn('tiendas.tipo_negocio', ['contable', 'conjunto']);
    }

    // Productos que se ven en la tienda en línea, de todas las tiendas visibles (sin el filtro de tienda actual)
    private function productosVisibles()
    {
        $ids = $this->tiendasVisibles()->select('tiendas.id');
        return Producto::withoutGlobalScope('tienda')
            ->enCatalogo()
            ->whereIn('productos.tienda_id', $ids)
            // Con existencias (o que no controla inventario)
            ->where(fn ($q) => $q->where('productos.controla_inventario', false)
                ->orWhereHas('variantes', fn ($v) => $v->where('estado', 1)->where('stock', '>', 0)));
    }

    public static function precio(?Tienda $t, $valor): string
    {
        $m = Paises::datos($t?->pais);
        return $m['simbolo'] . number_format((float) $valor, $m['decimales'], $m['decimal'], $m['miles']);
    }

    public function index(Request $request)
    {
        // Página pública de todas las tiendas: sin el filtro de "tienda actual" (aunque entre el admin o un dueño con sesión)
        \App\Support\TiendaActual::set(null);

        $q = trim(Str::limit((string) $request->query('q', ''), 80, ''));
        $tipo = (string) $request->query('tipo', '');
        $ciudad = trim((string) $request->query('ciudad', ''));
        $orden = in_array($request->query('orden'), ['menor', 'mayor', 'nuevos'], true) ? $request->query('orden') : 'menor';
        if (!array_key_exists($tipo, Tienda::TIPOS)) $tipo = '';

        // Productos (buscados o los más recientes con foto)
        $productos = $this->productosVisibles()
            ->with(['tienda', 'fotos'])
            ->when($q !== '', function ($w) use ($q) {
                foreach (preg_split('/\s+/', $q) as $pal) {
                    if (mb_strlen($pal) < 2) continue;
                    $w->where(fn ($x) => $x->where('productos.nombre', 'like', "%{$pal}%")
                        ->orWhere('productos.descripcion', 'like', "%{$pal}%")
                        ->orWhereHas('tienda', fn ($t) => $t->where('nombre', 'like', "%{$pal}%")));
                }
            })
            ->when($tipo !== '', fn ($w) => $w->whereHas('tienda', fn ($t) => $t->where('tipo_negocio', $tipo)))
            ->when($ciudad !== '', fn ($w) => $w->whereHas('tienda', fn ($t) => $t->where('ciudad', $ciudad)))
            ->when($orden === 'menor', fn ($w) => $w->orderBy('productos.precio_venta'))
            ->when($orden === 'mayor', fn ($w) => $w->orderByDesc('productos.precio_venta'))
            ->when($orden === 'nuevos', fn ($w) => $w->orderByDesc('productos.id'))
            ->orderBy('productos.id')
            ->paginate(self::POR_PAGINA, ['productos.*'])
            ->withQueryString();

        $productos->getCollection()->transform(function (Producto $p) {
            $t = $p->tienda;
            $foto = $p->img_path ? asset('storage/productos/' . rawurlencode($p->img_path)) : optional($p->fotos->first())->url;
            return (object) [
                'nombre' => \App\Support\Textos::titulo($p->nombre),
                'precio' => self::precio($t, $p->precio_venta),
                'antes' => $p->descuento ? self::precio($t, $p->precio_antes) : null,
                'descuento' => $p->descuento,
                'img' => $foto,
                'url' => catalogoController::urlProducto($t, $p),
                'tienda' => $t->nombre,
                'tienda_url' => $t->url_catalogo,
                'ciudad' => $t->ciudad,
            ];
        });

        // Tiendas (con cuántos productos tiene cada una en su tienda en línea)
        $conteo = $this->productosVisibles()->select('productos.tienda_id', DB::raw('count(*) as n'))
            ->groupBy('productos.tienda_id')->pluck('n', 'productos.tienda_id');

        $tiendas = $this->tiendasVisibles()
            ->whereIn('id', $conteo->keys())
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%{$q}%")->orWhere('descripcion', 'like', "%{$q}%")))
            ->when($tipo !== '', fn ($w) => $w->where('tipo_negocio', $tipo))
            ->when($ciudad !== '', fn ($w) => $w->where('ciudad', $ciudad))
            ->get()
            ->each(fn ($t) => $t->n_productos = (int) ($conteo[$t->id] ?? 0))
            // Primero las que tienen plan pago al día, luego las de más productos
            ->sortBy([fn ($a, $b) => (int) $b->en_google <=> (int) $a->en_google, fn ($a, $b) => $b->n_productos <=> $a->n_productos])
            ->values();

        // Filtros: solo tipos y ciudades que tienen tiendas
        $base = $this->tiendasVisibles()->whereIn('id', $conteo->keys());
        $tipos = (clone $base)->select('tipo_negocio', DB::raw('count(*) as n'))->groupBy('tipo_negocio')->pluck('n', 'tipo_negocio')
            ->filter(fn ($n, $k) => isset(Tienda::TIPOS[$k]))->sortDesc();
        $ciudades = (clone $base)->whereNotNull('ciudad')->where('ciudad', '<>', '')->distinct()->orderBy('ciudad')->pluck('ciudad');

        $totalTiendas = $conteo->count();
        $totalProductos = (int) $conteo->sum();
        $filtrando = $q !== '' || $tipo !== '' || $ciudad !== '';

        return view('marketplace.index', compact('productos', 'tiendas', 'tipos', 'ciudades', 'q', 'tipo', 'ciudad', 'orden', 'totalTiendas', 'totalProductos', 'filtrando'));
    }
}
