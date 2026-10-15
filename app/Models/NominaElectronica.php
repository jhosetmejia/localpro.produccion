<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Documento soporte de pago de nómina electrónica (o su nota de ajuste) de un empleado en un mes
class NominaElectronica extends Model
{
    use PerteneceATienda;

    protected $table = 'nomina_electronicas';

    protected $fillable = ['empleado_id', 'mes', 'tipo_xml', 'tipo_nota', 'referencia_id', 'prefijo', 'consecutivo', 'numero', 'cune', 'estado', 'ambiente', 'proveedor', 'devengado', 'deducido', 'total', 'intentos', 'ultimo_error', 'payload', 'respuesta', 'enviado_en', 'validado_en'];

    protected $casts = ['payload' => 'array', 'respuesta' => 'array', 'enviado_en' => 'datetime', 'validado_en' => 'datetime', 'devengado' => 'float', 'deducido' => 'float', 'total' => 'float'];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function referencia()
    {
        return $this->belongsTo(self::class, 'referencia_id');
    }

    public function getNombreTipoAttribute(): string
    {
        if ($this->tipo_xml === '103') return $this->tipo_nota === 2 ? 'Nota de ajuste (eliminar)' : 'Nota de ajuste (reemplazar)';
        return 'Nómina individual';
    }

    public function getEstadoInfoAttribute(): array
    {
        return DocumentoElectronico::ESTADOS[$this->estado] ?? [$this->estado, '#5b665e', '#eef2ef'];
    }
}
