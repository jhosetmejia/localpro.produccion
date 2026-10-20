<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajuste fino de la foto del obituario: girar de a 1 grado, mover (arriba/abajo/izquierda/derecha) y acercar
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obituarios', function (Blueprint $table) {
            if (!Schema::hasColumn('obituarios', 'foto_rot')) $table->smallInteger('foto_rot')->default(0)->after('foto_forma');
            if (!Schema::hasColumn('obituarios', 'foto_x')) $table->smallInteger('foto_x')->default(0)->after('foto_rot');
            if (!Schema::hasColumn('obituarios', 'foto_y')) $table->smallInteger('foto_y')->default(0)->after('foto_x');
            if (!Schema::hasColumn('obituarios', 'foto_zoom')) $table->smallInteger('foto_zoom')->default(100)->after('foto_y');
        });
    }

    public function down(): void
    {
        Schema::table('obituarios', function (Blueprint $table) {
            $table->dropColumn(['foto_rot', 'foto_x', 'foto_y', 'foto_zoom']);
        });
    }
};
