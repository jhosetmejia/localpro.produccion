<?php

namespace App\Models;

use App\Support\TiendaActual;

/**
 * Datos del negocio que se muestran en tiquetes, facturas y configuración: son los de la tienda actual.
 */
class Negocio extends Tienda
{
    public static function datos(): Tienda
    {
        return TiendaActual::tienda() ?? new Tienda(['nombre' => 'LocalPro']);
    }
}
