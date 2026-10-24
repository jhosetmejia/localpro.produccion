<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Entrada de una persona a un evento: pendiente hasta que el negocio aprueba el pago; aprobada tiene QR único
class EventoEntrada extends Model
{
    use PerteneceATienda;

    protected $table = 'evento_entradas';

    protected $fillable = ['evento_id', 'asistente_id', 'precio', 'etapa', 'estado', 'aviso_pago_at', 'aprobada_at', 'aprobada_por', 'referencia_pago', 'nota', 'ingreso_at', 'ingreso_por', 'respuestas',
        'grupo', 'titular_nombre', 'titular_tipo_documento', 'titular_documento', 'titular_email', 'precio_lista', 'promo'];

    protected $casts = ['precio' => 'decimal:2', 'aviso_pago_at' => 'datetime', 'aprobada_at' => 'datetime', 'ingreso_at' => 'datetime', 'respuestas' => 'array', 'precio_lista' => 'decimal:2'];

    public const ESTADOS = ['pendiente' => 'Por aprobar', 'aprobada' => 'Pago aprobado', 'rechazada' => 'Rechazada', 'anulada' => 'Anulada'];

    protected static function booted(): void
    {
        static::creating(function (EventoEntrada $e) {
            $e->uuid = $e->uuid ?: (string) Str::uuid();
            if (!$e->codigo) {
                do {
                    $c = 'LPE-' . strtoupper(Str::random(10));
                } while (static::withoutGlobalScopes()->where('codigo', $c)->exists());
                $e->codigo = $c;
            }
        });
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id')->withoutGlobalScopes();
    }

    public function asistente()
    {
        return $this->belongsTo(EventoAsistente::class, 'asistente_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobada_por');
    }

    public function portero()
    {
        return $this->belongsTo(User::class, 'ingreso_por');
    }

    public function getEstadoTextoAttribute(): string
    {
        if ($this->estado === 'aprobada' && $this->ingreso_at) return 'Ya ingresó';
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    // Cuando alguien compra varias entradas, cada una puede ser de otra persona (acompañante).
    // Si no tiene titular propio, la entrada es del comprador.
    public function getNombreTitularAttribute(): string
    {
        return $this->titular_nombre ?: (string) $this->asistente?->nombre;
    }

    public function getDocumentoTitularAttribute(): string
    {
        return $this->titular_documento
            ? trim($this->titular_tipo_documento . ' ' . $this->titular_documento)
            : trim($this->asistente?->tipo_documento . ' ' . $this->asistente?->documento);
    }

    public function getEsAcompananteAttribute(): bool
    {
        return (bool) $this->titular_documento;
    }

    // Todas las entradas de la misma compra (una sola si es una compra individual o antigua)
    public function delGrupo()
    {
        $q = static::withoutGlobalScope('tienda')->where('evento_id', $this->evento_id);
        return $this->grupo ? $q->where('grupo', $this->grupo) : $q->whereKey($this->id);
    }
}
