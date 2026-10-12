<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    use PerteneceATienda;

    protected $table = 'inventario_movimientos';

    protected $fillable = ['producto_variante_id', 'tipo', 'cantidad', 'stock_resultante', 'user_id', 'referencia', 'nota'];

    protected $casts = ['cantidad' => 'float', 'stock_resultante' => 'float'];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
