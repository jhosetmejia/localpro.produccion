<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Insumos que gasta un procedimiento (ej. Resina → 1 cápsula de resina, 1 carpule de anestesia)
class ProcedimientoInsumo extends Model
{
    use PerteneceATienda;

    protected $fillable = ['producto_id', 'producto_variante_id', 'cantidad'];

    protected $casts = ['cantidad' => 'float'];

    public function servicio()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
