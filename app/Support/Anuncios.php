<?php

namespace App\Support;

use App\Models\User;

// Anuncios de LocalProo para todos los usuarios (config/anuncios.php)
class Anuncios
{
    public static function notificaciones(User $u): array
    {
        $hoy = today()->toDateString();
        $lista = [];
        foreach ((array) config('anuncios', []) as $a) {
            if (!empty($a['desde']) && $hoy < $a['desde']) continue;
            if (!empty($a['hasta']) && $hoy > $a['hasta']) continue;
            $lista[] = [
                'clave' => $a['clave'],
                'icono' => $a['icono'] ?? 'fa-bullhorn',
                'color' => $a['color'] ?? 'azul',
                'prioridad' => 4,
                'texto' => $a['texto'],
                'cuando' => $a['cuando'] ?? 'Novedad de LocalProo',
                'fecha' => $a['desde'] ?? '',
                'url' => isset($a['url']) ? url($a['url']) : null,
            ];
        }
        return $lista;
    }
}
