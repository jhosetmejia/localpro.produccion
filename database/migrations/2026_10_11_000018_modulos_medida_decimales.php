<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Módulos que cada negocio activa según lo que vende:
//  - mod_medida: vender por metro, kilo, litro… (cantidades con decimales)
//  - mod_vencimiento: fechas de vencimiento por lote (próximamente)
//  - mod_recetas: recetas e insumos (próximamente)
// Las cantidades e inventarios pasan a aceptar decimales (ej. 1,5 m de vidrio). Los valores enteros no cambian.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mod_medida')) {
                $t->boolean('mod_medida')->default(false);
                $t->boolean('mod_vencimiento')->default(false);
                $t->boolean('mod_recetas')->default(false);
            }
        });
        Schema::table('productos', function (Blueprint $t) {
            if (!Schema::hasColumn('productos', 'unidad')) {
                $t->string('unidad', 6)->default('und'); // und, m, cm, kg, g, l, ml
            }
        });

        Schema::table('producto_variantes', fn (Blueprint $t) => $t->decimal('stock', 14, 3)->default(0)->change());
        Schema::table('pos_venta_items', fn (Blueprint $t) => $t->decimal('cantidad', 14, 3)->change());
        Schema::table('inventario_movimientos', function (Blueprint $t) {
            $t->decimal('cantidad', 14, 3)->change();
            $t->decimal('stock_resultante', 14, 3)->change();
        });
        if (Schema::hasTable('devolucion_items')) Schema::table('devolucion_items', fn (Blueprint $t) => $t->decimal('cantidad', 14, 3)->change());
        if (Schema::hasTable('entrada_items')) Schema::table('entrada_items', fn (Blueprint $t) => $t->decimal('cantidad', 14, 3)->change());
    }

    public function down(): void
    {
    }
};
