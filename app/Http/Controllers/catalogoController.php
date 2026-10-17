<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
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

    // ¿Quien mira la tienda es el dueño o alguien de la tienda que puede editar productos?
    public static function puedeEditar(Tienda $tienda): bool
    {
        $u = auth()->user();
        return $u && (int) $u->tienda_id === (int) $tienda->id && $u->can('editar-producto');
    }

    // Para la tienda abierta con www (sin la sesión): el navegador pregunta al dominio principal
    // si la persona es de la tienda, y así se muestra el lápiz de editar.
    public function yo(Request $request, string $slug)
    {
        $tienda = Tienda::where('slug', $slug)->firstOrFail();
        $ok = self::puedeEditar($tienda);
        $origen = $request->headers->get('Origin');
        $dominio = config('app.dominio');
        $resp = response()->json($ok ? ['editar' => url('/productos/__ID__/edit'), 'panel' => route('productos.index')] : ['editar' => null])
            ->header('Cache-Control', 'private, no-store');
        if ($origen && $dominio && in_array($origen, ['https://www.' . $dominio, 'https://' . $dominio], true)) {
            $resp->header('Access-Control-Allow-Origin', $origen)->header('Access-Control-Allow-Credentials', 'true')->header('Vary', 'Origin');
        }
        return $resp;
    }

    public static function urlProducto(Tienda $t, $p): string
    {
        return route('catalogo.producto', ['slug' => $t->slug, 'producto' => $p->id . '-' . (Str::slug($p->nombre) ?: 'producto')]);
    }

    private function datos(Tienda $tienda, Producto $p): array
    {
        $vars = $p->variantes->filter(fn ($v) => !$p->controla_inventario || $v->stock > 0)->values();
        $precios = $vars->map(fn ($v) => (float) ($v->precio ?? $p->precio_venta));
        $fotosColor = $p->relationLoaded('fotos') ? $p->fotos->filter(fn ($f) => $f->color)->mapWithKeys(fn ($f) => [$f->color => $f->url])->all() : [];
        return [
            'id' => $p->id,
            // Para el público: "CHAQUETA ROMPE VIENTOS" → "Chaqueta rompe vientos" (se lee mejor y Google lo prefiere)
            'nombre' => \App\Support\Textos::titulo($p->nombre),
            'codigo' => $p->codigo,
            'unidad' => $p->es_fraccion ? $p->unidad_corta : null,
            'descripcion' => $p->descripcion,
            'precio' => (float) $p->precio_venta,
            // Promoción: precio antes tachado y % de descuento
            'antes' => $p->descuento ? (float) $p->precio_antes : null,
            'descuento' => $p->descuento,
            'desde' => $precios->count() > 1 && $precios->min() < $precios->max() ? (float) $precios->min() : null,
            // Foto principal; si no tiene, la del primer color
            'img' => $p->img_path ? asset('storage/productos/' . rawurlencode($p->img_path)) : ($fotosColor ? reset($fotosColor) : null),
            // Foto de cada color: {"NEGRO": url, ...}
            'fotos' => $fotosColor,
            'url' => self::urlProducto($tienda, $p),
            'categorias' => $cats = $p->categorias->map(fn ($c) => $c->caracteristica->nombre ?? null)->filter()->values()->all(),
            'para' => $p->para ? ($p->para === 'todos' ? '' : $p->para) : self::para($p->nombre . ' ' . implode(' ', $cats)),
            'disponible' => $vars->isNotEmpty(),
            'variantes' => $vars->map(fn ($v) => ['id' => $v->id, 'etiqueta' => $v->etiqueta, 'talla' => $v->talla, 'color' => $v->color, 'precio' => (float) ($v->precio ?? $p->precio_venta)])->all(),
        ];
    }

    // Para quién es el producto según su nombre y categorías: mujer, hombre, ninos o '' (para todos)
    public static function para(string $texto): string
    {
        $t = ' ' . mb_strtolower($texto) . ' ';
        if (preg_match('/\b(niñ[oa]s?|nin[oa]s?|infantil(es)?|beb[eé]s?|kids?|junior)\b/u', $t)) return 'ninos';
        $mujer = (bool) preg_match('/\b(damas?|mujer(es)?|femenin[oa]s?|señoras?|chicas?|ladies)\b/u', $t);
        $hombre = (bool) preg_match('/\b(caballeros?|hombres?|masculin[oa]s?|señor(es)?|chicos?|men)\b/u', $t);
        if (preg_match('/\bunisex\b/u', $t) || $mujer === $hombre) return '';
        return $mujer ? 'mujer' : 'hombre';
    }

    private function productos(Tienda $tienda)
    {
        return Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica', 'fotos'])
            ->where('estado', 1)->orderBy('nombre')->get()
            ->map(fn ($p) => $this->datos($tienda, $p))
            // El dueño puede elegir mostrar solo lo que tiene existencias
            ->filter(fn ($p) => $p['disponible'] || $tienda->catalogo_agotados !== false)
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

        // Para quién es cada categoría (para el filtro Mujer / Hombre / Niños)
        $catPara = [];
        foreach ($productos as $p) {
            foreach ($p['categorias'] ?: ['General'] as $c) $catPara[$c][$p['para'] ?: 'todos'] = true;
        }
        $catPara = array_map(fn ($g) => implode(' ', array_keys($g)), $catPara);

        // Solo se muestra el filtro si hay al menos dos grupos (ej. ropa de dama y de caballero)
        $grupos = ['mujer' => ['👩', 'Mujer'], 'hombre' => ['👨', 'Hombre'], 'ninos' => ['🧒', 'Niños']];
        $publicos = collect($grupos)->filter(fn ($g, $k) => $productos->where('para', $k)->isNotEmpty());
        if ($publicos->count() < 2) $publicos = collect();

        $enPromo = $productos->where('disponible', true)->where('descuento', '>', 0)->count();

        return view('catalogo.show', compact('tienda', 'productos', 'categorias', 'secciones', 'catPara', 'publicos', 'enPromo'));
    }

    public function producto(string $slug, string $producto)
    {
        $tienda = $this->tienda($slug);
        $id = (int) $producto;
        $p = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica', 'fotos'])
            ->where('estado', 1)->findOrFail($id);
        $dato = $this->datos($tienda, $p);
        // Agotado y la tienda solo muestra lo que hay: vuelve al catálogo
        if (!$dato['disponible'] && $tienda->catalogo_agotados === false) {
            return redirect()->route('catalogo.show', $tienda->slug);
        }
        // Si el nombre cambió, redirige a la dirección correcta (bueno para Google)
        if ('/' . trim(request()->path(), '/') !== rtrim((string) parse_url($dato['url'], PHP_URL_PATH), '/')) {
            return redirect($dato['url'], 301);
        }
        $catIds = $p->categorias->pluck('id');
        $relacionados = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1), 'categorias.caracteristica', 'fotos'])
            ->where('estado', 1)->where('id', '<>', $p->id)
            ->when($catIds->isNotEmpty(), fn ($q) => $q->whereHas('categorias', fn ($c) => $c->whereIn('categorias.id', $catIds)))
            ->inRandomOrder()->take(12)->get()->map(fn ($r) => $this->datos($tienda, $r))->filter(fn ($r) => $r['disponible'])
            // Primero los que tienen foto (se ven mejor al lado del producto)
            ->sortByDesc(fn ($r) => (bool) $r['img'])->take(8)->values();

        return view('catalogo.producto', ['tienda' => $tienda, 'p' => $dato, 'relacionados' => $relacionados]);
    }

    // Mapa del sitio para Google: tiendas con plan pago y sus productos
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [['loc' => url('/'), 'pri' => '1.0', 'mod' => null], ['loc' => route('registro'), 'pri' => '0.8', 'mod' => null]];
            $urls[] = ['loc' => route('blog.index'), 'pri' => '0.6', 'mod' => null];
            foreach (array_keys(config('blog_roles', [])) as $rol) $urls[] = ['loc' => route('blog.rol', $rol), 'pri' => '0.8', 'mod' => null];
            foreach (config('blog', []) as $slug => $a) {
                $f = $a['actualizado'] ?? $a['publicado'] ?? null;
                $urls[] = ['loc' => route('blog.show', $slug), 'pri' => '0.7', 'mod' => $f ? \Illuminate\Support\Carbon::parse($f) : null];
            }
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
