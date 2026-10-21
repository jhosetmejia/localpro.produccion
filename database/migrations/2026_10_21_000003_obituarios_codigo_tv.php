<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Código corto para abrir la pantalla en el TV: localproo.com/tv/1234 (en vez de la dirección larga con token).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituario_salas', 'codigo')) {
            Schema::table('obituario_salas', fn (Blueprint $t) => $t->string('codigo', 8)->nullable()->unique()->after('token'));
        }
        if (!Schema::hasColumn('tiendas', 'obituarios_codigo')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('obituarios_codigo', 8)->nullable()->unique()->after('obituarios_token'));
        }
        foreach (DB::table('obituario_salas')->whereNull('codigo')->pluck('id') as $id) {
            DB::table('obituario_salas')->where('id', $id)->update(['codigo' => \App\Models\ObituarioSala::nuevoCodigo()]);
        }
        foreach (DB::table('tiendas')->whereNotNull('obituarios_token')->whereNull('obituarios_codigo')->pluck('id') as $id) {
            DB::table('tiendas')->where('id', $id)->update(['obituarios_codigo' => \App\Models\ObituarioSala::nuevoCodigo()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_salas', 'codigo')) Schema::table('obituario_salas', fn (Blueprint $t) => $t->dropColumn('codigo'));
        if (Schema::hasColumn('tiendas', 'obituarios_codigo')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('obituarios_codigo'));
    }
};
