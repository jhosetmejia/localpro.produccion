<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El negocio puede dejar un producto solo para la caja (no sale en el catálogo en línea)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $t) {
            if (!Schema::hasColumn('productos', 'ocultar_catalogo')) $t->boolean('ocultar_catalogo')->default(false)->after('mostrar_sin_foto');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $t) {
            $t->dropColumn('ocultar_catalogo');
        });
    }
};
