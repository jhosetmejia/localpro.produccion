<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// Tienda en línea de cada negocio: productos por categoría, botón Comprar (WhatsApp con el producto exacto)
// y páginas por producto para que aparezcan en Google.
class catalogoController extends Controller
{
    private function tienda(string $slug): Tienda
    {
        $tienda = Tienda::with('plan')->where('slug', $slug)->firstOrFail();
        abort_unless($tienda->catalogo_activo && $tienda->activa && $tienda->onboarding_completo, 404);
        TiendaActual::set($tienda->id);
        return $tienda;
    }

    public static function urlProducto(Tienda $t, $p): string
    {
        return route('catalogo.producto', ['slug' => $t->slug, 'producto' => $p->id . '-' . (Str::slug($p->nombre) ?: 'producto')]);
    }

    private function datos(Tienda $tienda, Producto $p): array
    {
        $vars = $p->variantes->filter(fn ($v) => !$p->controla_inventario || $v->stock > 0)->values();
        $precios = $vars->map(fn ($v) => (float) ($v->precio ?? $p->precio_venta));
        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'codigo' => $p->codigo,
            'unidad' => $p->es_fraccion ? $p->unidad_corta : null,
            'descripcion' => $p->descripcion,
            'precio' => (float) $p->precio_venta,
            'desde' => $precios->count() > 1 && $precios->min() < $precios->max() ? (float) $precios->min() : null,
            'img' => $p->img_path ? asset('storage/productos/' . rawurlencode($p->img_path)) : null,
            'url' => self::urlProducto($tienda, $p),
            'categorias' => $p->categorias->map(fn ($c) => $c->caracteristica->nombre ?? null)->filter()->values()->all(),
            'disponible' => $vars->isNotEmpty(),
            'variantes' => $vars->map(fn ($v) => ['id' => $v->id, 'etiqueta' => $v->etiqueta, 'talla' => $v->talla, 'color' => $v->color, 'precio' => (float) ($v->precio ?? $p->precio_venta)])->all(),
        ];
    }

    private function productos(Tienda $tienda)
    {
        return Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica'])
            ->where('estado', 1)->orderBy('nombre')->get()
            ->map(fn ($p) => $this->datos($tienda, $p))
            ->sortByDesc('disponible')->values();
    }

    public function show(string $slug)
    {
        $tienda = $this->tienda($slug);
        $productos = $this->productos($tienda);

        // Secciones por categoría (las que más productos tienen primero; "General" al final)
        $secciones = [];
        foreach ($productos as $p) {
            $cats = $p['categorias'] ?: ['General'];
            $secciones[$cats[0]][] = $p;
        }
        uksort($secciones, fn ($a, $b) => ($a === 'General') <=> ($b === 'General') ?: count($secciones[$b]) <=> count($secciones[$a]));
        $categorias = collect(array_keys($secciones));

        return view('catalogo.show', compact('tienda', 'productos', 'categorias', 'secciones'));
    }

    public function producto(string $slug, string $producto)
    {
        $tienda = $this->tienda($slug);
        $id = (int) $producto;
        $p = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica'])
            ->where('estado', 1)->findOrFail($id);
        $dato = $this->datos($tienda, $p);
        // Si el nombre cambió, redirige a la dirección correcta (bueno para Google)
        if ('/' . trim(request()->path(), '/') !== rtrim((string) parse_url($dato['url'], PHP_URL_PATH), '/')) {
            return redirect($dato['url'], 301);
        }
        $catIds = $p->categorias->pluck('id');
        $relacionados = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1), 'categorias.caracteristica'])
            ->where('estado', 1)->where('id', '<>', $p->id)
            ->when($catIds->isNotEmpty(), fn ($q) => $q->whereHas('categorias', fn ($c) => $c->whereIn('categorias.id', $catIds)))
            ->inRandomOrder()->take(8)->get()->map(fn ($r) => $this->datos($tienda, $r))->filter(fn ($r) => $r['disponible'])->values();

        return view('catalogo.producto', ['tienda' => $tienda, 'p' => $dato, 'relacionados' => $relacionados]);
    }

    // Mapa del sitio para Google: tiendas con plan pago y sus productos
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [['loc' => url('/'), 'pri' => '1.0', 'mod' => null], ['loc' => route('registro'), 'pri' => '0.8', 'mod' => null]];
            $urls[] = ['loc' => route('blog.index'), 'pri' => '0.6', 'mod' => null];
            foreach (array_keys(config('blog', [])) as $slug) $urls[] = ['loc' => route('blog.show', $slug), 'pri' => '0.7', 'mod' => null];
            foreach (Tienda::with('plan')->where('catalogo_activo', true)->where('estado', 'activa')->where('onboarding_completo', true)->get() as $t) {
                if (!$t->en_google) continue;
                $urls[] = ['loc' => $t->url_catalogo, 'pri' => '0.9', 'mod' => $t->updated_at];
                TiendaActual::como($t->id, function () use ($t, &$urls) {
                    foreach (Producto::where('estado', 1)->get(['id', 'nombre', 'updated_at']) as $p) {
                        $urls[] = ['loc' => self::urlProducto($t, $p), 'pri' => '0.7', 'mod' => $p->updated_at];
                    }
                });
            }
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $u) {
                $out .= '<url><loc>' . e($u['loc']) . '</loc>' . ($u['mod'] ? '<lastmod>' . $u['mod']->toAtomString() . '</lastmod>' : '') . '<priority>' . $u['pri'] . '</priority></url>' . "\n";
            }
            return $out . '</urlset>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
