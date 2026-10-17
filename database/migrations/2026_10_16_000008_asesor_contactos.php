<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Base de datos del asesor: sus contactos (del celular) para ofrecerles LocalPro, 20 al día
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asesor_contactos')) {
            Schema::create('asesor_contactos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('asesor_id')->constrained('asesores')->cascadeOnDelete();
                $t->string('nombre', 80);
                $t->string('telefono', 20);
                $t->timestamp('contactado_at')->nullable()->index();
                $t->string('estado', 20)->nullable();
                $t->timestamps();
                $t->unique(['asesor_id', 'telefono']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_contactos');
    }
};
