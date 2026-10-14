<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Servicio que el negocio pone en alquiler: qué se alquila, fecha, hora, lugar, responsable y valor
class Alquiler extends Model
{
    use PerteneceATienda;

    protected $table = 'alquileres';

    protected $fillable = ['numero', 'que_alquila', 'fecha', 'hora', 'lugar', 'responsable_telefono', 'encargado_nombre', 'encargado_telefono', 'nota', 'valor', 'con_comision', 'comision', 'estado', 'user_id', 'pos_venta_id', 'cobrado_en', 'abonado', 'entrega', 'entregado_en', 'devuelto_en', 'devolucion_estado', 'devolucion_nota'];

    protected $casts = ['fecha' => 'date', 'valor' => 'float', 'comision' => 'float', 'con_comision' => 'boolean', 'cobrado_en' => 'datetime', 'abonado' => 'float', 'entregado_en' => 'datetime', 'devuelto_en' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function venta() { return $this->belongsTo(PosVenta::class, 'pos_venta_id'); }
    public function abonos() { return $this->hasMany(AlquilerAbono::class)->orderBy('id'); }

    // Lo que falta por pagar
    public function getSaldoAttribute(): float
    {
        return max(0, round($this->valor - $this->abonado, 2));
    }

    public function getPagadoAttribute(): bool
    {
        return $this->saldo <= 0.009;
    }

    // Estado de lo alquilado: por entregar, en manos del cliente o devuelto
    public function getEntregaTextoAttribute(): string
    {
        return match ($this->entrega) {
            'alquilado' => '🔑 Alquilado · lo tiene el cliente',
            'devuelto' => $this->devolucion_estado === 'con_novedad' ? '⚠️ Devuelto con novedad' : '✅ Devuelto en orden',
            default => '📦 Por entregar',
        };
    }

    // Lo que le queda al local: valor del alquiler menos la comisión (si se paga comisión)
    public function getGananciaAttribute(): float
    {
        return round($this->valor - ($this->con_comision ? $this->comision : 0), 2);
    }

    public function getCobradoAttribute(): bool
    {
        return (bool) $this->pos_venta_id;
    }

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
