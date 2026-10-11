<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cada tienda decide si en la caja es obligatorio poner el cliente para cobrar
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'cliente_obligatorio')) {
            Schema::table('tiendas', function (Blueprint $table) {
                $table->boolean('cliente_obligatorio')->default(false)->after('catalogo_activo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('cliente_obligatorio'));
    }
};
