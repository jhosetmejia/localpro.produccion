<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    protected $fillable = ['pedido_id', 'producto_id', 'producto_variante_id', 'estacion_id', 'nombre', 'etiqueta', 'cantidad', 'precio', 'subtotal', 'nota', 'estado', 'ronda', 'enviado_at', 'listo_at', 'entregado_at'];

    protected $casts = ['cantidad' => 'float', 'precio' => 'float', 'subtotal' => 'float', 'enviado_at' => 'datetime', 'listo_at' => 'datetime', 'entregado_at' => 'datetime'];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function estacion()
    {
        return $this->belongsTo(PedidoEstacion::class, 'estacion_id');
    }
}
