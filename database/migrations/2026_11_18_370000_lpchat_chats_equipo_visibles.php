<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** 2026-11-18 (Jhoset): chats entre dos cuentas del equipo (p. ej. el administrador con Mía de NES/PFN) siempre se ven. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_miembros')) {
            return;
        }
        $ocultos = DB::table('lpchat_miembros')->where('estado', 'oculto')->distinct()->pluck('chat_id');
        foreach ($ocultos->chunk(500) as $lote) {
            $normales = DB::table('lpchat_miembros as m')->join('lpchat_usuarios as u', 'u.id', '=', 'm.usuario_id')
                ->whereIn('m.chat_id', $lote)->where('u.sin_limite', false)->distinct()->pluck('m.chat_id');
            $equipo = $lote->diff($normales)->values();
            if ($equipo->isNotEmpty()) {
                DB::table('lpchat_miembros')->whereIn('chat_id', $equipo)->where('estado', 'oculto')->update(['estado' => 'activo', 'seccion' => 'mis']);
            }
        }
    }

    public function down(): void
    {
    }
};
