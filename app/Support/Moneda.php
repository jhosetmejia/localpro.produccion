<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Moneda con la que LocalPro COBRA el plan y PAGA las comisiones según el país.
 * - Colombia y el resto: pesos colombianos (COP), como siempre.
 * - Venezuela: dólares (USD). Su tienda vende en dólares (Paises), el plan se le cobra en dólares
 *   y las comisiones de esas ventas se pagan en dólares.
 * Para sumar otro país en dólares basta agregarlo a PAISES_USD.
 */
class Moneda
{
    public const COP = 'COP';
    public const USD = 'USD';

    // Países a los que se les cobra el plan y se les pagan comisiones en dólares
    public const PAISES_USD = ['VE'];

    public static function dePais(?string $pais): string
    {
        return in_array(strtoupper((string) $pais), self::PAISES_USD, true) ? self::USD : self::COP;
    }

    // Moneda de quien está usando el sistema: la del asesor (si es de la red) o la de su tienda
    public static function actual(): string
    {
        $u = auth()->user();
        if ($u && ($a = $u->asesor)) {
            return self::dePais($a->pais);
        }
        return self::dePais(TiendaActual::tienda()?->pais);
    }

    public static function esUsd(?string $moneda): bool
    {
        return strtoupper((string) $moneda) === self::USD;
    }

    // COP: $100.000 · USD: US$25 / US$1,25
    public static function formato($valor, ?string $moneda = self::COP, bool $codigo = false): string
    {
        $v = (float) $valor;
        if (self::esUsd($moneda)) {
            $dec = abs($v - round($v)) < 0.005 ? 0 : 2;
            $txt = ($v < 0 ? '-' : '') . 'US$' . number_format(abs($v), $dec, ',', '.');
            return $codigo ? $txt . ' USD' : $txt;
        }
        $txt = ($v < 0 ? '-' : '') . '$' . number_format(abs($v), 0, ',', '.');
        return $codigo ? $txt . ' COP' : $txt;
    }

    // Función de JavaScript equivalente a formato() para usar en las vistas: var fmt = {!! Moneda::js($m) !!};
    public static function js(?string $moneda = self::COP): string
    {
        return self::esUsd($moneda)
            ? "function (n) { var r = Math.round((+n || 0) * 100) / 100, a = Math.abs(r), t = (a % 1 ? a.toFixed(2) : String(Math.round(a))).split('.'); t[0] = t[0].replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.'); return (r < 0 ? '-' : '') + 'US$' + t.join(','); }"
            : "function (n) { var r = Math.round(+n || 0); return (r < 0 ? '-$' : '$') + Math.abs(r).toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.'); }";
    }

    // Pesos colombianos por cada dólar (para cobrar con Wompi, que solo recibe COP). Se cambia en Admin → Planes.
    public static function tasa(): float
    {
        return max(1, (float) Ajuste::valor('tasa_usd_cop', 4000));
    }

    // Lo que Wompi debe cobrar en pesos por un valor en esa moneda
    public static function aCop($valor, ?string $moneda): float
    {
        return self::esUsd($moneda) ? round((float) $valor * self::tasa()) : round((float) $valor);
    }

    // Paso de los campos de dinero de esa moneda
    public static function paso(?string $moneda): string
    {
        return self::esUsd($moneda) ? '0.01' : '1';
    }
}
