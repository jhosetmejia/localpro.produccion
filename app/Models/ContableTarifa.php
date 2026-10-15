<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Tarifa de retención en la fuente con vigencia (desde / hasta), editable por el analista
class ContableTarifa extends Model
{
    use PerteneceATienda;

    protected $table = 'contable_tarifas';

    protected $fillable = ['concepto', 'nombre', 'base_uvt', 'tarifa_declarante', 'tarifa_no_declarante', 'desde', 'hasta', 'orden'];

    protected $casts = ['base_uvt' => 'float', 'tarifa_declarante' => 'float', 'tarifa_no_declarante' => 'float', 'desde' => 'date', 'hasta' => 'date'];
}
