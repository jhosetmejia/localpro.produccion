<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Planes de suscripción de LocalPro (precio en pesos colombianos; precio_usd para los países que pagan en dólares, ej. Venezuela)
class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = ['nombre', 'precio', 'precio_usd', 'dias', 'max_usuarios', 'max_productos', 'descripcion', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean', 'precio' => 'float', 'precio_usd' => 'float'];

    // Precio del plan en la moneda indicada (COP o USD)
    public function precioEn(?string $moneda): float
    {
        if (\App\Support\Moneda::esUsd($moneda)) {
            return $this->precio_usd !== null ? (float) $this->precio_usd : round((float) $this->precio / \App\Support\Moneda::tasa(), 2);
        }
        return (float) $this->precio;
    }

    // Precio formateado en la moneda de cobro de esa tienda (US$55 en Venezuela, $220.000 en Colombia)
    public function precioTexto(?Tienda $tienda = null): string
    {
        $m = $tienda?->moneda_cobro ?? \App\Support\Moneda::COP;
        return \App\Support\Moneda::formato($this->precioEn($m), $m);
    }

    public function tiendas()
    {
        return $this->hasMany(Tienda::class);
    }

    public static function gratis(): ?self
    {
        return static::where('precio', 0)->whereNull('dias')->orderBy('orden')->first();
    }

    public function getEsGratisAttribute(): bool
    {
        return (float) $this->precio <= 0;
    }
}
