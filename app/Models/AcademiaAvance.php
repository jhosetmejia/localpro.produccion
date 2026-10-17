<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Avance de una persona en un curso de "Aprende LocalPro"
class AcademiaAvance extends Model
{
    protected $table = 'academia_avance';
    protected $fillable = ['user_id', 'curso', 'lecciones', 'nota', 'aprobado_at'];
    protected $casts = ['lecciones' => 'array', 'aprobado_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
