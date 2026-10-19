<?php

namespace App\Support;

/** Lector MIME sencillo: encabezados, texto plano (o el HTML pasado a texto), codificaciones y juegos de caracteres. */
class Mime
{
    public static function parsear(string $crudo): array
    {
        [$h, $cuerpo] = self::partir($crudo);
        $headers = self::encabezados($h);
        return ['headers' => $headers, 'texto' => self::texto($headers, $cuerpo)];
    }

    private static function partir(string $s): array
    {
        $p = preg_split("/\r?\n\r?\n/", $s, 2);
        return [$p[0] ?? '', $p[1] ?? ''];
    }

    private static function encabezados(string $h): array
    {
        $h = preg_replace("/\r?\n[ \t]+/", ' ', $h);
        $r = [];
        foreach (preg_split("/\r?\n/", $h) as $l) {
            if (!str_contains($l, ':')) continue;
            [$k, $v] = explode(':', $l, 2);
            $k = strtolower(trim($k));
            if (!isset($r[$k])) $r[$k] = trim($v);
        }
        return $r;
    }

    public static function encabezado(string $v): string
    {
        $d = @iconv_mime_decode($v, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        return trim($d !== false ? $d : mb_decode_mimeheader($v));
    }

    /** "Nombre <correo@x.com>" → [correo, nombre] */
    public static function direccion(string $v): array
    {
        $v = self::encabezado($v);
        if (preg_match('/^(.*)<([^>]+)>/', $v, $m)) return [trim($m[2]), trim(trim($m[1]), "\"' ")];
        $e = trim($v);
        return [filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null, null];
    }

    private static function param(string $v, string $nombre): ?string
    {
        return preg_match('/' . $nombre . '\s*=\s*"?([^";]+)"?/i', $v, $m) ? trim($m[1]) : null;
    }

    private static function texto(array $h, string $cuerpo, int $prof = 0): string
    {
        $tipo = strtolower($h['content-type'] ?? 'text/plain');
        if (str_starts_with($tipo, 'multipart/') && $prof < 5) {
            $limite = self::param($h['content-type'], 'boundary');
            if (!$limite) return '';
            $partes = explode('--' . $limite, $cuerpo);
            $plano = null;
            $html = null;
            foreach (array_slice($partes, 1) as $parte) {
                if (str_starts_with($parte, '--')) break;
                [$ph, $pc] = self::partir(ltrim($parte, "\r\n"));
                $phh = self::encabezados($ph);
                $pt = strtolower($phh['content-type'] ?? 'text/plain');
                if (str_contains(strtolower($phh['content-disposition'] ?? ''), 'attachment')) continue;
                if (str_starts_with($pt, 'multipart/')) {
                    $t = self::texto($phh, $pc, $prof + 1);
                    if ($t !== '') $plano ??= $t;
                } elseif (str_starts_with($pt, 'text/plain')) {
                    $plano ??= self::decodificar($phh, $pc);
                } elseif (str_starts_with($pt, 'text/html')) {
                    $html ??= self::decodificar($phh, $pc);
                }
            }
            return $plano ?? ($html !== null ? self::deHtml($html) : '');
        }
        $t = self::decodificar($h, $cuerpo);
        return str_starts_with($tipo, 'text/html') ? self::deHtml($t) : $t;
    }

    private static function decodificar(array $h, string $c): string
    {
        $enc = strtolower(trim($h['content-transfer-encoding'] ?? ''));
        if ($enc === 'base64') $c = base64_decode(preg_replace('/[^A-Za-z0-9+\/=]/', '', $c)) ?: '';
        elseif ($enc === 'quoted-printable') $c = quoted_printable_decode($c);
        $cs = strtoupper(self::param($h['content-type'] ?? '', 'charset') ?: 'UTF-8');
        if ($cs !== 'UTF-8' && $cs !== 'US-ASCII') {
            $conv = @mb_convert_encoding($c, 'UTF-8', $cs);
            if ($conv !== false) $c = $conv;
        }
        return mb_check_encoding($c, 'UTF-8') ? $c : mb_convert_encoding($c, 'UTF-8', 'ISO-8859-1');
    }

    private static function deHtml(string $h): string
    {
        $h = preg_replace('#<(style|script|head)[^>]*>.*?</\1>#si', '', $h);
        $h = preg_replace('#<(br|/p|/div|/tr|/h\d|/li)[^>]*>#i', "\n", $h);
        $t = html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $t)));
    }

    /** Quita lo citado de correos anteriores ("> ..." y "El ... escribió:" / "On ... wrote:"). */
    public static function sinCitas(string $t): string
    {
        $t = str_replace("\r", '', $t);
        $out = [];
        foreach (explode("\n", $t) as $l) {
            if (preg_match('/^\s*(El|On)\b.{5,200}(escribió|wrote)\s*:\s*$/iu', $l)) break;
            if (preg_match('/^-{2,}\s*(Original Message|Mensaje original)/i', $l)) break;
            if (str_starts_with(ltrim($l), '>')) continue;
            $out[] = rtrim($l);
        }
        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $out)));
    }
}
