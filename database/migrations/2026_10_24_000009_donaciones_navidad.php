<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Donación opcional al pagar el plan: "Dona $5.000 para los niños más necesitados de Pasto en diciembre".
// Va aparte del valor del plan (no cuenta para comisiones ni reportes de ventas).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_suscripcion', function (Blueprint $t) {
            if (!Schema::hasColumn('pagos_suscripcion', 'donacion')) $t->decimal('donacion', 12, 2)->nullable();
        });
        foreach (['donacion_activa' => '1', 'donacion_monto' => '5000', 'donacion_marcada' => '0'] as $k => $v) {
            if (!DB::table('ajustes')->where('clave', $k)->exists()) {
                DB::table('ajustes')->insert(['clave' => $k, 'valor' => $v, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pagos_suscripcion', fn (Blueprint $t) => $t->dropColumn('donacion'));
    }
};
