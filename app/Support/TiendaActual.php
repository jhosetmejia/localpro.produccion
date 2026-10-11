<?php

namespace App\Support;

use App\Models\Tienda;

/**
 * La tienda con la que se está trabajando en esta petición.
 * Todo lo que pertenece a una tienda se filtra y se guarda con este id.
 */
class TiendaActual
{
    private static ?int $id = null;
    private static ?Tienda $tienda = null;

    public static function set(?int $id): void
    {
        self::$id = $id;
        self::$tienda = null;
    }

    public static function id(): ?int
    {
        return self::$id;
    }

    public static function tienda(): ?Tienda
    {
        if (self::$id && (!self::$tienda || self::$tienda->id !== self::$id)) {
            self::$tienda = Tienda::find(self::$id);
        }
        return self::$id ? self::$tienda : null;
    }

    /**
     * Revisa el límite del plan (usuarios o productos). Devuelve un mensaje si ya llegó al máximo.
     */
    public static function limitePlan(string $que): ?string
    {
        $plan = self::tienda()?->plan;
        $max = $que === 'usuarios' ? $plan?->max_usuarios : $plan?->max_productos;
        if (!$max) {
            return null;
        }
        $actual = $que === 'usuarios'
            ? \App\Models\User::where('tienda_id', self::id())->count()
            : \App\Models\Producto::count();
        return $actual >= $max ? "Su plan {$plan->nombre} permite hasta {$max} {$que}. Mejore su plan en \"Mi plan\" para agregar más." : null;
    }

    /**
     * Ejecuta algo como si se estuviera dentro de otra tienda (o de ninguna).
     */
    public static function como(?int $id, callable $fn)
    {
        $antes = self::$id;
        self::set($id);
        try {
            return $fn();
        } finally {
            self::set($antes);
        }
    }
}
