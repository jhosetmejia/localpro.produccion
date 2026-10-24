<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tienda extends Model
{
    protected $table = 'tiendas';

    protected $fillable = [
        'nombre', 'departamento', 'slug', 'catalogo_activo', 'catalogo_agotados', 'mod_medida', 'mod_vencimiento', 'mod_recetas', 'mod_alquiler', 'mod_obituarios', 'mod_eventos', 'mod_contable', 'mod_consultorio', 'mod_pedidos', 'mod_servicios', 'pedidos_cobro', 'cliente_obligatorio', 'pais', 'tipo_negocio', 'descripcion', 'usa_tallas', 'nit', 'regimen',
        'resolucion_numero', 'resolucion_fecha', 'resolucion_vigencia', 'resolucion_prefijo', 'resolucion_desde', 'resolucion_hasta', 'direccion', 'ciudad', 'telefono',
        'email', 'logo_path', 'mensaje_tiquete', 'menu_oculto',
        // Facturación DIAN
        'razon_social', 'dv', 'tipo_persona', 'responsabilidades', 'ciiu', 'municipio_dane', 'email_facturacion', 'pos_electronico',
        'fe_activa', 'fe_ambiente', 'fe_resolucion_numero', 'fe_resolucion_fecha', 'fe_resolucion_vigencia', 'fe_prefijo', 'fe_desde', 'fe_hasta', 'fe_clave_tecnica', 'fe_credenciales', 'estado', 'onboarding_paso', 'onboarding_completo', 'conj_dia_vence', 'conj_interes_mora', 'conj_zonas',
    ];

    protected $casts = ['mod_medida' => 'boolean', 'mod_vencimiento' => 'boolean', 'mod_recetas' => 'boolean', 'mod_alquiler' => 'boolean', 'mod_obituarios' => 'boolean', 'mod_eventos' => 'boolean', 'mod_contable' => 'boolean', 'mod_consultorio' => 'boolean', 'mod_pedidos' => 'boolean', 'mod_servicios' => 'boolean', 'usa_tallas' => 'boolean', 'onboarding_completo' => 'boolean', 'catalogo_activo' => 'boolean', 'compartir_asesores' => 'boolean', 'catalogo_agotados' => 'boolean', 'cliente_obligatorio' => 'boolean', 'plan_vence' => 'date', 'datos_depurados_at' => 'datetime', 'resolucion_fecha' => 'date', 'resolucion_vigencia' => 'date', 'pos_electronico' => 'boolean', 'fe_activa' => 'boolean', 'fe_resolucion_fecha' => 'date', 'fe_resolucion_vigencia' => 'date', 'fe_clave_tecnica' => 'encrypted', 'fe_credenciales' => 'encrypted:array', 'enlaces_activo' => 'boolean', 'enlaces_catalogo' => 'boolean', 'enlaces_redes' => 'array', 'menu_oculto' => 'array'];

    // Las llaves de facturación nunca salen en JSON ni en registros
    protected $hidden = ['fe_clave_tecnica', 'fe_credenciales'];

    // Categorías de negocio que se pueden elegir al registrarse
    // Módulos que se encienden solos según el tipo de negocio (se pueden cambiar en Datos del negocio)
    public const MODULOS_POR_TIPO = [
        'minimercado' => ['mod_vencimiento'],
        'belleza' => ['mod_vencimiento', 'mod_servicios'],
        'ferreteria' => ['mod_medida'],
        'restaurante' => ['mod_recetas', 'mod_pedidos'],
        'cafeteria' => ['mod_recetas', 'mod_vencimiento', 'mod_pedidos'],
        'funeraria' => ['mod_obituarios', 'mod_servicios'],
        'eventos' => ['mod_eventos'],
        'contable' => ['mod_contable'],
        'consultorio' => ['mod_consultorio', 'mod_servicios'],
    ];

    // Secciones del menú que el negocio puede esconder si no las usa: [nombre, ícono, grupo, para qué sirve]
    // (Panel, Datos del negocio, Mi plan, Usuarios y Personalizar menú siempre se ven)
    public const MENU_OPCIONAL = [
        'consultorio' => ['Consultorio', 'fa-user-doctor', 'Inicio', 'Pacientes, agenda e historia clínica'],
        'obituarios' => ['Obituarios', 'fa-dove', 'Inicio', 'Salas de velación y pantallas (funerarias)'],
        'eventos' => ['Eventos y entradas', 'fa-ticket', 'Inicio', 'Página del evento, registro, aprobar pagos y QR en la puerta'],
        'contabilidad' => ['Contabilidad (retenciones e IVA)', 'fa-calculator', 'Inicio', 'Cruce DIAN vs. contabilidad y borrador del 350'],
        'pos' => ['Vender (POS)', 'fa-cash-register', 'Inicio', 'La caja para cobrar'],
        'pedidos' => ['Pedidos (mesero, caja y cocina)', 'fa-bell-concierge', 'Inicio', 'Mesero toma el pedido, caja cobra y cocina lo prepara'],
        'separados' => ['Separados y abonos', 'fa-box-archive', 'Inicio', 'Apartar productos con abonos'],
        'tiquetera' => ['Tiquetera', 'fa-ticket', 'Inicio', 'Almuerzos o servicios prepagados (restaurantes y cafeterías)'],
        'egresos' => ['Egresos', 'fa-arrow-trend-down', 'Inicio', 'Gastos y pagos del negocio'],
        'alquiler' => ['Alquiler', 'fa-calendar-days', 'Inicio', 'Alquiler de equipos, trajes, salones…'],
        'caja' => ['Mi caja', 'fa-vault', 'Inicio', 'Abrir y cerrar la caja del turno'],
        'ventas' => ['Ventas', 'fa-receipt', 'Inicio', 'Lista de ventas hechas'],
        'reportes' => ['Reportes', 'fa-chart-pie', 'Inicio', 'Gráficas y exportar a Excel'],
        'nomina' => ['Nómina', 'fa-users-gear', 'Inicio', 'Empleados y pago de nómina'],
        'cierres' => ['Cierres de caja', 'fa-scale-balanced', 'Inicio', 'Cuadres de todas las cajas'],
        'productos' => ['Productos', 'fa-box', 'Inventario', 'Productos y servicios que vende'],
        'existencias' => ['Existencias', 'fa-warehouse', 'Inventario', 'Cuánto hay de cada producto'],
        'entradas' => ['Entradas de mercancía', 'fa-truck-ramp-box', 'Inventario', 'Registrar compras a proveedores'],
        'categorias' => ['Categorías', 'fa-tags', 'Inventario', 'Grupos de productos'],
        'redes' => ['Mis redes', 'fa-hashtag', 'Inventario', 'Mensajes de Facebook e Instagram'],
        'clientes' => ['Clientes', 'fa-people-group', 'Contactos', 'Datos de sus clientes'],
        'proveedores' => ['Proveedores', 'fa-handshake', 'Contactos', 'A quién le compra'],
        'facturacion' => ['Facturación DIAN', 'fa-file-invoice-dollar', 'Mi negocio', 'Factura electrónica'],
        'enlaces' => ['Mis enlaces', 'fa-link', 'Mi negocio', 'Página tipo Linktree'],
        'catalogo' => ['Mi catálogo en línea', 'fa-globe', 'Mi negocio', 'Enlace a su tienda en línea'],
        'metodos' => ['Métodos de pago', 'fa-credit-card', 'Mi negocio', 'Efectivo, Nequi, tarjeta…'],
    ];

    // Lo que se esconde de entrada según el tipo de negocio (el dueño lo puede volver a mostrar)
    public const MENU_OCULTO_POR_TIPO = [
        'restaurante' => ['separados'],
        'cafeteria' => ['separados'],
        'funeraria' => ['separados', 'existencias', 'entradas', 'categorias'],
        'eventos' => ['separados', 'existencias', 'entradas', 'categorias'],
        'contable' => ['separados', 'existencias', 'entradas', 'categorias', 'catalogo', 'redes'],
        'consultorio' => ['separados', 'existencias', 'entradas', 'categorias', 'catalogo'],
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
        'eventos' => ['Eventos, fiestas y conciertos', 'fa-ticket', false, 'Venta de entradas con registro, pago por Nequi y QR en la puerta'],
        'contable' => ['Contador / analista contable', 'fa-calculator', false, 'Retenciones, IVA y cruce de facturas con la DIAN para sus clientes'],
        'conjunto' => ['Conjunto residencial / edificio', 'fa-building', false, 'Administración de conjuntos, torres y edificios'],
        'consultorio' => ['Consultorio / salud', 'fa-user-doctor', false, 'Psicología, odontología, estética, fisioterapia, nutrición, medicina'],
        'instituto' => ['Instituto / academia', 'fa-graduation-cap', false, 'Institutos técnicos, academias y cursos: inscripciones, matrículas y cuotas'],
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
        // Al encender Pedidos (restaurante, cafetería…) quedan creados los roles Caja, Mesero, Cocina… con sus permisos
        static::saved(function (Tienda $t) {
            if ($t->mod_pedidos && ($t->wasRecentlyCreated || $t->wasChanged('mod_pedidos'))
                && \Illuminate\Support\Facades\Schema::hasTable('negocio_roles')) {
                try {
                    \App\Support\RolesNegocio::crearPorDefecto($t);
                } catch (\Throwable $e) {
                    report($e);
                }
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

    // Para los botones "Ver mi tienda en línea" del panel: con www para que el navegador lo abra en
    // una pestaña normal y no dentro de la app instalada de LocalPro (la app solo abarca localproo.com).
    // Lo que se comparte sigue saliendo sin www (url_catalogo).
    public function getUrlCatalogoNavegadorAttribute(): string
    {
        $dominio = config('app.dominio');
        return app()->environment('production') && $dominio
            ? 'https://www.' . $dominio . '/t/' . $this->slug
            : $this->url_catalogo;
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

    // Ícono de los productos según el tipo de negocio (camiseta solo para ropa)
    public const ICONO_PRODUCTO = [
        'moda' => 'fa-shirt', 'calzado' => 'fa-shoe-prints', 'accesorios' => 'fa-gem', 'restaurante' => 'fa-burger',
        'cafeteria' => 'fa-mug-hot', 'minimercado' => 'fa-basket-shopping', 'belleza' => 'fa-spa', 'tecnologia' => 'fa-mobile-screen',
        'papeleria' => 'fa-pen-ruler', 'ferreteria' => 'fa-screwdriver-wrench', 'funeraria' => 'fa-dove', 'eventos' => 'fa-ticket', 'conjunto' => 'fa-building', 'contable' => 'fa-calculator', 'consultorio' => 'fa-stethoscope', 'instituto' => 'fa-graduation-cap',
    ];

    public function getIconoProductoAttribute(): string
    {
        return self::ICONO_PRODUCTO[$this->tipo_negocio] ?? 'fa-box';
    }

    // Ícono de producto de la tienda con la que se trabaja
    public static function iconoProducto(): string
    {
        return \App\Support\TiendaActual::tienda()?->icono_producto ?? 'fa-box';
    }

    // "prenda" solo en tiendas de ropa; en las demás "producto"
    public static function palabraProducto(bool $plural = false): string
    {
        $ropa = (bool) \App\Support\TiendaActual::tienda()?->usa_tallas;
        return ($ropa ? 'prenda' : 'producto') . ($plural ? 's' : '');
    }

    public function getTipoIconoAttribute(): string
    {
        return self::TIPOS[$this->tipo_negocio][1] ?? 'fa-store';
    }

    public function getMonedaAttribute(): array
    {
        return \App\Support\Paises::datos($this->pais);
    }

    // Moneda en que LocalPro le cobra el plan y paga las comisiones (USD para Venezuela, COP para los demás)
    public function getMonedaCobroAttribute(): string
    {
        return \App\Support\Moneda::dePais($this->pais);
    }

    // Venezuela: cobra también en bolívares a la tasa del día (la propia de la tienda o la general del panel)
    public function getUsaBolivaresAttribute(): bool
    {
        return $this->pais === 'VE' && $this->tasa_bs_vigente > 0;
    }

    // Bolívares por US$1 que usa esta tienda (0 si no hay tasa)
    public function getTasaBsVigenteAttribute(): float
    {
        if ($this->pais !== 'VE') return 0.0;
        $propia = (float) ($this->attributes['tasa_bs'] ?? 0);
        return $propia > 0 ? $propia : \App\Support\Moneda::tasaVes();
    }

    // Fecha de la tasa que usa la tienda (para avisar si quedó vieja)
    public function getTasaBsFechaAttribute(): ?\Carbon\Carbon
    {
        if ((float) ($this->attributes['tasa_bs'] ?? 0) > 0) {
            return !empty($this->attributes['tasa_bs_at']) ? \Carbon\Carbon::parse($this->attributes['tasa_bs_at']) : null;
        }
        return \App\Support\Moneda::tasaVesFecha();
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

    // Instituto / academia (inscripciones, matrículas y cuotas)
    public function getEsInstitutoAttribute(): bool
    {
        return $this->tipo_negocio === 'instituto';
    }

    // Administración de conjuntos residenciales
    public function getEsConjuntoAttribute(): bool
    {
        return $this->tipo_negocio === 'conjunto';
    }

    // Número consecutivo de atención de la historia clínica (consultorios)
    public function siguienteAtencion(): int
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = (int) $fila->consecutivo_hc + 1;
        $fila->forceFill(['consecutivo_hc' => $n])->save();
        return $n;
    }

    public function siguienteRecibo(): string
    {
        $fila = static::whereKey($this->id)->lockForUpdate()->first();
        $n = (int) $fila->consecutivo_rc + 1;
        $fila->forceFill(['consecutivo_rc' => $n])->save();
        return 'RC-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    // ¿Se muestra esta sección en el menú?
    // La Tiquetera es para restaurantes y cafeterías
    public const TIPOS_TIQUETERA = ['restaurante', 'cafeteria'];

    public function getUsaTiqueteraAttribute(): bool
    {
        return in_array($this->tipo_negocio, self::TIPOS_TIQUETERA, true);
    }

    public function enMenu(string $clave): bool
    {
        return !in_array($clave, (array) ($this->menu_oculto ?? []), true);
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
