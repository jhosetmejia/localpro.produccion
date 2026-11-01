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

    public static function guardar(int $tiendaId, string $html): void
    {
        DB::table('cartas_ia')->updateOrInsert(['tienda_id' => $tiendaId], ['html' => $html, 'updated_at' => now(), 'created_at' => now()]);
    }

    public static function borrar(int $tiendaId): void
    {
        DB::table('cartas_ia')->where('tienda_id', $tiendaId)->delete();
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

    // Instrucciones para pegar en la IA, con los productos y precios reales del negocio
    public static function instrucciones(Tienda $tienda, array $secciones): string
    {
        $lineas = [];
        foreach ($secciones as $cat => $items) {
            $lineas[] = "\n" . mb_strtoupper($cat) . ':';
            foreach ($items as $p) {
                $precio = ($p['desde'] ? 'desde ' : '') . dinero($p['desde'] ?? $p['precio']);
                $desc = $p['descripcion'] ? ' — ' . \Illuminate\Support\Str::limit(trim(strip_tags($p['descripcion'])), 90) : '';
                $lineas[] = '- ' . $p['nombre'] . ' — ' . $precio . $desc . (!$p['disponible'] ? ' (agotado)' : '');
            }
        }
        $productos = $lineas ? implode("\n", $lineas) : "\n(Aún no tengo platos creados: invente ejemplos y yo los cambio.)";
        $qr = $tienda->slug ? 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=' . rawurlencode($tienda->url_carta) : null;
        $datos = array_filter([
            'Nombre del negocio: ' . $tienda->nombre,
            $tienda->ciudad ? 'Ciudad: ' . $tienda->ciudad : null,
            $tienda->direccion ? 'Dirección: ' . $tienda->direccion : null,
            $tienda->telefono ? 'WhatsApp / teléfono: ' . $tienda->telefono : null,
            $tienda->logo_url ? 'Logo (imagen): ' . $tienda->logo_url : null,
            $qr ? 'Código QR para pedir en línea (imagen): ' . $qr : null,
            $qr ? 'Enlace de la carta en línea: ' . $tienda->url_carta : null,
        ]);

        return "Créame la carta (menú) de mi restaurante como UNA sola página HTML con el CSS adentro.\n\n"
            . "Reglas:\n"
            . "- Todo en un solo archivo: HTML + <style>. NO use JavaScript (no funciona) ni formularios.\n"
            . "- Puede usar fuentes de Google Fonts e imágenes por enlace https.\n"
            . "- Que se vea muy bien en el celular y que al imprimir quepa en hojas tamaño carta (@media print).\n"
            . "- Use exactamente los nombres y precios que le doy, sin inventar platos.\n"
            . ($qr ? "- Ponga el código QR abajo con el texto \"Escanea y pide en línea\".\n" : '')
            . "- Estilo que quiero: [ESCRIBA AQUÍ EL ESTILO, ej.: rústico de madera, elegante, moderno con colores de mi marca, comida rápida…]\n\n"
            . "Datos del negocio:\n- " . implode("\n- ", $datos) . "\n\n"
            . "Productos por categoría:" . $productos . "\n\n"
            . "Respóndame SOLO con el código HTML completo, desde <!DOCTYPE html> hasta </html>.";
    }
}
