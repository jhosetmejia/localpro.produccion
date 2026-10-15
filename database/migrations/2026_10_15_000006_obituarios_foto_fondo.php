<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Foto del fallecido: qué va detrás (fondo suave, sin fondo o un color) y la forma (círculo o recorte libre)
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('obituarios', 'foto_fondo')) {
            Schema::table('obituarios', function (Blueprint $t) {
                $t->string('foto_fondo', 10)->default('suave'); // suave · sin · color
                $t->string('foto_color', 9)->nullable();
                $t->string('foto_forma', 10)->default('circulo'); // circulo · libre
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'foto_fondo')) {
            Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn(['foto_fondo', 'foto_color', 'foto_forma']));
        }
    }
};
