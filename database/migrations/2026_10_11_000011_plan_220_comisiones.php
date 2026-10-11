<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Plan anual con asesor a 220.000: asesor 100.000 · 5.000 a cada cargo de arriba (zona, ciudad, depto, país) · LocalPro 100.000
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();
        foreach ([
            'red_valor_plan' => 220000, 'red_nueva_venta' => 100000,
            'red_nueva_zona' => 5000, 'red_nueva_ciudad' => 5000, 'red_nueva_departamento' => 5000, 'red_nueva_pais' => 5000,
            'red_salario_minimo' => 1750000,
        ] as $k => $v) {
            DB::table('ajustes')->updateOrInsert(['clave' => $k], ['valor' => $v, 'updated_at' => $ahora, 'created_at' => $ahora]);
        }
        DB::table('planes')->where('nombre', 'Anual')->where('dias', 365)->update(['precio' => 220000, 'updated_at' => $ahora]);
    }

    public function down(): void
    {
    }
};
