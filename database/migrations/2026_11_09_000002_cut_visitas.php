<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LocalProCut: cuántas visitas tiene, desde dónde (ciudad aproximada por la IP), en qué equipo y qué hace cada persona
// (agregar videos, exportar, compartir, instalar la app, registrarse). Admin → LocalProCut.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cut_visitas')) {
            Schema::create('cut_visitas', function (Blueprint $t) {
                $t->id();
                $t->string('visitante', 32)->index();             // id anónimo guardado en el navegador (nuevo vs. vuelve)
                $t->boolean('nuevo')->default(true);
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('ip', 45)->nullable();                 // se borra apenas se ubica la ciudad (queda solo el hash)
                $t->string('ip_hash', 64)->nullable()->index();
                $t->boolean('geo_consultada')->default(false)->index();
                $t->string('pais', 60)->nullable();
                $t->string('pais_codigo', 4)->nullable();
                $t->string('region', 80)->nullable();
                $t->string('ciudad', 80)->nullable();
                $t->string('operador', 120)->nullable();
                $t->boolean('datos_moviles')->default(false);
                $t->boolean('vpn')->default(false);
                $t->string('dispositivo', 20)->nullable();        // Celular | Tablet | Computador
                $t->string('sistema', 30)->nullable();
                $t->string('navegador', 30)->nullable();
                $t->string('app_origen', 30)->nullable();         // abrió desde Instagram, TikTok, Facebook…
                $t->string('marca', 30)->nullable();
                $t->boolean('es_app')->default(false);            // abierta desde el ícono instalado
                $t->string('pantalla', 40)->nullable();
                $t->string('idioma', 20)->nullable();
                $t->string('referencia', 120)->nullable();        // de qué página llegó (dominio)
                $t->string('origen', 60)->nullable()->index();    // ?ref= (wa, compartido, menu, blog…)
                $t->string('invito', 32)->nullable()->index();    // visitante que le compartió el enlace
                $t->string('ultimo_evento', 40)->nullable();
                $t->unsignedInteger('segundos')->default(0);      // tiempo con la pantalla abierta
                $t->unsignedSmallInteger('videos')->default(0);
                $t->unsignedSmallInteger('exportados')->default(0);
                $t->unsignedSmallInteger('compartidos')->default(0);
                $t->boolean('clic_registro')->default(false);
                $t->boolean('clic_app')->default(false);
                $t->boolean('instalo')->default(false);
                $t->timestamp('ultima_vez')->nullable()->index();
                $t->timestamps();
                $t->index('created_at');
            });
        }
        if (!Schema::hasTable('cut_eventos')) {
            Schema::create('cut_eventos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('visita_id')->constrained('cut_visitas')->cascadeOnDelete();
                $t->string('tipo', 40)->index();
                $t->json('datos')->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_eventos');
        Schema::dropIfExists('cut_visitas');
    }
};
