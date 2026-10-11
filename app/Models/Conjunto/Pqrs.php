<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Pqrs extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_pqrs';
    protected $fillable = ['unidad_id', 'tipo', 'asunto', 'detalle', 'estado', 'respuesta'];

    public const TIPOS = ['peticion' => 'Petición', 'queja' => 'Queja', 'reclamo' => 'Reclamo', 'sugerencia' => 'Sugerencia', 'dano' => 'Daño / reparación'];
    public const ESTADOS = ['abierta' => 'Abierta', 'en_proceso' => 'En proceso', 'cerrada' => 'Cerrada'];

    public function unidad() { return $this->belongsTo(Unidad::class); }
}
