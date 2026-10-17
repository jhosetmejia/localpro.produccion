<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Aprende LocalPro": avance de cada persona (dueño, vendedores, asesores) en los cursos
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academia_avance')) {
            Schema::create('academia_avance', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('curso', 40);
                $t->json('lecciones')->nullable();          // índices de las lecciones terminadas
                $t->unsignedTinyInteger('nota')->nullable(); // % de la prueba
                $t->timestamp('aprobado_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'curso']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academia_avance');
    }
};
