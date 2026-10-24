<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// Persona que compra entradas: entra con su correo y clave a "Mis entradas" (no es usuario del negocio)
class EventoAsistente extends Authenticatable
{
    protected $table = 'evento_asistentes';

    protected $fillable = ['nombre', 'email', 'password', 'tipo_documento', 'documento', 'fecha_nacimiento', 'colegio', 'celular', 'acepta_datos_at', 'autoriza_acudiente'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['fecha_nacimiento' => 'date', 'acepta_datos_at' => 'datetime', 'autoriza_acudiente' => 'boolean', 'password' => 'hashed'];

    public const DOCUMENTOS = ['CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad', 'CE' => 'Cédula de extranjería', 'PP' => 'Pasaporte'];

    public function entradas()
    {
        return $this->hasMany(EventoEntrada::class, 'asistente_id');
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }
}
