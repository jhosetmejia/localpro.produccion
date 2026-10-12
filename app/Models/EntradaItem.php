<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntradaItem extends Model
{
    protected $table = 'entrada_items';

    protected $fillable = ['entrada_id', 'producto_variante_id', 'descripcion', 'cantidad', 'costo', 'subtotal'];

    protected $casts = ['cantidad' => 'float'];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
