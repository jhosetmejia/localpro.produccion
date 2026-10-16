<?php

namespace App\Support;

use App\Models\Ajuste;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Prospección automática de LocalProo: ajustes, buzón propio de envío (aparte de Brevo, para no arriesgar
 * los correos del sistema), búsqueda de firmas en Google Maps y lectura del correo publicado en su página web.
 */
class Prospectos
{
    // Ciudades que se recorren en orden (Nariño primero); se pueden cambiar en el panel
    public const CIUDADES = [
        'Pasto', 'Ipiales', 'Tumaco', 'Túquerres', 'La Unión Nariño', 'Cali', 'Popayán', 'Palmira', 'Buga', 'Tuluá',
        'Bogotá', 'Medellín', 'Barranquilla', 'Cartagena', 'Bucaramanga', 'Pereira', 'Manizales', 'Armenia', 'Ibagué',
        'Neiva', 'Villavicencio', 'Cúcuta', 'Santa Marta', 'Montería', 'Sincelejo', 'Valledupar', 'Tunja', 'Sogamoso',
        'Duitama', 'Florencia', 'Mocoa', 'Yopal', 'Riohacha', 'Quibdó', 'Girardot', 'Fusagasugá', 'Zipaquirá',
        'Facatativá', 'Soacha', 'Chía', 'Envigado', 'Itagüí', 'Bello', 'Rionegro', 'Floridablanca', 'Jamundí',
        'Cartago', 'Pitalito', 'Apartadó', 'Barrancabermeja',
    ];

    public const TERMINOS = ['firma contable', 'contador público', 'asesoría contable y tributaria', 'outsourcing contable'];

    // Correos que nunca son de contacto (imágenes, plantillas, servicios)
    private const BASURA = ['example.', 'sentry', 'wixpress', 'domain.com', 'email.com', 'correo.com', 'tudominio',
        'yourdomain', 'sitio.com', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', 'godaddy', 'wordpress', 'noreply',
        'no-reply', 'nombre@', 'usuario@', 'test@', 'ejemplo'];

    // ---------- Ajustes (se guardan en la tabla ajustes; las claves, cifradas) ----------

    public static function ajuste(string $clave, $defecto = null)
    {
        return Ajuste::valor('prosp_' . $clave, $defecto);
    }

    public static function secreto(string $clave): ?string
    {
        $v = self::ajuste($clave);
        if (!$v) return null;
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function activo(): bool
    {
        return (bool) self::ajuste('activo', false);
    }

    public static function limiteDiario(): int
    {
        return max(1, min(200, (int) self::ajuste('limite', 25)));
    }

    public static function ciudades(): array
    {
        $txt = trim((string) self::ajuste('ciudades', ''));
        $lista = $txt !== '' ? preg_split('/[\r\n,;]+/', $txt) : self::CIUDADES;
        return array_values(array_filter(array_map('trim', $lista)));
    }

    public static function remitente(): array
    {
        return [(string) self::ajuste('smtp_usuario'), (string) self::ajuste('remitente', 'Jhoset Mejía · LocalProo')];
    }

    public static function buzonListo(): bool
    {
        return (bool) self::ajuste('smtp_usuario') && (bool) self::secreto('smtp_clave');
    }

    // Mailer "prospectos" armado con el buzón del panel (por defecto Gmail con clave de aplicación)
    public static function mailer()
    {
        $puerto = (int) self::ajuste('smtp_puerto', 587);
        config(['mail.mailers.prospectos' => [
            'transport' => 'smtp',
            'host' => self::ajuste('smtp_host', 'smtp.gmail.com'),
            'port' => $puerto,
            'encryption' => $puerto === 465 ? 'ssl' : 'tls',
            'scheme' => $puerto === 465 ? 'smtps' : 'smtp',
            'username' => self::ajuste('smtp_usuario'),
            'password' => self::secreto('smtp_clave'),
            'timeout' => 30,
        ]]);
        app('mail.manager')->purge('prospectos');
        return Mail::mailer('prospectos');
    }

    // ---------- Búsqueda en Google Maps (Places API, opcional: necesita clave) ----------

    /** @return array<int, array{empresa:string, web:?string, telefono:?string, direccion:?string}> */
    public static function buscarEnMaps(string $consulta): array
    {
        $clave = self::secreto('maps_clave');
        if (!$clave) return [];
        $r = Http::timeout(20)->withHeaders([
            'X-Goog-Api-Key' => $clave,
            'X-Goog-FieldMask' => 'places.displayName,places.websiteUri,places.nationalPhoneNumber,places.formattedAddress',
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $consulta, 'languageCode' => 'es', 'regionCode' => 'CO', 'pageSize' => 20,
        ]);
        if (!$r->ok()) {
            throw new \RuntimeException('Google Maps respondió ' . $r->status() . ': ' . mb_substr($r->body(), 0, 200));
        }
        return array_map(fn ($p) => [
            'empresa' => mb_substr($p['displayName']['text'] ?? 'Sin nombre', 0, 150),
            'web' => $p['websiteUri'] ?? null,
            'telefono' => $p['nationalPhoneNumber'] ?? null,
            'direccion' => $p['formattedAddress'] ?? null,
        ], $r->json('places') ?? []);
    }

    // ---------- Correo publicado en la página web ----------

    /** Busca el correo de contacto en la página y en su página de contacto. @return array{0:?string,1:?string} [correo, página donde está] */
    public static function correoDeLaWeb(string $url): array
    {
        $url = self::normalizarWeb($url);
        if (!$url) return [null, null];
        $host = self::host($url);
        $visitadas = [];
        $pendientes = [$url];
        $hallados = [];
        while ($pendientes && count($visitadas) < 4) {
            $p = array_shift($pendientes);
            if (isset($visitadas[$p])) continue;
            $visitadas[$p] = true;
            $html = self::bajar($p);
            if ($html === null) continue;
            foreach (self::correosEn($html) as $c) {
                $hallados[$c] ??= $p;
            }
            if (count($visitadas) === 1) {
                // Enlaces a "contacto" del mismo sitio
                preg_match_all('/href=["\']([^"\'#]+)["\']/i', $html, $m);
                foreach ($m[1] as $href) {
                    if (!preg_match('/contact|contacto|contactenos|contáctenos|escribanos|nosotros/i', $href)) continue;
                    $abs = self::absoluta($href, $p);
                    if ($abs && self::host($abs) === $host) $pendientes[] = $abs;
                }
                foreach (['/contacto', '/contactenos', '/contact'] as $ruta) {
                    $pendientes[] = rtrim(preg_replace('#^(https?://[^/]+).*#', '$1', $p), '/') . $ruta;
                }
                $pendientes = array_values(array_unique($pendientes));
            }
        }
        if (!$hallados) return [null, null];
        // Preferir el correo del mismo dominio de la página; luego gmail/hotmail/outlook (firmas pequeñas)
        $base = preg_replace('/^www\./', '', $host);
        uksort($hallados, function ($a, $b) use ($base) {
            $pa = str_ends_with($a, '@' . $base) ? 0 : (preg_match('/@(gmail|hotmail|outlook|yahoo|live)\./', $a) ? 1 : 2);
            $pb = str_ends_with($b, '@' . $base) ? 0 : (preg_match('/@(gmail|hotmail|outlook|yahoo|live)\./', $b) ? 1 : 2);
            return $pa <=> $pb ?: strlen($a) <=> strlen($b);
        });
        $correo = array_key_first($hallados);
        return [$correo, $hallados[$correo]];
    }

    /** @return string[] */
    public static function correosEn(string $html): array
    {
        $txt = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $txt = str_ireplace(['[at]', '(at)', ' [arroba] ', '(arroba)'], '@', $txt);
        $lista = [];
        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,10}/i', $txt, $m)) $lista = $m[0];
        // Correos escondidos por Cloudflare (data-cfemail)
        if (preg_match_all('/data-cfemail="([0-9a-f]+)"/i', $html, $cf)) {
            foreach ($cf[1] as $hex) {
                $k = hexdec(substr($hex, 0, 2));
                $s = '';
                for ($i = 2; $i < strlen($hex); $i += 2) $s .= chr(hexdec(substr($hex, $i, 2)) ^ $k);
                $lista[] = $s;
            }
        }
        $limpios = [];
        foreach ($lista as $c) {
            $c = strtolower(trim($c, " .\t\n\r\0\x0B-_"));
            if (!filter_var($c, FILTER_VALIDATE_EMAIL)) continue;
            foreach (self::BASURA as $b) {
                if (str_contains($c, $b)) continue 2;
            }
            $limpios[$c] = true;
        }
        return array_keys($limpios);
    }

    public static function normalizarWeb(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
        // Redes sociales: no se leen (piden iniciar sesión)
        if (preg_match('#(^|\.)(facebook|instagram|linkedin|twitter|x|tiktok|wa)\.(com|me)$#i', self::host($url))) return null;
        return $url;
    }

    public static function host(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }

    private static function bajar(string $url): ?string
    {
        try {
            $r = Http::timeout(12)->connectTimeout(6)->withOptions(['allow_redirects' => ['max' => 4]])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; LocalProoBot/1.0; +https://localproo.com)', 'Accept-Language' => 'es-CO,es'])
                ->get($url);
            if (!$r->ok()) return null;
            return mb_substr($r->body(), 0, 600000);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function absoluta(string $href, string $base): ?string
    {
        if (preg_match('#^(mailto|tel|javascript|whatsapp):#i', $href)) return null;
        if (preg_match('#^https?://#i', $href)) return $href;
        $raiz = preg_replace('#^(https?://[^/]+).*#', '$1', $base);
        if (str_starts_with($href, '//')) return 'https:' . $href;
        if (str_starts_with($href, '/')) return $raiz . $href;
        return rtrim(preg_replace('#/[^/]*$#', '', $base), '/') . '/' . $href;
    }
}
