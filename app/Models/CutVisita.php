<?php

namespace App\Models;

use App\Support\GeoIp;
use Illuminate\Database\Eloquent\Model;

/** Una visita a LocalProCut (una pestaña abierta). Ver Admin → LocalProCut. */
class CutVisita extends Model
{
    protected $table = 'cut_visitas';

    protected $guarded = ['id'];

    protected $casts = [
        'nuevo' => 'boolean', 'geo_consultada' => 'boolean', 'datos_moviles' => 'boolean', 'vpn' => 'boolean', 'es_app' => 'boolean',
        'clic_registro' => 'boolean', 'clic_app' => 'boolean', 'instalo' => 'boolean', 'ultima_vez' => 'datetime',
    ];

    public function eventos() { return $this->hasMany(CutEvento::class, 'visita_id'); }
    public function user() { return $this->belongsTo(User::class); }

    /** Ubica las visitas pendientes y borra la IP (queda solo el hash para contar personas distintas). */
    public static function ubicarPendientes(int $limite = 200): int
    {
        // Máximo un intento cada 20 s (el panel se refresca solo y el servicio de ubicación tiene límite)
        if (!\Illuminate\Support\Facades\Cache::add('cut_geo_intento', 1, 20)) return 0;
        $pend = static::where('geo_consultada', false)->whereNotNull('ip')->orderByDesc('id')->limit($limite)->get(['id', 'ip']);
        if ($pend->isEmpty()) return 0;
        $geo = GeoIp::consultar($pend->pluck('ip')->all());
        $n = 0;
        foreach ($pend as $v) {
            if (!isset($geo[$v->ip])) continue; // el servicio no respondió: se intenta después
            static::whereKey($v->id)->update($geo[$v->ip] + ['geo_consultada' => true, 'ip' => null]);
            $n++;
        }
        // Las que llevan más de 2 días sin poderse ubicar tampoco guardan la IP
        static::where('geo_consultada', false)->where('created_at', '<', now()->subDays(2))->update(['ip' => null, 'geo_consultada' => true]);
        return $n;
    }
}
