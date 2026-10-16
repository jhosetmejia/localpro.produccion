<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Contabilidad también para los asesores de la red: cada asesor tiene su propio espacio (asesor_id) sin crear un negocio.
return new class extends Migration
{
    private array $tablas = ['contable_clientes', 'contable_terceros', 'contable_periodos', 'contable_documentos', 'contable_tarifas'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (!Schema::hasTable($tabla)) continue;
            Schema::table($tabla, function (Blueprint $t) use ($tabla) {
                $t->unsignedBigInteger('tienda_id')->nullable()->change();
                if (!Schema::hasColumn($tabla, 'asesor_id')) {
                    $t->foreignId('asesor_id')->nullable()->after('tienda_id')->constrained('asesores')->cascadeOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (!Schema::hasColumn($tabla, 'asesor_id')) continue;
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropConstrainedForeignId('asesor_id');
            });
        }
    }
};
