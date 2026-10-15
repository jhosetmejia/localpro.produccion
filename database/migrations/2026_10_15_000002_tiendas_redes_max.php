<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cuántas redes sociales (página de Facebook + su Instagram) puede conectar cada negocio. Por defecto 1;
// el superadmin habilita más (se cobra como adicional).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'redes_max')) {
            Schema::table('tiendas', function (Blueprint $table) {
                $table->unsignedTinyInteger('redes_max')->default(1);
            });
        }
        // Los negocios que ya tienen varias cuentas conectadas (Abril Store, en pruebas) conservan las que tienen
        $conectadas = DB::table('redes_cuentas')->where('estado', '<>', 'desconectada')
            ->selectRaw('tienda_id, COUNT(*) n')->groupBy('tienda_id')->having('n', '>', 1)->pluck('n', 'tienda_id');
        foreach ($conectadas as $tid => $n) {
            DB::table('tiendas')->where('id', $tid)->update(['redes_max' => min(255, (int) $n)]);
        }
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('redes_max'));
    }
};
