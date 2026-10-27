<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Logo de los obituarios: igual en todas las plantillas. Por defecto sin fondo (un PNG sin fondo se ve tal cual);
// se puede elegir ponerlo sobre un recuadro blanco.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas') || Schema::hasColumn('obituario_plantillas', 'logo_fondo')) return;
        Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->string('logo_fondo', 10)->default('sin')->after('mostrar_logo'));
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'logo_fondo')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn('logo_fondo'));
    }
};
