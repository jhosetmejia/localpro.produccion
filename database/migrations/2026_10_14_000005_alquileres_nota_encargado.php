<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alquiler: nota para el encargado (le llega en el WhatsApp), aparte de la nota para el cliente (sale en la factura)
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('alquileres', 'encargado_nota')) return;
        Schema::table('alquileres', fn (Blueprint $t) => $t->string('encargado_nota', 255)->nullable()->after('encargado_valor'));
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn('encargado_nota'));
    }
};
