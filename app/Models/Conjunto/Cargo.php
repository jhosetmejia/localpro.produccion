<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_cargos';
    protected $fillable = ['unidad_id', 'periodo', 'tipo', 'concepto', 'valor', 'pagado', 'vence', 'estado'];
    protected $casts = ['vence' => 'date', 'valor' => 'float', 'pagado' => 'float'];

    public const TIPOS = ['administracion' => 'Administración', 'extraordinaria' => 'Cuota extraordinaria', 'multa' => 'Multa / sanción', 'interes' => 'Intereses de mora', 'otro' => 'Otro'];

    public function unidad() { return $this->belongsTo(Unidad::class); }

    public function getSaldoAttribute(): float { return max(0, round($this->valor - $this->pagado, 2)); }

    public function getVencidoAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'parcial'], true) && $this->vence && $this->vence->lt(today());
    }
}
