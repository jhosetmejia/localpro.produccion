<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Contactar clientes nuevos": el negocio puede compartir su base de datos con sus asesores (cada número se escribe una sola vez)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'compartir_asesores')) $t->boolean('compartir_asesores')->default(false)->index();
            // null = todos los asesores (solo el súper admin); si no, el asesor dueño del negocio: se comparte con la gente de su red
            if (!Schema::hasColumn('tiendas', 'compartir_asesor_id')) $t->foreignId('compartir_asesor_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['compartir_asesores', 'compartir_asesor_id']));
    }
};
