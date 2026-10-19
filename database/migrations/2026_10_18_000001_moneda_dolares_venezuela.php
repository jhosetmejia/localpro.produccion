<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Venezuela en dólares: precio del plan en USD, moneda en cada pago y en cada comisión
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('planes', 'precio_usd')) {
            Schema::table('planes', function (Blueprint $t) {
                $t->decimal('precio_usd', 12, 2)->nullable()->after('precio'); // lo que paga un negocio de Venezuela
            });
        }
        if (!Schema::hasColumn('pagos_suscripcion', 'moneda')) {
            Schema::table('pagos_suscripcion', function (Blueprint $t) {
                $t->string('moneda', 3)->default('COP')->after('monto');
                $t->decimal('cobro_cop', 14, 2)->nullable()->after('moneda'); // lo que cobra Wompi en pesos (si el pago es en USD)
            });
        }
        if (!Schema::hasColumn('comisiones', 'moneda')) {
            Schema::table('comisiones', function (Blueprint $t) {
                $t->string('moneda', 3)->default('COP')->after('monto');
            });
        }

        // Precio en dólares de los planes: el anual 55 USD (220.000 / 4.000), los demás al cambio de 4.000
        foreach (DB::table('planes')->whereNull('precio_usd')->get() as $p) {
            $anual = (int) $p->dias >= 360 && (int) $p->dias <= 400 && (float) $p->precio > 0;
            $usd = (float) $p->precio <= 0 ? 0 : ($anual ? 55 : round((float) $p->precio / 4000, 2));
            DB::table('planes')->where('id', $p->id)->update(['precio_usd' => $usd]);
        }

        // Valores por defecto de la red en dólares (proporcionales a los de pesos) y la tasa para Wompi
        $defectos = [
            'tasa_usd_cop' => 4000,
            'red_usd_valor_plan' => 55,
            'red_usd_nueva_venta' => 25, 'red_usd_nueva_zona' => 1.25, 'red_usd_nueva_ciudad' => 1.25, 'red_usd_nueva_departamento' => 1.25, 'red_usd_nueva_pais' => 1.25,
            'red_usd_renov_venta' => 1.25, 'red_usd_renov_zona' => 2.5, 'red_usd_renov_ciudad' => 2.5, 'red_usd_renov_departamento' => 2.5, 'red_usd_renov_pais' => 2.5,
            'red_usd_salario_minimo' => 200,
        ];
        foreach ($defectos as $k => $v) {
            if (!DB::table('ajustes')->where('clave', $k)->exists()) {
                DB::table('ajustes')->insert(['clave' => $k, 'valor' => $v, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('comisiones', 'moneda')) Schema::table('comisiones', fn (Blueprint $t) => $t->dropColumn('moneda'));
        if (Schema::hasColumn('pagos_suscripcion', 'moneda')) Schema::table('pagos_suscripcion', fn (Blueprint $t) => $t->dropColumn(['moneda', 'cobro_cop']));
        if (Schema::hasColumn('planes', 'precio_usd')) Schema::table('planes', fn (Blueprint $t) => $t->dropColumn('precio_usd'));
    }
};
