<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Cliente del analista contable (persona natural o empresa) al que se le revisan retenciones e IVA
class ContableCliente extends Model
{
    use PerteneceATienda;

    protected $table = 'contable_clientes';

    protected $fillable = ['nombre', 'nit', 'dv', 'tipo_persona', 'agente_retenedor', 'autorretenedor', 'periodicidad_iva', 'ciudad', 'notas', 'activo'];

    protected $casts = ['agente_retenedor' => 'boolean', 'autorretenedor' => 'boolean', 'activo' => 'boolean'];

    public const PERIODICIDAD = ['bimestral' => 'IVA bimestral', 'cuatrimestral' => 'IVA cuatrimestral', 'no_responsable' => 'No responsable de IVA'];

    public function periodos()
    {
        return $this->hasMany(ContablePeriodo::class, 'cliente_id');
    }

    public function terceros()
    {
        return $this->hasMany(ContableTercero::class, 'cliente_id');
    }

    public function getNitCompletoAttribute(): string
    {
        return $this->nit . ($this->dv !== null && $this->dv !== '' ? '-' . $this->dv : '');
    }
}
