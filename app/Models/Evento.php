<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Evento con su página pública (landing), precio de la etapa, datos de pago y entradas
class Evento extends Model
{
    use PerteneceATienda;

    protected $table = 'eventos';

    protected $fillable = ['nombre', 'slug', 'subtitulo', 'descripcion', 'fecha_evento', 'lugar', 'ciudad', 'mapa_url', 'etapa', 'precio', 'venta_hasta', 'cupo',
        'pago_metodo', 'pago_numero', 'pago_titular', 'whatsapp', 'mensaje_whatsapp', 'instagram', 'tiktok', 'pedir_colegio', 'edad_minima', 'condiciones', 'color', 'estado', 'tema', 'mostrar_visitas', 'campos', 'pedir_nacimiento', 'pedir_celular', 'promos', 'max_por_compra'];

    protected $casts = ['fecha_evento' => 'datetime', 'venta_hasta' => 'datetime', 'precio' => 'decimal:2', 'pedir_colegio' => 'boolean', 'cupo' => 'integer', 'edad_minima' => 'integer', 'visitas' => 'integer', 'mostrar_visitas' => 'boolean', 'campos' => 'array', 'pedir_nacimiento' => 'boolean', 'pedir_celular' => 'boolean', 'promos' => 'array', 'max_por_compra' => 'integer'];

    public const ESTADOS = ['borrador' => 'Borrador (no se ve)', 'publicado' => 'Publicado (a la venta)', 'cerrado' => 'Venta cerrada'];

    public const MENSAJE = 'Hola, ya pagué aquí está mi comprobante';

    // Estilo de la página pública
    public const TEMAS = ['normal' => 'Normal', 'terror' => 'Terror / Halloween (manos)'];

    // De "https://www.tiktok.com/@usuario?lang=es", "tiktok.com/@usuario" o "@usuario" deja solo "usuario"
    public static function usuarioRed(?string $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') return null;
        $v = preg_replace('~^(https?://)?([a-z]+\.)?(instagram|tiktok)\.com/~i', '', $v);
        $v = preg_split('~[/?#\s]~', ltrim($v, '@/'))[0] ?? '';
        $v = ltrim($v, '@');
        return $v !== '' ? mb_substr($v, 0, 80) : null;
    }

    public function getTiktokUrlAttribute(): ?string
    {
        if (!$this->tiktok) return null;
        // Enlaces cortos (vm.tiktok.com/xxxx) se dejan tal cual
        if (preg_match('~^https?://~i', $this->tiktok)) return $this->tiktok;
        return 'https://www.tiktok.com/@' . rawurlencode($this->tiktok);
    }

    public function getInstagramUrlAttribute(): ?string
    {
        return $this->instagram ? 'https://www.instagram.com/' . rawurlencode($this->instagram) . '/' : null;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getEsTerrorAttribute(): bool
    {
        return $this->tema === 'terror';
    }

    public function entradas()
    {
        // Sin el filtro de "tienda actual": la página pública la abre cualquiera (también usuarios de otro negocio)
        return $this->hasMany(EventoEntrada::class, 'evento_id')->withoutGlobalScope('tienda');
    }

    public static function slugUnico(string $nombre, ?int $ignorar = null): string
    {
        $base = Str::slug($nombre) ?: 'evento';
        $base = Str::limit($base, 70, '');
        $slug = $base;
        $i = 2;
        while (static::withoutGlobalScopes()->where('slug', $slug)->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function getUrlAttribute(): string
    {
        return url('/evento/' . $this->slug);
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_path ? Storage::disk('public')->url($this->imagen_path) : null;
    }

    public function getImagen2UrlAttribute(): ?string
    {
        return $this->imagen2_path ? Storage::disk('public')->url($this->imagen2_path) : null;
    }

    // Entradas que ocupan cupo (pendientes por aprobar y aprobadas)
    public function getOcupadasAttribute(): int
    {
        return $this->entradas()->whereIn('estado', ['pendiente', 'aprobada'])->count();
    }

    public function getQuedanAttribute(): ?int
    {
        return $this->cupo ? max(0, $this->cupo - $this->ocupadas) : null;
    }

    // ¿Se pueden registrar compradores ahora? Devuelve null si sí, o el motivo si no
    public function motivoSinVenta(): ?string
    {
        if ($this->estado === 'cerrado') return 'La venta de entradas de este evento está cerrada.';
        if ($this->estado !== 'publicado') return 'Este evento todavía no está a la venta.';
        if ($this->venta_hasta && $this->venta_hasta->isPast()) return 'La venta de esta etapa terminó.';
        if ($this->fecha_evento && $this->fecha_evento->copy()->endOfDay()->isPast()) return 'Este evento ya pasó.';
        if ($this->cupo && $this->quedan <= 0) return 'Las entradas se agotaron.';
        return null;
    }

    public function getWhatsappLinkAttribute(): ?string
    {
        $n = preg_replace('/\D/', '', (string) $this->whatsapp);
        if (!$n) return null;
        if (strlen($n) === 10) $n = '57' . $n;
        return 'https://wa.me/' . $n;
    }

    // Plata en la moneda del país del negocio (la página pública no tiene "tienda actual")
    public function plata($valor): string
    {
        $pais = $this->relationLoaded('tienda') ? $this->tienda?->pais : Tienda::whereKey($this->tienda_id)->value('pais');
        $m = \App\Support\Paises::datos($pais);
        return $m['simbolo'] . number_format((float) $valor, $m['decimales'], $m['decimal'], $m['miles']);
    }

    // Botón "Cómo llegar": el enlace de Google Maps que puso el organizador; si no lo puso pero hay lugar,
    // se busca el lugar y la ciudad en Google Maps. Si el lugar es sorpresa (vacío), no hay botón.
    public function getComoLlegarUrlAttribute(): ?string
    {
        if ($this->mapa_url) return $this->mapa_url;
        if (!$this->lugar) return null;
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($this->lugar . ($this->ciudad ? ', ' . $this->ciudad : '')));
    }

    public function getPrecioTextoAttribute(): string
    {
        return (float) $this->precio > 0 ? $this->plata($this->precio) : 'Gratis';
    }

    public function getFechaTextoAttribute(): ?string
    {
        return $this->fecha_evento?->locale('es')->isoFormat('dddd D [de] MMMM [·] h:mm a');
    }

    /**
     * Qué puede hacer la persona en Eventos (cada rol del negocio tiene su parte):
     * admin = todo (crear, borrar, precio, pago, publicar) · pagos = compradores y aprobar pagos (contabilidad)
     * info = datos del evento (logística) · diseno = afiches, logo, estilo y color · puerta = escanear el QR
     */
    public static function puede(?\App\Models\User $u = null): array
    {
        $u ??= auth()->user();
        $admin = (bool) ($u && ($u->es_superadmin || $u->can('gestionar-eventos') || $u->can('editar-negocio')));
        $c = fn (string $p) => $admin || ($u && $u->can($p));
        return [
            'admin' => $admin,
            'pagos' => $c('eventos-pagos'),
            'info' => $c('eventos-informacion'),
            'diseno' => $c('eventos-diseno'),
            'puerta' => $c('eventos-ingreso'),
        ];
    }

    public static function puedeAlgo(?\App\Models\User $u = null): bool
    {
        return in_array(true, self::puede($u), true);
    }

    // Tipos de campo que el organizador puede agregar al formulario de registro
    public const TIPOS_CAMPO = ['texto' => 'Texto corto', 'parrafo' => 'Texto largo', 'numero' => 'Número', 'lista' => 'Lista de opciones', 'fecha' => 'Fecha', 'si_no' => 'Sí / No'];

    // Campos propios del formulario, ya limpios: [['id','etiqueta','tipo','opciones','obligatorio'], …]
    public function camposFormulario(): array
    {
        return collect((array) ($this->campos ?? []))->filter(fn ($c) => is_array($c) && trim((string) ($c['etiqueta'] ?? '')) !== '')
            ->map(fn ($c) => [
                'id' => (string) ($c['id'] ?? ''), 'etiqueta' => (string) $c['etiqueta'],
                'tipo' => isset(self::TIPOS_CAMPO[$c['tipo'] ?? '']) ? $c['tipo'] : 'texto',
                'opciones' => array_values(array_filter(array_map('trim', (array) ($c['opciones'] ?? [])), 'strlen')),
                'obligatorio' => (bool) ($c['obligatorio'] ?? false),
            ])->filter(fn ($c) => $c['id'] !== '')->values()->all();
    }

    // Reglas de validación para las respuestas de los campos propios (respuestas.{id})
    public function reglasCampos(): array
    {
        $r = [];
        foreach ($this->camposFormulario() as $c) {
            $base = [$c['obligatorio'] ? 'required' : 'nullable'];
            $r['respuestas.' . $c['id']] = array_merge($base, match ($c['tipo']) {
                'numero' => ['numeric'],
                'fecha' => ['date'],
                'lista' => [\Illuminate\Validation\Rule::in($c['opciones'])],
                'si_no' => ['in:Sí,No'],
                'parrafo' => ['string', 'max:1000'],
                default => ['string', 'max:200'],
            });
        }
        return $r;
    }

    public function nombresCampos(): array
    {
        return collect($this->camposFormulario())->mapWithKeys(fn ($c) => ['respuestas.' . $c['id'] => mb_strtolower($c['etiqueta'])])->all();
    }

    /**
     * Promociones que arma el organizador: "Compra X y la siguiente con Y % de descuento" (100 % = gratis).
     * Se repite por cada bloque: con "compra 2, la 3.ª gratis", quien compra 6 lleva 2 gratis.
     * Cada una: ['compra' => 2, 'descuento' => 100, 'activa' => true, 'hasta' => '2026-10-30' | null]
     */
    public function promosVigentes(): array
    {
        return collect((array) ($this->promos ?? []))
            ->filter(fn ($p) => is_array($p) && ($p['activa'] ?? true) && (int) ($p['compra'] ?? 0) >= 1
                && (int) ($p['descuento'] ?? 0) >= 1 && (int) ($p['descuento'] ?? 0) <= 100
                && (empty($p['hasta']) || now()->lte(\Illuminate\Support\Carbon::parse($p['hasta'])->endOfDay())))
            ->map(fn ($p) => ['compra' => (int) $p['compra'], 'descuento' => (int) $p['descuento'], 'hasta' => $p['hasta'] ?? null] + ['texto' => self::textoPromo((int) $p['compra'], (int) $p['descuento'])])
            ->values()->all();
    }

    public static function textoPromo(int $compra, int $descuento): string
    {
        $n = $compra + 1;
        return "Compra {$compra} y la {$n}.ª " . ($descuento >= 100 ? 'es gratis' : "con {$descuento} % de descuento");
    }

    /**
     * Precio de cada entrada en una compra de $cantidad, con la mejor promoción que aplique.
     * Devuelve ['precios' => [..], 'total', 'lista', 'ahorro', 'promo' => texto|null]
     */
    public function cotizar(int $cantidad): array
    {
        $precio = (float) $this->precio;
        $cantidad = max(1, $cantidad);
        $mejor = ['precios' => array_fill(0, $cantidad, $precio), 'ahorro' => 0.0, 'promo' => null];
        foreach ($this->promosVigentes() as $p) {
            $bloque = $p['compra'] + 1;
            $precios = [];
            for ($i = 1; $i <= $cantidad; $i++) {
                $precios[] = $i % $bloque === 0 ? round($precio * (100 - $p['descuento']) / 100, 2) : $precio;
            }
            $ahorro = $precio * $cantidad - array_sum($precios);
            if ($ahorro > $mejor['ahorro']) $mejor = ['precios' => $precios, 'ahorro' => $ahorro, 'promo' => $p['texto']];
        }
        return $mejor + ['total' => array_sum($mejor['precios']), 'lista' => $precio * $cantidad];
    }
}
