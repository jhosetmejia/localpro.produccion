<?php

namespace App\Support;

use App\Models\Tienda;

/**
 * Impresora de tiquetes (térmica de rollo). Las de 80 mm imprimen ~72 mm y las de 58 mm ~48 mm:
 * si el tiquete se dibuja de 80 mm sale cortado a los dos lados ("chueco"). Por eso se dibuja con el ancho real y centrado. Aquí se guarda cómo es la del negocio
 * y se arma el CSS que usan el tiquete, los recibos y la comanda de cocina.
 */
class Impresora
{
    public const PAPELES = [80 => 'Rollo de 80 mm (el más común)', 58 => 'Rollo de 58 mm (pequeña)'];

    public static function defecto(int $papel = 80): array
    {
        return ['papel' => $papel, 'ancho' => $papel === 58 ? 48 : 72, 'margen' => 0, 'letra' => 12, 'negrita' => false, 'final' => 8];
    }

    // Une lo guardado con los valores por defecto y lo deja dentro de límites seguros
    public static function normalizar(array $c): array
    {
        $papel = (int) ($c['papel'] ?? 80) === 58 ? 58 : 80;
        $d = self::defecto($papel);
        $num = fn ($k, $min, $max) => max($min, min($max, round((float) ($c[$k] ?? $d[$k]), 1)));
        return [
            'papel' => $papel,
            'ancho' => $num('ancho', 40, $papel),
            'margen' => $num('margen', -8, 15),
            'letra' => (int) $num('letra', 9, 18),
            'negrita' => filter_var($c['negrita'] ?? $d['negrita'], FILTER_VALIDATE_BOOLEAN),
            'final' => (int) $num('final', 0, 40),
        ];
    }

    public static function de(?Tienda $t = null): array
    {
        $t = $t ?: TiendaActual::tienda();
        return self::normalizar(is_array($t?->impresora) ? $t->impresora : []);
    }

    // CSS que se pone después del estilo del tiquete (.tk) para respetar la impresora del negocio
    public static function css(?array $c = null): string
    {
        $c = $c ?: self::de();
        $w = $c['ancho'];
        return '@page{size:' . $c['papel'] . 'mm auto;margin:0}'
            . '.tk{width:' . $w . 'mm;padding:2mm 0 ' . $c['final'] . 'mm;font-size:' . $c['letra'] . 'px' . ($c['negrita'] ? ';font-weight:700' : '') . '}'
            . '.tk .logo{max-width:' . round($w * .65) . 'mm}'
            . '.acc{width:' . max($w, 72) . 'mm}'
            . '@media print{html,body{width:' . $c['papel'] . 'mm;margin:0;padding:0}'
            . '.tk{width:' . $w . 'mm;margin:0 0 0 ' . self::izquierda($c) . 'mm!important;box-shadow:none}}';
    }

    // Distancia desde el borde izquierdo del papel: centrado + el ajuste del dueño (negativo = hacia la izquierda)
    public static function izquierda(array $c): float
    {
        return max(0, round(($c['papel'] - $c['ancho']) / 2 + $c['margen'], 1));
    }
}
