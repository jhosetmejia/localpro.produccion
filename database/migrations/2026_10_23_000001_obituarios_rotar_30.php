<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pantalla de obituarios: 30 s por obituario (12 s no alcanzaba para leer). Solo cambia a quienes tenían el valor de fábrica (12).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'obituarios_rotar')) return;
        DB::table('tiendas')->where('obituarios_rotar', 12)->update(['obituarios_rotar' => 30]);
        DB::statement('ALTER TABLE tiendas ALTER obituarios_rotar SET DEFAULT 30');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tiendas', 'obituarios_rotar')) return;
        DB::statement('ALTER TABLE tiendas ALTER obituarios_rotar SET DEFAULT 12');
    }
};
