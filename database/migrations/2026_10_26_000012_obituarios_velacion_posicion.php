<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dónde sale el lugar de velación en la tarjeta: sobre la foto (etiqueta, por defecto), debajo de la foto o con los datos;
// y se puede mover un poco a cualquier lado.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas') || Schema::hasColumn('obituario_plantillas', 'velacion_pos')) return;
        Schema::table('obituario_plantillas', function (Blueprint $t) {
            $t->string('velacion_pos', 10)->default('foto');
            $t->smallInteger('velacion_x')->default(0);
            $t->smallInteger('velacion_y')->default(0);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'velacion_pos')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn(['velacion_pos', 'velacion_x', 'velacion_y']));
    }
};
