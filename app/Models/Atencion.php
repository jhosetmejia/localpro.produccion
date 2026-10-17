<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Una atención (consulta) dentro de la historia clínica. Mientras está abierta solo la edita quien la creó;
// al cerrarla queda firmada (con huella SHA-256) y ya no se modifica: se corrige con notas aclaratorias.
class Atencion extends Model
{
    use PerteneceATienda;

    protected $table = 'atenciones';

    protected $fillable = ['paciente_id', 'profesional_id', 'cita_id', 'numero', 'plantilla', 'fecha', 'contenido', 'diagnosticos', 'formula',
        'incapacidad_dias', 'incapacidad_desde', 'profesional_nombre', 'profesional_registro', 'profesional_especialidad', 'cerrada_at', 'huella'];

    protected $casts = [
        'fecha' => 'datetime', 'cerrada_at' => 'datetime', 'incapacidad_desde' => 'date',
        'contenido' => 'encrypted:array', 'diagnosticos' => 'encrypted:array', 'formula' => 'encrypted',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function notas()
    {
        return $this->hasMany(AtencionNota::class)->orderBy('created_at');
    }

    public function getCerradaAttribute(): bool
    {
        return $this->cerrada_at !== null;
    }

    public function calcularHuella(): string
    {
        return hash('sha256', json_encode([
            $this->id, $this->paciente_id, $this->profesional_id, optional($this->fecha)->toIso8601String(),
            $this->plantilla, $this->contenido, $this->diagnosticos, $this->formula, $this->incapacidad_dias,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function getIntegraAttribute(): bool
    {
        return !$this->huella || hash_equals($this->huella, $this->calcularHuella());
    }
}
