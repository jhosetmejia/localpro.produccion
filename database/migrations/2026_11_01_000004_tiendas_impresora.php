<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajustes de la impresora de tiquetes (papel 58/80 mm, ancho que imprime, margen, letra y espacio para el corte)
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tiendas') && !Schema::hasColumn('tiendas', 'impresora')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->json('impresora')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'impresora')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('impresora'));
        }
    }
};
