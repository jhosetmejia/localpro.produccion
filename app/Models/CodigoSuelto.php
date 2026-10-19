<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Etiqueta de código de barras impresa sin producto (mercancía nueva) o etiqueta extra de una talla/color
class CodigoSuelto extends Model
{
    use PerteneceATienda;

    protected $table = 'codigos_sueltos';

    protected $fillable = ['codigo', 'lote', 'producto_variante_id', 'usado_at', 'user_id'];

    protected $casts = ['usado_at' => 'datetime'];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
