<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Foto por color: en la tienda en línea, al tocar un color cambia la foto del producto
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('producto_fotos')) {
            Schema::create('producto_fotos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $t->string('color', 60)->nullable();
                $t->string('path');
                $t->unsignedSmallInteger('orden')->default(0);
                $t->timestamps();
                $t->index(['producto_id', 'color']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_fotos');
    }
};
