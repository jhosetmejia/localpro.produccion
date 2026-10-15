<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mis enlaces: página tipo Linktree de cada negocio (localproo.com/@negocio) con sus enlaces, redes y estadísticas
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'enlaces_activo')) {
                $t->boolean('enlaces_activo')->default(false);
                $t->string('enlaces_bio', 200)->nullable();
                $t->string('enlaces_tema', 20)->default('localproo');
                $t->string('enlaces_boton', 20)->default('pildora');
                $t->string('enlaces_foto_path')->nullable();
                $t->json('enlaces_redes')->nullable();
                $t->boolean('enlaces_catalogo')->default(true); // botón automático "Ver nuestra tienda en línea"
                $t->unsignedInteger('enlaces_visitas')->default(0);
            }
        });

        if (!Schema::hasTable('enlaces')) {
            Schema::create('enlaces', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('tipo', 10)->default('enlace'); // enlace · titulo (separador)
                $t->string('titulo', 80);
                $t->string('url', 500)->nullable();
                $t->string('icono', 40)->nullable();       // vacío = se detecta por la dirección
                $t->boolean('destacado')->default(false);  // se mueve para llamar la atención
                $t->boolean('activo')->default(true);
                $t->dateTime('desde')->nullable();         // programado: aparece desde / hasta
                $t->dateTime('hasta')->nullable();
                $t->unsignedInteger('orden')->default(0);
                $t->unsignedInteger('clics')->default(0);
                $t->timestamps();
                $t->index(['tienda_id', 'orden']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enlaces');
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['enlaces_activo', 'enlaces_bio', 'enlaces_tema', 'enlaces_boton', 'enlaces_foto_path', 'enlaces_redes', 'enlaces_catalogo', 'enlaces_visitas']));
    }
};
