<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Todos los diseños: mover y agrandar a mano cada elemento (logo, foto, nombre, datos…) sobre el diseño
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas') || Schema::hasColumn('obituario_plantillas', 'mover')) return;
        Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->json('mover')->nullable());
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'mover')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn('mover'));
    }
};
