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

    protected $fillable = ['evento_id', 'asistente_id', 'precio', 'etapa', 'estado', 'aviso_pago_at', 'aprobada_at', 'aprobada_por', 'referencia_pago', 'nota', 'ingreso_at', 'ingreso_por', 'respuestas'];

    protected $casts = ['precio' => 'decimal:2', 'aviso_pago_at' => 'datetime', 'aprobada_at' => 'datetime', 'ingreso_at' => 'datetime', 'respuestas' => 'array'];

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
}
