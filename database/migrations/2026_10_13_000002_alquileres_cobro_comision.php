<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alquileres: al cobrarlos quedan como venta (con factura) y la comisión se descuenta de la ganancia del local
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alquileres', function (Blueprint $t) {
            $t->boolean('con_comision')->default(false)->after('valor');
            $t->decimal('comision', 14, 2)->default(0)->after('con_comision');
            $t->foreignId('pos_venta_id')->nullable()->after('estado')->constrained('pos_ventas')->nullOnDelete();
            $t->dateTime('cobrado_en')->nullable()->after('pos_venta_id');
        });
    }

    public function down(): void
    {
        Schema::table('alquileres', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pos_venta_id');
            $t->dropColumn(['con_comision', 'comision', 'cobrado_en']);
        });
    }
};
