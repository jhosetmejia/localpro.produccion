<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Plan Gratis sin límite de productos (durante los 3 meses de prueba). En el panel se guía al negocio con 3 pasos para vender más.
return new class extends Migration
{
    public function up(): void
    {
        // Metas de los vendedores: por dinero vendido o por cantidad de ventas
        if (!Schema::hasColumn('users', 'meta_tipo')) {
            Schema::table('users', fn (Blueprint $t) => $t->string('meta_tipo', 8)->default('dinero'));
        }
        DB::table('planes')->where('precio', 0)->whereNull('dias')->update([
            'max_productos' => null, 'max_usuarios' => null,
            'descripcion' => '3 meses gratis con todo: productos y usuarios ilimitados.', 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
    }
};
