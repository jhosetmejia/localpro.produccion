<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class RedConversacion extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_conversaciones';

    protected $fillable = ['cuenta_id', 'plataforma', 'contacto_id', 'contacto_nombre', 'contacto_usuario', 'contacto_foto', 'cliente_id', 'ultimo_texto', 'ultimo_en', 'ultimo_del_cliente_en', 'no_leidos', 'etiqueta'];

    protected $casts = ['ultimo_en' => 'datetime', 'ultimo_del_cliente_en' => 'datetime'];

    public const ETIQUETAS = ['nuevo' => ['Nuevo', '#2f7cf6'], 'interesado' => ['Interesado', '#f5a300'], 'pedido' => ['Hizo pedido', '#16b34a'], 'cliente' => ['Cliente', '#0b0d0c'], 'perdido' => ['No compró', '#9aa69e']];

    public function cuenta() { return $this->belongsTo(RedCuenta::class, 'cuenta_id'); }
    public function mensajes() { return $this->hasMany(RedMensaje::class, 'conversacion_id'); }
    public function cliente() { return $this->belongsTo(Cliente::class); }

    // Meta solo deja responder dentro de las 24 horas siguientes al último mensaje del cliente
    public function getPuedeResponderAttribute(): bool
    {
        return $this->ultimo_del_cliente_en && $this->ultimo_del_cliente_en->gt(now()->subHours(24));
    }

    public function getNombreAttribute(): string
    {
        return $this->contacto_nombre ?: ($this->contacto_usuario ? '@' . $this->contacto_usuario : ($this->plataforma === 'instagram' ? 'Usuario de Instagram' : 'Usuario de Facebook'));
    }
}
