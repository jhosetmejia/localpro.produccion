<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Factura, nota o documento soporte de un periodo: viene de la DIAN o de la contabilidad
class ContableDocumento extends Model
{
    use PerteneceATienda;

    protected $table = 'contable_documentos';

    protected $fillable = ['periodo_id', 'origen', 'direccion', 'tipo_doc', 'documento', 'clave', 'cufe', 'fecha', 'nit_tercero', 'nombre_tercero',
        'base', 'iva', 'otros_impuestos', 'total', 'rete_fuente', 'rete_iva', 'rete_ica', 'cuenta', 'concepto', 'par_id'];

    protected $casts = ['fecha' => 'date', 'base' => 'float', 'iva' => 'float', 'otros_impuestos' => 'float', 'total' => 'float',
        'rete_fuente' => 'float', 'rete_iva' => 'float', 'rete_ica' => 'float'];

    public function par()
    {
        return $this->belongsTo(self::class, 'par_id');
    }
}
