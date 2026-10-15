<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Persona vinculada al negocio a la que se le liquida nómina
class Empleado extends Model
{
    use PerteneceATienda;

    protected $table = 'empleados';

    protected $fillable = [
        'tipo_documento', 'documento', 'nombres', 'apellidos', 'email', 'telefono', 'direccion', 'municipio_dane', 'cargo',
        'tipo', 'tipo_contrato', 'salario', 'salario_integral', 'aux_transporte', 'riesgo_arl', 'personalizado', 'seguridad_social',
        'prestaciones', 'enviar_dian', 'fecha_ingreso', 'fecha_retiro', 'eps', 'afp', 'caja', 'forma_pago', 'banco', 'tipo_cuenta',
        'numero_cuenta', 'activo',
    ];

    protected $casts = [
        'salario' => 'float', 'salario_integral' => 'boolean', 'aux_transporte' => 'boolean', 'personalizado' => 'boolean',
        'seguridad_social' => 'boolean', 'prestaciones' => 'boolean', 'enviar_dian' => 'boolean', 'activo' => 'boolean',
        'fecha_ingreso' => 'date', 'fecha_retiro' => 'date',
    ];

    public const DOCUMENTOS = ['13' => 'Cédula de ciudadanía', '22' => 'Cédula de extranjería', '41' => 'Pasaporte', '47' => 'PEP', '48' => 'PPT', '12' => 'Tarjeta de identidad'];
    public const CONTRATOS = ['indefinido' => 'Término indefinido', 'fijo' => 'Término fijo', 'obra' => 'Obra o labor', 'aprendizaje' => 'Aprendizaje', 'practicas' => 'Prácticas'];
    // Códigos DIAN de tipo de contrato
    public const CONTRATOS_DIAN = ['fijo' => '1', 'indefinido' => '2', 'obra' => '3', 'aprendizaje' => '4', 'practicas' => '5'];

    public function liquidaciones()
    {
        return $this->hasMany(NominaLiquidacion::class);
    }

    public function getNombreAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    // Datos que necesita el liquidador
    public function paraLiquidar(): array
    {
        return [
            'salario' => $this->salario, 'integral' => $this->salario_integral, 'tipo' => $this->tipo, 'riesgo_arl' => $this->riesgo_arl,
            'fecha_ingreso' => $this->fecha_ingreso?->format('Y-m-d'), 'fecha_retiro' => $this->fecha_retiro?->format('Y-m-d'),
            'aux_transporte' => $this->aux_transporte, 'personalizado' => $this->personalizado,
            'seguridad_social' => $this->seguridad_social, 'prestaciones' => $this->prestaciones,
        ];
    }
}
