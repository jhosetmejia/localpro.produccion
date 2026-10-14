<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alquiler: lo que se le paga al encargado por el turno (se descuenta de la ganancia del local)
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alquileres', 'encargado_valor')) return;
        Schema::table('alquileres', fn (Blueprint $t) => $t->decimal('encargado_valor', 14, 2)->default(0)->after('encargado_telefono'));
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn('encargado_valor'));
    }
};
