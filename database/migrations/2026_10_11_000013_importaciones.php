<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Importación de productos desde Excel: lo hace el negocio solo o se lo pide al equipo de LocalPro
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('importaciones')) return;
        Schema::create('importaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('tipo', 12)->default('propia');        // propia | solicitud (la hace el equipo)
            $t->string('estado', 12)->default('pendiente');   // pendiente | lista | rechazada
            $t->string('archivo', 255)->nullable();           // ruta en storage/app
            $t->string('nombre_original', 160)->nullable();
            $t->string('telefono', 20)->nullable();
            $t->text('nota')->nullable();
            $t->text('resultado')->nullable();                // resumen de la importación
            $t->unsignedInteger('creados')->default(0);
            $t->unsignedInteger('actualizados')->default(0);
            $t->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('atendido_at')->nullable();
            $t->timestamps();
            $t->index(['estado', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
