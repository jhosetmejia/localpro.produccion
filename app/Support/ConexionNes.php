<?php

namespace App\Support;

/**
 * 2026-11-17 (Jhoset): Nariño ExpoShow (NES) manda a sus marcas y empresas a crear su tienda en LocalProo
 * (un solo marketplace). NES abre el registro con un enlace firmado (datos prellenados) y luego consulta
 * aquí, servidor a servidor, si esa persona ya creó su cuenta/tienda para quitarle la tarea pendiente.
 * La clave es la misma de LP Chat: LPCHAT_SECRETO_NES (en NES: LPCHAT_SECRETO).
 */
class ConexionNes
{
    public static function secreto(): ?string
    {
        return config('lpchat.origenes.nes.secreto') ?: null;
    }

    public static function firma(string $datos): ?string
    {
        $s = self::secreto();

        return $s ? hash_hmac('sha256', $datos, $s) : null;
    }

    /** Datos del enlace firmado de NES (nombre, email, celular...) o null si no es válido o venció (2 h). */
    public static function datosEnlace(?string $d, ?string $f): ?array
    {
        if (! $d || ! $f || ! ($firma = self::firma($d)) || ! hash_equals($firma, $f)) {
            return null;
        }
        $p = json_decode((string) base64_decode(strtr($d, '-_', '+/')), true);
        if (! is_array($p) || abs(time() - (int) ($p['ts'] ?? 0)) > 7200) {
            return null;
        }

        return array_intersect_key($p, array_flip(['id', 'nombre', 'email', 'celular', 'empresa', 'ciudad']));
    }
}
