<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Institutos: cada programa tiene su precio pleno, precio de contado y plan de financiación (cuota inicial + N cuotas)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inst_programas', function (Blueprint $t) {
            if (!Schema::hasColumn('inst_programas', 'precio_contado')) $t->decimal('precio_contado', 12, 2)->nullable();
            if (!Schema::hasColumn('inst_programas', 'precio_financiado')) $t->decimal('precio_financiado', 12, 2)->nullable();
            if (!Schema::hasColumn('inst_programas', 'fin_cuota_inicial')) $t->decimal('fin_cuota_inicial', 12, 2)->nullable();
            if (!Schema::hasColumn('inst_programas', 'fin_num_cuotas')) $t->unsignedTinyInteger('fin_num_cuotas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inst_programas', fn (Blueprint $t) => $t->dropColumn(['precio_contado', 'precio_financiado', 'fin_cuota_inicial', 'fin_num_cuotas']));
    }
};
