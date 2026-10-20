<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_matriculas';
    protected $fillable = ['estudiante_id', 'programa_id', 'programa_nombre', 'periodo', 'semestre', 'jornada', 'modulos_pendientes', 'modalidad', 'valor_semestre', 'cuota_inicial', 'num_cuotas', 'valor_cuota', 'fecha', 'estado', 'user_id'];
    protected $casts = ['fecha' => 'date', 'valor_semestre' => 'float', 'cuota_inicial' => 'float', 'valor_cuota' => 'float'];

    public function estudiante() { return $this->belongsTo(Estudiante::class); }
    public function programa() { return $this->belongsTo(Programa::class); }
    public function cargos() { return $this->hasMany(Cargo::class); }

    // "2026-2" → "2026 - II"
    public function getPeriodoTextoAttribute(): string
    {
        [$a, $p] = array_pad(explode('-', (string) $this->periodo), 2, '1');
        return $a . ' - ' . ($p === '2' ? 'II' : 'I');
    }

    public static function periodoActual(): string
    {
        return now()->year . '-' . (now()->month <= 6 ? '1' : '2');
    }
}
