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
    // Cuenta una visita a la tienda en línea: una por persona cada 30 minutos.
    // No cuentan el dueño ni su equipo, ni los robots (Google, vista previa de WhatsApp/Facebook…)
    private function contarVisita(Tienda $tienda): void
    {
        $u = auth()->user();
        if ($u && (int) $u->tienda_id === (int) $tienda->id) return;
        if (preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|python|headless/i', (string) request()->userAgent())) return;
        $clave = 'visita_tienda_' . $tienda->id;
        $ultima = (int) session($clave);
        if ($ultima && $ultima > now()->subMinutes(30)->timestamp) return;
        session([$clave => now()->timestamp]);
        Tienda::whereKey($tienda->id)->increment('catalogo_visitas');
        $tienda->catalogo_visitas = (int) $tienda->catalogo_visitas + 1;
    }

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
        // Solo fotos cuyo archivo existe: una foto perdida no sale rota, el producto se trata como sin foto
        $fotosColor = $p->relationLoaded('fotos') ? $p->fotos->filter(fn ($f) => $f->color && $f->existe)->mapWithKeys(fn ($f) => [$f->color => $f->url])->all() : [];
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
            'img' => $p->foto_url ?: ($fotosColor ? reset($fotosColor) : null),
            'sin_foto_ok' => (bool) $p->mostrar_sin_foto,
            // Foto de cada color: {"NEGRO": url, ...}
            'fotos' => $fotosColor,
            'url' => self::urlProducto($tienda, $p),
            'categorias' => $cats = $p->categorias->map(fn ($c) => $c->caracteristica->nombre ?? null)->filter()->values()->all(),
            'para' => $p->para ? ($p->para === 'todos' ? '' : $p->para) : self::para($p->nombre . ' ' . implode(' ', $cats)),
            // Desactivado por el negocio (ej. se acabaron los almuerzos) = no se puede pedir
            'disponible' => $vars->isNotEmpty() && $p->disponible !== false,
            'desactivado' => $p->disponible === false,
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
            ->enCatalogo()->orderBy('nombre')->get()
            ->map(fn ($p) => $this->datos($tienda, $p))
            // Sin foto (o con la foto perdida en el servidor) solo sale si el dueño marcó "mostrar aunque no tenga foto"
            ->filter(fn ($p) => $p['img'] || $p['sin_foto_ok'])
            // El dueño puede elegir mostrar solo lo que tiene existencias
            ->filter(fn ($p) => $p['disponible'] || $tienda->catalogo_agotados !== false)
            // Primero lo disponible y siempre del más barato al más caro (enganchan los precios bajos)
            ->sortBy([
                fn ($a, $b) => (int) $b['disponible'] <=> (int) $a['disponible'],
                fn ($a, $b) => ($a['desde'] ?? $a['precio']) <=> ($b['desde'] ?? $b['precio']),
                fn ($a, $b) => strnatcasecmp($a['nombre'], $b['nombre']),
            ])->values();
    }

    public function show(string $slug)
    {
        $tienda = $this->tienda($slug);
        $this->contarVisita($tienda);
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

    // Imagen de la vista previa del link de la tienda (WhatsApp, Facebook, X, Telegram): 1200x630 con fotos de productos
    public static function datosImagen(Tienda $tienda, $productos): array
    {
        $fotos = collect($productos)->filter(fn ($p) => $p['disponible'] && $p['img'])->pluck('img')->unique()->take(4)->values()->all();
        return [
            'tienda' => [
                'id' => $tienda->id,
                'nombre' => $tienda->nombre,
                'lugar' => trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null]))),
                'cuantos' => collect($productos)->count(),
                'logo' => $tienda->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->path($tienda->logo_path) : null,
            ],
            'fotos' => $fotos,
            'v' => substr(md5(json_encode([$fotos, $tienda->logo_path, $tienda->nombre, $tienda->ciudad])), 0, 10),
        ];
    }

    public function imagenCompartir(string $slug)
    {
        $tienda = $this->tienda($slug);
        $d = self::datosImagen($tienda, $this->productos($tienda));
        $archivo = \App\Support\ImagenCompartir::tienda($d['tienda'], array_filter(array_map([\App\Support\ImagenCompartir::class, 'rutaLocal'], $d['fotos'])));
        if (!$archivo) {
            $logo = $tienda->logo_url;
            return $logo ? redirect($logo) : abort(404);
        }
        return response()->file($archivo, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400']);
    }

    // Carta virtual (restaurantes y cafeterías): menú por categorías con diseño propio y pedido por WhatsApp.
    // Aquí salen también los platos sin foto (en una carta es normal). Los desactivados salen al final
    // de su categoría como "Agotado hoy" y no se pueden pedir.
    public function carta(Request $request, string $slug)
    {
        $tienda = $this->tienda($slug);
        $this->contarVisita($tienda);
        [$secciones, $total] = $this->seccionesCarta($tienda);

        // ?tema=fresca deja ver otro diseño (para que el dueño compare antes de elegir)
        $tema = isset(Tienda::CARTA_TEMAS[$request->query('tema')]) ? $request->query('tema') : $tienda->carta_tema_actual;

        return view('catalogo.carta', compact('tienda', 'secciones', 'tema') + ['total' => $total]);
    }

    // Productos de la carta agrupados por categoría (los desactivados salen como "agotado por hoy")
    private function seccionesCarta(Tienda $tienda): array
    {
        $productos = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica', 'fotos'])
            ->where('productos.estado', 1)->get()
            ->map(fn ($p) => $this->datos($tienda, $p))
            ->filter(fn ($p) => $p['disponible'] || $p['desactivado'] || $tienda->catalogo_agotados !== false)
            ->sortBy([
                fn ($a, $b) => (int) $b['disponible'] <=> (int) $a['disponible'],
                fn ($a, $b) => ($a['desde'] ?? $a['precio']) <=> ($b['desde'] ?? $b['precio']),
                fn ($a, $b) => strnatcasecmp($a['nombre'], $b['nombre']),
            ])->values();

        $secciones = [];
        foreach ($productos as $p) $secciones[($p['categorias'] ?: ['Otros'])[0]][] = $p;
        uksort($secciones, fn ($a, $b) => ($a === 'Otros') <=> ($b === 'Otros') ?: count($secciones[$b]) <=> count($secciones[$a]));
        return [$secciones, $productos->count()];
    }

    // Carta para imprimir y compartir: 3 plantillas tamaño hoja, descarga en PDF y JPG, con QR a la carta en línea
    public const PLANTILLAS_CARTA = [
        'pro' => ['Pro negro y amarillo', 'Estilo restaurante profesional: brochazos, fotos y 3 columnas'],
        'clasica' => ['Clásica', 'Papel crema, letra elegante y puntos hasta el precio'],
        'pizarra' => ['Pizarra', 'Tablero de tiza con marco de madera'],
        'moderna' => ['Moderna con fotos', 'Fotos de cada plato y precios destacados'],
    ];

    public function cartaImprimir(Request $request, string $slug)
    {
        $tienda = $this->tienda($slug);
        [$secciones] = $this->seccionesCarta($tienda);
        $plantilla = isset(self::PLANTILLAS_CARTA[$request->query('plantilla')]) ? $request->query('plantilla') : 'pro';
        // Rutas de las fotos dentro del mismo dominio (para poder dibujarlas en el PDF/JPG)
        $ruta = fn ($u) => $u ? ((parse_url($u, PHP_URL_PATH) ?: '') . (($q = parse_url($u, PHP_URL_QUERY)) ? '?' . $q : '')) : null;
        $datos = [];
        foreach ($secciones as $nombre => $items) {
            $datos[] = ['nombre' => $nombre, 'items' => array_map(fn ($p) => [
                'nombre' => $p['nombre'],
                'desc' => $p['descripcion'] ? \Illuminate\Support\Str::limit(strip_tags($p['descripcion']), 110) : null,
                'precio' => ($p['desde'] ? 'Desde ' : '') . dinero($p['desde'] ?? $p['precio']) . ($p['unidad'] ? ' / ' . $p['unidad'] : ''),
                'img' => $ruta($p['img']),
                'tag' => $p['descuento'] ? 'PROMO -' . $p['descuento'] . '%' : null,
                'valor' => (float) ($p['desde'] ?? $p['precio']),
            ], $items)];
        }
        return view('catalogo.carta-imprimir', [
            'tienda' => $tienda, 'secciones' => $datos, 'plantilla' => $plantilla,
            'logo' => $ruta($tienda->logo_url),
            'accion' => in_array($request->query('accion'), ['pdf', 'jpg'], true) ? $request->query('accion') : null,
        ]);
    }

    public function producto(string $slug, string $producto)
    {
        $tienda = $this->tienda($slug);
        $this->contarVisita($tienda);
        $id = (int) $producto;
        $p = Producto::with(['variantes' => fn ($q) => $q->where('estado', 1)->orderBy('talla')->orderBy('color'), 'categorias.caracteristica', 'fotos'])
            ->enCatalogo()->findOrFail($id);
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
            ->enCatalogo()->where('id', '<>', $p->id)
            ->when($catIds->isNotEmpty(), fn ($q) => $q->whereHas('categorias', fn ($c) => $c->whereIn('categorias.id', $catIds)))
            ->inRandomOrder()->take(12)->get()->map(fn ($r) => $this->datos($tienda, $r))->filter(fn ($r) => $r['disponible'] && ($r['img'] || $r['sin_foto_ok']))
            // Primero los que tienen foto (se ven mejor al lado del producto)
            ->sortByDesc(fn ($r) => (bool) $r['img'])->take(8)
            ->sortBy(fn ($r) => $r['desde'] ?? $r['precio'])->values();

        return view('catalogo.producto', ['tienda' => $tienda, 'p' => $dato, 'relacionados' => $relacionados]);
    }

    // Mapa del sitio para Google: tiendas con plan pago y sus productos
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [['loc' => url('/'), 'pri' => '1.0', 'mod' => null], ['loc' => route('registro'), 'pri' => '0.8', 'mod' => null]];
            $urls[] = ['loc' => route('marketplace'), 'pri' => '0.9', 'mod' => null];
            $urls[] = ['loc' => route('funciones'), 'pri' => '0.9', 'mod' => null];
            $urls[] = ['loc' => route('blog.index'), 'pri' => '0.6', 'mod' => null];
            foreach (array_keys(config('blog_roles', [])) as $rol) $urls[] = ['loc' => route('blog.rol', $rol), 'pri' => '0.8', 'mod' => null];
            foreach (config('blog', []) as $slug => $a) {
                $f = $a['actualizado'] ?? $a['publicado'] ?? null;
                $urls[] = ['loc' => route('blog.show', $slug), 'pri' => '0.7', 'mod' => $f ? \Illuminate\Support\Carbon::parse($f) : null];
            }
            $mk = [];
            foreach (Tienda::with('plan')->where('catalogo_activo', true)->where('estado', 'activa')->where('onboarding_completo', true)->get() as $t) {
                // Páginas del Marketplace por ciudad y por tipo de negocio
                if ($t->ciudad) $mk['c:' . $t->ciudad] = route('marketplace', ['ciudad' => $t->ciudad]);
                if ($t->tipo_negocio && isset(Tienda::TIPOS[$t->tipo_negocio])) $mk['t:' . $t->tipo_negocio] = route('marketplace', ['tipo' => $t->tipo_negocio]);
                if (!$t->en_google) continue;
                $urls[] = ['loc' => $t->url_catalogo, 'pri' => '0.9', 'mod' => $t->updated_at];
                TiendaActual::como($t->id, function () use ($t, &$urls) {
                    foreach (Producto::enCatalogo()->get(['id', 'nombre', 'img_path', 'updated_at']) as $p) {
                        $urls[] = ['loc' => self::urlProducto($t, $p), 'pri' => '0.7', 'mod' => $p->updated_at,
                            'img' => $p->foto_url, 'tit' => \App\Support\Textos::titulo($p->nombre)];
                    }
                });
            }
            foreach ($mk as $u) $urls[] = ['loc' => $u, 'pri' => '0.8', 'mod' => null];
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
            foreach ($urls as $u) {
                $out .= '<url><loc>' . e($u['loc']) . '</loc>' . ($u['mod'] ? '<lastmod>' . $u['mod']->toAtomString() . '</lastmod>' : '') . '<priority>' . $u['pri'] . '</priority>' . (!empty($u['img']) ? '<image:image><image:loc>' . e($u['img']) . '</image:loc><image:title>' . e($u['tit'] ?? '') . '</image:title></image:image>' : '') . '</url>' . "\n";
            }
            return $out . '</urlset>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
