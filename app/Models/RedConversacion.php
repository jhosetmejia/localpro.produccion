<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class RedConversacion extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_conversaciones';

    protected $fillable = ['cuenta_id', 'plataforma', 'contacto_id', 'contacto_nombre', 'contacto_usuario', 'contacto_foto', 'cliente_id', 'ultimo_texto', 'ultimo_en', 'ultimo_del_cliente_en', 'no_leidos', 'etiqueta', 'asunto', 'ultimo_mid'];

    protected $casts = ['ultimo_en' => 'datetime', 'ultimo_del_cliente_en' => 'datetime'];

    public const ETIQUETAS = ['nuevo' => ['Nuevo', '#2f7cf6'], 'interesado' => ['Interesado', '#f5a300'], 'pedido' => ['Hizo pedido', '#16b34a'], 'cliente' => ['Cliente', '#0b0d0c'], 'perdido' => ['No compró', '#9aa69e']];

    public function cuenta() { return $this->belongsTo(RedCuenta::class, 'cuenta_id'); }
    public function mensajes() { return $this->hasMany(RedMensaje::class, 'conversacion_id'); }
    public function cliente() { return $this->belongsTo(Cliente::class); }

    // Meta solo deja responder dentro de las 24 horas siguientes al último mensaje del cliente
    // (7 días con el permiso de "Agente humano"). El correo se puede responder siempre.
    public function getPuedeResponderAttribute(): bool
    {
        if ($this->plataforma === 'correo') return true;
        return $this->ultimo_del_cliente_en && $this->ultimo_del_cliente_en->gt(now()->subHours(\App\Support\Meta::ventanaHoras()));
    }

    // Hasta cuándo se puede responder (null en el correo)
    public function getVenceEnAttribute(): ?\Carbon\Carbon
    {
        if ($this->plataforma === 'correo' || !$this->ultimo_del_cliente_en) return null;
        return $this->ultimo_del_cliente_en->copy()->addHours(\App\Support\Meta::ventanaHoras());
    }

    public function getNombreAttribute(): string
    {
        return $this->contacto_nombre ?: ($this->contacto_usuario ? '@' . $this->contacto_usuario : ($this->plataforma === 'correo' ? $this->contacto_id : ($this->plataforma === 'instagram' ? 'Usuario de Instagram' : 'Usuario de Facebook')));
    }
}
