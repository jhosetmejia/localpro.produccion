<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Contabilidad: retención de IVA bien calculada (agente de retención de IVA / gran contribuyente)
// y recordatorios del calendario tributario.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contable_clientes') && !Schema::hasColumn('contable_clientes', 'agente_rete_iva')) {
            Schema::table('contable_clientes', function (Blueprint $t) {
                // Gran contribuyente, entidad pública o designado por la DIAN (art. 437-2 ET): practica retención de IVA
                $t->boolean('agente_rete_iva')->default(false)->after('autorretenedor');
                $t->boolean('recordatorios')->default(true)->after('activo');
            });
        }
        if (Schema::hasTable('contable_terceros') && !Schema::hasColumn('contable_terceros', 'gran_contribuyente')) {
            Schema::table('contable_terceros', function (Blueprint $t) {
                // Entre grandes contribuyentes no se practica retención de IVA
                $t->boolean('gran_contribuyente')->default(false)->after('autorretenedor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contable_clientes', 'agente_rete_iva')) {
            Schema::table('contable_clientes', fn (Blueprint $t) => $t->dropColumn(['agente_rete_iva', 'recordatorios']));
        }
        if (Schema::hasColumn('contable_terceros', 'gran_contribuyente')) {
            Schema::table('contable_terceros', fn (Blueprint $t) => $t->dropColumn('gran_contribuyente'));
        }
    }
};
