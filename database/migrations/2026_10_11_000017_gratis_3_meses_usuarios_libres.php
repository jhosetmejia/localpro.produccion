<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Plan Gratis: usuarios ilimitados durante 3 meses; al terminar, el negocio paga para seguir.
// A las tiendas que ya estaban en Gratis sin fecha se les dan 3 meses desde hoy.
return new class extends Migration
{
    public function up(): void
    {
        $gratis = DB::table('planes')->where('precio', 0)->whereNull('dias')->orderBy('orden')->first();
        if (!$gratis) return;
        DB::table('planes')->where('id', $gratis->id)->update([
            'max_usuarios' => null,
            'descripcion' => '3 meses gratis para empezar a vender. Usuarios ilimitados.',
            'updated_at' => now(),
        ]);
        DB::table('tiendas')->where('plan_id', $gratis->id)->whereNull('plan_vence')
            ->update(['plan_vence' => today()->addDays(89), 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
