<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Plantillas de obituario: cuántas flores y adornos lleva (muchos · pocos · ninguno)
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituario_plantillas', 'adornos')) {
            Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->string('adornos', 10)->default('muchos'));
            DB::table('obituario_plantillas')->where('diseno', 'sencillo')->update(['adornos' => 'pocos']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'adornos')) {
            Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn('adornos'));
        }
    }
};
