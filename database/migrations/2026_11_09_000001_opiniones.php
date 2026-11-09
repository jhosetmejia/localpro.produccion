<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Retroalimentación de la gente: botón "Opinar / reportar falla" en todo LocalPro (panel y LocalProCut)
// + errores del navegador que se reportan solos (agrupados por firma para no llenar la base).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('opiniones')) {
            Schema::create('opiniones', function (Blueprint $t) {
                $t->id();
                $t->string('tipo', 12)->default('falla');          // falla | idea | otro
                $t->text('mensaje');
                $t->string('modulo', 40)->nullable()->index();      // cut, panel, pos, ...
                $t->string('pagina', 300)->nullable();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->unsignedBigInteger('tienda_id')->nullable()->index();
                $t->string('contacto', 120)->nullable();            // WhatsApp o correo (si no tiene sesión)
                $t->string('foto', 200)->nullable();                // captura de pantalla (disco privado)
                $t->json('datos')->nullable();                      // navegador, pantalla, errores recientes, estado del editor
                $t->string('estado', 12)->default('nuevo')->index(); // nuevo | revisando | resuelto | descartado
                $t->text('respuesta')->nullable();                  // lo que le contestamos (la persona lo ve en "Mis reportes")
                $t->timestamp('respondido_at')->nullable();
                $t->string('ip_hash', 64)->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('errores_cliente')) {
            Schema::create('errores_cliente', function (Blueprint $t) {
                $t->id();
                $t->string('firma', 40)->unique();                  // sha1 de mensaje + archivo + línea
                $t->string('modulo', 40)->nullable()->index();
                $t->string('mensaje', 500);
                $t->string('donde', 300)->nullable();               // archivo:línea
                $t->text('pila')->nullable();
                $t->string('pagina', 300)->nullable();
                $t->string('navegador', 300)->nullable();
                $t->json('datos')->nullable();                      // último contexto que llegó
                $t->unsignedInteger('veces')->default(1);
                $t->unsignedInteger('usuarios')->default(1);        // aproximado (personas distintas)
                $t->boolean('resuelto')->default(false)->index();
                $t->timestamp('primera_vez')->nullable();
                $t->timestamp('ultima_vez')->nullable()->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('errores_cliente');
        Schema::dropIfExists('opiniones');
    }
};
