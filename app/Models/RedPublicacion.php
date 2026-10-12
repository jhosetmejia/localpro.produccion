<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RedPublicacion extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_publicaciones';

    protected $fillable = ['cuenta_id', 'user_id', 'producto_id', 'texto', 'imagen', 'en_facebook', 'en_instagram', 'estado', 'programada_para', 'publicada_en', 'fb_post_id', 'ig_media_id', 'fb_url', 'ig_url', 'error'];

    protected $casts = ['en_facebook' => 'boolean', 'en_instagram' => 'boolean', 'programada_para' => 'datetime', 'publicada_en' => 'datetime'];

    public function cuenta() { return $this->belongsTo(RedCuenta::class, 'cuenta_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? url(Storage::disk('public')->url($this->imagen)) : null;
    }
}
