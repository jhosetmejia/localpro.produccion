<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class RedMensaje extends Model
{
    use PerteneceATienda;

    protected $table = 'redes_mensajes';

    protected $fillable = ['conversacion_id', 'mid', 'del_cliente', 'texto', 'adjunto_tipo', 'adjunto_url', 'user_id', 'enviado_en'];

    protected $casts = ['del_cliente' => 'boolean', 'enviado_en' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
