<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Comision;
use App\Models\PagoSuscripcion;

/**
 * Reparte cada pago de plan entre la red comercial.
 * Venta nueva (por defecto, plan de 200.000): asesor 80.000 · líder de ciudad 10.000 · líder de departamento 10.000 · LocalPro el resto.
 * Renovación (cada año): 10.000 a cada uno · LocalPro el resto.
 * Si el pago es de otro valor, las comisiones se ajustan en proporción. Un cargo vacío deja su parte para LocalPro.
 */
class Comisiones
{
    public static function valores(): array
    {
        $v = fn ($k, $d) => (float) Ajuste::valor($k, $d);
        return [
            'valor_plan' => $v('red_valor_plan', 200000),
            'nueva' => ['venta' => $v('red_nueva_venta', 80000), 'lider_ciudad' => $v('red_nueva_ciudad', 10000), 'lider_departamento' => $v('red_nueva_departamento', 10000)],
            'renovacion' => ['venta' => $v('red_renov_venta', 10000), 'lider_ciudad' => $v('red_renov_ciudad', 10000), 'lider_departamento' => $v('red_renov_departamento', 10000)],
        ];
    }

    public static function generar(PagoSuscripcion $pago): void
    {
        $tienda = $pago->tienda;
        if (!$tienda || !$tienda->asesor_id || (float) $pago->monto <= 0 || $pago->estado !== 'aprobado') {
            return;
        }
        if (Comision::where('pago_suscripcion_id', $pago->id)->exists()) {
            return;
        }
        $vendedor = $tienda->asesor;
        if (!$vendedor) {
            return;
        }

        $anteriores = PagoSuscripcion::where('tienda_id', $tienda->id)->where('estado', 'aprobado')
            ->where('monto', '>', 0)->where('id', '<>', $pago->id)->exists();
        $tipo = $anteriores ? 'renovacion' : 'nueva';

        $cfg = self::valores();
        $factor = $cfg['valor_plan'] > 0 ? min(1, (float) $pago->monto / $cfg['valor_plan']) : 1;
        $lideres = $vendedor->lideres();
        $quien = [
            'venta' => $vendedor->activo ? $vendedor : null,
            'lider_ciudad' => $lideres['ciudad'],
            'lider_departamento' => $lideres['departamento'],
        ];

        foreach ($quien as $concepto => $asesor) {
            $monto = round($cfg[$tipo][$concepto] * $factor, 2);
            if (!$asesor || $monto <= 0) {
                continue; // la parte queda para LocalPro
            }
            Comision::create([
                'asesor_id' => $asesor->id,
                'tienda_id' => $tienda->id,
                'pago_suscripcion_id' => $pago->id,
                'concepto' => $concepto,
                'tipo' => $tipo,
                'monto' => $monto,
                'estado' => 'pendiente',
            ]);
        }
    }
}
