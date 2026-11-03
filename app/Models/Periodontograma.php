<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Periodontograma: profundidad al sondaje (6 sitios por pieza), sangrado, movilidad y furca
class Periodontograma extends Model
{
    use PerteneceATienda;

    protected $fillable = ['paciente_id', 'user_id', 'profesional_nombre', 'datos', 'resumen', 'notas'];

    protected $casts = ['datos' => 'encrypted:array', 'resumen' => 'array', 'notas' => 'encrypted'];
}
