<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alquiler: hora de finalización del servicio (la hora que ya existía es la de inicio)
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alquileres', 'hora_fin')) return;
        Schema::table('alquileres', fn (Blueprint $t) => $t->time('hora_fin')->nullable()->after('hora'));
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn('hora_fin'));
    }
};
