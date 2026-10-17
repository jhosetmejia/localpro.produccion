<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

// Paciente del consultorio. Alergias, antecedentes y notas van cifrados (datos sensibles, Ley 1581 de 2012).
class Paciente extends Model
{
    use PerteneceATienda;

    public const DOCUMENTOS = ['CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad', 'RC' => 'Registro civil', 'CE' => 'Cédula de extranjería', 'PA' => 'Pasaporte', 'PPT' => 'Permiso por protección temporal'];

    public const ANTECEDENTES = [
        'personales' => 'Personales (enfermedades)',
        'quirurgicos' => 'Quirúrgicos',
        'medicamentos' => 'Medicamentos que toma',
        'familiares' => 'Familiares',
        'habitos' => 'Hábitos (cigarrillo, alcohol, ejercicio)',
        'gineco' => 'Gineco-obstétricos',
    ];

    protected $fillable = ['tipo_documento', 'documento', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'correo', 'direccion',
        'ciudad', 'aseguradora', 'ocupacion', 'acompanante', 'alergias', 'antecedentes', 'notas', 'autoriza_datos_at'];

    protected $casts = [
        'fecha_nacimiento' => 'date', 'autoriza_datos_at' => 'datetime',
        'alergias' => 'encrypted', 'antecedentes' => 'encrypted:array', 'notas' => 'encrypted',
    ];

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    public function atenciones()
    {
        return $this->hasMany(Atencion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function getEdadAttribute(): ?string
    {
        if (!$this->fecha_nacimiento) return null;
        $d = $this->fecha_nacimiento->diff(Carbon::today());
        if ($d->y >= 2) return $d->y . ' años';
        return ($d->y * 12 + $d->m) . ' meses';
    }

    public function getWhatsappAttribute(): ?string
    {
        $n = preg_replace('/\D/', '', (string) $this->telefono);
        if ($n === '') return null;
        return strlen($n) === 10 ? '57' . $n : $n;
    }
}
