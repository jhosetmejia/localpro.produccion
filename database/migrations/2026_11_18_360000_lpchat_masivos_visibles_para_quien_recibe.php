<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): los chats de NES/PFN se ven igual que allá. Los mensajes del equipo que nadie respondió
 * siguen ocultos solo para quien los mandó; a quien los recibió le aparecen en Mis chats (como en NES/PFN).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_miembros')) {
            return;
        }
        $chats = DB::table('lpchat_miembros')->where('estado', 'oculto')->distinct()->pluck('chat_id');
        foreach ($chats->chunk(500) as $lote) {
            $autores = DB::table('lpchat_mensajes')->whereIn('chat_id', $lote)->whereNotNull('usuario_id')
                ->select('chat_id', 'usuario_id')->distinct()->get()->groupBy('chat_id');
            foreach ($lote as $cid) {
                $a = ($autores[$cid] ?? collect())->pluck('usuario_id')->unique();
                if ($a->count() === 1) {
                    DB::table('lpchat_miembros')->where('chat_id', $cid)->where('usuario_id', '!=', $a->first())
                        ->where('estado', 'oculto')->update(['estado' => 'activo', 'seccion' => 'mis']);
                } elseif ($a->count() >= 2) {
                    DB::table('lpchat_miembros')->where('chat_id', $cid)->where('estado', 'oculto')->update(['estado' => 'activo']);
                }
            }
        }
    }

    public function down(): void
    {
    }
};
