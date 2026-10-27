<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cotizaciones: hora del alquiler/evento (opcional, de inicio y de fin) y un logo propio de la cotización
// (si no se sube, sale el logo del negocio). Sirve, por ejemplo, para hacerle la cotización a otro negocio con su logo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $t) {
            if (!Schema::hasColumn('cotizaciones', 'evento_hora')) $t->time('evento_hora')->nullable()->after('evento_hasta');
            if (!Schema::hasColumn('cotizaciones', 'evento_hora_fin')) $t->time('evento_hora_fin')->nullable()->after('evento_hora');
            if (!Schema::hasColumn('cotizaciones', 'logo_path')) $t->string('logo_path', 255)->nullable()->after('condiciones');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $t) {
            foreach (['evento_hora', 'evento_hora_fin', 'logo_path'] as $col) {
                if (Schema::hasColumn('cotizaciones', $col)) $t->dropColumn($col);
            }
        });
    }
};
