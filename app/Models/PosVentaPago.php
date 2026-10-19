<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosVentaPago extends Model
{
    protected $table = 'pos_venta_pagos';

    protected $fillable = ['pos_venta_id', 'metodo_pago_id', 'monto', 'referencia', 'moneda', 'monto_moneda', 'tasa'];

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
