<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ubicación desde donde se registra el invitado del 360 (para la base de clientes)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giro360_clientes', function (Blueprint $t) {
            if (!Schema::hasColumn('giro360_clientes', 'latitud')) {
                $t->decimal('latitud', 10, 7)->nullable()->after('ip');
                $t->decimal('longitud', 10, 7)->nullable()->after('latitud');
                $t->unsignedInteger('precision_m')->nullable()->after('longitud');
            }
        });
    }

    public function down(): void
    {
        Schema::table('giro360_clientes', function (Blueprint $t) {
            $t->dropColumn(['latitud', 'longitud', 'precision_m']);
        });
    }
};
