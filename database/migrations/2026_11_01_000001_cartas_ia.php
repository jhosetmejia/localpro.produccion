<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Carta personalizada que cada negocio hace con su IA (código HTML ya limpio). Tabla aparte para no cargarla con la tienda.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cartas_ia')) return;
        Schema::create('cartas_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->unique()->constrained('tiendas')->cascadeOnDelete();
            $table->longText('html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas_ia');
    }
};
