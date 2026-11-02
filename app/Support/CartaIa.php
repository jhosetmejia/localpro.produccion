<?php

namespace App\Support;

use App\Models\Tienda;
use Illuminate\Support\Facades\DB;

/**
 * Carta personalizada hecha con la IA del negocio (ChatGPT, Claude, Gemini…): el dueño pega el código HTML
 * y se publica en /t/{slug}/carta-ia. Por seguridad se limpia (sin scripts, iframes ni eventos) y se sirve
 * en "caja de arena" (CSP sandbox): aunque trajera código, no puede correr ni tocar las sesiones de LocalPro.
 */
class CartaIa
{
    public const MAX_BYTES = 600 * 1024;

    public static function existe(int $tiendaId): bool
    {
        return DB::table('cartas_ia')->where('tienda_id', $tiendaId)->exists();
    }

    public static function leer(int $tiendaId): ?string
    {
        return DB::table('cartas_ia')->where('tienda_id', $tiendaId)->value('html');
    }

    public static function fecha(int $tiendaId): ?\Illuminate\Support\Carbon
    {
        $f = DB::table('cartas_ia')->where('tienda_id', $tiendaId)->value('updated_at');
        return $f ? \Illuminate\Support\Carbon::parse($f) : null;
    }

    public static function enQr(int $tiendaId): bool
    {
        return (bool) DB::table('cartas_ia')->where('tienda_id', $tiendaId)->value('en_qr');
    }

    public static function usarEnQr(int $tiendaId, bool $si): void
    {
        DB::table('cartas_ia')->where('tienda_id', $tiendaId)->update(['en_qr' => $si]);
    }

    public static function guardar(int $tiendaId, string $html): void
    {
        DB::table('cartas_ia')->updateOrInsert(['tienda_id' => $tiendaId], ['html' => $html, 'updated_at' => now(), 'created_at' => now()]);
    }

    public static function borrar(int $tiendaId): void
    {
        DB::table('cartas_ia')->where('tienda_id', $tiendaId)->delete();
    }

    // ¿Quiere que su diseño sea público? null = no ha respondido, true = sí, false = no
    public static function compartido(int $tiendaId): ?bool
    {
        $v = DB::table('cartas_ia')->where('tienda_id', $tiendaId)->value('compartir');
        return $v === null ? null : (bool) $v;
    }

    public static function compartir(int $tiendaId, ?bool $si): void
    {
        DB::table('cartas_ia')->where('tienda_id', $tiendaId)->update(['compartir' => $si]);
    }

    /**
     * Diseños públicos para la galería: solo los que el dueño aceptó compartir, que traen las marcas
     * (así cada negocio ve SUS platos y precios, nunca los del autor) y que el administrador no ocultó.
     */
    public static function publicas(int $excluirTiendaId, int $max = 24): array
    {
        return DB::table('cartas_ia')
            ->join('tiendas', 'tiendas.id', '=', 'cartas_ia.tienda_id')
            ->where('cartas_ia.compartir', true)->where('cartas_ia.oculta', false)
            ->where('cartas_ia.tienda_id', '<>', $excluirTiendaId)
            ->where('cartas_ia.html', 'like', '%PLATOS%')
            ->orderByDesc('cartas_ia.usos')->orderByDesc('cartas_ia.updated_at')
            ->limit($max * 2)
            ->get(['cartas_ia.id', 'cartas_ia.html', 'cartas_ia.usos', 'tiendas.nombre', 'tiendas.ciudad'])
            ->filter(fn ($r) => self::esPlantilla($r->html))
            ->take($max)
            ->map(fn ($r) => (object) ['id' => $r->id, 'usos' => (int) $r->usos, 'autor' => $r->nombre, 'ciudad' => $r->ciudad])
            ->values()->all();
    }

    // HTML de un diseño público (o null si ya no está disponible)
    public static function publica(int $id): ?string
    {
        $html = DB::table('cartas_ia')->where('id', $id)->where('compartir', true)->where('oculta', false)->value('html');
        return $html !== null && self::esPlantilla($html) ? $html : null;
    }

    // Copia un diseño público a la carta del negocio. La copia no se vuelve a compartir (ya está en la galería).
    public static function usarPublica(int $id, int $tiendaId): bool
    {
        $html = self::publica($id);
        if ($html === null) return false;
        $propia = DB::table('cartas_ia')->where('tienda_id', $tiendaId)->value('id');
        if ((int) $propia === $id) return false;
        DB::table('cartas_ia')->updateOrInsert(['tienda_id' => $tiendaId], ['html' => $html, 'compartir' => false, 'updated_at' => now(), 'created_at' => now()]);
        DB::table('cartas_ia')->where('id', $id)->increment('usos');
        return true;
    }

    // ¿El diseño trae las marcas para que LocalPro ponga los platos y precios solo?
    public static function esPlantilla(string $html): bool
    {
        return (bool) preg_match('#<!--\s*PLATOS\s*-->#i', $html);
    }

    /**
     * Arma la carta: el diseño de la IA + los platos y precios de hoy. Marcas:
     *  <!--CATEGORIAS--> … {{categoria}} … <!--/CATEGORIAS-->  (se repite por categoría)
     *  <!--PLATOS--> … {{nombre}} {{precio}} {{descripcion}} {{foto}} … <!--/PLATOS-->  (se repite por plato)
     *  <!--SI_FOTO-->, <!--SI_DESCRIPCION-->, <!--SI_AGOTADO-->, <!--SI_LOGO--> … <!--/SI_…-->  (solo si hay dato)
     *  {{negocio}} {{logo}} {{qr}} {{whatsapp}} {{direccion}} {{ciudad}} {{link_pedir}}
     * Todo dato va escapado: un nombre de plato no puede meter código.
     */
    public static function armar(string $html, Tienda $tienda, array $secciones): string
    {
        if (!self::esPlantilla($html)) return $html;
        $bloque = fn (string $nombre) => '#<!--\s*' . $nombre . '\s*-->(.*?)<!--\s*/\s*' . $nombre . '\s*-->#is';
        $si = function (string $txt, string $marca, bool $mostrar) use ($bloque) {
            return preg_replace_callback($bloque($marca), fn ($m) => $mostrar ? $m[1] : '', $txt);
        };
        $poner = fn (string $txt, array $vals) => preg_replace_callback('#\{\{\s*([a-z_]+)\s*\}\}#i', fn ($m) => array_key_exists(strtolower($m[1]), $vals) ? e((string) $vals[strtolower($m[1])]) : $m[0], $txt);

        $plato = function (string $molde, array $p) use ($si, $poner) {
            $desc = $p['descripcion'] ? \Illuminate\Support\Str::limit(trim(strip_tags($p['descripcion'])), 140) : '';
            $molde = $si($molde, 'SI_FOTO', (bool) $p['img']);
            $molde = $si($molde, 'SI_DESCRIPCION', $desc !== '');
            $molde = $si($molde, 'SI_AGOTADO', !$p['disponible']);
            return $poner($molde, [
                'nombre' => $p['nombre'], 'descripcion' => $desc, 'foto' => $p['img'] ?: '',
                'precio' => ($p['desde'] ? 'Desde ' : '') . dinero($p['desde'] ?? $p['precio']) . ($p['unidad'] ? ' / ' . $p['unidad'] : ''),
                'agotado' => $p['disponible'] ? '' : 'Agotado',
            ]);
        };
        $platos = fn (string $txt, array $items) => preg_replace_callback($bloque('PLATOS'), fn ($m) => implode('', array_map(fn ($p) => $plato($m[1], $p), $items)), $txt);

        if (preg_match($bloque('CATEGORIAS'), $html)) {
            $html = preg_replace_callback($bloque('CATEGORIAS'), function ($m) use ($secciones, $platos, $poner) {
                $out = '';
                foreach ($secciones as $cat => $items) {
                    $out .= $poner($platos($m[1], $items), ['categoria' => $cat]);
                }
                return $out;
            }, $html);
        } else {
            $html = $platos($html, array_merge(...array_values($secciones ?: [[]])));
        }

        $html = $si($html, 'SI_LOGO', (bool) $tienda->logo_url);
        return $poner($html, [
            'negocio' => $tienda->nombre, 'logo' => $tienda->logo_url ?: '', 'qr' => self::qr($tienda) ?: '',
            'whatsapp' => $tienda->telefono ?: '', 'direccion' => $tienda->direccion ?: '', 'ciudad' => $tienda->ciudad ?: '',
            'link_pedir' => $tienda->slug ? $tienda->url_carta . '?pedir=1' : '',
        ]);
    }

    // Imagen del QR que lleva a la carta en línea (la hace un servicio externo: no carga el servidor)
    public static function qr(Tienda $tienda): ?string
    {
        return $tienda->slug ? 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' . rawurlencode($tienda->url_carta) : null;
    }

    // Botón flotante para pedir en línea (solo HTML y CSS: la carta no puede correr scripts)
    public static function conBotonPedir(string $html, Tienda $tienda, string $mesa = ''): string
    {
        if (!$tienda->slug) return $html;
        // Desde el QR de una mesa (?mesa=4) el botón lleva la mesa para que el pedido ya salga con ese número
        $btn = '<a href="' . e($tienda->url_carta . '?pedir=1' . ($mesa !== '' ? '&mesa=' . rawurlencode($mesa) : '')) . '" style="position:fixed;right:16px;bottom:16px;z-index:2147483647;display:inline-flex;align-items:center;gap:8px;'
            . 'padding:13px 20px;border-radius:999px;background:#25d366;color:#fff;font:800 15px/1 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;text-decoration:none;'
            . 'box-shadow:0 10px 24px -8px rgba(0,0,0,.45)">🛒 Pedir en línea</a>'
            . '<style>@media print{a[href*="pedir=1"]{display:none!important}}</style>';
        return preg_match('#</body>#i', $html) ? preg_replace('#</body>#i', $btn . '</body>', $html, 1) : $html . $btn;
    }

    // Quita lo peligroso y lo que la IA a veces agrega alrededor (```html … ```)
    public static function limpiar(string $html): string
    {
        $html = trim(str_replace("\0", '', $html));
        if (preg_match('/```(?:html)?\s*(.*?)```/is', $html, $m)) {
            $html = trim($m[1]);
        }
        // Etiquetas que pueden correr código o cargar otras páginas
        $html = preg_replace('#<\s*(script|iframe|frame|frameset|object|embed|applet|noscript|template)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('#<\s*/?\s*(script|iframe|frame|frameset|object|embed|applet|base|form|input|button|textarea|select|portal)\b[^>]*>#is', '', $html);
        $html = preg_replace('#<\s*meta\b[^>]*http-equiv[^>]*>#is', '', $html);
        // Eventos (onclick=, onload=…) y direcciones javascript:/vbscript:/data:text
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src|action|xlink:href|formaction)\s*=\s*(["\']?)\s*(javascript|vbscript|data:text)[^"\'>\s]*\2#i', '$1="#"', $html);
        $html = preg_replace('#expression\s*\(|url\s*\(\s*["\']?\s*javascript:#i', '', $html);
        return $html;
    }

    // Cabeceras para servir la carta encerrada: sin scripts, sin acceso a cookies ni al resto del sitio
    public static function respuesta(string $html, Tienda $tienda)
    {
        if (!preg_match('/<html/i', $html)) {
            $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
                . e($tienda->nombre) . ' — Carta</title></head><body>' . $html . '</body></html>';
        }
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "sandbox allow-popups allow-popups-to-escape-sandbox; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }

    // Instrucciones para la IA del cliente: solo el diseño con marcas; LocalPro pone platos y precios (y se actualizan solos)
    public static function instrucciones(Tienda $tienda, array $secciones): string
    {
        $cats = array_slice(array_keys($secciones), 0, 8);
        $ejemplos = [];
        foreach ($secciones as $items) {
            foreach (array_slice($items, 0, 2) as $p) $ejemplos[] = $p['nombre'] . ' ' . dinero($p['desde'] ?? $p['precio']);
            if (count($ejemplos) >= 4) break;
        }
        $tipo = $tienda->tipo_negocio ? str_replace('_', ' ', $tienda->tipo_negocio) : 'restaurante';

        return "Diseña la carta (menú) de mi negocio \"{$tienda->nombre}\" ({$tipo}) como UNA sola página HTML con el CSS dentro de <style>.\n"
            . "Estilo que quiero: [ESCRIBA AQUÍ EL ESTILO, ej.: rústico de madera, elegante, moderno con colores verde y negro]\n\n"
            . "MUY IMPORTANTE: mi sistema (LocalPro) pone los platos y precios solo. NO escribas platos ni precios: usa estas marcas tal cual:\n"
            . "1. Título: {{negocio}}. Logo: <!--SI_LOGO--><img src=\"{{logo}}\" alt=\"logo\"><!--/SI_LOGO-->\n"
            . "2. UNA categoría de muestra entre <!--CATEGORIAS--> y <!--/CATEGORIAS-->, con su nombre {{categoria}}.\n"
            . "3. Dentro de esa categoría, UN plato de muestra entre <!--PLATOS--> y <!--/PLATOS--> con: {{nombre}}, {{precio}}, "
            . "<!--SI_DESCRIPCION-->{{descripcion}}<!--/SI_DESCRIPCION--> y <!--SI_FOTO--><img src=\"{{foto}}\" alt=\"\"><!--/SI_FOTO-->. "
            . "Opcional: <!--SI_AGOTADO-->{{agotado}}<!--/SI_AGOTADO-->.\n"
            . "4. Al final: <img src=\"{{qr}}\" alt=\"QR\"> con el texto \"Escanea y pide en línea\", y {{whatsapp}} y {{direccion}}.\n"
            . "5. Sin JavaScript ni formularios. Que se vea muy bien en el celular y al imprimir en hoja carta (@media print).\n\n"
            . ($cats ? 'Para que te hagas una idea, mis categorías son: ' . implode(', ', $cats) . '.' . ($ejemplos ? ' Algunos platos: ' . implode('; ', $ejemplos) . '.' : '') . "\n\n" : '')
            . "Responde SOLO con el código HTML completo, desde <!DOCTYPE html> hasta </html>.";
    }
}
