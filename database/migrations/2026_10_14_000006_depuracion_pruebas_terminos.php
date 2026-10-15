<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Depuración de negocios que no pasaron a un plan pago al terminar la prueba, y aceptación de condiciones al registrarse.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'datos_depurados_at')) {
            Schema::table('tiendas', function (Blueprint $table) {
                $table->timestamp('datos_depurados_at')->nullable(); // cuándo se borraron productos, ventas, imágenes…
            });
        }
        if (!Schema::hasColumn('users', 'terminos_aceptados_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('terminos_aceptados_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('datos_depurados_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('terminos_aceptados_at'));
    }
};
