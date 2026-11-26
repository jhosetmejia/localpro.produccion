<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2026-11-26 (Jhoset): menú de cada chat en la lista, como WhatsApp: archivar, silenciar, fijar, marcar no leído,
// favoritos, listas, bloquear, vaciar y eliminar. Todo es de cada persona (no cambia nada para el otro).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lpchat_miembros', function (Blueprint $t) {
            foreach (['archivado', 'no_leido', 'favorito'] as $c) {
                if (! Schema::hasColumn('lpchat_miembros', $c)) {
                    $t->boolean($c)->default(false);
                }
            }
            if (! Schema::hasColumn('lpchat_miembros', 'silencio_hasta')) {
                $t->timestamp('silencio_hasta')->nullable(); // sin avisos hasta esa fecha (2100 = siempre)
            }
            if (! Schema::hasColumn('lpchat_miembros', 'fijado_at')) {
                $t->timestamp('fijado_at')->nullable(); // fijado arriba de la lista (máximo 3)
            }
            if (! Schema::hasColumn('lpchat_miembros', 'vaciado_id')) {
                $t->unsignedBigInteger('vaciado_id')->default(0); // "Vaciar chat": no se muestran los mensajes hasta este id
            }
        });
        if (! Schema::hasTable('lpchat_listas')) {
            Schema::create('lpchat_listas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('nombre', 40);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('lpchat_lista_chats')) {
            Schema::create('lpchat_lista_chats', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('lista_id');
                $t->unsignedBigInteger('chat_id');
                $t->unique(['lista_id', 'chat_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_lista_chats');
        Schema::dropIfExists('lpchat_listas');
    }
};
