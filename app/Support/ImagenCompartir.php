<?php

namespace App\Support;

/**
 * Imagen para la vista previa de los links (WhatsApp, Facebook, X, Telegram): 1200x630.
 * Mismo estilo que la imagen de estados: fondo oscuro con brillo verde LocalProo, fotos a sangre con esquinas redondas.
 * Se genera una vez por combinación de fotos/logo y queda guardada en storage/app/public/compartir.
 */
class ImagenCompartir
{
    const W = 1200, H = 630;

    public static function disponible(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagettftext') && function_exists('imagejpeg');
    }

    /** Ruta local de una imagen a partir de su URL pública (/storage/...) */
    public static function rutaLocal(?string $url): ?string
    {
        if (!$url) return null;
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (!str_starts_with($path, '/storage/')) return null;
        $f = storage_path('app/public/' . substr($path, strlen('/storage/')));
        return is_file($f) ? $f : null;
    }

    /**
     * Imagen de una tienda. Devuelve la ruta del archivo jpg (lo crea si no existe).
     * @param array $tienda ['id','nombre','lugar','cuantos','logo' => ruta local|null]
     * @param array $fotos rutas locales (hasta 4)
     */
    public static function tienda(array $tienda, array $fotos): ?string
    {
        if (!self::disponible()) return null;
        $fotos = array_values(array_slice(array_filter($fotos, 'is_file'), 0, 4));
        $clave = md5(json_encode([$tienda['nombre'], $tienda['lugar'], $tienda['cuantos'], $tienda['logo'] ? [$tienda['logo'], @filemtime($tienda['logo'])] : null,
            array_map(fn ($f) => [$f, @filemtime($f)], $fotos), 'v1']));
        $dir = storage_path('app/public/compartir');
        $archivo = $dir . '/tienda-' . $tienda['id'] . '-' . substr($clave, 0, 12) . '.jpg';
        if (is_file($archivo)) return $archivo;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        // Se borran las versiones viejas de esta tienda
        foreach (glob($dir . '/tienda-' . $tienda['id'] . '-*.jpg') ?: [] as $viejo) @unlink($viejo);

        $im = self::lienzo();
        $f600 = resource_path('fonts/PlusJakartaSans-600.ttf');
        $f800 = resource_path('fonts/PlusJakartaSans-800.ttf');

        // Collage a la izquierda
        $X0 = 40; $Y0 = 40; $AW = 600; $AH = 550; $g = 12; $half = ($AW - $g) / 2; $hh = ($AH - $g) / 2;
        $n = count($fotos);
        $cajas = match (true) {
            $n === 1 => [[0, 0, $AW, $AH]],
            $n === 2 => [[0, 0, $half, $AH], [$half + $g, 0, $half, $AH]],
            $n === 3 => [[0, 0, $half, $AH], [$half + $g, 0, $half, $hh], [$half + $g, $hh + $g, $half, $hh]],
            $n >= 4 => [[0, 0, $half, $hh], [$half + $g, 0, $half, $hh], [0, $hh + $g, $half, $hh], [$half + $g, $hh + $g, $half, $hh]],
            default => [],
        };
        $pintadas = 0;
        foreach ($fotos as $i => $f) {
            $src = self::abrir($f);
            if (!$src) continue;
            [$cx, $cy, $cw, $ch] = $cajas[$i];
            self::cubrir($im, $src, (int) ($X0 + $cx), (int) ($Y0 + $cy), (int) $cw, (int) $ch, 28);
            imagedestroy($src);
            $pintadas++;
        }
        if (!$pintadas) self::rect($im, $X0, $Y0, $AW, $AH, 28, imagecolorallocatealpha($im, 166, 243, 74, 112));

        // Panel derecho: logo, nombre, lugar y chip
        $px = 690; $pw = self::W - $px - 50;
        $logo = $tienda['logo'] ? self::abrir($tienda['logo']) : null;
        self::rect($im, $px, 60, 120, 120, 34, imagecolorallocate($im, 255, 255, 255));
        if ($logo) { self::encajar($im, $logo, $px + 10, 70, 100, 100); imagedestroy($logo); }
        else {
            self::rect($im, $px, 60, 120, 120, 34, imagecolorallocate($im, 47, 209, 90));
            $letra = mb_strtoupper(mb_substr($tienda['nombre'], 0, 1));
            $bb = imagettfbbox(54, 0, $f800, $letra);
            imagettftext($im, 54, 0, (int) ($px + 60 - ($bb[2] - $bb[0]) / 2), 148, imagecolorallocate($im, 11, 13, 12), $f800, $letra);
        }
        $blanco = imagecolorallocate($im, 255, 255, 255);
        $y = 236;
        foreach (self::lineas($tienda['nombre'], $f800, 34, $pw, 3) as $l) { imagettftext($im, 34, 0, $px, $y, $blanco, $f800, $l); $y += 50; }
        if ($tienda['lugar']) { imagettftext($im, 18, 0, $px, $y + 4, imagecolorallocatealpha($im, 255, 255, 255, 40), $f600, self::lineas($tienda['lugar'], $f600, 18, $pw, 1)[0] ?? ''); $y += 40; }
        $chip = ($tienda['cuantos'] > 1 ? $tienda['cuantos'] . ' productos · ' : '') . 'Compre por WhatsApp';
        $bb = imagettfbbox(15, 0, $f800, $chip); $cw = $bb[2] - $bb[0] + 44;
        if ($cw > $pw) { $chip = 'Compre por WhatsApp'; $bb = imagettfbbox(15, 0, $f800, $chip); $cw = $bb[2] - $bb[0] + 44; }
        self::rect($im, $px, $y + 14, $cw, 50, 25, imagecolorallocate($im, 166, 243, 74));
        imagettftext($im, 15, 0, $px + 22, $y + 47, imagecolorallocate($im, 11, 13, 12), $f800, $chip);
        imagettftext($im, 15, 0, $px, 586, imagecolorallocatealpha($im, 255, 255, 255, 60), $f600, 'Tienda en línea · localproo.com');

        imagejpeg($im, $archivo, 88);
        imagedestroy($im);
        return is_file($archivo) ? $archivo : null;
    }

    // ===== Ayudantes de dibujo =====
    private static function lienzo()
    {
        $im = imagecreatetruecolor(self::W, self::H);
        imagealphablending($im, true);
        imagefilledrectangle($im, 0, 0, self::W, self::H, imagecolorallocate($im, 11, 15, 12));
        // Brillos verdes (círculos con transparencia que se van apilando)
        foreach ([[180, 60, 760, [47, 209, 90]], [1080, 560, 640, [166, 243, 74]]] as [$cx, $cy, $r, $c]) {
            for ($k = 60; $k >= 1; $k--) {
                $rr = (int) ($r * $k / 60);
                imagefilledellipse($im, $cx, $cy, $rr * 2, $rr * 2, imagecolorallocatealpha($im, $c[0], $c[1], $c[2], 126));
            }
        }
        return $im;
    }

    private static function abrir(string $f)
    {
        $d = @file_get_contents($f);
        $im = $d !== false ? @imagecreatefromstring($d) : false;
        return $im ?: null;
    }

    /** Copia la foto llenando el recuadro (recorta lo que sobra) con esquinas redondas */
    private static function cubrir($dst, $src, int $x, int $y, int $w, int $h, int $r): void
    {
        $sw = imagesx($src); $sh = imagesy($src);
        $k = max($w / $sw, $h / $sh); $cw = (int) round($w / $k); $ch = (int) round($h / $k);
        $tile = imagecreatetruecolor($w, $h);
        imagealphablending($tile, false); imagesavealpha($tile, true);
        imagecopyresampled($tile, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);
        self::esquinas($tile, $w, $h, $r);
        imagealphablending($dst, true);
        imagecopy($dst, $tile, $x, $y, 0, 0, $w, $h);
        imagedestroy($tile);
    }

    private static function encajar($dst, $src, int $x, int $y, int $w, int $h): void
    {
        $sw = imagesx($src); $sh = imagesy($src); $k = min($w / $sw, $h / $sh);
        $dw = (int) ($sw * $k); $dh = (int) ($sh * $k);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, $x + (int) (($w - $dw) / 2), $y + (int) (($h - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);
    }

    /** Vuelve transparentes las esquinas (con borde suave) */
    private static function esquinas($im, int $w, int $h, int $r): void
    {
        for ($i = 0; $i < $r; $i++) {
            for ($j = 0; $j < $r; $j++) {
                $d = sqrt(($r - $i - 0.5) ** 2 + ($r - $j - 0.5) ** 2) - $r;
                if ($d <= -1) continue;
                $a = $d >= 0 ? 127 : (int) round(127 * ($d + 1));
                foreach ([[$i, $j], [$w - 1 - $i, $j], [$i, $h - 1 - $j], [$w - 1 - $i, $h - 1 - $j]] as [$px, $py]) {
                    $c = imagecolorat($im, $px, $py);
                    $alfa = max(($c >> 24) & 0x7F, $a);
                    imagesetpixel($im, $px, $py, imagecolorallocatealpha($im, ($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF, $alfa));
                }
            }
        }
    }

    /** Rectángulo con esquinas redondas */
    private static function rect($im, $x, $y, $w, $h, $r, $color): void
    {
        $x = (int) $x; $y = (int) $y; $w = (int) $w; $h = (int) $h; $r = (int) min($r, $w / 2, $h / 2);
        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r - 1, $y + $h - 1, $color);
        imagefilledrectangle($im, $x, $y + $r, $x + $r - 1, $y + $h - $r - 1, $color);
        imagefilledrectangle($im, $x + $w - $r, $y + $r, $x + $w - 1, $y + $h - $r - 1, $color);
        foreach ([[$x + $r, $y + $r], [$x + $w - $r - 1, $y + $r], [$x + $r, $y + $h - $r - 1], [$x + $w - $r - 1, $y + $h - $r - 1]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** Parte el texto en líneas que caben en $ancho (con … si sobra) */
    private static function lineas(string $t, string $font, int $size, int $ancho, int $max): array
    {
        $mide = fn ($s) => (function ($b) { return $b[2] - $b[0]; })(imagettfbbox($size, 0, $font, $s));
        $out = []; $l = '';
        foreach (preg_split('/\s+/u', trim($t)) as $p) {
            $n = $l === '' ? $p : $l . ' ' . $p;
            if ($l !== '' && $mide($n) > $ancho) { $out[] = $l; $l = $p; } else $l = $n;
        }
        if ($l !== '') $out[] = $l;
        if (count($out) > $max) {
            $out = array_slice($out, 0, $max);
            $u = $out[$max - 1];
            while (mb_strlen($u) > 1 && $mide($u . '…') > $ancho) $u = mb_substr($u, 0, -1);
            $out[$max - 1] = rtrim($u) . '…';
        }
        return $out;
    }
}
