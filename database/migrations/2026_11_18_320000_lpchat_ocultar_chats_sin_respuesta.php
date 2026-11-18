<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): de los chats que se trajeron de NES/PFN solo se muestran aquellos en los que
 * las dos personas se escribieron. Los de un solo lado (por ejemplo, mensajes masivos sin respuesta) quedan ocultos
 * y aparecen solos apenas alguien responda.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lpchat_mensajes', 'origen')) {
            return;
        }
        $chats = DB::table('lpchat_chats as c')->where('c.tipo', 'directo')
            ->whereExists(fn ($q) => $q->from('lpchat_mensajes as m')->whereColumn('m.chat_id', 'c.id')->whereNotNull('m.origen'))
            ->pluck('c.id');
        foreach ($chats->chunk(500) as $lote) {
            $autores = DB::table('lpchat_mensajes')->whereIn('chat_id', $lote)->whereNotNull('usuario_id')
                ->selectRaw('chat_id, count(distinct usuario_id) n')->groupBy('chat_id')->pluck('n', 'chat_id');
            $ocultar = $lote->filter(fn ($id) => (int) ($autores[$id] ?? 0) < 2)->values();
            if ($ocultar->isNotEmpty()) {
                DB::table('lpchat_miembros')->whereIn('chat_id', $ocultar)->where('estado', 'activo')->update(['estado' => 'oculto']);
            }
        }
    }

    public function down(): void
    {
        DB::table('lpchat_miembros')->where('estado', 'oculto')->update(['estado' => 'activo']);
    }
};
