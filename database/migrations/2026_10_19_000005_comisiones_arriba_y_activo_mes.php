<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Nuevas comisiones (19/10/2026):
// Venta nueva: asesor 100.000 + 10.000 al que está arriba (quien lo sumó); los líderes ya no ganan en la venta nueva.
// Renovación: asesor 10.000 de por vida y cada líder 10.000. Venezuela en dólares: 25 + 2,50 · renovación 2,50.
return new class extends Migration
{
    public function up(): void
    {
        $valores = [
            'red_nueva_venta' => 100000, 'red_nueva_arriba' => 10000,
            'red_nueva_zona' => 0, 'red_nueva_ciudad' => 0, 'red_nueva_departamento' => 0, 'red_nueva_pais' => 0,
            'red_renov_venta' => 10000, 'red_renov_arriba' => 0,
            'red_renov_zona' => 10000, 'red_renov_ciudad' => 10000, 'red_renov_departamento' => 10000, 'red_renov_pais' => 10000,
            'red_usd_nueva_venta' => 25, 'red_usd_nueva_arriba' => 2.5,
            'red_usd_nueva_zona' => 0, 'red_usd_nueva_ciudad' => 0, 'red_usd_nueva_departamento' => 0, 'red_usd_nueva_pais' => 0,
            'red_usd_renov_venta' => 2.5, 'red_usd_renov_arriba' => 0,
            'red_usd_renov_zona' => 2.5, 'red_usd_renov_ciudad' => 2.5, 'red_usd_renov_departamento' => 2.5, 'red_usd_renov_pais' => 2.5,
        ];
        foreach ($valores as $clave => $valor) {
            DB::table('ajustes')->updateOrInsert(['clave' => $clave], ['valor' => (string) $valor, 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public function down(): void
    {
    }
};
