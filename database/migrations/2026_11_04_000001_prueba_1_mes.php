<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// La prueba gratis pasa de 2 meses a 1 mes (solo negocios nuevos; los que ya están en prueba conservan su fecha).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')->whereIn('nombre', ['Prueba 2 meses', 'Prueba 3 meses'])->update([
            'nombre' => 'Prueba 1 mes', 'dias' => 30,
            'descripcion' => 'Prueba gratis por 1 mes.', 'updated_at' => now(),
        ]);
        DB::table('planes')->where('precio', 0)->whereNull('dias')->update([
            'descripcion' => '1 mes gratis con todo (hasta 100 productos y usuarios ilimitados).', 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('planes')->where('nombre', 'Prueba 1 mes')->update(['nombre' => 'Prueba 2 meses', 'dias' => 60]);
    }
};
