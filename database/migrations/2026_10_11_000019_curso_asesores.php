<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Curso corto y sencillo para aspirantes a asesor: 4 lecciones, examen fácil (intentos ilimitados) y certificado.
// Quien aprueba el examen queda como asesor activo automáticamente (estado "capacitacion" → "activo").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesores', function (Blueprint $table) {
            if (!Schema::hasColumn('asesores', 'curso_modulos')) {
                $table->json('curso_modulos')->nullable()->after('aprobado_at');       // claves de los módulos completados
                $table->unsignedTinyInteger('curso_nota')->nullable()->after('curso_modulos'); // mejor nota del examen (0-100)
                $table->timestamp('curso_aprobado_at')->nullable()->after('curso_nota');
                $table->string('certificado', 15)->nullable()->unique()->after('curso_aprobado_at'); // CERT-00001
            }
        });

        if (!Schema::hasTable('asesor_examenes')) {
            Schema::create('asesor_examenes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asesor_id')->constrained('asesores')->cascadeOnDelete();
                $table->unsignedTinyInteger('nota');            // porcentaje 0-100
                $table->unsignedTinyInteger('correctas');
                $table->unsignedTinyInteger('total');
                $table->boolean('aprobado')->default(false);
                $table->json('respuestas')->nullable();         // [{p: id, r: opción elegida, ok: bool}]
                $table->timestamps();
            });
        }

        $ahora = now();
        foreach (['curso_nota_minima' => 70, 'curso_preguntas' => 10] as $k => $v) {
            if (!DB::table('ajustes')->where('clave', $k)->exists()) {
                DB::table('ajustes')->insert(['clave' => $k, 'valor' => $v, 'created_at' => $ahora, 'updated_at' => $ahora]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_examenes');
        Schema::table('asesores', function (Blueprint $table) {
            $table->dropUnique(['certificado']);
            $table->dropColumn(['curso_modulos', 'curso_nota', 'curso_aprobado_at', 'certificado']);
        });
    }
};
