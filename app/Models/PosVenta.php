<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class PosVenta extends Model
{
    use PerteneceATienda;

    protected $table = 'pos_ventas';

    protected $fillable = [
        'numero', 'caja_id', 'user_id', 'cliente_id', 'cliente_nombre', 'cliente_telefono', 'subtotal',
        'descuento', 'total', 'cambio', 'estado', 'anulada_en', 'anulada_por',
    ];

    protected $casts = ['anulada_en' => 'datetime'];

    public function items()
    {
        return $this->hasMany(PosVentaItem::class);
    }

    public function pagos()
    {
        return $this->hasMany(PosVentaPago::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Enlace firmado para que el cliente vea su factura sin iniciar sesión
    public function getUrlFacturaAttribute(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('factura.publica', ['venta' => $this->id]);
    }

    // Teléfono listo para wa.me con el indicativo del país de la tienda
    public function getWhatsappNumeroAttribute(): ?string
    {
        return numero_whatsapp($this->cliente_telefono);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
