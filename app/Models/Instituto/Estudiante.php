<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_estudiantes';
    protected $guarded = ['id', 'tienda_id'];
    protected $casts = ['fecha_inscripcion' => 'date', 'fecha_nacimiento' => 'date', 'autoriza_imagen' => 'boolean', 'autoriza_datos' => 'boolean'];

    public const ESTADOS = ['aspirante' => 'Aspirante (inscrito)', 'matriculado' => 'Matriculado', 'retirado' => 'Retirado', 'graduado' => 'Graduado'];
    public const TIPOS_DOC = ['CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad', 'CE' => 'Cédula de extranjería', 'PPT' => 'Permiso de protección temporal', 'PA' => 'Pasaporte'];
    public const JORNADAS = ['manana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche', 'fin_semana' => 'Fin de semana'];

    public function programa() { return $this->belongsTo(Programa::class); }
    public function matriculas() { return $this->hasMany(Matricula::class)->latest('fecha')->latest('id'); }
    public function cargos() { return $this->hasMany(Cargo::class); }
    public function pagos() { return $this->hasMany(Pago::class); }

    public function getNombreAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function getContactoAttribute(): ?string
    {
        return $this->celular ?: ($this->telefono ?: $this->acu_telefono);
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento ? $this->fecha_nacimiento->age : null;
    }

    public function saldo(): float
    {
        return (float) $this->cargos()->whereIn('estado', ['pendiente', 'parcial'])->selectRaw('COALESCE(SUM(valor - pagado),0) s')->value('s');
    }
}
