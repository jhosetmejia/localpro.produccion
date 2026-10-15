<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Plantillas de obituario: dónde van las flores junto a la foto (posición, X/Y, tamaño)
// y cuánta animación lleva (cantidad, tamaño y velocidad de pétalos, destellos…)
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituario_plantillas', 'flores')) {
            Schema::table('obituario_plantillas', function (Blueprint $t) {
                $t->json('flores')->nullable();   // flores junto a la foto: pos, x, y, tam
                $t->json('anim_op')->nullable();  // animación: cant, tam, vel (%)
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'flores')) {
            Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn(['flores', 'anim_op']));
        }
    }
};
