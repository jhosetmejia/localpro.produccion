<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tallas y colores más largos (ej. "Azul encendido", "Talla única ajustable")
return new class extends Migration
{
    public function up(): void
    {
        foreach (['producto_variantes', 'pos_venta_items'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('talla', 40)->nullable()->change();
                $table->string('color', 60)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
    }
};
