<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Evento 360 de cada alquiler: logos, canción y enlace/QR para que los invitados graben con eso ya puesto
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alquileres', function (Blueprint $t) {
            if (!Schema::hasColumn('alquileres', 'e360_token')) {
                $t->string('e360_token', 16)->nullable()->unique();
                $t->json('e360')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn(['e360_token', 'e360']));
    }
};
