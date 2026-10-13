<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Servicio que el negocio pone en alquiler: qué se alquila, fecha, hora, lugar, responsable y valor
class Alquiler extends Model
{
    use PerteneceATienda;

    protected $table = 'alquileres';

    protected $fillable = ['numero', 'que_alquila', 'fecha', 'hora', 'lugar', 'responsable_telefono', 'nota', 'valor', 'estado', 'user_id'];

    protected $casts = ['fecha' => 'date', 'valor' => 'float'];

    public function user() { return $this->belongsTo(User::class); }

    public function getHoraTextoAttribute(): string
    {
        return $this->hora ? \Carbon\Carbon::parse($this->hora)->format('g:i a') : '';
    }

    public function getVencidoAttribute(): bool
    {
        return $this->estado === 'programado' && $this->fecha && $this->fecha->lt(today());
    }

    public function getTelefonoWaAttribute(): ?string
    {
        return $this->responsable_telefono ? numero_whatsapp($this->responsable_telefono) : null;
    }

    // Siguiente número ALQ-000001 de la tienda actual (incluye los borrados para no repetir)
    public static function siguienteNumero(): string
    {
        $ultimo = (int) substr((string) static::query()->orderByDesc('id')->value('numero'), 4);
        return 'ALQ-' . str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT);
    }
}
