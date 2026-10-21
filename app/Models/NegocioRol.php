<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Rol propio de un negocio (Caja, Mesero, Cocina, Entradas…): el dueño elige qué puede hacer cada uno
class NegocioRol extends Model
{
    use PerteneceATienda;

    protected $table = 'negocio_roles';

    protected $fillable = ['tienda_id', 'nombre', 'icono', 'permisos', 'estaciones', 'inicio', 'orden'];

    protected $casts = ['permisos' => 'array', 'estaciones' => 'array'];

    // Pantalla con la que arranca la persona al entrar
    public const INICIOS = [
        'panel' => 'Panel (inicio normal)',
        'mesero' => 'Mis pedidos (mesero)',
        'caja' => 'Pedidos por cobrar (caja)',
        'estacion' => 'Pantalla de su estación (cocina, cafetería, entradas…)',
        'pos' => 'Vender (POS)',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'negocio_rol_id');
    }

    public function tiene(string $permiso): bool
    {
        return in_array($permiso, (array) $this->permisos, true);
    }

    // Estaciones que ve en su pantalla (vacío = todas)
    public function estacionIds(): array
    {
        return array_values(array_map('intval', (array) $this->estaciones));
    }
}
