<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// La prueba gratis pasa de 3 a 2 meses (solo negocios nuevos; los que ya están en prueba conservan su fecha)
// y los planes pueden mostrar un "precio antes" tachado (plan anual: antes $350.000, ahora $220.000).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('planes', 'precio_antes')) {
            Schema::table('planes', function (Blueprint $t) {
                $t->decimal('precio_antes', 12, 2)->nullable()->after('precio');
            });
        }

        DB::table('planes')->where('nombre', 'Prueba 3 meses')->update([
            'nombre' => 'Prueba 2 meses', 'dias' => 60,
            'descripcion' => 'Prueba gratis por 2 meses (negocios inscritos por un asesor).', 'updated_at' => now(),
        ]);
        DB::table('planes')->where('precio', 0)->whereNull('dias')->update([
            'descripcion' => '2 meses gratis con todo: productos y usuarios ilimitados.', 'updated_at' => now(),
        ]);
        // Plan anual pago: antes $350.000
        DB::table('planes')->where('precio', '>', 0)->where('dias', '>=', 365)->whereNull('precio_antes')
            ->update(['precio_antes' => 350000, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('planes')->where('nombre', 'Prueba 2 meses')->update(['nombre' => 'Prueba 3 meses', 'dias' => 90]);
        if (Schema::hasColumn('planes', 'precio_antes')) {
            Schema::table('planes', fn (Blueprint $t) => $t->dropColumn('precio_antes'));
        }
    }
};
