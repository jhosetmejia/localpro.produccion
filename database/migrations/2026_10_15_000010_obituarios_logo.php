<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Logo propio para los obituarios (PNG sin fondo); si no hay, se usa el logo del negocio
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'obituarios_logo_path')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('obituarios_logo_path')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'obituarios_logo_path')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('obituarios_logo_path'));
        }
    }
};
