<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Tiquetera prepagada (restaurantes, cafeterías): N almuerzos o servicios pagados por adelantado
class Tiquetera extends Model
{
    use PerteneceATienda;

    protected $fillable = ['tienda_id', 'codigo', 'cliente_id', 'cliente_nombre', 'cliente_telefono', 'nombre', 'total', 'usados', 'valor', 'pos_venta_id', 'vence', 'estado', 'nota', 'user_id'];
    protected $casts = ['vence' => 'date', 'valor' => 'float', 'total' => 'integer', 'usados' => 'integer'];

    public function usos()
    {
        return $this->hasMany(TiqueteraUso::class)->latest();
    }

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function getQuedanAttribute(): int
    {
        return max(0, $this->total - $this->usados);
    }

    public function getVencidaAttribute(): bool
    {
        return $this->vence && $this->vence->lt(today());
    }

    public function getWhatsappNumeroAttribute(): ?string
    {
        return numero_whatsapp($this->cliente_telefono);
    }
}
