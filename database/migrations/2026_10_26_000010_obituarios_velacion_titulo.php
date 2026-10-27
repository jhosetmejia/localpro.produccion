<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Lugar de velación debajo de la foto, con el nombre del campo que elija la funeraria (vacío = sin título)
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituarios') || Schema::hasColumn('obituarios', 'velacion_titulo')) return;
        Schema::table('obituarios', fn (Blueprint $t) => $t->string('velacion_titulo', 60)->nullable()->after('sala_id'));
        DB::table('obituarios')->update(['velacion_titulo' => 'Lugar de velación']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'velacion_titulo')) Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn('velacion_titulo'));
    }
};
