<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Nota aclaratoria de una atención cerrada (no se borra ni se edita)
class AtencionNota extends Model
{
    protected $table = 'atencion_notas';

    public const UPDATED_AT = null;

    protected $fillable = ['atencion_id', 'user_id', 'autor', 'texto'];

    protected $casts = ['texto' => 'encrypted'];
}
