<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = ['nombre', 'es_efectivo', 'activo', 'orden'];

    protected $casts = ['es_efectivo' => 'boolean', 'activo' => 'boolean'];

    public function scopeActivos($q)
    {
        return $q->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }
}
