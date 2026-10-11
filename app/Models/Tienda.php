<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tienda extends Model
{
    protected $table = 'tiendas';

    protected $fillable = [
        'nombre', 'slug', 'catalogo_activo', 'pais', 'tipo_negocio', 'descripcion', 'usa_tallas', 'nit', 'regimen',
        'resolucion_numero', 'resolucion_fecha', 'resolucion_vigencia', 'resolucion_prefijo', 'resolucion_desde', 'resolucion_hasta', 'direccion', 'ciudad', 'telefono',
        'email', 'logo_path', 'mensaje_tiquete', 'estado', 'onboarding_paso', 'onboarding_completo',
    ];

    protected $casts = ['usa_tallas' => 'boolean', 'onboarding_completo' => 'boolean', 'catalogo_activo' => 'boolean', 'plan_vence' => 'date', 'resolucion_fecha' => 'date', 'resolucion_vigencia' => 'date'];

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

    // Al crear o renombrar una tienda sin dirección de catálogo, se le asigna una única
    protected static function booted(): void
    {
        static::saving(function (Tienda $t) {
            if (empty($t->slug) || ($t->isDirty('nombre') && str_starts_with((string) $t->getOriginal('nombre'), 'Tienda de '))) {
                $base = \Illuminate\Support\Str::slug($t->nombre) ?: 'tienda';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $t->id ?? 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $t->slug = $slug;
            }
        });
    }

    public function getUrlCatalogoAttribute(): string
    {
        return url('/t/' . $this->slug);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // El plan pagado ya pasó su fecha de vencimiento
    public function getPlanVencidoAttribute(): bool
    {
        return $this->plan_vence !== null && $this->plan_vence->lt(today());
    }

    public function getDiasParaVencerAttribute(): ?int
    {
        return $this->plan_vence ? (int) today()->diffInDays($this->plan_vence, false) : null;
    }

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

    // Siguiente número de devolución / nota crédito (NC-000001…). Dentro de una transacción.
    public function siguienteNumeroNc(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = $fila->consecutivo_nc + 1;
        $fila->forceFill(['consecutivo_nc' => $n])->save();
        return 'NC-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Siguiente número de venta POS de esta tienda (POS-000001, POS-000002…). Llamar dentro de una transacción.
     */
    public function siguienteNumeroPos(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = $fila->consecutivo_pos + 1;
        // Con resolución DIAN: numeración con su prefijo, dentro del rango autorizado
        if ($fila->resolucion_prefijo) {
            $n = max($n, (int) ($fila->resolucion_desde ?: 1));
            $fila->forceFill(['consecutivo_pos' => $n])->save();
            return $fila->resolucion_prefijo . '-' . $n;
        }
        $fila->forceFill(['consecutivo_pos' => $n])->save();
        return 'POS-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
