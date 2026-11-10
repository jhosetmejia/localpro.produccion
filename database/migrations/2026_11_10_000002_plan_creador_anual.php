<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Creadores de contenido: además del mensual ($10.000), el anual de $80.000. Solo lo ven y pagan los creadores.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('planes')->where('nombre', 'Creador anual')->exists()) return;
        DB::table('planes')->insert([
            'nombre' => 'Creador anual', 'precio' => 80000, 'dias' => 365, 'max_usuarios' => 1, 'max_productos' => null,
            'descripcion' => 'Para creadores de contenido, todo el año: publicar y programar en Facebook, Instagram y YouTube, LocalProCut y Mis enlaces.',
            'activo' => true, 'orden' => 91, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('planes')->where('nombre', 'Creador anual')->whereNotIn('id', DB::table('tiendas')->whereNotNull('plan_id')->select('plan_id'))->delete();
    }
};
