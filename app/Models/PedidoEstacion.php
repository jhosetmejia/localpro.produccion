<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Estación que prepara o atiende lo pedido: Cocina, Cafetería, Bar, Entradas, Paseos…
class PedidoEstacion extends Model
{
    use PerteneceATienda;

    protected $table = 'pedido_estaciones';

    protected $fillable = ['tienda_id', 'nombre', 'icono', 'imprimir', 'activo', 'orden'];

    protected $casts = ['imprimir' => 'boolean', 'activo' => 'boolean'];

    // Estaciones de ejemplo: [nombre, ícono]
    public const SUGERIDAS = [
        ['Cocina', 'fa-fire-burner'], ['Cafetería', 'fa-mug-hot'], ['Bar y bebidas', 'fa-martini-glass'],
        ['Parrilla', 'fa-drumstick-bite'], ['Postres', 'fa-ice-cream'], ['Entradas', 'fa-ticket'],
        ['Paseos con animales', 'fa-horse'], ['Granja', 'fa-tractor'],
    ];

    public function items()
    {
        return $this->hasMany(PedidoItem::class, 'estacion_id');
    }

    public static function activas()
    {
        return static::where('activo', true)->orderBy('orden')->orderBy('id');
    }

    // Toda tienda con pedidos tiene al menos la Cocina
    public static function asegurar(): void
    {
        if (!static::exists()) static::create(['nombre' => 'Cocina', 'icono' => 'fa-fire-burner', 'orden' => 1]);
    }
}
