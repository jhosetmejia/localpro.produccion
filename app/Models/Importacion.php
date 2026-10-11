<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Sin filtro automático por tienda: el panel de LocalPro las ve todas
class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = ['tienda_id', 'user_id', 'tipo', 'estado', 'archivo', 'nombre_original', 'telefono', 'nota', 'resultado', 'creados', 'actualizados', 'atendido_por', 'atendido_at'];

    protected $casts = ['atendido_at' => 'datetime'];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
