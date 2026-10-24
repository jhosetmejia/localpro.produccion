<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eventos: contador de visitas de la página del evento (se puede mostrar u ocultar al público)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $t) {
            if (!Schema::hasColumn('eventos', 'visitas')) $t->unsignedInteger('visitas')->default(0);
            if (!Schema::hasColumn('eventos', 'mostrar_visitas')) $t->boolean('mostrar_visitas')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $t) {
            $t->dropColumn(['visitas', 'mostrar_visitas']);
        });
    }
};
