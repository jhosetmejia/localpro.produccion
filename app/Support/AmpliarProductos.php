<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Tienda;

/**
 * Paquetes de productos extra para planes pagos (ej. $20.000 por cada 100 productos más).
 * El precio y la cantidad se cambian en Admin → Planes y cobros.
 */
class AmpliarProductos
{
    public static function cantidad(): int
    {
        return max(1, (int) Ajuste::valor('productos_paquete_cantidad', 100));
    }

    public static function precio(): int
    {
        return max(0, (int) Ajuste::valor('productos_paquete_precio', 20000));
    }

    public static function precioTexto(): string
    {
        return '$' . number_format(self::precio(), 0, ',', '.');
    }

    /** Frase corta: "$20.000 por cada 100 productos más" */
    public static function oferta(): string
    {
        return self::precioTexto() . ' por cada ' . self::cantidad() . ' productos más';
    }

    /** Enlace de WhatsApp a soporte de LocalPro para pedir la ampliación (o null si no hay número) */
    public static function urlWhatsapp(?Tienda $tienda, int $max): ?string
    {
        $tel = preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp'));
        if (!$tel) {
            return null;
        }
        $msg = 'Hola LocalPro 👋 Llegué al límite de ' . number_format($max, 0, ',', '.') . ' productos en mi tienda '
            . ($tienda?->nombre ?? '') . ($tienda?->codigo ? ' (' . $tienda->codigo . ')' : ' (#' . $tienda?->id . ')')
            . ' y quiero ampliar: ' . self::oferta() . '.';
        return 'https://wa.me/' . $tel . '?text=' . rawurlencode($msg);
    }
}
