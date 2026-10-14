<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alquiler: encargado opcional de nuestro lado (quien presta el servicio), aparte del cliente que contrata
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alquileres', 'encargado_nombre')) return;
        Schema::table('alquileres', function (Blueprint $t) {
            $t->string('encargado_nombre', 80)->nullable()->after('responsable_telefono');
            $t->string('encargado_telefono', 20)->nullable()->after('encargado_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn(['encargado_nombre', 'encargado_telefono']));
    }
};
