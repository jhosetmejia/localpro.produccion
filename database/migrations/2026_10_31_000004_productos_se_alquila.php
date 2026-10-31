<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Productos que el negocio alquila (separados de los que vende). Solo estos salen en "¿Qué tengo libre?".
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'se_alquila')) {
            Schema::table('productos', fn (Blueprint $t) => $t->boolean('se_alquila')->default(false)->after('es_servicio'));
            // Los que ya se usaron en cotizaciones con fecha de evento se marcan como de alquiler
            if (Schema::hasTable('cotizacion_items') && Schema::hasTable('cotizaciones')) {
                $ids = DB::table('cotizacion_items as i')->join('cotizaciones as c', 'c.id', '=', 'i.cotizacion_id')
                    ->whereNotNull('c.evento_fecha')->whereNotNull('i.producto_id')->distinct()->pluck('i.producto_id');
                foreach ($ids->chunk(500) as $lote) DB::table('productos')->whereIn('id', $lote->all())->update(['se_alquila' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'se_alquila')) Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('se_alquila'));
    }
};
