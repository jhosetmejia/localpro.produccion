<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Base de contactos de LocalPro (la sube el administrador) compartida por todos los asesores:
// cada día se le reparten a cada asesor y a cada persona se le escribe UNA sola vez.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('red_contactos')) {
            Schema::create('red_contactos', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 80);
                $t->string('telefono', 20)->unique();
                $t->string('lote', 80)->nullable();                 // nombre del archivo con que se subió
                $t->foreignId('asignado_a')->nullable()->index();   // asesor al que le salió
                $t->date('asignado_fecha')->nullable()->index();
                $t->timestamp('contactado_at')->nullable()->index();
                $t->foreignId('contactado_por')->nullable()->index(); // asesor que le escribió
                $t->string('estado', 20)->nullable();               // enviado · numero_malo · no_interesa
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('red_contactos');
    }
};
