<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Consentimiento informado firmado en pantalla. Una vez firmado no se cambia (queda con su huella).
class Consentimiento extends Model
{
    use PerteneceATienda;

    protected $fillable = ['paciente_id', 'plantilla', 'titulo', 'procedimiento', 'texto', 'firmante_nombre', 'firmante_documento', 'firmante_relacion',
        'firma_path', 'firmado_at', 'ip', 'profesional_id', 'profesional_nombre', 'profesional_registro', 'huella'];

    protected $casts = ['texto' => 'encrypted', 'firmado_at' => 'datetime'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function calcularHuella(): string
    {
        return hash('sha256', json_encode([$this->id, $this->paciente_id, $this->titulo, $this->procedimiento, $this->texto,
            $this->firmante_nombre, $this->firmante_documento, optional($this->firmado_at)->toIso8601String()], JSON_UNESCAPED_UNICODE));
    }

    public function getIntegroAttribute(): bool
    {
        return !$this->huella || hash_equals($this->huella, $this->calcularHuella());
    }
}
