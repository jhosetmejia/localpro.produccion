<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Obituarios: otros días o lugares además de la velación y las exequias (ej. "Homenaje en el colegio", "Segundo día de velación")
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituarios', 'actos')) {
            Schema::table('obituarios', function (Blueprint $t) {
                $t->json('actos')->nullable()->after('destino_final');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'actos')) {
            Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn('actos'));
        }
    }
};
