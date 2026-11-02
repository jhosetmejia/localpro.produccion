<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Segunda impresora: la de cocina (comandas). tiendas.impresora sigue siendo la de caja (tiquetes, facturas, cuadre, recibos).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tiendas') && !Schema::hasColumn('tiendas', 'impresora_cocina')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->json('impresora_cocina')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'impresora_cocina')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('impresora_cocina'));
        }
    }
};
