<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeparadoItem extends Model
{
    protected $table = 'separado_items';

    protected $fillable = ['separado_id', 'producto_variante_id', 'descripcion', 'talla', 'color', 'cantidad', 'precio', 'subtotal'];

    protected $casts = ['cantidad' => 'float', 'precio' => 'float', 'subtotal' => 'float'];

    public function variante() { return $this->belongsTo(ProductoVariante::class, 'producto_variante_id'); }

    public function getProductoAttribute(): ?Producto
    {
        return $this->variante?->producto;
    }

    /** Foto de lo que se separó: la del color elegido si existe, si no la foto principal del producto */
    public function getFotoUrlAttribute(): ?string
    {
        $p = $this->producto;
        if (!$p) {
            return null;
        }
        if ($this->color) {
            $f = $p->fotos->first(fn ($x) => mb_strtolower(trim((string) $x->color)) === mb_strtolower(trim($this->color)));
            if ($f) {
                return $f->url;
            }
        }
        if ($p->img_path) {
            return asset('storage/productos/' . rawurlencode($p->img_path));
        }
        return $p->fotos->first()?->url;
    }

    public function getEtiquetaAttribute(): string
    {
        return trim(implode(' · ', array_filter([$this->talla, $this->color])));
    }
}
