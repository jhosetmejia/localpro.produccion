<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Plan anual: hasta 200 productos (antes 100). Para ampliar más, el negocio habla con soporte.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')->where('precio', '>', 0)->whereBetween('dias', [360, 400])
            ->whereNotNull('max_productos')->where('max_productos', '<', 200)
            ->update(['max_productos' => 200, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('planes')->where('precio', '>', 0)->whereBetween('dias', [360, 400])->where('max_productos', 200)
            ->update(['max_productos' => 100, 'updated_at' => now()]);
    }
};
