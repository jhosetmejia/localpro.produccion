<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class TratamientoItem extends Model
{
    use PerteneceATienda;

    protected $fillable = ['plan_id', 'pieza', 'caras', 'producto_id', 'descripcion', 'cantidad', 'valor', 'estado', 'hecho_at', 'hecho_por', 'orden'];

    protected $casts = ['valor' => 'float', 'cantidad' => 'integer', 'hecho_at' => 'datetime'];

    public function plan()
    {
        return $this->belongsTo(TratamientoPlan::class, 'plan_id');
    }

    public function getTotalAttribute(): float
    {
        return round((float) $this->valor * max(1, (int) $this->cantidad), 2);
    }
}
