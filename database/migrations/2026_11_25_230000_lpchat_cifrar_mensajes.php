<?php

use App\Support\LpChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 2026-11-25 (Jhoset): seguridad de los chats: los mensajes que ya existían también quedan cifrados en la base de datos.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_mensajes')) {
            return;
        }
        DB::table('lpchat_mensajes')->whereNotNull('texto')->where('texto', '!=', '')->where('texto', 'not like', 'enc:%')
            ->where(fn ($q) => $q->whereNull('tipo')->orWhere('tipo', '!=', 'sistema'))
            ->orderBy('id')->select('id', 'texto')->chunkById(500, function ($filas) {
                foreach ($filas as $f) {
                    DB::table('lpchat_mensajes')->where('id', $f->id)->update(['texto' => LpChat::cifrar($f->texto)]);
                }
            });
    }

    public function down(): void
    {
    }
};
