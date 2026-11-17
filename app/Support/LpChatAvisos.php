<?php

namespace App\Support;

use App\Models\LpchatUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/** 2026-11-17 (Jhoset): campanita de LocalProo — mensajes sin leer en LP Chat (personales y de la empresa). */
class LpChatAvisos
{
    public static function notificaciones(User $u): array
    {
        if (! Schema::hasTable('lpchat_usuarios')) {
            return [];
        }
        $out = [];
        $yo = LpchatUsuario::where('user_id', $u->id)->first();
        if ($yo && ($n = LpChat::sinLeerTotal($yo) + LpChat::solicitudesTotal($yo))) {
            $out[] = ['clave' => 'lpchat-yo-'.$n, 'icono' => 'fa-comments', 'color' => 'verde', 'prioridad' => 3,
                'texto' => 'Tienes <b>'.$n.' mensaje'.($n === 1 ? '' : 's').'</b> sin leer en LP Chat.', 'cuando' => 'LP Chat', 'fecha' => now()->format('Y-m-d H:i'), 'url' => route('lpchat.index')];
        }
        $emp = $u->tienda_id ? LpchatUsuario::where('tienda_id', $u->tienda_id)->first() : null;
        if ($emp && ($n = LpChat::sinLeerTotal($emp))) {
            $out[] = ['clave' => 'lpchat-emp-'.$n, 'icono' => 'fa-store', 'color' => 'verde', 'prioridad' => 2,
                'texto' => 'Tu negocio tiene <b>'.$n.' mensaje'.($n === 1 ? '' : 's').'</b> de clientes en LP Chat. Ábrelo y toca <b>Usar como empresa</b>.', 'cuando' => 'LP Chat · Empresas', 'fecha' => now()->format('Y-m-d H:i'), 'url' => route('lpchat.index')];
        }

        return $out;
    }
}
