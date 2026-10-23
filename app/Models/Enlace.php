<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Un botón de la página "Mis enlaces" (tipo Linktree) de un negocio
class Enlace extends Model
{
    use PerteneceATienda;

    protected $table = 'enlaces';

    protected $fillable = ['tipo', 'titulo', 'url', 'icono', 'destacado', 'activo', 'desde', 'hasta', 'orden'];

    protected $casts = ['destacado' => 'boolean', 'activo' => 'boolean', 'desde' => 'datetime', 'hasta' => 'datetime'];

    // Temas de la página pública: [nombre, fondo, texto, fondo del botón, texto del botón, borde del botón]
    public const TEMAS = [
        'localproo' => ['LocalProo', 'linear-gradient(160deg,#0b3d1f 0%,#11702f 55%,#2fd15a 100%)', '#ffffff', '#ffffff', '#0b3d1f', 'transparent'],
        'noche' => ['Noche', 'radial-gradient(circle at 20% 0%,#1f2a22 0%,#0b0d0c 60%)', '#ffffff', 'rgba(255,255,255,.06)', '#ffffff', '#a6f34a'],
        'rosa' => ['Rosa', 'linear-gradient(160deg,#ffe3ee 0%,#ff9cc2 100%)', '#3a0d22', '#ffffff', '#3a0d22', 'transparent'],
        'oceano' => ['Océano', 'linear-gradient(160deg,#0f2b5b 0%,#1e6fd9 60%,#5ad1ff 100%)', '#ffffff', 'rgba(255,255,255,.95)', '#0f2b5b', 'transparent'],
        'atardecer' => ['Atardecer', 'linear-gradient(160deg,#ff7a18 0%,#ff3d6e 55%,#7a1fa2 100%)', '#ffffff', 'rgba(255,255,255,.95)', '#7a1fa2', 'transparent'],
        'minimal' => ['Minimal', '#f5f3ee', '#151a17', '#151a17', '#ffffff', 'transparent'],
        'cafe' => ['Café', 'linear-gradient(160deg,#3b2618 0%,#6f4a2f 100%)', '#fff6ea', '#fff6ea', '#3b2618', 'transparent'],
        'lila' => ['Lila', 'linear-gradient(160deg,#ece4ff 0%,#b9a2ff 100%)', '#2a1660', '#2a1660', '#ffffff', 'transparent'],
    ];

    // Forma de los botones
    public const BOTONES = ['pildora' => 'Píldora', 'redondeado' => 'Redondeado', 'cuadrado' => 'Cuadrado', 'contorno' => 'Contorno'];

    // Redes que salen como íconos arriba: [nombre, ícono, cómo se escribe]
    public const REDES = [
        'whatsapp' => ['WhatsApp', 'fa-brands fa-whatsapp', 'Número de celular'],
        'instagram' => ['Instagram', 'fa-brands fa-instagram', '@usuario o enlace'],
        'facebook' => ['Facebook', 'fa-brands fa-facebook', 'Página o enlace'],
        'tiktok' => ['TikTok', 'fa-brands fa-tiktok', '@usuario o enlace'],
        'youtube' => ['YouTube', 'fa-brands fa-youtube', '@canal o enlace'],
        'x' => ['X (Twitter)', 'fa-brands fa-x-twitter', '@usuario o enlace'],
        'telegram' => ['Telegram', 'fa-brands fa-telegram', '@usuario o enlace'],
        'email' => ['Correo', 'fa-solid fa-envelope', 'correo@negocio.com'],
        'web' => ['Sitio web', 'fa-solid fa-globe', 'www.minegocio.com'],
    ];

    // Íconos que el negocio puede escoger para un botón (vacío = automático según la dirección)
    public const ICONOS = [
        '' => 'Automático', 'fa-solid fa-link' => 'Enlace', 'fa-brands fa-whatsapp' => 'WhatsApp', 'fa-brands fa-instagram' => 'Instagram',
        'fa-brands fa-facebook' => 'Facebook', 'fa-brands fa-tiktok' => 'TikTok', 'fa-brands fa-youtube' => 'YouTube',
        'fa-solid fa-store' => 'Tienda', 'fa-solid fa-bag-shopping' => 'Compras', 'fa-solid fa-tag' => 'Promoción',
        'fa-solid fa-location-dot' => 'Ubicación', 'fa-solid fa-calendar-check' => 'Reservas', 'fa-solid fa-utensils' => 'Menú',
        'fa-solid fa-truck' => 'Domicilios', 'fa-solid fa-gift' => 'Regalo', 'fa-solid fa-star' => 'Destacado',
        'fa-solid fa-ticket' => 'Evento', 'fa-solid fa-phone' => 'Llamar', 'fa-solid fa-envelope' => 'Correo', 'fa-solid fa-file-pdf' => 'Documento',
    ];

    // Se ve hoy en la página pública (activo y dentro de las fechas programadas)
    public function getVisibleAttribute(): bool
    {
        $ahora = now();
        return $this->activo && (!$this->desde || $this->desde->lte($ahora)) && (!$this->hasta || $this->hasta->gte($ahora));
    }

    public function getIconoFinalAttribute(): string
    {
        $auto = self::iconoPara((string) $this->url);
        // Si el negocio escogió el ícono de otra red (ej. WhatsApp en un enlace de Instagram), manda el de la dirección
        if ($this->icono && str_starts_with($auto, 'fa-brands') && str_starts_with($this->icono, 'fa-brands') && $this->icono !== $auto) {
            return $auto;
        }
        return $this->icono ?: $auto;
    }

    // Texto que se muestra en el botón: si el negocio pegó una dirección como título, se cambia por un nombre amable
    public function getTituloMostrarAttribute(): string
    {
        return self::tituloAmable((string) $this->titulo, (string) $this->url);
    }

    public static function pareceUrl(string $texto): bool
    {
        $t = trim($texto);
        return (bool) preg_match('#^(https?://|www\.)#i', $t) || (bool) preg_match('#^[\w\-]+(\.[\w\-]+)+/\S*$#', $t);
    }

    public static function tituloAmable(string $titulo, string $url): string
    {
        $titulo = trim($titulo);
        if ($titulo !== '' && !self::pareceUrl($titulo)) return $titulo;
        $u = strtolower($url ?: $titulo);
        $mapa = [
            'wa.me' => 'Escríbenos por WhatsApp', 'whatsapp' => 'Escríbenos por WhatsApp', 'instagram' => 'Síguenos en Instagram',
            'facebook' => 'Síguenos en Facebook', 'fb.com' => 'Síguenos en Facebook', 'tiktok' => 'Síguenos en TikTok',
            'youtu' => 'Míranos en YouTube', 'twitter' => 'Síguenos en X', 'x.com' => 'Síguenos en X', 't.me' => 'Únete en Telegram',
            'telegram' => 'Únete en Telegram', 'spotify' => 'Escúchanos en Spotify', 'linkedin' => 'Visítanos en LinkedIn',
            'pinterest' => 'Síguenos en Pinterest', 'maps' => 'Cómo llegar', 'goo.gl' => 'Cómo llegar', 'waze' => 'Cómo llegar',
            'mailto:' => 'Escríbenos un correo', 'tel:' => 'Llámanos', '.pdf' => 'Ver documento',
        ];
        foreach ($mapa as $k => $txt) {
            if (str_contains($u, $k)) return $txt;
        }
        $host = parse_url(str_contains($u, '://') ? $u : 'https://' . $u, PHP_URL_HOST);
        return $host ? 'Visitar ' . preg_replace('/^www\./', '', $host) : 'Abrir enlace';
    }

    // Ícono según la dirección del enlace
    public static function iconoPara(string $url): string
    {
        $u = strtolower($url);
        $mapa = [
            'wa.me' => 'fa-brands fa-whatsapp', 'whatsapp' => 'fa-brands fa-whatsapp', 'instagram' => 'fa-brands fa-instagram',
            'facebook' => 'fa-brands fa-facebook', 'fb.com' => 'fa-brands fa-facebook', 'tiktok' => 'fa-brands fa-tiktok',
            'youtu' => 'fa-brands fa-youtube', 'twitter' => 'fa-brands fa-x-twitter', 'x.com' => 'fa-brands fa-x-twitter',
            't.me' => 'fa-brands fa-telegram', 'telegram' => 'fa-brands fa-telegram', 'spotify' => 'fa-brands fa-spotify',
            'linkedin' => 'fa-brands fa-linkedin', 'pinterest' => 'fa-brands fa-pinterest', 'maps' => 'fa-solid fa-location-dot',
            'goo.gl' => 'fa-solid fa-location-dot', 'waze' => 'fa-brands fa-waze', 'mailto:' => 'fa-solid fa-envelope', 'tel:' => 'fa-solid fa-phone',
            '/t/' => 'fa-solid fa-store', '.pdf' => 'fa-solid fa-file-pdf',
        ];
        foreach ($mapa as $k => $ico) {
            if (str_contains($u, $k)) return $ico;
        }
        return 'fa-solid fa-link';
    }

    /**
     * Limpia lo que escribe el negocio: agrega https://, convierte un número en WhatsApp y
     * no deja pasar direcciones peligrosas (javascript:, data:…). Devuelve null si no sirve.
     */
    public static function normalizarUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (preg_match('/^\+?[\d\s\-()]{7,}$/', $url)) {
            return 'https://wa.me/' . numero_whatsapp($url);
        }
        if (filter_var($url, FILTER_VALIDATE_EMAIL)) return 'mailto:' . $url;
        if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) $url = 'https://' . ltrim($url, '/');
        $esquema = strtolower(parse_url($url, PHP_URL_SCHEME) ?: '');
        if (!in_array($esquema, ['http', 'https', 'mailto', 'tel'], true)) return null;
        if (in_array($esquema, ['http', 'https'], true) && !filter_var($url, FILTER_VALIDATE_URL)) return null;
        return mb_substr($url, 0, 500);
    }

    // Dirección completa de una red a partir de lo que escribió el negocio (@usuario, número o enlace)
    public static function urlRed(string $red, ?string $valor): ?string
    {
        $v = trim((string) $valor);
        if ($v === '') return null;
        if ($red === 'whatsapp') {
            if (preg_match('#^https?://#i', $v)) return self::normalizarUrl($v);
            $n = numero_whatsapp($v);
            return $n ? 'https://wa.me/' . $n : null;
        }
        if ($red === 'email') return filter_var($v, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $v : null;
        if (preg_match('#^https?://#i', $v) || str_contains($v, '.')) return self::normalizarUrl($v);
        $u = ltrim($v, '@');
        if (!preg_match('/^[\w.\-]{1,60}$/u', $u)) return null;
        return match ($red) {
            'instagram' => 'https://instagram.com/' . $u,
            'facebook' => 'https://facebook.com/' . $u,
            'tiktok' => 'https://www.tiktok.com/@' . $u,
            'youtube' => 'https://www.youtube.com/@' . $u,
            'x' => 'https://x.com/' . $u,
            'telegram' => 'https://t.me/' . $u,
            default => null,
        };
    }
}
