<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): los chats que ya existían en NES y PFN se pasan a LP Chat y quedan sincronizados en los dos sentidos.
 * origen = "nes:m123" / "pfn:m45": mensaje que vino de ese sistema (no se devuelve y no se borra a los 7 días).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lpchat_mensajes') && ! Schema::hasColumn('lpchat_mensajes', 'origen')) {
            Schema::table('lpchat_mensajes', function (Blueprint $t) {
                $t->string('origen', 40)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lpchat_mensajes', 'origen')) {
            Schema::table('lpchat_mensajes', fn (Blueprint $t) => $t->dropColumn('origen'));
        }
    }
};
