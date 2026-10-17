<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Conteo físico de inventario: se toma una foto del stock del sistema, se cuentan las prendas (a mano o escaneando)
// y al aplicar se ajusta cada talla/color con la diferencia (contado − sistema al momento del conteo), así las ventas
// hechas mientras se contaba no se pierden.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventario_conteos')) {
            Schema::create('inventario_conteos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('user_id')->nullable();
                $t->string('nombre', 120);
                $t->foreignId('categoria_id')->nullable();
                $t->string('estado', 15)->default('abierto')->index(); // abierto, aplicado, cancelado
                $t->foreignId('aplicado_por')->nullable();
                $t->timestamp('aplicado_at')->nullable();
                $t->json('resumen')->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('inventario_conteo_items')) {
            Schema::create('inventario_conteo_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('inventario_conteo_id')->index();
                $t->foreignId('producto_variante_id')->index();
                $t->decimal('stock_sistema', 12, 3)->default(0);
                $t->decimal('contado', 12, 3)->nullable();
                $t->foreignId('user_id')->nullable();
                $t->timestamp('contado_at')->nullable();
                $t->unique(['inventario_conteo_id', 'producto_variante_id'], 'conteo_variante_unico');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_conteo_items');
        Schema::dropIfExists('inventario_conteos');
    }
};
