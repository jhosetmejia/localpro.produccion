<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Proveedor o cliente de un cliente contable: se recuerda si es persona natural o jurídica,
// si es declarante, si es autorretenedor y su concepto habitual de retención
class ContableTercero extends Model
{
    use PerteneceATienda;

    protected $table = 'contable_terceros';

    protected $fillable = ['cliente_id', 'nit', 'nombre', 'tipo_persona', 'declarante', 'autorretenedor', 'concepto'];

    protected $casts = ['declarante' => 'boolean', 'autorretenedor' => 'boolean'];
}
