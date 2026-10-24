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

    protected $fillable = ['nombre', 'slug', 'subtitulo', 'descripcion', 'fecha_evento', 'lugar', 'ciudad', 'etapa', 'precio', 'venta_hasta', 'cupo',
        'pago_metodo', 'pago_numero', 'pago_titular', 'whatsapp', 'mensaje_whatsapp', 'instagram', 'tiktok', 'pedir_colegio', 'edad_minima', 'condiciones', 'color', 'estado', 'tema', 'mostrar_visitas', 'campos', 'pedir_nacimiento', 'pedir_celular'];

    protected $casts = ['fecha_evento' => 'datetime', 'venta_hasta' => 'datetime', 'precio' => 'decimal:2', 'pedir_colegio' => 'boolean', 'cupo' => 'integer', 'edad_minima' => 'integer', 'visitas' => 'integer', 'mostrar_visitas' => 'boolean', 'campos' => 'array', 'pedir_nacimiento' => 'boolean', 'pedir_celular' => 'boolean'];

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
}
