<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Visitas de la tienda en línea (se muestran discretas en el pie del catálogo y en el panel del dueño)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'catalogo_visitas')) $t->unsignedInteger('catalogo_visitas')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('catalogo_visitas'));
    }
};
