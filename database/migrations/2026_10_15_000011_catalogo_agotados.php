<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tienda en línea: el dueño elige si se muestran los productos agotados (sin existencias) o solo los que hay
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'catalogo_agotados')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('catalogo_agotados')->default(true));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'catalogo_agotados')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('catalogo_agotados'));
        }
    }
};
