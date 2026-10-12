<?php

use App\Support\Paises;
use App\Support\TiendaActual;

if (!function_exists('moneda')) {
    // Configuración de moneda de la tienda actual (Colombia si no hay tienda)
    function moneda(): array
    {
        return Paises::datos(TiendaActual::tienda()?->pais);
    }
}

if (!function_exists('dinero')) {
    // 59900 -> "$59.900" en Colombia, "$59.90" en Ecuador, "S/59.90" en Perú
    function dinero($valor, bool $simbolo = true): string
    {
        $m = moneda();
        $txt = number_format((float) $valor, $m['decimales'], $m['decimal'], $m['miles']);
        return $simbolo ? $m['simbolo'] . $txt : $txt;
    }
}

if (!function_exists('paso_dinero')) {
    // Paso de los campos numéricos de dinero según la moneda
    function paso_dinero(): string
    {
        return moneda()['decimales'] > 0 ? '0.01' : '1';
    }
}

if (!function_exists('numero_whatsapp')) {
    // Número listo para wa.me con el indicativo del país de la tienda
    function numero_whatsapp(?string $telefono): ?string
    {
        $t = preg_replace('/\D/', '', (string) $telefono);
        if ($t === '') {
            return null;
        }
        $ind = moneda()['indicativo'];
        return str_starts_with($t, $ind) && strlen($t) > 10 ? $t : $ind . ltrim($t, '0');
    }
}

if (!function_exists('cantidad')) {
    // 3 -> "3", 1.5 -> "1,5", 0.125 -> "0,125" (con la unidad si se pasa: "1,5 m")
    function cantidad($n, ?string $unidad = null): string
    {
        $n = round((float) $n, 3);
        $dec = moneda()['decimal'] ?? ',';
        $txt = floor($n) == $n ? number_format($n, 0, $dec, moneda()['miles'] ?? '.') : rtrim(rtrim(number_format($n, 3, $dec, moneda()['miles'] ?? '.'), '0'), $dec);
        if ($unidad === null) return $txt;
        $corta = \App\Models\Producto::UNIDADES[$unidad][1] ?? $unidad;
        return $txt . ' ' . $corta;
    }
}

