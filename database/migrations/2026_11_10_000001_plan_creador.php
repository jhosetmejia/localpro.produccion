<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Plan para cuentas de Creador de contenido (llegan desde LocalProCut): $10.000 al mes.
// Solo lo ven y pagan los creadores; los negocios siguen viendo sus planes de siempre.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('planes')->where('nombre', 'Creador mensual')->exists()) return;
        DB::table('planes')->insert([
            'nombre' => 'Creador mensual', 'precio' => 10000, 'dias' => 30, 'max_usuarios' => 1, 'max_productos' => null,
            'descripcion' => 'Para creadores de contenido: publicar y programar en Facebook, Instagram y YouTube, LocalProCut y Mis enlaces.',
            'activo' => true, 'orden' => 90, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('planes')->where('nombre', 'Creador mensual')->whereNotIn('id', DB::table('tiendas')->whereNotNull('plan_id')->select('plan_id'))->delete();
    }
};
