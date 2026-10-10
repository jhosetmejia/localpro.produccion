<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosVenta extends Model
{
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

    // Teléfono listo para wa.me (Colombia +57 por defecto)
    public function getWhatsappNumeroAttribute(): ?string
    {
        $t = preg_replace('/\D/', '', (string) $this->cliente_telefono);
        if ($t === '') {
            return null;
        }
        return strlen($t) === 10 ? '57' . $t : $t;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
