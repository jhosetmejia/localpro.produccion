<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Documento de respaldo del alquiler: obligatorio cuando el elemento sale sin un encargado del negocio
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alquileres', function (Blueprint $table) {
            if (!Schema::hasColumn('alquileres', 'doc_tipo')) {
                $table->string('doc_tipo', 20)->nullable();       // cedula | licencia | pasaporte | ce | otro
                $table->string('doc_numero', 30)->nullable();
                $table->string('doc_nombre', 80)->nullable();     // a nombre de quién
                $table->boolean('doc_fisico')->default(false);    // dejó el documento físico (hay que devolverlo)
                $table->string('doc_archivo', 255)->nullable();   // foto / PDF en disco privado
                $table->timestamp('doc_devuelto_en')->nullable(); // cuándo se le devolvió el documento físico
            }
        });
    }

    public function down(): void
    {
        Schema::table('alquileres', fn (Blueprint $t) => $t->dropColumn(['doc_tipo', 'doc_numero', 'doc_nombre', 'doc_fisico', 'doc_archivo', 'doc_devuelto_en']));
    }
};
