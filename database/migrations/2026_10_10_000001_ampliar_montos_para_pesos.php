<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Los montos estaban en decimal(8,2): máximo 999.999,99. Se amplían para pesos colombianos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('impuesto', 14, 2)->unsigned()->change();
            $table->decimal('total', 14, 2)->unsigned()->change();
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('impuesto', 14, 2)->unsigned()->change();
            $table->decimal('total', 14, 2)->unsigned()->change();
        });
        Schema::table('producto_venta', function (Blueprint $table) {
            $table->decimal('precio_venta', 14, 2)->change();
            $table->decimal('descuento', 14, 2)->change();
        });
        Schema::table('compra_producto', function (Blueprint $table) {
            $table->decimal('precio_compra', 14, 2)->change();
            $table->decimal('precio_venta', 14, 2)->change();
        });
    }

    public function down(): void
    {
        // Sin vuelta atrás: reducir columnas podría truncar montos ya guardados.
    }
};
