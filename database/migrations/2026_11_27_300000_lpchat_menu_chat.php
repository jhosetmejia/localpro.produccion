<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2026-11-27 (Jhoset): menú de cada chat como WhatsApp (clic derecho / mantener presionado):
// archivar, silenciar, fijar, marcar como no leído, favoritos y vaciar chat (cada uno es solo para quien lo hace).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_miembros')) {
            return;
        }
        Schema::table('lpchat_miembros', function (Blueprint $t) {
            if (! Schema::hasColumn('lpchat_miembros', 'archivado')) {
                $t->boolean('archivado')->default(false);
            }
            if (! Schema::hasColumn('lpchat_miembros', 'silenciado_hasta')) {
                $t->timestamp('silenciado_hasta')->nullable();
            }
            if (! Schema::hasColumn('lpchat_miembros', 'fijado_at')) {
                $t->timestamp('fijado_at')->nullable();
            }
            if (! Schema::hasColumn('lpchat_miembros', 'no_leido')) {
                $t->boolean('no_leido')->default(false);
            }
            if (! Schema::hasColumn('lpchat_miembros', 'favorito')) {
                $t->boolean('favorito')->default(false);
            }
            if (! Schema::hasColumn('lpchat_miembros', 'vaciado_id')) {
                $t->unsignedBigInteger('vaciado_id')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('lpchat_miembros', function (Blueprint $t) {
            foreach (['archivado', 'silenciado_hasta', 'fijado_at', 'no_leido', 'favorito', 'vaciado_id'] as $c) {
                if (Schema::hasColumn('lpchat_miembros', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
