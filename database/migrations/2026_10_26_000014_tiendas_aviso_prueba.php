<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuando el administrador cambia el tiempo de prueba gratis de una tienda, el dueño ve un aviso en su panel
// ("Su prueba gratis es de 1 mes: tiene hasta el …") hasta que toque "Entendido".
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tiendas') || Schema::hasColumn('tiendas', 'aviso_prueba')) return;
        Schema::table('tiendas', fn (Blueprint $t) => $t->string('aviso_prueba', 60)->nullable());
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'aviso_prueba')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('aviso_prueba'));
    }
};
