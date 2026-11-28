<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2026-11-28 (Jhoset): como WhatsApp al seleccionar un mensaje: responder, reaccionar, eliminar (para mí / para todos),
// copiar, reenviar e info.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lpchat_mensajes', function (Blueprint $t) {
            if (! Schema::hasColumn('lpchat_mensajes', 'responde_id')) {
                $t->unsignedBigInteger('responde_id')->nullable();
            }
            if (! Schema::hasColumn('lpchat_mensajes', 'reenviado')) {
                $t->boolean('reenviado')->default(false);
            }
            if (! Schema::hasColumn('lpchat_mensajes', 'eliminado_at')) {
                $t->timestamp('eliminado_at')->nullable();
            }
        });
        if (! Schema::hasTable('lpchat_reacciones')) {
            Schema::create('lpchat_reacciones', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('chat_id')->index();
                $t->unsignedBigInteger('mensaje_id');
                $t->unsignedBigInteger('usuario_id');
                $t->string('emoji', 32);
                $t->timestamps();
                $t->unique(['mensaje_id', 'usuario_id']);
            });
        }
        if (! Schema::hasTable('lpchat_ocultos')) {
            // "Eliminar para mí"
            Schema::create('lpchat_ocultos', function (Blueprint $t) {
                $t->unsignedBigInteger('mensaje_id');
                $t->unsignedBigInteger('usuario_id');
                $t->primary(['usuario_id', 'mensaje_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_ocultos');
        Schema::dropIfExists('lpchat_reacciones');
    }
};
