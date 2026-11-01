<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Diseños de carta compartidos (tipo plantillas de Canva): el dueño decide si su diseño queda público para que otros
// negocios lo usen. compartir: null = aún no ha respondido, 1 = sí, 0 = no. usos = cuántos negocios lo tomaron.
// oculta = el súper administrador lo saca de la galería (contenido inapropiado).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cartas_ia')) return;
        Schema::table('cartas_ia', function (Blueprint $table) {
            if (!Schema::hasColumn('cartas_ia', 'compartir')) $table->boolean('compartir')->nullable()->after('en_qr');
            if (!Schema::hasColumn('cartas_ia', 'usos')) $table->unsignedInteger('usos')->default(0)->after('compartir');
            if (!Schema::hasColumn('cartas_ia', 'oculta')) $table->boolean('oculta')->default(false)->after('usos');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cartas_ia')) return;
        Schema::table('cartas_ia', function (Blueprint $table) {
            foreach (['compartir', 'usos', 'oculta'] as $c) {
                if (Schema::hasColumn('cartas_ia', $c)) $table->dropColumn($c);
            }
        });
    }
};
