<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lo que la gente nos escribe desde el botón "Opinar" (fallas, ideas y otras cosas).
 * No usa PerteneceATienda: solo la ve el súper administrador (y cada persona las suyas en "Mis reportes").
 */
class Opinion extends Model
{
    protected $table = 'opiniones';

    protected $fillable = ['tipo', 'mensaje', 'modulo', 'pagina', 'user_id', 'tienda_id', 'contacto', 'foto', 'datos', 'estado', 'respuesta', 'respondido_at', 'ip_hash'];

    protected $casts = ['datos' => 'array', 'respondido_at' => 'datetime'];

    public const TIPOS = ['falla' => 'Algo falla', 'idea' => 'Idea / mejora', 'otro' => 'Otra cosa'];
    public const ICONOS = ['falla' => '🐞', 'idea' => '💡', 'otro' => '💬'];
    public const ESTADOS = ['nuevo' => 'Nuevo', 'revisando' => 'Revisando', 'resuelto' => 'Resuelto', 'descartado' => 'Descartado'];
    public const MODULOS = ['cut' => 'LocalProCut (editor de video)', 'panel' => 'Panel', 'publico' => 'Página pública'];

    public function user() { return $this->belongsTo(User::class); }
    public function tienda() { return $this->belongsTo(Tienda::class); }
}
