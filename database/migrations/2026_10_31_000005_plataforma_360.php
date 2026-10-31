<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Negocios que alquilan plataforma 360: módulo propio (logos y canciones en su cuenta) y sus clientes del 360
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mod_360')) $t->boolean('mod_360')->default(false)->after('mod_alquiler');
        });
        Schema::table('giro360_clientes', function (Blueprint $t) {
            if (!Schema::hasColumn('giro360_clientes', 'tienda_id')) $t->unsignedBigInteger('tienda_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_360'));
        Schema::table('giro360_clientes', fn (Blueprint $t) => $t->dropColumn('tienda_id'));
    }
};
