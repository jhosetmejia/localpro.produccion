<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use App\Support\Nomina\Liquidador;
use Illuminate\Database\Eloquent\Model;

// Periodo de pago (quincena o mes) con la liquidación de cada empleado
class NominaPeriodo extends Model
{
    use PerteneceATienda;

    protected $table = 'nomina_periodos';

    protected $fillable = ['nombre', 'periodicidad', 'fecha_inicio', 'fecha_fin', 'fecha_pago', 'estado', 'total_devengado', 'total_deducido', 'total_neto', 'total_aportes', 'total_provisiones', 'nota', 'user_id'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'fecha_pago' => 'date', 'total_devengado' => 'float', 'total_deducido' => 'float', 'total_neto' => 'float', 'total_aportes' => 'float', 'total_provisiones' => 'float'];

    public const ESTADOS = ['borrador' => ['Borrador', '#92600a', '#fef3c7'], 'liquidada' => ['Liquidada', '#1d4ed8', '#dbeafe'], 'pagada' => ['Pagada', '#11702f', '#e8f8ec']];

    public function liquidaciones()
    {
        return $this->hasMany(NominaLiquidacion::class)->with('empleado');
    }

    public function getEditableAttribute(): bool
    {
        return $this->estado === 'borrador';
    }

    // Vuelve a calcular a todos los empleados y los totales del periodo
    public function recalcular(): void
    {
        $exonerado = (bool) (\App\Support\TiendaActual::tienda()?->nomina_exonerada ?? true);
        $tot = ['total_devengado' => 0, 'total_deducido' => 0, 'total_neto' => 0, 'total_aportes' => 0, 'total_provisiones' => 0];
        foreach ($this->liquidaciones()->get() as $l) {
            $e = $l->empleado;
            $datos = $e->paraLiquidar();
            $datos['salario'] = $l->salario;
            $r = Liquidador::liquidar($datos, ['inicio' => $this->fecha_inicio->format('Y-m-d'), 'fin' => $this->fecha_fin->format('Y-m-d')], $l->novedades ?? [], $exonerado);
            $l->update(['resultado' => $r, 'dias' => $r['dias_periodo'], 'ibc' => $r['ibc'], 'total_devengado' => $r['total_devengado'], 'total_deducido' => $r['total_deducido'], 'neto' => $r['neto']]);
            $tot['total_devengado'] += $r['total_devengado'];
            $tot['total_deducido'] += $r['total_deducido'];
            $tot['total_neto'] += $r['neto'];
            $tot['total_aportes'] += $r['total_aportes'];
            $tot['total_provisiones'] += $r['total_provisiones'];
        }
        $this->update($tot);
    }
}
