<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_pagos';
    protected $fillable = ['estudiante_id', 'numero', 'fecha', 'valor', 'saldo_favor', 'metodo', 'referencia', 'nota', 'user_id', 'anulado'];
    protected $casts = ['fecha' => 'date', 'valor' => 'float', 'saldo_favor' => 'float', 'anulado' => 'boolean'];

    public function estudiante() { return $this->belongsTo(Estudiante::class); }
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function cargos()
    {
        return $this->belongsToMany(Cargo::class, 'inst_pago_cargo', 'pago_id', 'cargo_id')->withPivot('valor');
    }
}
