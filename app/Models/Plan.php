<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Planes de suscripción de LocalPro (precio en pesos colombianos)
class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = ['nombre', 'precio', 'dias', 'max_usuarios', 'max_productos', 'descripcion', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean', 'precio' => 'float'];

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
