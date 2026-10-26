<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    protected $table = 'cotizacion_items';

    public $timestamps = false;

    protected $fillable = ['cotizacion_id', 'producto_id', 'descripcion', 'cantidad', 'precio', 'precio_lista', 'subtotal', 'orden'];

    protected $casts = ['cantidad' => 'float', 'precio' => 'float', 'precio_lista' => 'float', 'subtotal' => 'float'];

    public function producto() { return $this->belongsTo(Producto::class); }

    // Cantidad sin ceros de más: 2 en vez de 2,000
    public function getCantidadTextoAttribute(): string
    {
        return rtrim(rtrim(number_format($this->cantidad, 3, ',', '.'), '0'), ',');
    }
}
