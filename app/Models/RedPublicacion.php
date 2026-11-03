<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

// Publicación en las redes del negocio: foto (Facebook/Instagram) o video (Reels de Facebook/Instagram y YouTube)
class RedPublicacion extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_publicaciones';

    protected $fillable = ['cuenta_id', 'user_id', 'producto_id', 'tipo', 'texto', 'imagen', 'video', 'portada', 'duracion', 'ancho', 'alto', 'peso',
        'en_facebook', 'en_instagram', 'en_youtube', 'yt_cuenta_id', 'titulo', 'yt_privacidad',
        'estado', 'programada_para', 'publicada_en', 'empezo_en', 'fb_post_id', 'ig_media_id', 'yt_video_id', 'fb_url', 'ig_url', 'yt_url',
        'yt_subida', 'ig_contenedor', 'fallos', 'error'];

    protected $casts = ['en_facebook' => 'boolean', 'en_instagram' => 'boolean', 'en_youtube' => 'boolean', 'programada_para' => 'datetime',
        'publicada_en' => 'datetime', 'empezo_en' => 'datetime', 'fallos' => 'array', 'duracion' => 'float', 'yt_subida' => 'encrypted'];

    protected $hidden = ['yt_subida'];

    public const PRIVACIDAD = ['public' => 'Público', 'unlisted' => 'No listado (solo con el enlace)', 'private' => 'Privado'];

    public function cuenta() { return $this->belongsTo(RedCuenta::class, 'cuenta_id'); }
    public function ytCuenta() { return $this->belongsTo(RedCuenta::class, 'yt_cuenta_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public function getEsVideoAttribute(): bool
    {
        return $this->tipo === 'video';
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? url(Storage::disk('public')->url($this->imagen)) : null;
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video ? url(Storage::disk('public')->url($this->video)) : null;
    }

    public function getPortadaUrlAttribute(): ?string
    {
        return $this->portada ? url(Storage::disk('public')->url($this->portada)) : null;
    }

    // Vertical (o cuadrado) y de máximo 3 minutos: YouTube lo muestra como Short
    public function getEsShortAttribute(): bool
    {
        return self::cuentaComoShort($this->duracion, $this->ancho, $this->alto);
    }

    public static function cuentaComoShort($duracion, $ancho, $alto): bool
    {
        return $duracion !== null && (float) $duracion > 0 && (float) $duracion <= 180 && (int) $alto >= (int) $ancho && (int) $alto > 0;
    }

    // Vertical y de 3 a 90 segundos: Facebook lo publica como Reel (si no, como video de la página)
    public function getEsReelFacebookAttribute(): bool
    {
        return $this->duracion !== null && $this->duracion >= 3 && $this->duracion <= 90 && (int) $this->alto > (int) $this->ancho;
    }

    // Redes elegidas, en el orden en que se publican
    public function redes(): array
    {
        return array_keys(array_filter(['facebook' => $this->en_facebook, 'instagram' => $this->en_instagram, 'youtube' => $this->en_youtube]));
    }

    public function getRedesTextoAttribute(): string
    {
        return implode(' + ', array_map(fn ($r) => ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'][$r], $this->redes()));
    }

    // Ya salió en esa red
    public function listaEn(string $red): bool
    {
        return (bool) match ($red) { 'facebook' => $this->fb_post_id, 'instagram' => $this->ig_media_id, 'youtube' => $this->yt_video_id, default => false };
    }

    public function borrarArchivos(): void
    {
        foreach (array_filter([$this->imagen, $this->video, $this->portada]) as $r) Storage::disk('public')->delete($r);
    }
}
