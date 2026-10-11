<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Metas de venta para los vendedores de cada tienda y límites de los planes:
// Gratis = dueño + 2 vendedores y 10 productos. Pro (planes pagos y prueba) = 100 productos (se cambia en Planes y cobros).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'meta_dia')) {
            Schema::table('users', function (Blueprint $t) {
                $t->decimal('meta_dia', 14, 2)->nullable();
                $t->decimal('meta_mes', 14, 2)->nullable();
            });
        }

        DB::table('planes')->where('precio', 0)->whereNull('dias')->update(['max_usuarios' => 3, 'max_productos' => 10, 'updated_at' => now()]);
        DB::table('planes')->where(fn ($q) => $q->where('precio', '>', 0)->orWhereNotNull('dias'))->whereNull('max_productos')->update(['max_productos' => 100, 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
