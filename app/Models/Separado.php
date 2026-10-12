<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Prenda(s) separada(s) por un cliente que va pagando con abonos
class Separado extends Model
{
    use PerteneceATienda;

    protected $table = 'separados';

    protected $fillable = ['numero', 'cliente_id', 'cliente_nombre', 'cliente_telefono', 'user_id', 'total', 'abonado', 'estado', 'vence_en', 'nota', 'pos_venta_id', 'entregado_en', 'cancelado_en', 'motivo_cancelacion'];

    protected $casts = ['vence_en' => 'date', 'entregado_en' => 'datetime', 'cancelado_en' => 'datetime', 'total' => 'float', 'abonado' => 'float'];

    public function items() { return $this->hasMany(SeparadoItem::class); }
    public function abonos() { return $this->hasMany(SeparadoAbono::class)->orderBy('id'); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function venta() { return $this->belongsTo(PosVenta::class, 'pos_venta_id'); }

    public function getSaldoAttribute(): float
    {
        return max(0, round($this->total - $this->abonado, 2));
    }

    public function getPagadoAttribute(): bool
    {
        return $this->saldo <= 0.009;
    }

    public function getVencidoAttribute(): bool
    {
        return $this->estado === 'activo' && $this->vence_en && $this->vence_en->lt(today());
    }

    public function getPorcentajeAttribute(): int
    {
        return $this->total > 0 ? (int) min(100, round($this->abonado / $this->total * 100)) : 0;
    }

    public function getTelefonoWaAttribute(): ?string
    {
        return $this->cliente_telefono ? numero_whatsapp($this->cliente_telefono) : null;
    }

    // Recalcula lo abonado con los abonos vigentes
    public function recalcular(): void
    {
        $this->abonado = (float) $this->abonos()->where('anulado', false)->sum('monto');
        $this->save();
    }
}
