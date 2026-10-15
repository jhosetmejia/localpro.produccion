<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tienda extends Model
{
    protected $table = 'tiendas';

    protected $fillable = [
        'nombre', 'departamento', 'slug', 'catalogo_activo', 'mod_medida', 'mod_vencimiento', 'mod_recetas', 'mod_alquiler', 'mod_obituarios', 'cliente_obligatorio', 'pais', 'tipo_negocio', 'descripcion', 'usa_tallas', 'nit', 'regimen',
        'resolucion_numero', 'resolucion_fecha', 'resolucion_vigencia', 'resolucion_prefijo', 'resolucion_desde', 'resolucion_hasta', 'direccion', 'ciudad', 'telefono',
        'email', 'logo_path', 'mensaje_tiquete',
        // Facturación DIAN
        'razon_social', 'dv', 'tipo_persona', 'responsabilidades', 'ciiu', 'municipio_dane', 'email_facturacion', 'pos_electronico',
        'fe_activa', 'fe_ambiente', 'fe_resolucion_numero', 'fe_resolucion_fecha', 'fe_resolucion_vigencia', 'fe_prefijo', 'fe_desde', 'fe_hasta', 'fe_clave_tecnica', 'fe_credenciales', 'estado', 'onboarding_paso', 'onboarding_completo', 'conj_dia_vence', 'conj_interes_mora', 'conj_zonas',
    ];

    protected $casts = ['mod_medida' => 'boolean', 'mod_vencimiento' => 'boolean', 'mod_recetas' => 'boolean', 'mod_alquiler' => 'boolean', 'mod_obituarios' => 'boolean', 'usa_tallas' => 'boolean', 'onboarding_completo' => 'boolean', 'catalogo_activo' => 'boolean', 'cliente_obligatorio' => 'boolean', 'plan_vence' => 'date', 'datos_depurados_at' => 'datetime', 'resolucion_fecha' => 'date', 'resolucion_vigencia' => 'date', 'pos_electronico' => 'boolean', 'fe_activa' => 'boolean', 'fe_resolucion_fecha' => 'date', 'fe_resolucion_vigencia' => 'date', 'fe_clave_tecnica' => 'encrypted', 'fe_credenciales' => 'encrypted:array', 'enlaces_activo' => 'boolean', 'enlaces_catalogo' => 'boolean', 'enlaces_redes' => 'array'];

    // Las llaves de facturación nunca salen en JSON ni en registros
    protected $hidden = ['fe_clave_tecnica', 'fe_credenciales'];

    // Categorías de negocio que se pueden elegir al registrarse
    // Módulos que se encienden solos según el tipo de negocio (se pueden cambiar en Datos del negocio)
    public const MODULOS_POR_TIPO = [
        'minimercado' => ['mod_vencimiento'],
        'belleza' => ['mod_vencimiento'],
        'ferreteria' => ['mod_medida'],
        'restaurante' => ['mod_recetas'],
        'cafeteria' => ['mod_recetas', 'mod_vencimiento'],
        'funeraria' => ['mod_obituarios'],
    ];

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
        'funeraria' => ['Funeraria', 'fa-dove', false, 'Salas de velación, exequias, planes y obituarios'],
        'conjunto' => ['Conjunto residencial / edificio', 'fa-building', false, 'Administración de conjuntos, torres y edificios'],
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
        // Código del negocio: LCT-00001, LCT-00002…
        static::creating(function (Tienda $t) {
            if (empty($t->codigo) && \Illuminate\Support\Facades\Schema::hasColumn('tiendas', 'codigo')) {
                $max = (int) static::where('codigo', 'like', 'LCT-%')->selectRaw('MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) m')->value('m');
                do {
                    $c = 'LCT-' . str_pad((string) ++$max, 5, '0', STR_PAD_LEFT);
                } while (static::where('codigo', $c)->exists());
                $t->codigo = $c;
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
    // La tienda en línea aparece en Google (sitemap e indexación) solo con un plan pago al día
    public function getEnGoogleAttribute(): bool
    {
        return $this->plan && (float) $this->plan->precio > 0 && !$this->plan_vencido;
    }

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

    public function pagos()
    {
        return $this->hasMany(PagoSuscripcion::class);
    }

    // Se borraron sus productos y demás datos porque la prueba terminó sin pasar a un plan pago
    public function getDatosDepuradosAttribute(): bool
    {
        return $this->datos_depurados_at !== null;
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
     * Siguiente número de factura electrónica (prefijo y rango de su resolución). Llamar dentro de una transacción.
     */
    public function siguienteNumeroFe(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = max((int) $fila->consecutivo_fe + 1, (int) ($fila->fe_desde ?: 1));
        $fila->forceFill(['consecutivo_fe' => $n])->save();
        return ($fila->fe_prefijo ?: 'FE') . '-' . $n;
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

    // Asesor de la red comercial que trajo este negocio
    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }

    // Súper administrador que tomó el negocio como venta directa de LocalPro (sin asesor)
    public function ventaDirecta()
    {
        return $this->belongsTo(User::class, 'venta_directa_id');
    }

    // Enlace firmado para pagar el plan con Wompi (lo comparte el asesor o LocalPro por WhatsApp)
    public function getUrlPagoAttribute(): string
    {
        return url(\Illuminate\Support\Facades\URL::signedRoute('pagar.show', ['tienda' => $this->id], null, false));
    }

    // Sin asesor de la red y sin asignar a LocalPro
    public function scopeSinAsignar($q)
    {
        return $q->whereNull('asesor_id')->whereNull('venta_directa_id');
    }

    // Administración de conjuntos residenciales
    public function getEsConjuntoAttribute(): bool
    {
        return $this->tipo_negocio === 'conjunto';
    }

    public function siguienteRecibo(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = (int) $fila->consecutivo_rc + 1;
        $fila->forceFill(['consecutivo_rc' => $n])->save();
        return 'RC-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    // Funerarias: obituarios en las pantallas de las salas de velación
    public function getEsFunerariaAttribute(): bool
    {
        return $this->tipo_negocio === 'funeraria';
    }

    public function getZonasComunesAttribute(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->conj_zonas))));
    }
}
