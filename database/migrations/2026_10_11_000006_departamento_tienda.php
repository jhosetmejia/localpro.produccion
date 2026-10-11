<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Departamento / provincia de la tienda (la ciudad ya existía)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->string('departamento', 60)->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('departamento'));
    }
};
