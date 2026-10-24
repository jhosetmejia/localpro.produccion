<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Foto de un color del producto (ej. la camiseta en negro). Se guarda en storage/productos como la foto principal.
class ProductoFoto extends Model
{
    use PerteneceATienda;

    protected $table = 'producto_fotos';
    protected $fillable = ['producto_id', 'color', 'path', 'orden'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/productos/' . rawurlencode($this->path));
    }

    public function getExisteAttribute(): bool
    {
        return Producto::fotoExiste($this->path);
    }
}
