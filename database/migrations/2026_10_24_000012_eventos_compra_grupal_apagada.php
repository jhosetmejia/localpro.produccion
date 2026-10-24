<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Comprar varias entradas y las promociones quedan apagadas hasta que el organizador las configure:
// máximo 1 entrada por compra en los eventos que no han tocado esa opción.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eventos', 'max_por_compra')) return;
        Schema::table('eventos', fn (Blueprint $t) => $t->unsignedSmallInteger('max_por_compra')->default(1)->change());
        DB::table('eventos')->whereNull('promos')->update(['max_por_compra' => 1]);
    }

    public function down(): void
    {
    }
};
