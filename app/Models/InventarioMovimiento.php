<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected $fillable = ['producto_variante_id', 'tipo', 'cantidad', 'stock_resultante', 'user_id', 'referencia', 'nota'];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
