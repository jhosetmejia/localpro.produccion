<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Campaña de donación al pagar el plan (Navidad 2026: ayudas para los niños de los barrios más necesitados de Pasto).
 * La donación se cobra junto con el plan en Wompi pero se guarda aparte (pagos_suscripcion.donacion):
 * no cuenta como venta del plan ni para comisiones de asesores.
 * Se maneja en Admin → Donaciones (encender, valor y si la casilla sale marcada).
 */
class Donacion
{
    public static function activa(): bool
    {
        return (bool) (int) Ajuste::valor('donacion_activa', 0) && self::monto() > 0;
    }

    public static function monto(): float
    {
        return (float) Ajuste::valor('donacion_monto', 5000);
    }

    public static function marcada(): bool
    {
        return (bool) (int) Ajuste::valor('donacion_marcada', 0);
    }
}
