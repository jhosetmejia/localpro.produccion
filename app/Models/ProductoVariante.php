<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    use PerteneceATienda;

    protected $table = 'producto_variantes';

    protected $fillable = ['producto_id', 'talla', 'color', 'codigo_barras', 'stock', 'precio', 'costo', 'estado'];

    protected $casts = ['stock' => 'integer', 'precio' => 'decimal:2', 'costo' => 'decimal:2'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos()
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    // Precio que se cobra: el de la variante o, si está vacío, el del producto
    public function getPrecioFinalAttribute(): float
    {
        return (float) ($this->precio ?? $this->producto->precio_venta ?? 0);
    }

    // Costo unitario: el de la variante o, si está vacío, el del producto
    public function getCostoFinalAttribute(): float
    {
        return (float) ($this->costo ?? $this->producto->precio_costo ?? 0);
    }

    // ¿Se descuenta y se valida el stock al vender? (comidas y servicios normalmente no)
    public function getControlaAttribute(): bool
    {
        return (bool) ($this->producto->controla_inventario ?? true);
    }

    public function getEtiquetaAttribute(): string
    {
        return trim(implode(' · ', array_filter([$this->talla, $this->color]))) ?: 'Única';
    }

    /**
     * Suma o resta stock y deja el movimiento registrado. Llamar dentro de una transacción.
     */
    public function moverStock(int $cantidad, string $tipo, ?string $referencia = null, ?string $nota = null): void
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $nuevo = $fila->stock + $cantidad;
        $fila->update(['stock' => $nuevo]);
        $this->stock = $nuevo;

        InventarioMovimiento::create([
            'producto_variante_id' => $this->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_resultante' => $nuevo,
            'user_id' => auth()->id(),
            'referencia' => $referencia,
            'nota' => $nota,
        ]);
    }
}
