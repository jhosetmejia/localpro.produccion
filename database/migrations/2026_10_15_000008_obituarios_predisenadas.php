<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tarjetas prediseñadas de obituario: frase destacada (cita), de qué prediseño viene cada plantilla
// y si el negocio ya recibió las 5 plantillas listas para usar
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituario_plantillas', 'cita')) {
            Schema::table('obituario_plantillas', function (Blueprint $t) {
                $t->string('cita', 160)->nullable();
                $t->string('base', 20)->nullable();
            });
        }
        if (!Schema::hasColumn('tiendas', 'obituarios_predisenadas')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('obituarios_predisenadas')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'cita')) {
            Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn(['cita', 'base']));
        }
        if (Schema::hasColumn('tiendas', 'obituarios_predisenadas')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('obituarios_predisenadas'));
        }
    }
};
