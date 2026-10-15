<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Liquidación de un empleado en un periodo: novedades que se registraron y el resultado del cálculo
class NominaLiquidacion extends Model
{
    protected $table = 'nomina_liquidaciones';

    protected $fillable = ['nomina_periodo_id', 'empleado_id', 'salario', 'novedades', 'resultado', 'dias', 'ibc', 'total_devengado', 'total_deducido', 'neto'];

    protected $casts = ['novedades' => 'array', 'resultado' => 'array', 'salario' => 'float', 'ibc' => 'float', 'total_devengado' => 'float', 'total_deducido' => 'float', 'neto' => 'float'];

    public function periodo()
    {
        return $this->belongsTo(NominaPeriodo::class, 'nomina_periodo_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
