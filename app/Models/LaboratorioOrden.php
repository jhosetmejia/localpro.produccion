<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Trabajo enviado al laboratorio dental (coronas, prótesis, placas…)
class LaboratorioOrden extends Model
{
    use PerteneceATienda;

    protected $table = 'laboratorio_ordenes';

    public const ESTADOS = [
        'enviado' => ['En el laboratorio', '#f59e0b'],
        'recibido' => ['Recibido', '#0891b2'],
        'instalado' => ['Instalado', '#16a34a'],
        'devuelto' => ['Devuelto para corregir', '#e11d48'],
    ];

    protected $fillable = ['paciente_id', 'plan_id', 'numero', 'laboratorio', 'trabajo', 'pieza', 'color', 'material', 'enviado', 'entrega', 'recibido', 'costo', 'estado', 'notas', 'user_id'];

    protected $casts = ['enviado' => 'date', 'entrega' => 'date', 'recibido' => 'date', 'costo' => 'float'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function getAtrasadoAttribute(): bool
    {
        return in_array($this->estado, ['enviado', 'devuelto'], true) && $this->entrega && $this->entrega->lt(today());
    }

    public static function siguienteNumero(): string
    {
        $n = (int) static::query()->selectRaw("MAX(CAST(SUBSTRING(numero, 5) AS UNSIGNED)) m")->value('m');
        return 'LAB-' . str_pad((string) ($n + 1), 5, '0', STR_PAD_LEFT);
    }
}
