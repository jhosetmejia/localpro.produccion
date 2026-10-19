<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Página de Facebook (y su Instagram profesional, si lo tiene) o un correo (tipo 'correo') conectado a una tienda.
// En los correos, page_id = 'correo:<email>', page_nombre = el correo y page_token = la configuración cifrada (JSON).
class RedCuenta extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_cuentas';

    protected $fillable = ['tipo', 'page_id', 'page_nombre', 'page_foto', 'page_token', 'ig_id', 'ig_usuario', 'ig_foto', 'conectado_por', 'estado', 'error', 'sincronizado_en'];

    protected $casts = ['page_token' => 'encrypted', 'sincronizado_en' => 'datetime'];

    protected $hidden = ['page_token'];

    public function conversaciones() { return $this->hasMany(RedConversacion::class, 'cuenta_id'); }

    // Solo las cuentas de Facebook/Instagram (las de Meta)
    public function scopeMeta($q)
    {
        return $q->where(fn ($w) => $w->where('tipo', 'meta')->orWhereNull('tipo'));
    }

    public function scopeCorreo($q)
    {
        return $q->where('tipo', 'correo');
    }

    public function getEsCorreoAttribute(): bool
    {
        return $this->tipo === 'correo';
    }

    // Configuración del correo (servidor, usuario, clave…) guardada cifrada
    public function getCorreoConfigAttribute(): array
    {
        if (!$this->es_correo) return [];
        $c = json_decode((string) $this->page_token, true);
        return is_array($c) ? $c : [];
    }

    public function guardarCorreoConfig(array $cfg): void
    {
        $this->forceFill(['page_token' => json_encode($cfg)])->save();
    }

    public function getTieneInstagramAttribute(): bool
    {
        return filled($this->ig_id);
    }
}
