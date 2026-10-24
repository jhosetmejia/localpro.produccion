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
        'pago_metodo', 'pago_numero', 'pago_titular', 'whatsapp', 'mensaje_whatsapp', 'instagram', 'tiktok', 'pedir_colegio', 'edad_minima', 'condiciones', 'color', 'estado'];

    protected $casts = ['fecha_evento' => 'datetime', 'venta_hasta' => 'datetime', 'precio' => 'decimal:2', 'pedir_colegio' => 'boolean', 'cupo' => 'integer', 'edad_minima' => 'integer'];

    public const ESTADOS = ['borrador' => 'Borrador (no se ve)', 'publicado' => 'Publicado (a la venta)', 'cerrado' => 'Venta cerrada'];

    public const MENSAJE = 'Hola, ya pagué aquí está mi comprobante';

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
}
