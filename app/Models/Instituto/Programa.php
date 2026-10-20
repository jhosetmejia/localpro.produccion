<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_programas';
    protected $fillable = ['nombre', 'tipo', 'semestres', 'valor_semestre', 'valor_inscripcion', 'modulos', 'activo', 'precio_contado', 'precio_financiado', 'fin_cuota_inicial', 'fin_num_cuotas'];
    protected $casts = ['activo' => 'boolean', 'valor_semestre' => 'float', 'valor_inscripcion' => 'float', 'semestres' => 'integer',
        'precio_contado' => 'float', 'precio_financiado' => 'float', 'fin_cuota_inicial' => 'float', 'fin_num_cuotas' => 'integer'];

    // Programas técnicos laborales de ejemplo (se pueden cargar con un botón y luego editar)
    public const EJEMPLOS = [
        'Auxiliar en Diseño Gráfico', 'Asistente en Gestión del Talento Humano', 'Deportes, Actividad Física y Recreación',
        'Asistente en Preparación Física y Entrenamiento Deportivo', 'Soporte, Sistemas e Informática', 'Asistente en Contabilidad y Finanzas',
        'Asistente en Fisioterapia', 'Recreación y Fitness', 'Auxiliar Administrativo en Salud', 'Auxiliar en Enfermería',
        'Auxiliar en Servicios Farmacéuticos', 'Auxiliar en Salud Oral',
    ];

    // Precios de ejemplo (lista 2026 del Instituto Técnico Surcolombiano): [pleno, contado, financiado, cuota inicial, No. cuotas]
    public const PRECIOS_EJEMPLO = [
        'Asistente en Gestión del Talento Humano' => [1240000, 1050000, 1140000, 260000, 4],
        'Asistente en Contabilidad y Finanzas' => [1240000, 1050000, 1140000, 260000, 4],
        'Soporte, Sistemas e Informática' => [1240000, 1050000, 1140000, 260000, 4],
        'Auxiliar en Diseño Gráfico' => [1240000, 1050000, 1140000, 260000, 4],
        'Deportes, Actividad Física y Recreación' => [1450000, 1300000, 1350000, 390000, 4],
        'Asistente en Fisioterapia' => [1550000, 1350000, 1450000, 450000, 4],
        'Auxiliar Administrativo en Salud' => [1700000, 1450000, 1490000, 390000, 4],
        'Auxiliar en Servicios Farmacéuticos' => [1700000, 1450000, 1490000, 390000, 4],
        'Auxiliar en Salud Oral' => [1700000, 1450000, 1490000, 390000, 4],
        'Auxiliar en Enfermería' => [2200000, 1850000, 1890000, 690000, 3],
    ];

    public function estudiantes() { return $this->hasMany(Estudiante::class); }

    // ¿Tiene plan de financiación? (precio financiado con cuota inicial y cuotas)
    public function getTieneFinanciacionAttribute(): bool
    {
        return (float) $this->precio_financiado > 0 && (int) $this->fin_num_cuotas > 0;
    }

    // Valor de cada cuota del plan de financiación
    public function getValorCuotaFinAttribute(): float
    {
        if (!$this->tiene_financiacion) return 0;
        return floor(max(0, (float) $this->precio_financiado - (float) $this->fin_cuota_inicial) / (int) $this->fin_num_cuotas);
    }

    // Lo que se ahorra (valor y %) frente al precio pleno
    public function ahorro(?float $precio): ?array
    {
        $pleno = (float) $this->valor_semestre;
        if (!$precio || $pleno <= 0 || $precio >= $pleno) return null;
        return [$pleno - $precio, round(($pleno - $precio) / $pleno * 100, 2)];
    }

    public function getListaModulosAttribute(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->modulos))));
    }
}
