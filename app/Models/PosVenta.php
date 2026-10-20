<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class PosVenta extends Model
{
    use PerteneceATienda;

    protected $table = 'pos_ventas';

    protected $fillable = [
        'numero', 'tipo_doc', 'base_gravable', 'impuestos', 'impuestos_detalle', 'adquiriente', 'caja_id', 'user_id', 'cliente_id', 'cliente_nombre', 'cliente_telefono', 'subtotal',
        'descuento', 'total', 'cambio', 'estado', 'anulada_en', 'anulada_por', 'tasa_bs', 'corregida_en', 'registrada_despues_en',
    ];

    protected $casts = ['anulada_en' => 'datetime', 'corregida_en' => 'datetime', 'registrada_despues_en' => 'datetime', 'impuestos_detalle' => 'array', 'adquiriente' => 'array'];

    // Documento electrónico (factura o POS electrónico) enviado a la DIAN por el proveedor
    public function documento()
    {
        return $this->hasOne(DocumentoElectronico::class)->whereIn('tipo', ['fe', 'pos_e'])->latest('id');
    }

    // Si la venta salió de un alquiler: sus detalles (fecha, horario, lugar) se muestran en la factura
    public function alquiler()
    {
        return $this->hasOne(Alquiler::class, 'pos_venta_id');
    }

    // Correcciones por error al digitar (las hace el administrador)
    public function correcciones()
    {
        return $this->hasMany(PosVentaCorreccion::class);
    }

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
