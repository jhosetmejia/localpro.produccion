<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Comunicado extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_comunicados';
    protected $fillable = ['titulo', 'mensaje', 'user_id'];
}
