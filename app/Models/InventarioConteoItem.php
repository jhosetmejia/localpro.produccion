<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioConteoItem extends Model
{
    protected $table = 'inventario_conteo_items';

    public $timestamps = false;

    protected $fillable = ['inventario_conteo_id', 'producto_variante_id', 'stock_sistema', 'contado', 'user_id', 'contado_at'];

    protected $casts = ['stock_sistema' => 'float', 'contado' => 'float', 'contado_at' => 'datetime'];

    public function conteo()
    {
        return $this->belongsTo(InventarioConteo::class, 'inventario_conteo_id');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
