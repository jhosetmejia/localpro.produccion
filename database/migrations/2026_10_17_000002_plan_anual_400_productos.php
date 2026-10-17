<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Plan anual: hasta 400 productos (antes 200). Cuando se llena, el negocio habla con LocalPro y compra
// paquetes de productos extra (por defecto $20.000 por cada 100 productos), que quedan en tiendas.productos_extra.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')->where('precio', '>', 0)->whereBetween('dias', [360, 400])
            ->whereNotNull('max_productos')->where('max_productos', '<', 400)
            ->update(['max_productos' => 400, 'updated_at' => now()]);

        if (!Schema::hasColumn('tiendas', 'productos_extra')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->unsignedInteger('productos_extra')->default(0);
            });
        }
    }

    public function down(): void
    {
        DB::table('planes')->where('precio', '>', 0)->whereBetween('dias', [360, 400])->where('max_productos', 400)
            ->update(['max_productos' => 200, 'updated_at' => now()]);
        if (Schema::hasColumn('tiendas', 'productos_extra')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('productos_extra'));
        }
    }
};
