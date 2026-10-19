<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Venezuela como se trabaja allá: precios en dólares y cobro en dólares o bolívares a la tasa del día.
 * - tiendas.tasa_bs: tasa propia de la tienda (si no tiene, usa la tasa general del panel).
 * - metodos_pago.moneda: VES = el cliente paga en bolívares (Pago Móvil, punto de venta…); null = moneda de la tienda.
 * - Cada venta guarda la tasa y cada pago en Bs guarda lo que entró en Bs.
 * - La caja cuenta aparte el efectivo en dólares y el efectivo en bolívares.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'tasa_bs')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->decimal('tasa_bs', 16, 4)->nullable();
                $t->timestamp('tasa_bs_at')->nullable();
            });
        }
        if (!Schema::hasColumn('metodos_pago', 'moneda')) {
            Schema::table('metodos_pago', function (Blueprint $t) {
                $t->string('moneda', 3)->nullable();
            });
        }
        if (!Schema::hasColumn('pos_ventas', 'tasa_bs')) {
            Schema::table('pos_ventas', function (Blueprint $t) {
                $t->decimal('tasa_bs', 16, 4)->nullable();
            });
        }
        if (!Schema::hasColumn('pos_venta_pagos', 'moneda')) {
            Schema::table('pos_venta_pagos', function (Blueprint $t) {
                $t->string('moneda', 3)->nullable();
                $t->decimal('monto_moneda', 18, 2)->nullable(); // lo que entró en esa moneda (ej. Bs)
                $t->decimal('tasa', 16, 4)->nullable();
            });
        }
        if (!Schema::hasColumn('cajas', 'efectivo_bs_esperado')) {
            Schema::table('cajas', function (Blueprint $t) {
                $t->decimal('efectivo_bs_esperado', 18, 2)->nullable();
                $t->decimal('efectivo_bs_contado', 18, 2)->nullable();
            });
        }
        if (!Schema::hasColumn('ajustes', 'clave')) {
            return;
        }
        // Venezuela trabaja en dólares (los bolívares van como forma de cobro a la tasa del día)
        DB::table('ajustes')->updateOrInsert(['clave' => 'moneda_venezuela'], ['valor' => 'USD', 'updated_at' => now()]);
        DB::table('ajustes')->updateOrInsert(['clave' => 'tasa_ves_usd_at'], ['valor' => now()->toDateTimeString(), 'updated_at' => now()]);

        // Tiendas de Venezuela que ya existen: sus métodos de pago en bolívares y uno de "Efectivo (bolívares)"
        foreach (DB::table('tiendas')->where('pais', 'VE')->pluck('id') as $tid) {
            $metodos = DB::table('metodos_pago')->where('tienda_id', $tid)->get();
            foreach ($metodos as $m) {
                $n = mb_strtolower($m->nombre);
                $enBs = str_contains($n, 'pago m') || str_contains($n, 'bol') || str_contains($n, 'bs')
                    || str_contains($n, 'punto') || str_contains($n, 'tarjeta') || (str_contains($n, 'transfer') && !str_contains($n, 'zelle'));
                if ($enBs && !$m->moneda) DB::table('metodos_pago')->where('id', $m->id)->update(['moneda' => 'VES']);
            }
            if (!$metodos->contains(fn ($m) => str_contains(mb_strtolower($m->nombre), 'efectivo (bol'))) {
                DB::table('metodos_pago')->insert([
                    'tienda_id' => $tid, 'nombre' => 'Efectivo (bolívares)', 'medio_dian' => '10', 'es_efectivo' => true, 'es_credito' => false,
                    'activo' => true, 'orden' => (int) $metodos->max('orden') + 1, 'moneda' => 'VES',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cajas', 'efectivo_bs_esperado')) Schema::table('cajas', fn (Blueprint $t) => $t->dropColumn(['efectivo_bs_esperado', 'efectivo_bs_contado']));
        if (Schema::hasColumn('pos_venta_pagos', 'moneda')) Schema::table('pos_venta_pagos', fn (Blueprint $t) => $t->dropColumn(['moneda', 'monto_moneda', 'tasa']));
        if (Schema::hasColumn('pos_ventas', 'tasa_bs')) Schema::table('pos_ventas', fn (Blueprint $t) => $t->dropColumn('tasa_bs'));
        if (Schema::hasColumn('metodos_pago', 'moneda')) Schema::table('metodos_pago', fn (Blueprint $t) => $t->dropColumn('moneda'));
        if (Schema::hasColumn('tiendas', 'tasa_bs')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['tasa_bs', 'tasa_bs_at']));
    }
};
