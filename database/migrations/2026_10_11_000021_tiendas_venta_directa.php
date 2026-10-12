<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Negocios asignados a LocalPro (venta directa del súper administrador): ya no cuentan como "Sin asesor"
// y muestran el nombre de quien los tomó. Las comisiones siguen siendo 100% para LocalPro (asesor_id queda vacío).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'venta_directa_id')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->foreignId('venta_directa_id')->nullable()->after('asesor_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'venta_directa_id')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->dropConstrainedForeignId('venta_directa_id');
            });
        }
    }
};
