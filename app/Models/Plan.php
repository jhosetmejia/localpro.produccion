<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Planes de suscripción de LocalPro (precio en pesos colombianos; precio_usd para los países que pagan en dólares, ej. Venezuela)
class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = ['nombre', 'precio', 'precio_antes', 'precio_usd', 'dias', 'max_usuarios', 'max_productos', 'descripcion', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean', 'precio' => 'float', 'precio_antes' => 'float', 'precio_usd' => 'float'];

    // Precio del plan en la moneda indicada: COP, USD (precio_usd) o VES (el de dólares a la tasa Bs/US$)
    public function precioEn(?string $moneda): float
    {
        $m = strtoupper((string) ($moneda ?: 'COP'));
        if ($m === 'COP') {
            return (float) $this->precio;
        }
        $usd = $this->precio_usd !== null ? (float) $this->precio_usd : \App\Support\Moneda::convertir($this->precio, 'COP', 'USD');
        return $m === 'USD' ? $usd : \App\Support\Moneda::convertir($usd, 'USD', $m);
    }

    // Precio formateado en la moneda de cobro de esa tienda (US$55 o Bs en Venezuela, $220.000 en Colombia)
    public function precioTexto(?Tienda $tienda = null): string
    {
        $m = $tienda?->moneda_cobro ?? \App\Support\Moneda::COP;
        return \App\Support\Moneda::formato($this->precioEn($m), $m);
    }

    // "Antes $350.000": precio anterior tachado en la moneda de la tienda (en dólares se calcula en la misma proporción)
    public function precioAntesTexto(?Tienda $tienda = null): ?string
    {
        if (!$this->precio_antes || $this->precio_antes <= $this->precio || $this->precio <= 0) return null;
        $m = $tienda?->moneda_cobro ?? \App\Support\Moneda::COP;
        $v = strtoupper($m) === 'COP' ? (float) $this->precio_antes : round($this->precioEn($m) * $this->precio_antes / $this->precio, 2);
        return \App\Support\Moneda::formato($v, $m);
    }

    // Plan de prueba para negocios que llegan por un asesor o por un código (2 meses; antes 3)
    public static function prueba(): ?self
    {
        return static::whereIn('nombre', ['Prueba 2 meses', 'Prueba 3 meses'])->orderByRaw("nombre = 'Prueba 2 meses' desc")->first();
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
