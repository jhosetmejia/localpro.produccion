<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Diseño "Ninguna (solo mi imagen)": cada bloque (foto, nombre, datos…) se ubica a mano sobre la imagen de la funeraria
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas') || Schema::hasColumn('obituario_plantillas', 'libre')) return;
        Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->json('libre')->nullable());
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'libre')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn('libre'));
    }
};
