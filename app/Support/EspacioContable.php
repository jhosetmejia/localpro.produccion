<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Dueño de los datos de Contabilidad en esta petición:
 *  - el negocio (tienda) que tiene el módulo activo, o
 *  - el asesor de la red, que tiene su propio espacio contable aunque no tenga negocio.
 * Así un asesor puede llevar los clientes de un analista contable sin crear una tienda aparte.
 */
class EspacioContable
{
    private static array $cacheAsesor = [];

    /** Id del asesor activo con curso aprobado (no superadministrador), o null */
    public static function asesorId(): ?int
    {
        $u = Auth::user();
        if (!$u || $u->es_superadmin) return null;
        if (!array_key_exists($u->id, self::$cacheAsesor)) {
            $a = $u->asesor;
            self::$cacheAsesor[$u->id] = ($a && $a->activo && $a->curso_aprobado_at) ? (int) $a->id : null;
        }
        return self::$cacheAsesor[$u->id];
    }

    /** ['tienda_id', id] · ['asesor_id', id] · null (consola o sin contexto) */
    public static function dueno(): ?array
    {
        $tienda = TiendaActual::id();
        // El negocio con Contabilidad activa gana; si no la tiene y el usuario es asesor, usa su espacio de asesor
        if ($tienda && (TiendaActual::tienda()?->mod_contable || !self::asesorId())) {
            return ['tienda_id', $tienda];
        }
        if ($a = self::asesorId()) return ['asesor_id', $a];
        return $tienda ? ['tienda_id', $tienda] : null;
    }

    public static function esAsesor(): bool
    {
        return (self::dueno()[0] ?? null) === 'asesor_id';
    }

    /** ¿Puede trabajar ya en Contabilidad? (el asesor siempre; el negocio cuando la activa) */
    public static function activo(): bool
    {
        return self::esAsesor() || (bool) TiendaActual::tienda()?->mod_contable;
    }

    public static function esMio($modelo): bool
    {
        $d = self::dueno();
        return $modelo && $d && (int) $modelo->{$d[0]} === (int) $d[1];
    }

    /** Carpeta privada de los archivos subidos */
    public static function carpeta(): string
    {
        $d = self::dueno();
        return 'contabilidad/' . (($d[0] ?? null) === 'asesor_id' ? 'asesor-' . $d[1] : ($d[1] ?? 0));
    }
}
