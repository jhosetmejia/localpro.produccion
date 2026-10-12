<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeparadoItem extends Model
{
    protected $table = 'separado_items';

    protected $fillable = ['separado_id', 'producto_variante_id', 'descripcion', 'talla', 'color', 'cantidad', 'precio', 'subtotal'];

    protected $casts = ['cantidad' => 'float', 'precio' => 'float', 'subtotal' => 'float'];

    public function variante() { return $this->belongsTo(ProductoVariante::class, 'producto_variante_id'); }

    public function getEtiquetaAttribute(): string
    {
        return trim(implode(' · ', array_filter([$this->talla, $this->color])));
    }
}
