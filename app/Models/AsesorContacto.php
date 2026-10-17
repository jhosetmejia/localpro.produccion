<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Contacto de la base de datos de un asesor (para ofrecerle LocalPro)
class AsesorContacto extends Model
{
    protected $table = 'asesor_contactos';
    protected $fillable = ['asesor_id', 'nombre', 'telefono', 'contactado_at', 'estado'];
    protected $casts = ['contactado_at' => 'datetime'];
}
