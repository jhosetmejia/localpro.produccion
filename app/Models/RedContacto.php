<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Contacto de la base de LocalPro compartida por todos los asesores (se contacta una sola vez)
class RedContacto extends Model
{
    protected $table = 'red_contactos';
    protected $fillable = ['nombre', 'telefono', 'lote', 'asignado_a', 'asignado_fecha', 'contactado_at', 'contactado_por', 'estado'];
    protected $casts = ['contactado_at' => 'datetime', 'asignado_fecha' => 'date'];

    public const ESTADOS = ['enviado' => 'Enviado ✓', 'numero_malo' => 'Número malo', 'no_interesa' => 'No le interesa'];

    public function contactadoPor()
    {
        return $this->belongsTo(Asesor::class, 'contactado_por');
    }

    public function asignadoA()
    {
        return $this->belongsTo(Asesor::class, 'asignado_a');
    }
}
