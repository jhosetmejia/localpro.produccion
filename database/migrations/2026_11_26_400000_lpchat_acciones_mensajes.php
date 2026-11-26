<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2026-11-26 (Jhoset): LP Chat como WhatsApp: flechita y carita en cada mensaje con su menú
// (info, responder, copiar, reaccionar, reenviar, difusión, fijar, destacar, editar, añadir a la nota y eliminar).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lpchat_mensajes', function (Blueprint $t) {
            if (! Schema::hasColumn('lpchat_mensajes', 'responde_id')) {
                $t->unsignedBigInteger('responde_id')->nullable(); // mensaje al que responde
            }
            if (! Schema::hasColumn('lpchat_mensajes', 'reenviado')) {
                $t->boolean('reenviado')->default(false);
            }
            if (! Schema::hasColumn('lpchat_mensajes', 'editado_at')) {
                $t->timestamp('editado_at')->nullable();
            }
            if (! Schema::hasColumn('lpchat_mensajes', 'cambio_at')) {
                // Cuándo cambió (reacción, edición, borrado): así los demás lo ven sin recargar.
                $t->timestamp('cambio_at')->nullable();
                $t->index(['chat_id', 'cambio_at']);
            }
        });
        Schema::table('lpchat_chats', function (Blueprint $t) {
            if (! Schema::hasColumn('lpchat_chats', 'fijado_id')) {
                $t->unsignedBigInteger('fijado_id')->nullable();
            }
        });
        Schema::table('lpchat_miembros', function (Blueprint $t) {
            if (! Schema::hasColumn('lpchat_miembros', 'nota')) {
                $t->text('nota')->nullable(); // nota privada de cada persona sobre el chat (cifrada)
            }
        });
        if (! Schema::hasTable('lpchat_reacciones')) {
            Schema::create('lpchat_reacciones', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('mensaje_id');
                $t->unsignedBigInteger('chat_id');
                $t->unsignedBigInteger('usuario_id');
                $t->string('emoji', 32);
                $t->timestamps();
                $t->unique(['mensaje_id', 'usuario_id']);
                $t->index('chat_id');
            });
        }
        if (! Schema::hasTable('lpchat_destacados')) {
            Schema::create('lpchat_destacados', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id');
                $t->unsignedBigInteger('mensaje_id');
                $t->unsignedBigInteger('chat_id');
                $t->timestamp('created_at')->nullable();
                $t->unique(['usuario_id', 'mensaje_id']);
                $t->index(['usuario_id', 'chat_id']);
            });
        }
        if (! Schema::hasTable('lpchat_ocultos')) {
            // "Eliminar para mí"
            Schema::create('lpchat_ocultos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id');
                $t->unsignedBigInteger('mensaje_id');
                $t->unsignedBigInteger('chat_id');
                $t->timestamp('created_at')->nullable();
                $t->unique(['usuario_id', 'mensaje_id']);
                $t->index(['usuario_id', 'chat_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_ocultos');
        Schema::dropIfExists('lpchat_destacados');
        Schema::dropIfExists('lpchat_reacciones');
    }
};
