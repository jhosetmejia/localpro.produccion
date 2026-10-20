<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Mis contactos" del asesor: un líder puede compartir su base con los asesores que tiene en su red (cada número una sola vez)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesores', function (Blueprint $t) {
            if (!Schema::hasColumn('asesores', 'compartir_contactos')) $t->boolean('compartir_contactos')->default(false);
        });
        Schema::table('asesor_contactos', function (Blueprint $t) {
            if (!Schema::hasColumn('asesor_contactos', 'asignado_a')) $t->foreignId('asignado_a')->nullable()->index();
            if (!Schema::hasColumn('asesor_contactos', 'asignado_fecha')) $t->date('asignado_fecha')->nullable()->index();
            if (!Schema::hasColumn('asesor_contactos', 'contactado_por')) $t->foreignId('contactado_por')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asesores', fn (Blueprint $t) => $t->dropColumn('compartir_contactos'));
        Schema::table('asesor_contactos', fn (Blueprint $t) => $t->dropColumn(['asignado_a', 'asignado_fecha', 'contactado_por']));
    }
};
