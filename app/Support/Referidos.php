<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use App\Models\Plan;
use App\Models\Tienda;
use Illuminate\Support\Facades\DB;

/**
 * "Comparta LocalProo y gane": referidos entre negocios.
 * - Cada negocio tiene su código (LCT-00001) y su enlace localproo.com/r/LCT-00001.
 * - El negocio invitado arranca con 2 meses gratis y queda con el mismo asesor (o venta directa) del que lo invitó.
 * - Cada vez que el invitado paga su plan ANUAL (de por vida), el que lo invitó recibe un abono (10.000 por defecto,
 *   ajuste `ref_negocio_valor`; en dólares `ref_negocio_usd`) que se descuenta solo cuando él paga su plan anual.
 * - Los pagos de prueba no cuentan. LocalPro no consigna plata: es saldo para el plan.
 */
class Referidos
{
    public const MINIMO_WOMPI = 1500; // si queda algo por cobrar, nunca menos de lo que Wompi permite

    // Valor del abono en la moneda del negocio que recibe
    public static function valor(string $moneda = 'COP'): float
    {
        $moneda = strtoupper($moneda ?: 'COP');
        if ($moneda === 'COP') return (float) Ajuste::valor('ref_negocio_valor', 10000);
        $usd = (float) Ajuste::valor('ref_negocio_usd', 2.5);
        return $moneda === 'USD' ? $usd : Moneda::convertir($usd, 'USD', $moneda);
    }

    // ¿Es un código de negocio? (LCT-00012, lct12, LCT 12)
    public static function esCodigoNegocio(?string $codigo): bool
    {
        return (bool) preg_match('/^LCT[\s-]*\d{1,7}$/i', trim((string) $codigo));
    }

    public static function tiendaPorCodigo(?string $codigo): ?Tienda
    {
        if (!preg_match('/^LCT[\s-]*0*(\d{1,7})$/i', trim((string) $codigo), $m)) return null;
        return Tienda::where('codigo', 'LCT-' . str_pad($m[1], 5, '0', STR_PAD_LEFT))->first();
    }

    public static function enlace(Tienda $t): string
    {
        return url('/r/' . $t->codigo);
    }

    public static function esAnual(?Plan $plan): bool
    {
        $d = (int) ($plan->dias ?? 0);
        return $d >= 360 && $d <= 400;
    }

    // Cuánto del saldo se descuenta en este pago (solo el plan anual)
    public static function descuento(Tienda $t, Plan $plan, float $monto): float
    {
        $saldo = (float) $t->saldo_referidos;
        if ($saldo <= 0 || $monto <= 0 || !self::esAnual($plan)) return 0.0;
        if ($saldo >= $monto) return $monto; // lo paga todo con su saldo
        $minimo = $t->moneda_cobro === 'COP' ? self::MINIMO_WOMPI : 1;
        return round(max(0, min($saldo, $monto - $minimo)), 2);
    }

    // Al aprobarse un pago: descuenta el saldo usado y abona al negocio que invitó (si es el plan anual del invitado)
    public static function alAprobar(PagoSuscripcion $pago): void
    {
        if ($pago->es_prueba) return;
        $pago->loadMissing(['tienda', 'plan']);
        DB::transaction(function () use ($pago) {
            // 1) El saldo que este negocio usó para pagar
            if ((float) $pago->abono_saldo > 0 && !DB::table('referido_movimientos')->where('pago_suscripcion_id', $pago->id)->where('concepto', 'uso')->exists()) {
                $t = Tienda::whereKey($pago->tienda_id)->lockForUpdate()->first();
                $usa = round(min((float) $pago->abono_saldo, max(0, (float) $t->saldo_referidos)), 2);
                if ($usa > 0) {
                    $t->forceFill(['saldo_referidos' => round((float) $t->saldo_referidos - $usa, 2)])->save();
                    DB::table('referido_movimientos')->insert(['tienda_id' => $t->id, 'pago_suscripcion_id' => $pago->id, 'concepto' => 'uso', 'valor' => -$usa,
                        'moneda' => $pago->moneda ?: 'COP', 'nota' => 'Descontado de su plan ' . ($pago->plan->nombre ?? ''), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            // 2) Abono de por vida al negocio que lo invitó
            $invitado = $pago->tienda;
            if (!$invitado?->referida_por_tienda_id || !self::esAnual($pago->plan)) return;
            if (DB::table('referido_movimientos')->where('pago_suscripcion_id', $pago->id)->where('concepto', 'abono')->exists()) return;
            $ref = Tienda::whereKey($invitado->referida_por_tienda_id)->lockForUpdate()->first();
            if (!$ref || (int) $ref->id === (int) $invitado->id || ($ref->estado ?? 'activa') === 'suspendida') return;
            $valor = self::valor($ref->moneda_cobro);
            $ref->forceFill(['saldo_referidos' => round((float) $ref->saldo_referidos + $valor, 2)])->save();
            DB::table('referido_movimientos')->insert(['tienda_id' => $ref->id, 'referida_id' => $invitado->id, 'pago_suscripcion_id' => $pago->id, 'concepto' => 'abono', 'valor' => $valor,
                'moneda' => $ref->moneda_cobro, 'nota' => $invitado->nombre . ' pagó su plan anual', 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
