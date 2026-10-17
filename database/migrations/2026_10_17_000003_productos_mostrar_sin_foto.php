<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Productos sin foto: por defecto no salen en el catálogo en línea, salvo que el negocio marque "mostrar aunque no tenga foto".
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'mostrar_sin_foto')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->boolean('mostrar_sin_foto')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'mostrar_sin_foto')) {
            Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('mostrar_sin_foto'));
        }
    }
};
