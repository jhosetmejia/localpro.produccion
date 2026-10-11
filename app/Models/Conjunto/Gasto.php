<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use PerteneceATienda;

    protected $table = 'conjunto_gastos';
    protected $fillable = ['fecha', 'categoria', 'concepto', 'valor', 'proveedor', 'user_id'];
    protected $casts = ['fecha' => 'date', 'valor' => 'float'];

    public const CATEGORIAS = ['Vigilancia', 'Aseo', 'Servicios públicos', 'Mantenimiento', 'Jardinería', 'Ascensores', 'Piscina', 'Nómina', 'Honorarios', 'Seguros', 'Papelería', 'Otros'];
}
