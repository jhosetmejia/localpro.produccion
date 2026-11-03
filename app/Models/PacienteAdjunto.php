<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Radiografías, fotos (antes / después) y documentos del paciente. Se guardan en el disco privado (no quedan públicos).
class PacienteAdjunto extends Model
{
    use PerteneceATienda;

    public const TIPOS = ['radiografia' => ['Radiografía', 'fa-x-ray'], 'foto' => ['Foto', 'fa-camera'], 'antes_despues' => ['Antes / después', 'fa-images'], 'documento' => ['Documento', 'fa-file-lines']];

    protected $fillable = ['paciente_id', 'tipo', 'titulo', 'path', 'mime', 'tamano', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
