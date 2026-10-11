<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Devolución o cambio de una venta del POS
class Devolucion extends Model
{
    use PerteneceATienda;

    protected $table = 'devoluciones';

    protected $fillable = ['codigo', 'pos_venta_id', 'caja_id', 'user_id', 'tipo', 'metodo_pago_id', 'total', 'saldo_disponible', 'motivo'];

    public function items()
    {
        return $this->hasMany(DevolucionItem::class);
    }

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
