<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Búsquedas hechas en Google Maps (ej. "firma contable en Pasto"), para no repetirlas y rotar ciudades
class ProspectoBusqueda extends Model
{
    protected $table = 'prospecto_busquedas';

    protected $fillable = ['consulta', 'encontrados', 'hecha_at'];

    protected $casts = ['hecha_at' => 'datetime'];
}
