<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Avisos de vencimiento de la prueba / plan (correos y pantalla vista). Sirven de constancia de notificación.
class AvisoPlan extends Model
{
    protected $table = 'avisos_plan';

    protected $fillable = ['tienda_id', 'tipo', 'canal', 'estado', 'plan_vence', 'fecha_borrado', 'destinatario', 'asunto', 'user_id', 'ip', 'detalle'];

    protected $casts = ['plan_vence' => 'date', 'fecha_borrado' => 'date'];

    public const TIPOS = [
        'por_vencer' => 'La prueba está por terminar',
        'vencida' => 'La prueba terminó: pague para no perder la información',
        'aviso_final' => 'Último aviso antes de eliminar',
        'datos_eliminados' => 'Se eliminó la información',
    ];

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
