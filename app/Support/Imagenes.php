<?php

namespace App\Support;

/**
 * Achica y comprime imágenes para que el servidor no se llene.
 * Mantiene el mismo formato (JPG, PNG o WebP) para que las rutas y validaciones sigan igual,
 * respeta la transparencia de los PNG/WebP y endereza las fotos de celular (EXIF).
 * Solo reemplaza el archivo si el resultado pesa menos.
 */
class Imagenes
{
    public const CALIDAD_JPG = 82;
    public const CALIDAD_WEBP = 80;
    // Por debajo de este peso no se toca (ya está liviana)
    public const MINIMO_BYTES = 250 * 1024;

    public static function disponible(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagejpeg');
    }

    /** Devuelve [bytes antes, bytes después] o null si no se cambió. */
    public static function optimizar(string $ruta, int $maxLado = 2048): ?array
    {
        if (!self::disponible() || !is_file($ruta)) return null;
        $antes = filesize($ruta);
        if ($antes < self::MINIMO_BYTES) return null;

        $info = @getimagesize($ruta);
        if (!$info) return null;
        [$w, $h, $tipo] = $info;
        if (!in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return null;
        // Imágenes gigantes (más de 40 megapíxeles) se saltan para no agotar la memoria
        if ($w * $h > 40_000_000) return null;

        $img = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
        };
        if (!$img) return null;

        // Fotos de celular: enderezar según la orientación guardada en la foto
        if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta);
            $giro = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            if ($giro) {
                $r = imagerotate($img, $giro, 0);
                if ($r) { imagedestroy($img); $img = $r; $w = imagesx($img); $h = imagesy($img); }
            }
        }

        // Achicar si se pasa del lado máximo
        $escala = min(1, $maxLado / max($w, $h));
        if ($escala < 1) {
            $nw = max(1, (int) round($w * $escala));
            $nh = max(1, (int) round($h * $escala));
            $nueva = imagecreatetruecolor($nw, $nh);
            if ($tipo !== IMAGETYPE_JPEG) {
                imagealphablending($nueva, false);
                imagesavealpha($nueva, true);
                imagefill($nueva, 0, 0, imagecolorallocatealpha($nueva, 0, 0, 0, 127));
            }
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $nueva;
        } elseif ($tipo !== IMAGETYPE_JPEG) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        $tmp = $ruta . '.opt';
        $ok = match ($tipo) {
            IMAGETYPE_JPEG => (imageinterlace($img, true) || true) && imagejpeg($img, $tmp, self::CALIDAD_JPG),
            IMAGETYPE_PNG => imagepng($img, $tmp, 9),
            IMAGETYPE_WEBP => imagewebp($img, $tmp, self::CALIDAD_WEBP),
        };
        imagedestroy($img);
        clearstatcache(true, $tmp);
        if (!$ok || !is_file($tmp)) { @unlink($tmp); return null; }

        $despues = filesize($tmp);
        if ($despues >= $antes) { @unlink($tmp); return null; }
        if (!@rename($tmp, $ruta)) { @copy($tmp, $ruta); @unlink($tmp); }
        clearstatcache(true, $ruta);
        return [$antes, $despues];
    }

    // Lado máximo según para qué es la imagen (nombre del campo del formulario o carpeta)
    public static function maxPara(string $clave): int
    {
        $clave = strtolower($clave);
        return match (true) {
            str_contains($clave, 'logo') => 1000,
            str_contains($clave, 'img_path'), str_contains($clave, 'productos') => 1400,
            str_contains($clave, 'fondo'), $clave === 'imagen' => 3840, // fondos de pantallas 4K
            default => 2048,
        };
    }
}
