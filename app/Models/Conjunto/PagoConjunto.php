<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class PagoConjunto extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_pagos';
    protected $fillable = ['unidad_id', 'numero', 'fecha', 'valor', 'saldo_favor', 'metodo', 'referencia', 'nota', 'user_id', 'anulado'];
    protected $casts = ['fecha' => 'date', 'valor' => 'float', 'saldo_favor' => 'float', 'anulado' => 'boolean'];

    public function unidad() { return $this->belongsTo(Unidad::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function cargos()
    {
        return $this->belongsToMany(Cargo::class, 'conjunto_pago_cargo', 'pago_id', 'cargo_id')->withPivot('valor');
    }
}
