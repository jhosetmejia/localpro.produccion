<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El plan Gratis (2 meses) queda con un tope de 100 productos; usuarios siguen ilimitados.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')->where('precio', 0)->whereNull('dias')->update([
            'max_productos' => 100,
            'descripcion' => '2 meses gratis con todo: hasta 100 productos y usuarios ilimitados.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('planes')->where('precio', 0)->whereNull('dias')->update([
            'max_productos' => null,
            'descripcion' => '2 meses gratis con todo: productos y usuarios ilimitados.',
        ]);
    }
};
