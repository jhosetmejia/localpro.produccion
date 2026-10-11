<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Comision;
use App\Models\PagoSuscripcion;

/**
 * Reparte cada pago de plan entre la red comercial.
 * Solo el PLAN ANUAL genera comisión (mensualidades y servicios adicionales son 100% de LocalPro).
 * Venta nueva (plan de 220.000): asesor 100.000 · líder de zona, ciudad, departamento y país 5.000 cada uno · LocalPro 100.000.
 * Renovación anual: 10.000 a cada uno · LocalPro el resto. Un cargo vacío deja su parte para LocalPro.
 */
class Comisiones
{
    public static function valores(): array
    {
        $v = fn ($k, $d) => (float) Ajuste::valor($k, $d);
        return [
            'valor_plan' => $v('red_valor_plan', 220000),
            'nueva' => ['venta' => $v('red_nueva_venta', 100000), 'lider_zona' => $v('red_nueva_zona', 5000), 'lider_ciudad' => $v('red_nueva_ciudad', 5000), 'lider_departamento' => $v('red_nueva_departamento', 5000), 'lider_pais' => $v('red_nueva_pais', 5000)],
            'renovacion' => ['venta' => $v('red_renov_venta', 5000), 'lider_zona' => $v('red_renov_zona', 10000), 'lider_ciudad' => $v('red_renov_ciudad', 10000), 'lider_departamento' => $v('red_renov_departamento', 10000), 'lider_pais' => $v('red_renov_pais', 10000)],
        ];
    }

    public static function generar(PagoSuscripcion $pago): void
    {
        $tienda = $pago->tienda;
        if (!$tienda || !$tienda->asesor_id || (float) $pago->monto <= 0 || $pago->estado !== 'aprobado') {
            return;
        }
        // Solo el plan anual paga comisión
        if (!self::esAnual($pago)) {
            return;
        }
        if (Comision::where('pago_suscripcion_id', $pago->id)->exists()) {
            return;
        }
        $vendedor = $tienda->asesor;
        // La renovación se paga de por vida, siempre que el negocio y el asesor sigan activos
        if (!$vendedor || !$tienda->activa) {
            return;
        }

        // Renovación si ya había pagado antes un plan anual
        $anteriores = PagoSuscripcion::with('plan')->where('tienda_id', $tienda->id)->where('estado', 'aprobado')
            ->where('monto', '>', 0)->where('id', '<>', $pago->id)->get()->contains(fn ($p) => self::esAnual($p));
        $tipo = $anteriores ? 'renovacion' : 'nueva';

        $cfg = self::valores();
        $lideres = $vendedor->lideres();
        $quien = [
            'venta' => $vendedor->activo ? $vendedor : null,
            'lider_zona' => $lideres['zona'],
            'lider_ciudad' => $lideres['ciudad'],
            'lider_departamento' => $lideres['departamento'],
            'lider_pais' => $lideres['pais'],
        ];
        // Nunca se reparte más de lo que pagó el negocio
        if (array_sum($cfg[$tipo]) > (float) $pago->monto) {
            return;
        }

        foreach ($quien as $concepto => $asesor) {
            $monto = round($cfg[$tipo][$concepto], 2);
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

    // Plan de un año (365 días aprox.)
    public static function esAnual(PagoSuscripcion $pago): bool
    {
        $dias = (int) ($pago->plan->dias ?? 0);
        return $dias >= 360 && $dias <= 400;
    }
}
