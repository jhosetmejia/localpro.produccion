<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Qué hace el negocio: vende, alquila (solo alquiler) o vende y alquila. Es un ajuste encima del tipo de negocio:
// "solo alquila" va directo a Alquileres y esconde la caja de venta y el inventario, sin borrar nada.
// Vacío = como antes (vende, o vende y alquila si tiene el módulo de alquiler encendido).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'actividad')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('actividad', 10)->nullable()->after('tipo_negocio'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'actividad')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('actividad'));
    }
};
