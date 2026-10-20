<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_programas';
    protected $fillable = ['nombre', 'tipo', 'semestres', 'valor_semestre', 'valor_inscripcion', 'modulos', 'activo'];
    protected $casts = ['activo' => 'boolean', 'valor_semestre' => 'float', 'valor_inscripcion' => 'float', 'semestres' => 'integer'];

    // Programas técnicos laborales de ejemplo (se pueden cargar con un botón y luego editar)
    public const EJEMPLOS = [
        'Auxiliar en Diseño Gráfico', 'Asistente en Gestión del Talento Humano', 'Deportes, Actividad Física y Recreación',
        'Asistente en Preparación Física y Entrenamiento Deportivo', 'Soporte, Sistemas e Informática', 'Asistente en Contabilidad y Finanzas',
        'Asistente en Fisioterapia', 'Recreación y Fitness', 'Auxiliar Administrativo en Salud', 'Auxiliar en Enfermería',
        'Auxiliar en Servicios Farmacéuticos', 'Auxiliar en Salud Oral',
    ];

    public function estudiantes() { return $this->hasMany(Estudiante::class); }

    public function getListaModulosAttribute(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->modulos))));
    }
}
