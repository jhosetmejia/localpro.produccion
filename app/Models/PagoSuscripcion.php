<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoSuscripcion extends Model
{
    protected $table = 'pagos_suscripcion';

    protected $fillable = ['tienda_id', 'plan_id', 'monto', 'metodo', 'referencia', 'estado', 'periodo_desde', 'periodo_hasta', 'nota', 'registrado_por', 'respuesta'];

    protected $casts = ['periodo_desde' => 'date', 'periodo_hasta' => 'date', 'respuesta' => 'array'];

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
    }
}
