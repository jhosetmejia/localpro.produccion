<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Visitas de todo LocalProo (panel y páginas públicas): quién entra, desde dónde, en qué equipo y qué páginas recorre.
// Admin → Visitas y uso (pestaña "Todo el sistema"; LocalProCut tiene la suya).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visitas_sitio')) {
            Schema::create('visitas_sitio', function (Blueprint $t) {
                $t->id();
                $t->string('visitante', 32)->index();           // cookie anónima del navegador (nuevo vs. vuelve)
                $t->boolean('nuevo')->default(true);
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->unsignedBigInteger('tienda_id')->nullable()->index();
                $t->string('ip', 45)->nullable();               // se borra apenas se ubica la ciudad
                $t->boolean('geo_consultada')->default(false)->index();
                $t->string('pais', 60)->nullable();
                $t->string('pais_codigo', 4)->nullable();
                $t->string('region', 80)->nullable();
                $t->string('ciudad', 80)->nullable();
                $t->string('operador', 120)->nullable();
                $t->boolean('datos_moviles')->default(false);
                $t->boolean('vpn')->default(false);
                $t->string('dispositivo', 20)->nullable();
                $t->string('sistema', 30)->nullable();
                $t->string('navegador', 30)->nullable();
                $t->string('app_origen', 30)->nullable();       // abrió el enlace desde Instagram, TikTok, Facebook…
                $t->string('marca', 30)->nullable();
                $t->boolean('es_app')->default(false);          // desde la app instalada (?app=1)
                $t->string('referencia', 120)->nullable();      // dominio de donde llegó
                $t->string('origen', 60)->nullable();           // utm_source / ref / desde
                $t->string('entrada', 200)->nullable();         // primera página
                $t->string('ultima_ruta', 80)->nullable();
                $t->string('ultima_pagina', 200)->nullable();
                $t->unsignedInteger('paginas')->default(0);
                $t->timestamp('ultima_vez')->nullable()->index();
                $t->timestamps();
                $t->index('created_at');
            });
        }
        if (!Schema::hasTable('visitas_paginas')) {
            Schema::create('visitas_paginas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('visita_id')->constrained('visitas_sitio')->cascadeOnDelete();
                $t->string('ruta', 80)->nullable()->index();
                $t->string('pagina', 200);
                $t->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_paginas');
        Schema::dropIfExists('visitas_sitio');
    }
};
