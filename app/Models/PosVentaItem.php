<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosVentaItem extends Model
{
    protected $table = 'pos_venta_items';

    protected $fillable = ['pos_venta_id', 'producto_variante_id', 'descripcion', 'talla', 'color', 'cantidad', 'precio', 'costo', 'subtotal'];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
