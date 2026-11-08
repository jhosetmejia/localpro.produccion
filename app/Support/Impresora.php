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

    // Cada negocio puede tener dos: 'caja' (tiquetes, facturas, cuadre, recibos) y 'cocina' (comandas)
    public const PARA = ['caja' => 'impresora', 'cocina' => 'impresora_cocina'];

    public static function para(?string $para): string
    {
        return isset(self::PARA[$para]) ? $para : 'caja';
    }

    // Ajuste guardado en ESTE computador (cookie del navegador, por negocio): manda sobre el del negocio
    public static function cookie(?Tienda $t, string $para): string
    {
        return 'lp_imp_' . self::para($para) . '_' . ($t?->id ?? 0);
    }

    public static function delEquipo(?Tienda $t, string $para): ?array
    {
        if (!$t) return null;
        try {
            $v = request()->cookie(self::cookie($t, $para));
            $a = is_string($v) ? json_decode($v, true) : null;
            return is_array($a) && isset($a['papel']) ? $a : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function datos(?Tienda $t, string $para): ?array
    {
        if ($e = self::delEquipo($t, $para)) return $e;
        $v = $t?->{self::PARA[self::para($para)]};
        return is_array($v) && $v !== [] ? $v : null;
    }

    // ¿El negocio tiene ajuste general (para todos sus computadores)?
    public static function delNegocio(?Tienda $t, string $para): ?array
    {
        $v = $t?->{self::PARA[self::para($para)]};
        return is_array($v) && $v !== [] ? self::normalizar($v) : null;
    }

    public static function de(?Tienda $t = null, string $para = 'caja'): array
    {
        $t = $t ?: TiendaActual::tienda();
        return self::normalizar(self::datos($t, $para) ?? []);
    }

    // Ajustes para la comanda: los de la impresora de cocina; si no tiene, los de caja (como antes); si no, null (sin cambios)
    public static function deCocina(?Tienda $t = null): ?array
    {
        $t = $t ?: TiendaActual::tienda();
        $d = self::datos($t, 'cocina') ?? self::datos($t, 'caja');
        return $d ? self::normalizar($d) : null;
    }

    // ¿Este negocio ya ajustó su impresora? (los ajustes son de cada negocio: columna tiendas.impresora)
    public static function configurada(?Tienda $t = null, string $para = 'caja'): bool
    {
        $t = $t ?: TiendaActual::tienda();
        return self::datos($t, $para) !== null;
    }

    /**
     * CSS que se pone después del estilo del tiquete (.tk).
     * - Negocio que nunca ajustó su impresora: no cambia nada (el tiquete sale exactamente como siempre).
     * - Negocio con ajustes: la hoja mide lo que mide el papel y los lados quedan como relleno (padding),
     *   así el texto nunca queda pegado al borde, en pantalla ni impreso.
     */
    public static function css(?array $c = null): string
    {
        if ($c === null) {
            if (!self::configurada()) return '';
            $c = self::de();
        }
        $papel = $c['papel'];
        $izq = self::izquierda($c);
        $der = max(0, round($papel - $c['ancho'] - $izq, 1));
        return '@page{size:' . $papel . 'mm auto;margin:0}'
            . '.tk{width:' . $papel . 'mm;padding:3mm ' . $der . 'mm ' . $c['final'] . 'mm ' . $izq . 'mm;font-size:' . $c['letra'] . 'px' . ($c['negrita'] ? ';font-weight:700' : '') . '}'
            . '.tk .logo{max-width:' . round($c['ancho'] * .65) . 'mm}'
            . '.acc{width:' . max($papel, 72) . 'mm}'
            . '@media print{html,body{width:' . $papel . 'mm;margin:0;padding:0}.tk{width:' . $papel . 'mm;margin:0!important;box-shadow:none}}';
    }

    // Distancia desde el borde izquierdo del papel: centrado + el ajuste del dueño (negativo = hacia la izquierda)
    public static function izquierda(array $c): float
    {
        return max(0, round(($c['papel'] - $c['ancho']) / 2 + $c['margen'], 1));
    }
}
