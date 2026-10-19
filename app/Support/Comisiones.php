<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Comision;
use App\Models\PagoSuscripcion;

/**
 * Reparte cada pago de plan entre la red comercial.
 * Solo el PLAN ANUAL genera comisión (mensualidades y servicios adicionales son 100% de LocalPro).
 * Valores fijos: si sube el precio del plan, las comisiones siguen iguales y la diferencia queda para LocalPro.
 * Venta nueva: asesor 100.000 · el de arriba (quien lo sumó; si no hay, su líder directo) y cada líder (zona, ciudad, departamento, país) 10.000 · LocalPro el resto.
 * Renovación anual: asesor, el de arriba y cada líder 10.000 de por vida · LocalPro el resto.
 * Cuándo se pagan: las de venta nueva se pueden retirar apenas se aprueba el pago; las de renovación se pagan una vez al mes.
 * Para cobrar, cada persona debe estar ACTIVA: haber hecho mínimo una venta nueva en los últimos 30 días
 * (la venta que se está pagando cuenta para quien la hizo). Si no, su parte queda para LocalPro. Un cargo vacío también.
 * Venezuela paga en dólares o bolívares (Admin → Planes y cobros): valores en USD (red_usd_*), en Bs a la tasa del panel.
 */
class Comisiones
{
    /**
     * Valores de comisión en la moneda indicada.
     * COP (Colombia y demás): ajustes red_*  ·  USD (Venezuela): ajustes red_usd_* (Admin → Red comercial → Valores en dólares).
     */
    public static function valores(?string $moneda = Moneda::COP): array
    {
        // Bolívares: los valores de dólares pasados a Bs con la tasa del panel
        if (strtoupper((string) $moneda) === Moneda::VES) {
            $usd = self::valores(Moneda::USD);
            $c = fn ($v) => Moneda::convertir($v, Moneda::USD, Moneda::VES);
            return [
                'moneda' => Moneda::VES,
                'valor_plan' => $c($usd['valor_plan']),
                'nueva' => array_map($c, $usd['nueva']),
                'renovacion' => array_map($c, $usd['renovacion']),
            ];
        }
        if (Moneda::esUsd($moneda)) {
            $u = fn ($k, $d) => (float) Ajuste::valor('red_usd_' . $k, $d);
            return [
                'moneda' => Moneda::USD,
                'valor_plan' => $u('valor_plan', 55),
                'nueva' => ['venta' => $u('nueva_venta', 25), 'arriba' => $u('nueva_arriba', 2.5), 'lider_zona' => $u('nueva_zona', 2.5), 'lider_ciudad' => $u('nueva_ciudad', 2.5), 'lider_departamento' => $u('nueva_departamento', 2.5), 'lider_pais' => $u('nueva_pais', 2.5)],
                'renovacion' => ['venta' => $u('renov_venta', 2.5), 'arriba' => $u('renov_arriba', 2.5), 'lider_zona' => $u('renov_zona', 2.5), 'lider_ciudad' => $u('renov_ciudad', 2.5), 'lider_departamento' => $u('renov_departamento', 2.5), 'lider_pais' => $u('renov_pais', 2.5)],
            ];
        }
        $v = fn ($k, $d) => (float) Ajuste::valor($k, $d);
        return [
            'moneda' => Moneda::COP,
            'valor_plan' => $v('red_valor_plan', 220000),
            'nueva' => ['venta' => $v('red_nueva_venta', 100000), 'arriba' => $v('red_nueva_arriba', 10000), 'lider_zona' => $v('red_nueva_zona', 10000), 'lider_ciudad' => $v('red_nueva_ciudad', 10000), 'lider_departamento' => $v('red_nueva_departamento', 10000), 'lider_pais' => $v('red_nueva_pais', 10000)],
            'renovacion' => ['venta' => $v('red_renov_venta', 10000), 'arriba' => $v('red_renov_arriba', 10000), 'lider_zona' => $v('red_renov_zona', 10000), 'lider_ciudad' => $v('red_renov_ciudad', 10000), 'lider_departamento' => $v('red_renov_departamento', 10000), 'lider_pais' => $v('red_renov_pais', 10000)],
        ];
    }

    // Meta del panel del asesor (un salario mínimo) en su moneda
    public static function salario(?string $moneda = Moneda::COP): float
    {
        if (Moneda::esExtranjera($moneda)) {
            return Moneda::convertir((float) Ajuste::valor('red_usd_salario_minimo', 200), Moneda::USD, $moneda);
        }
        return (float) Ajuste::valor('red_salario_minimo', 1750000);
    }

    public static function generar(PagoSuscripcion $pago): void
    {
        $tienda = $pago->tienda;
        if (!$tienda || !$tienda->asesor_id || (float) $pago->monto <= 0 || $pago->estado !== 'aprobado' || $pago->es_prueba) {
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
            ->where('monto', '>', 0)->where('id', '<>', $pago->id)->where('referencia', 'not like', PagoSuscripcion::PREFIJO_PRUEBA . '%')
            ->get()->contains(fn ($p) => self::esAnual($p));
        $tipo = $anteriores ? 'renovacion' : 'nueva';

        // Las comisiones se pagan en la moneda en que pagó el negocio (Venezuela: dólares)
        $moneda = $pago->moneda ?: Moneda::COP;
        $cfg = self::valores($moneda);
        $lideres = $vendedor->lideres();
        $quien = [
            'venta' => $vendedor->activo ? $vendedor : null,
            'arriba' => self::deArriba($vendedor),
            'lider_zona' => $lideres['zona'],
            'lider_ciudad' => $lideres['ciudad'],
            'lider_departamento' => $lideres['departamento'],
            'lider_pais' => $lideres['pais'],
        ];
        // Activo = mínimo una venta nueva en los últimos 30 días; si no, su parte queda para LocalPro
        $estaActivo = [];
        foreach ($quien as $concepto => $asesor) {
            if (!$asesor) continue;
            $estaActivo[$asesor->id] ??= self::vendioEsteMes($asesor, $pago);
            if (!$estaActivo[$asesor->id]) $quien[$concepto] = null;
        }
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
                'moneda' => $moneda,
                'estado' => 'pendiente',
            ]);
        }
    }

    // El que está inmediatamente arriba del asesor: quien lo sumó a la red; si no hay, su líder directo
    public static function deArriba(\App\Models\Asesor $vendedor): ?\App\Models\Asesor
    {
        $arriba = $vendedor->reclutador ?: $vendedor->padre;
        return ($arriba && $arriba->id !== $vendedor->id && $arriba->activo) ? $arriba : null;
    }

    public const DIAS_ACTIVO = 30;

    /**
     * ¿Hizo mínimo una VENTA NUEVA en los últimos 30 días? (un negocio suyo pagó su primer plan, sin contar pruebas)
     * La venta que se está pagando cuenta para quien la hizo; una renovación no cuenta como venta.
     */
    public static function vendioEsteMes(\App\Models\Asesor $asesor, ?PagoSuscripcion $pagoActual = null): bool
    {
        $desde = now()->subDays(self::DIAS_ACTIVO);
        $pagos = PagoSuscripcion::query()
            ->whereIn('tienda_id', \App\Models\Tienda::where('asesor_id', $asesor->id)->select('id'))
            ->where('estado', 'aprobado')->where('monto', '>', 0)
            ->where('referencia', 'not like', PagoSuscripcion::PREFIJO_PRUEBA . '%')
            ->where('updated_at', '>=', $desde)
            ->get(['id', 'tienda_id']);

        foreach ($pagos as $p) {
            $antes = PagoSuscripcion::where('tienda_id', $p->tienda_id)->where('estado', 'aprobado')->where('monto', '>', 0)
                ->where('referencia', 'not like', PagoSuscripcion::PREFIJO_PRUEBA . '%')->where('id', '<', $p->id)->exists();
            if (!$antes) {
                return true; // primer pago real de ese negocio = venta nueva
            }
        }
        return false;
    }

    // Plan de un año (365 días aprox.)
    public static function esAnual(PagoSuscripcion $pago): bool
    {
        $dias = (int) ($pago->plan->dias ?? 0);
        return $dias >= 360 && $dias <= 400;
    }
}
