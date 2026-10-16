<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// De dónde llegó el negocio al registrarse (ej. "blog:calendario-tributario-contadores-recordatorios"):
// sirve para saber qué artículos del blog traen clientes
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'origen')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('origen', 80)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tiendas', 'origen')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('origen'));
        }
    }
};
