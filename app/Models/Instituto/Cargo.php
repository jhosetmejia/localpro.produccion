<?php

namespace App\Models\Instituto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use PerteneceATienda;

    protected $table = 'inst_cargos';
    protected $fillable = ['estudiante_id', 'matricula_id', 'tipo', 'concepto', 'valor', 'pagado', 'vence', 'estado'];
    protected $casts = ['vence' => 'date', 'valor' => 'float', 'pagado' => 'float'];

    public const TIPOS = ['inscripcion' => 'Inscripción', 'matricula' => 'Matrícula (contado)', 'cuota_inicial' => 'Cuota inicial', 'cuota' => 'Cuota mensual', 'otro' => 'Otro cobro'];

    public function estudiante() { return $this->belongsTo(Estudiante::class); }
    public function matricula() { return $this->belongsTo(Matricula::class); }

    public function getSaldoAttribute(): float { return max(0, round($this->valor - $this->pagado, 2)); }

    public function getVencidoAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'parcial'], true) && $this->vence && $this->vence->lt(today());
    }
}
