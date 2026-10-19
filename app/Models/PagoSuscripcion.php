<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoSuscripcion extends Model
{
    protected $table = 'pagos_suscripcion';

    // Pagos de prueba (php artisan wompi:prueba): activan el plan pero no generan comisiones ni cuentan como pago anterior
    public const PREFIJO_PRUEBA = 'LP-PRUEBA-';

    public function getEsPruebaAttribute(): bool
    {
        return str_starts_with((string) $this->referencia, self::PREFIJO_PRUEBA);
    }

    protected $fillable = ['tienda_id', 'plan_id', 'monto', 'moneda', 'cobro_cop', 'metodo', 'referencia', 'estado', 'periodo_desde', 'periodo_hasta', 'nota', 'registrado_por', 'respuesta'];

    protected $casts = ['periodo_desde' => 'date', 'periodo_hasta' => 'date', 'respuesta' => 'array'];

    // Expresión SQL del valor del pago en pesos (los de dólares con lo que cobró Wompi o a la tasa del día), para sumar ingresos
    public static function sqlEnCop(): \Illuminate\Contracts\Database\Query\Expression
    {
        $tasa = \App\Support\Moneda::tasa();
        return \Illuminate\Support\Facades\DB::raw("CASE WHEN moneda = 'USD' THEN COALESCE(cobro_cop, monto * {$tasa}) ELSE monto END");
    }

    // Valor de este pago en pesos colombianos
    public function getMontoCopAttribute(): float
    {
        return ($this->moneda ?: 'COP') === 'USD'
            ? (float) ($this->cobro_cop ?? \App\Support\Moneda::aCop($this->monto, 'USD'))
            : (float) $this->monto;
    }

    // $220.000 o US$55 según la moneda del pago
    public function getMontoTextoAttribute(): string
    {
        return \App\Support\Moneda::formato($this->monto, $this->moneda ?: 'COP');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Aprueba el pago y extiende el plan de la tienda desde su vencimiento (o desde hoy si ya venció).
     */
    public function aprobar(): void
    {
        if ($this->estado === 'aprobado') {
            return;
        }
        $tienda = $this->tienda;
        $plan = $this->plan;
        $desde = $tienda->plan_vence && $tienda->plan_vence->isFuture() && (int) $tienda->plan_id === (int) $plan->id
            ? $tienda->plan_vence->copy()->addDay()
            : today();
        $hasta = $plan->dias ? $desde->copy()->addDays($plan->dias - 1) : null;

        $this->update(['estado' => 'aprobado', 'periodo_desde' => $desde, 'periodo_hasta' => $hasta]);
        $tienda->forceFill(['plan_id' => $plan->id, 'plan_vence' => $hasta, 'estado' => 'activa'])->save();

        // Comisiones de la red comercial (si el negocio llegó por un asesor)
        try {
            \App\Support\Comisiones::generar($this->fresh());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
