<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Líder de zona (comuna): está debajo del líder de ciudad y tiene máximo 10 asesores
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('asesores', 'zona')) {
            Schema::table('asesores', function (Blueprint $table) {
                $table->string('zona', 60)->nullable()->after('ciudad'); // ej. Comuna 10
            });
        }
    }

    public function down(): void
    {
        Schema::table('asesores', fn (Blueprint $t) => $t->dropColumn('zona'));
    }
};
