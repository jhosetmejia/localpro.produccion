<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Enlace de Google Maps del lugar del evento: en la página sale el botón "Cómo llegar"
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eventos') && !Schema::hasColumn('eventos', 'mapa_url')) {
            Schema::table('eventos', fn (Blueprint $t) => $t->string('mapa_url', 500)->nullable()->after('ciudad'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eventos', 'mapa_url')) Schema::table('eventos', fn (Blueprint $t) => $t->dropColumn('mapa_url'));
    }
};
