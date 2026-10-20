<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Corregir venta (error al digitar)": el administrador baja cantidades de una venta (aunque la caja ya esté cerrada)
// y la caja, el inventario y los reportes se recalculan. Queda el registro de quién corrigió y por qué.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_venta_correcciones')) {
            Schema::create('pos_venta_correcciones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('pos_venta_id')->index();
                $t->foreignId('caja_id')->nullable()->index();
                $t->foreignId('user_id')->nullable();
                $t->decimal('total_antes', 14, 2);
                $t->decimal('total_despues', 14, 2);
                $t->decimal('esperado_antes', 14, 2)->nullable();
                $t->decimal('esperado_despues', 14, 2)->nullable();
                $t->json('detalle')->nullable(); // productos y pagos que cambiaron
                $t->string('motivo', 255);
                $t->timestamps();
            });
        }
        Schema::table('pos_ventas', function (Blueprint $t) {
            if (!Schema::hasColumn('pos_ventas', 'corregida_en')) $t->timestamp('corregida_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_venta_correcciones');
        Schema::table('pos_ventas', fn (Blueprint $t) => $t->dropColumn('corregida_en'));
    }
};
