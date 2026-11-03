<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Control mensual de ortodoncia: arcos, ligaduras, elásticos y notas
class OrtodonciaControl extends Model
{
    use PerteneceATienda;

    protected $table = 'ortodoncia_controles';

    protected $fillable = ['plan_id', 'paciente_id', 'fecha', 'arco_sup', 'arco_inf', 'ligaduras', 'elasticos', 'notas', 'proximo', 'user_id', 'profesional_nombre'];

    protected $casts = ['fecha' => 'date', 'proximo' => 'date', 'notas' => 'encrypted'];
}
