<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionItem extends Model
{
    protected $table = 'devolucion_items';

    protected $fillable = ['devolucion_id', 'pos_venta_item_id', 'producto_variante_id', 'cantidad', 'precio', 'subtotal'];

    protected $casts = ['cantidad' => 'float'];

    public function ventaItem()
    {
        return $this->belongsTo(PosVentaItem::class, 'pos_venta_item_id');
    }
}
