<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'comisiones';

    protected $fillable = ['asesor_id', 'tienda_id', 'pago_suscripcion_id', 'concepto', 'tipo', 'monto', 'estado', 'pagada_at', 'nota'];

    protected $casts = ['pagada_at' => 'datetime'];

    public const CONCEPTOS = [
        'venta' => 'Por su venta',
        'lider_ciudad' => 'Como líder de ciudad',
        'lider_departamento' => 'Como líder de departamento',
    ];

    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function pago()
    {
        return $this->belongsTo(PagoSuscripcion::class, 'pago_suscripcion_id');
    }

    public function getConceptoNombreAttribute(): string
    {
        return self::CONCEPTOS[$this->concepto] ?? $this->concepto;
    }
}
