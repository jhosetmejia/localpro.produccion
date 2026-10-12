<?php

namespace App\Support\Dian;

// Desglosa el IVA / impoconsumo de una venta. En LocalPro los precios ya traen el impuesto incluido
// (precio final al público), así que aquí se separa la base y el impuesto de cada línea.
class Impuestos
{
    /**
     * @param array $lineas [['total' => float, 'impuesto' => 'iva19'|...], ...] total de cada línea antes del descuento general
     * @param float $descuento descuento general de la venta (se reparte proporcional entre las líneas)
     * @return array ['lineas' => [[total, base, iva, tasa, impuesto]], 'base' => float, 'impuestos' => float, 'detalle' => [...]]
     */
    public static function calcular(array $lineas, float $descuento = 0): array
    {
        $bruto = array_sum(array_map(fn ($l) => (float) $l['total'], $lineas));
        $descuento = min(max($descuento, 0), $bruto);
        $res = [];
        $repartido = 0.0;
        $ultimo = count($lineas) - 1;
        foreach (array_values($lineas) as $i => $l) {
            $tot = (float) $l['total'];
            // El último renglón se lleva el centavo que sobre del reparto
            $desc = $bruto > 0 ? ($i === $ultimo ? round($descuento - $repartido, 2) : round($descuento * $tot / $bruto, 2)) : 0;
            $repartido += $desc;
            $neto = round($tot - $desc, 2);
            [, $tasa, $codigo] = Catalogos::impuesto($l['impuesto'] ?? null);
            $base = $tasa > 0 ? round($neto / (1 + $tasa / 100), 2) : $neto;
            $res[] = ['total' => $neto, 'descuento' => $desc, 'base' => $base, 'iva' => round($neto - $base, 2), 'tasa' => (float) $tasa, 'impuesto' => $l['impuesto'] ?? 'ninguno', 'codigo' => $codigo];
        }

        $detalle = [];
        foreach ($res as $r) {
            if (!$r['codigo']) continue; // excluidos y no responsables no llevan tributo
            $k = $r['impuesto'];
            $detalle[$k] ??= ['impuesto' => $k, 'codigo' => $r['codigo'], 'nombre' => Catalogos::impuesto($k)[0], 'tasa' => $r['tasa'], 'base' => 0, 'valor' => 0];
            $detalle[$k]['base'] = round($detalle[$k]['base'] + $r['base'], 2);
            $detalle[$k]['valor'] = round($detalle[$k]['valor'] + $r['iva'], 2);
        }

        return [
            'lineas' => $res,
            'base' => round(array_sum(array_column($res, 'base')), 2),
            'impuestos' => round(array_sum(array_column($res, 'iva')), 2),
            'detalle' => array_values($detalle),
        ];
    }
}
