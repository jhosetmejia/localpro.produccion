<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Activar / desactivar un producto para la venta (ej. "se acabaron los almuerzos de hoy") sin ocultarlo del sistema,
// y el diseño elegido para la carta virtual de restaurantes y cafeterías.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'disponible')) {
            Schema::table('productos', fn (Blueprint $t) => $t->boolean('disponible')->default(true)->after('estado'));
        }
        if (!Schema::hasColumn('tiendas', 'carta_tema')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('carta_tema', 20)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'disponible')) Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('disponible'));
        if (Schema::hasColumn('tiendas', 'carta_tema')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('carta_tema'));
    }
};
