<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEspacioContable;
use Illuminate\Database\Eloquent\Model;

// Un mes de un cliente contable: sus facturas de la DIAN, su base contable y el cruce
class ContablePeriodo extends Model
{
    use PerteneceAEspacioContable;

    protected $table = 'contable_periodos';

    protected $fillable = ['cliente_id', 'anio', 'mes', 'estado', 'autorretencion_base', 'autorretencion_valor', 'archivo_dian', 'archivo_contable', 'mapeo_contable', 'notas'];

    protected $casts = ['mapeo_contable' => 'array', 'autorretencion_base' => 'float', 'autorretencion_valor' => 'float'];

    public const ESTADOS = ['abierto' => 'En revisión', 'revisado' => 'Revisado', 'presentado' => 'Presentado en la DIAN'];

    public function cliente()
    {
        return $this->belongsTo(ContableCliente::class, 'cliente_id');
    }

    public function documentos()
    {
        return $this->hasMany(ContableDocumento::class, 'periodo_id');
    }

    public function getNombreAttribute(): string
    {
        return \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::create($this->anio, $this->mes, 1)->locale('es')->translatedFormat('F Y'));
    }

    public function getDesdeAttribute(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::create($this->anio, $this->mes, 1)->startOfDay();
    }

    public function getHastaAttribute(): \Illuminate\Support\Carbon
    {
        return $this->desde->copy()->endOfMonth();
    }
}
