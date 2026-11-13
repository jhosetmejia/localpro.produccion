<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-13 (Jhoset): el asistente de la pagina aprende. Se guarda cada pregunta y respuesta para
 * evaluarlas (bien/mal), las que no supo responder quedan pendientes para que el admin las conteste,
 * y esas respuestas (conocimiento) se le dan a la IA en adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asistente_mensajes')) {
            Schema::create('asistente_mensajes', function (Blueprint $t) {
                $t->id();
                $t->string('sesion', 40)->nullable()->index();
                $t->text('pregunta');
                $t->text('respuesta')->nullable();
                $t->boolean('sin_respuesta')->default(false)->index();
                $t->boolean('resuelta')->default(false);
                $t->string('calificacion', 10)->nullable(); // bien | mal
                $t->string('pagina', 255)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('asistente_conocimiento')) {
            Schema::create('asistente_conocimiento', function (Blueprint $t) {
                $t->id();
                $t->text('pregunta');
                $t->text('respuesta');
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistente_mensajes');
        Schema::dropIfExists('asistente_conocimiento');
    }
};
