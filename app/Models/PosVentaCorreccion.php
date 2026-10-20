<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Corrección de una venta por error al digitar (la hace el administrador)
class PosVentaCorreccion extends Model
{
    use PerteneceATienda;

    protected $table = 'pos_venta_correcciones';
    protected $fillable = ['tienda_id', 'pos_venta_id', 'caja_id', 'user_id', 'total_antes', 'total_despues', 'esperado_antes', 'esperado_despues', 'detalle', 'motivo'];
    protected $casts = ['detalle' => 'array'];

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
