<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Página de Facebook (y su Instagram profesional, si lo tiene) conectada a una tienda
class RedCuenta extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_cuentas';

    protected $fillable = ['page_id', 'page_nombre', 'page_foto', 'page_token', 'ig_id', 'ig_usuario', 'ig_foto', 'conectado_por', 'estado', 'error', 'sincronizado_en'];

    protected $casts = ['page_token' => 'encrypted', 'sincronizado_en' => 'datetime'];

    protected $hidden = ['page_token'];

    public function conversaciones() { return $this->hasMany(RedConversacion::class, 'cuenta_id'); }

    public function getTieneInstagramAttribute(): bool
    {
        return filled($this->ig_id);
    }
}
