<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tienda extends Model
{
    protected $table = 'tiendas';

    protected $fillable = [
        'nombre', 'pais', 'tipo_negocio', 'descripcion', 'usa_tallas', 'nit', 'direccion', 'ciudad', 'telefono',
        'email', 'logo_path', 'mensaje_tiquete', 'estado', 'onboarding_paso', 'onboarding_completo',
    ];

    protected $casts = ['usa_tallas' => 'boolean', 'onboarding_completo' => 'boolean'];

    // Categorías de negocio que se pueden elegir al registrarse
    public const TIPOS = [
        'moda' => ['Moda y ropa', 'fa-shirt', true, 'Ropa para dama, caballero, niños, urbana, deportiva…'],
        'calzado' => ['Calzado', 'fa-shoe-prints', true, 'Zapatos, tenis, sandalias, botas'],
        'accesorios' => ['Accesorios y joyería', 'fa-gem', false, 'Bolsos, relojes, bisutería, gafas'],
        'restaurante' => ['Restaurante y comidas', 'fa-burger', false, 'Comidas rápidas, almuerzos, asaderos'],
        'cafeteria' => ['Cafetería y panadería', 'fa-mug-hot', false, 'Café, panadería, repostería, heladería'],
        'minimercado' => ['Tienda y minimercado', 'fa-basket-shopping', false, 'Víveres, abarrotes, licores'],
        'belleza' => ['Belleza y cuidado personal', 'fa-spa', false, 'Cosméticos, peluquería, barbería'],
        'tecnologia' => ['Tecnología y celulares', 'fa-mobile-screen', false, 'Celulares, accesorios, reparación'],
        'papeleria' => ['Papelería y regalos', 'fa-pen-ruler', false, 'Útiles, piñatería, detalles'],
        'ferreteria' => ['Ferretería', 'fa-screwdriver-wrench', false, 'Herramientas, materiales, eléctricos'],
        'otro' => ['Otro negocio', 'fa-store', false, 'Cualquier otro comercio'],
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo_negocio][0] ?? 'Otro negocio';
    }

    public function getTipoIconoAttribute(): string
    {
        return self::TIPOS[$this->tipo_negocio][1] ?? 'fa-store';
    }

    public function getMonedaAttribute(): array
    {
        return \App\Support\Paises::datos($this->pais);
    }

    public function getActivaAttribute(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Siguiente número de venta POS de esta tienda (POS-000001, POS-000002…). Llamar dentro de una transacción.
     */
    public function siguienteNumeroPos(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = $fila->consecutivo_pos + 1;
        $fila->update(['consecutivo_pos' => $n]);
        return 'POS-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
