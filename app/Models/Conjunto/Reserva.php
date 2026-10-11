<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_reservas';
    protected $fillable = ['unidad_id', 'zona', 'fecha', 'hora_inicio', 'hora_fin', 'estado', 'nota'];
    protected $casts = ['fecha' => 'date'];

    public function unidad() { return $this->belongsTo(Unidad::class); }
}
