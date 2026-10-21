<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Servicios: cosas que se venden o se agendan sin inventario (no se acaban ni llevan stock).
// tiendas.mod_servicios = el negocio maneja servicios · productos.es_servicio = ese ítem es un servicio.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'mod_servicios')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('mod_servicios')->default(false)->after('mod_pedidos'));
        }
        if (!Schema::hasColumn('productos', 'es_servicio')) {
            Schema::table('productos', fn (Blueprint $t) => $t->boolean('es_servicio')->default(false)->after('controla_inventario'));
        }

        // Negocios que por su tipo ya trabajan con servicios
        $ids = DB::table('tiendas')->whereIn('tipo_negocio', ['consultorio', 'funeraria', 'belleza'])->pluck('id');
        DB::table('tiendas')->whereIn('id', $ids)->update(['mod_servicios' => true]);

        // Consultorios: todo lo que no controla inventario es servicio (consultas, sesiones, valoraciones)
        $cons = DB::table('tiendas')->where('tipo_negocio', 'consultorio')->pluck('id');
        DB::table('productos')->whereIn('tienda_id', $cons)->where('controla_inventario', false)->update(['es_servicio' => true]);

        // Funerarias: planes y servicios sin inventario
        $fun = DB::table('tiendas')->where('tipo_negocio', 'funeraria')->pluck('id');
        DB::table('productos')->whereIn('tienda_id', $fun)->where('controla_inventario', false)
            ->where(fn ($q) => $q->where('nombre', 'like', '%servicio%')->orWhere('nombre', 'like', '%plan %'))
            ->update(['es_servicio' => true]);
    }

    public function down(): void
    {
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('es_servicio'));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_servicios'));
    }
};
