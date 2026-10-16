<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Constancia de cada correo enviado (o intentado) a un prospecto
class ProspectoEnvio extends Model
{
    protected $table = 'prospecto_envios';

    protected $fillable = ['prospecto_id', 'paso', 'correo', 'asunto', 'estado', 'detalle'];

    public function prospecto()
    {
        return $this->belongsTo(Prospecto::class);
    }
}
