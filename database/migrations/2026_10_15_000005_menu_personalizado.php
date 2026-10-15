<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Personalizar menú: cada negocio esconde las secciones que no usa (ej. una funeraria no necesita "Separados")
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'menu_oculto')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->json('menu_oculto')->nullable());
        }
        // Las funerarias que ya existen arrancan con lo sugerido
        DB::table('tiendas')->where('tipo_negocio', 'funeraria')->whereNull('menu_oculto')
            ->update(['menu_oculto' => json_encode(\App\Models\Tienda::MENU_OCULTO_POR_TIPO['funeraria'])]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'menu_oculto')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('menu_oculto'));
        }
    }
};
