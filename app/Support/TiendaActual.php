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
        $u = self::uso($que);
        if (!$u || $u['actual'] < $u['max']) {
            return null;
        }
        if ($que === 'usuarios') {
            return $u['gratis']
                ? "El plan Gratis permite el dueño y " . ($u['max'] - 1) . " vendedores. Pásate a Pro en \"Mi plan\" para agregar más personas."
                : "Su plan {$u['plan']} permite hasta {$u['max']} usuarios. Mejore su plan en \"Mi plan\" para agregar más.";
        }
        return $u['gratis']
            ? "El plan Gratis permite hasta {$u['max']} productos. Pásate a Pro en \"Mi plan\" para crear más productos."
            : "Llegaste al límite de {$u['max']} productos de tu plan. Comunícate con soporte para ampliar la cantidad de productos.";
    }

    /** ['actual','max','plan','gratis'] o null si el plan no tiene límite */
    public static function uso(string $que): ?array
    {
        $plan = self::tienda()?->plan;
        $max = $que === 'usuarios' ? $plan?->max_usuarios : $plan?->max_productos;
        if (!$max) {
            return null;
        }
        $actual = $que === 'usuarios'
            ? \App\Models\User::where('tienda_id', self::id())->count()
            : \App\Models\Producto::count();
        return ['actual' => $actual, 'max' => (int) $max, 'plan' => $plan->nombre, 'gratis' => (float) $plan->precio <= 0 && !$plan->dias];
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
