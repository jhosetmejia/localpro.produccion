<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tiquetera (restaurantes y cafeterías): el cliente paga por adelantado N almuerzos/servicios y se le descuenta uno cada vez que consume
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tiqueteras')) {
            Schema::create('tiqueteras', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->index();
                $t->string('codigo', 20);                         // TQ-000001
                $t->foreignId('cliente_id')->nullable();
                $t->string('cliente_nombre', 80);
                $t->string('cliente_telefono', 20)->nullable();
                $t->string('nombre', 80);                         // Ej: Almuerzo ejecutivo
                $t->unsignedSmallInteger('total');                // servicios pagados
                $t->unsignedSmallInteger('usados')->default(0);
                $t->decimal('valor', 14, 2);
                $t->foreignId('pos_venta_id')->nullable();        // la venta con la que se pagó (cuadra la caja y los reportes)
                $t->date('vence')->nullable();
                $t->string('estado', 12)->default('activa');      // activa | agotada
                $t->string('nota', 255)->nullable();
                $t->foreignId('user_id')->nullable();
                $t->timestamps();
                $t->index(['tienda_id', 'estado']);
            });
        }
        if (!Schema::hasTable('tiquetera_usos')) {
            Schema::create('tiquetera_usos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tiquetera_id')->index();
                $t->foreignId('user_id')->nullable();
                $t->unsignedSmallInteger('cantidad')->default(1);
                $t->boolean('anulado')->default(false);
                $t->timestamps();
            });
        }
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'consecutivo_tiquetera')) $t->unsignedInteger('consecutivo_tiquetera')->default(0);
        });

        // Restaurantes y cafeterías: se esconde "Separados y abonos" (salvo que ya tengan separados activos); allá va la Tiquetera
        $conSeparados = Schema::hasTable('separados') ? DB::table('separados')->where('estado', 'activo')->pluck('tienda_id')->unique()->all() : [];
        foreach (DB::table('tiendas')->whereIn('tipo_negocio', ['restaurante', 'cafeteria'])->get(['id', 'menu_oculto']) as $t) {
            if (in_array($t->id, $conSeparados)) continue;
            $ocultos = json_decode((string) $t->menu_oculto, true) ?: [];
            if (!in_array('separados', $ocultos, true)) {
                $ocultos[] = 'separados';
                DB::table('tiendas')->where('id', $t->id)->update(['menu_oculto' => json_encode(array_values($ocultos))]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tiquetera_usos');
        Schema::dropIfExists('tiqueteras');
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('consecutivo_tiquetera'));
    }
};
