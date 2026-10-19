<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Etiquetas de código de barras "en blanco" (sin nombre ni precio) para sellar mercancía nueva
// antes de crearla en el sistema. Al crear el producto se escanea la etiqueta y queda con ese código.
// También guarda las etiquetas extra de una talla/color (varias prendas iguales con etiquetas distintas).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_sueltos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('codigo', 60);
            $table->string('lote', 20)->nullable();
            $table->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->timestamp('usado_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tienda_id', 'codigo']);
            $table->index(['tienda_id', 'lote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_sueltos');
    }
};
