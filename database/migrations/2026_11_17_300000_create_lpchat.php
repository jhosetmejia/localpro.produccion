<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-17 (Jhoset): LP Chat (LocalProChat), la red social de LocalProo tipo WhatsApp.
 * Cuentas propias (se entra con correo o celular), chats, grupos, solicitudes (como Instagram),
 * "Conocer gente" y link personal para compartir. Otros sistemas (PFN, NES) se conectan con un enlace firmado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_usuarios')) {
            Schema::create('lpchat_usuarios', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 80);
                $t->string('email', 150)->nullable()->unique();
                $t->string('celular', 20)->nullable()->unique();
                $t->string('password')->nullable(); // null = cuenta que solo entra desde PFN/NES
                $t->string('foto', 255)->nullable();
                $t->string('bio', 160)->nullable();
                $t->string('ciudad', 60)->nullable();
                $t->string('etiqueta', 60)->nullable(); // "Peleador PFN · MMA", "Negocio en LocalProo"...
                $t->boolean('conocer')->default(true); // sale en "Conocer gente"
                $t->string('codigo', 12)->unique(); // link personal: /chat/u/{codigo}
                $t->unsignedBigInteger('user_id')->nullable()->index(); // usuario de LocalProo, si lo es
                $t->unsignedBigInteger('tienda_id')->nullable()->unique(); // cuenta de EMPRESA (negocio de LocalProo)
                $t->string('origen', 20)->nullable()->index(); // pfn | nes | localproo (para filtrar en Conocer gente)
                $t->timestamp('visto_at')->nullable()->index();
                $t->rememberToken();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('lpchat_vinculos')) {
            // De qué sistema viene la cuenta: pfn:u15, nes:42...
            Schema::create('lpchat_vinculos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('origen', 20);
                $t->string('origen_id', 40);
                $t->timestamps();
                $t->unique(['origen', 'origen_id']);
            });
        }
        if (! Schema::hasTable('lpchat_chats')) {
            Schema::create('lpchat_chats', function (Blueprint $t) {
                $t->id();
                $t->string('tipo', 10)->default('directo'); // directo | grupo
                $t->string('via', 12)->default('link'); // link | contacto | conocer | empresa (los de conocer cuentan para el límite diario)
                $t->string('clave', 50)->nullable()->unique(); // directos: d:{menor}:{mayor}
                $t->string('nombre', 80)->nullable();
                $t->string('foto', 255)->nullable();
                $t->string('descripcion', 200)->nullable();
                $t->string('codigo', 12)->nullable()->unique(); // link para unirse al grupo
                $t->unsignedBigInteger('creador_id')->nullable();
                $t->unsignedBigInteger('ultimo_id')->default(0)->index();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('lpchat_miembros')) {
            Schema::create('lpchat_miembros', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('chat_id');
                $t->unsignedBigInteger('usuario_id');
                $t->string('rol', 10)->default('miembro'); // admin | miembro
                $t->string('estado', 12)->default('activo'); // activo | solicitud | descartado | salio
                $t->string('seccion', 8)->default('mis'); // mis = "Mis chats" (amigos: link, correo/celular, grupos) | general (Conocer gente y Empresas)
                $t->unsignedBigInteger('leido_id')->default(0);
                $t->timestamps();
                $t->unique(['chat_id', 'usuario_id']);
                $t->index(['usuario_id', 'estado']);
            });
        }
        if (! Schema::hasTable('lpchat_mensajes')) {
            Schema::create('lpchat_mensajes', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('chat_id');
                $t->unsignedBigInteger('usuario_id')->nullable(); // null = aviso del sistema
                $t->text('texto')->nullable();
                $t->string('adjunto', 255)->nullable();
                $t->string('adjunto_tipo', 8)->nullable(); // foto | video | audio
                $t->boolean('una_vez')->default(false); // ver una sola vez (foto, video o audio)
                $t->timestamp('abierto_at')->nullable(); // cuándo se abrió el "ver una vez" (ya no se puede ver)
                $t->string('tipo', 12)->nullable(); // sistema
                $t->timestamps();
                $t->index(['chat_id', 'id']);
            });
        }
        if (! Schema::hasTable('lpchat_estados')) {
            // Estados / historias (duran 24 horas)
            Schema::create('lpchat_estados', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('tipo', 8); // foto | video | texto
                $t->string('archivo', 255)->nullable();
                $t->string('texto', 700)->nullable();
                $t->string('color', 9)->nullable(); // fondo de los estados de texto
                $t->unsignedInteger('vistas')->default(0);
                $t->timestamps();
                $t->index('created_at');
            });
        }
        if (! Schema::hasTable('lpchat_estados_vistos')) {
            Schema::create('lpchat_estados_vistos', function (Blueprint $t) {
                $t->unsignedBigInteger('estado_id');
                $t->unsignedBigInteger('usuario_id');
                $t->timestamp('created_at')->nullable();
                $t->primary(['estado_id', 'usuario_id']);
            });
        }
        if (! Schema::hasTable('lpchat_bloqueos')) {
            Schema::create('lpchat_bloqueos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id');
                $t->unsignedBigInteger('bloqueado_id');
                $t->string('motivo', 300)->nullable(); // si lo denunció
                $t->boolean('denuncia')->default(false);
                $t->timestamps();
                $t->unique(['usuario_id', 'bloqueado_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['lpchat_estados_vistos', 'lpchat_estados', 'lpchat_bloqueos', 'lpchat_mensajes', 'lpchat_miembros', 'lpchat_chats', 'lpchat_vinculos', 'lpchat_usuarios'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
