<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Registrar venta olvidada": una venta que no se registró ese día se carga después en la caja de ese día
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_ventas', function (Blueprint $t) {
            if (!Schema::hasColumn('pos_ventas', 'registrada_despues_en')) $t->timestamp('registrada_despues_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pos_ventas', fn (Blueprint $t) => $t->dropColumn('registrada_despues_en'));
    }
};
