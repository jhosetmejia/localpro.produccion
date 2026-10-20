<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tipo de negocio "Instituto / academia": programas, inscripciones, estudiantes, matrículas, cuotas, pagos y cartera
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'inst_dia_pago')) {
                $t->unsignedTinyInteger('inst_dia_pago')->default(5);         // las cuotas vencen este día de cada mes
                $t->unsignedInteger('consecutivo_inscripcion')->default(0);    // consecutivo del formulario de inscripción
            }
            if (!Schema::hasColumn('tiendas', 'consecutivo_rc')) {
                $t->unsignedInteger('consecutivo_rc')->default(0);
            }
        });

        Schema::create('inst_programas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('nombre', 120);
            $t->string('tipo', 40)->default('Técnico laboral por competencias');
            $t->unsignedTinyInteger('semestres')->default(2);
            $t->decimal('valor_semestre', 12, 2)->default(0);
            $t->decimal('valor_inscripcion', 12, 2)->default(0);
            $t->text('modulos')->nullable();               // un módulo por línea
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('inst_estudiantes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('consecutivo', 20)->nullable();       // No. del formulario de inscripción
            $t->string('estado', 15)->default('aspirante');  // aspirante | matriculado | retirado | graduado
            $t->foreignId('programa_id')->nullable()->constrained('inst_programas')->nullOnDelete();
            $t->string('jornada', 20)->nullable();
            $t->date('fecha_inscripcion')->nullable();
            // Información personal
            $t->string('nombres', 80);
            $t->string('apellidos', 80);
            $t->string('tipo_doc', 4)->default('CC');
            $t->string('documento', 20)->nullable();
            $t->string('lugar_expedicion', 60)->nullable();
            $t->date('fecha_nacimiento')->nullable();
            $t->string('lugar_nacimiento', 60)->nullable();
            $t->string('nacionalidad', 40)->nullable();
            $t->string('estado_civil', 20)->nullable();
            $t->string('estrato', 4)->nullable();
            $t->string('eps', 60)->nullable();
            $t->string('direccion', 120)->nullable();
            $t->string('ciudad', 60)->nullable();
            $t->string('departamento', 60)->nullable();
            $t->string('email', 120)->nullable();
            $t->string('telefono', 20)->nullable();
            $t->string('celular', 20)->nullable();
            $t->string('enfermedad', 120)->nullable();
            // Laboral, académica y acudiente
            $t->string('lab_entidad', 80)->nullable();
            $t->string('lab_cargo', 60)->nullable();
            $t->string('lab_telefono', 20)->nullable();
            $t->string('bach_titulo', 80)->nullable();
            $t->string('bach_institucion', 120)->nullable();
            $t->string('bach_ciudad', 60)->nullable();
            $t->string('bach_fecha', 20)->nullable();
            $t->string('noveno_institucion', 120)->nullable();
            $t->string('noveno_fecha', 20)->nullable();
            $t->string('acu_parentesco', 30)->nullable();
            $t->string('acu_nombre', 100)->nullable();
            $t->string('acu_telefono', 20)->nullable();
            $t->boolean('autoriza_imagen')->default(false);
            $t->boolean('autoriza_datos')->default(false);
            $t->string('notas', 255)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['tienda_id', 'estado']);
            $t->index(['tienda_id', 'documento']);
        });

        Schema::create('inst_matriculas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('estudiante_id')->constrained('inst_estudiantes')->cascadeOnDelete();
            $t->foreignId('programa_id')->nullable()->constrained('inst_programas')->nullOnDelete();
            $t->string('programa_nombre', 120);
            $t->string('periodo', 7);                          // 2026-2
            $t->unsignedTinyInteger('semestre')->default(1);
            $t->string('jornada', 20)->nullable();
            $t->text('modulos_pendientes')->nullable();
            $t->string('modalidad', 10)->default('contado');  // contado | credito
            $t->decimal('valor_semestre', 12, 2)->default(0);
            $t->decimal('cuota_inicial', 12, 2)->default(0);
            $t->unsignedTinyInteger('num_cuotas')->default(0);
            $t->decimal('valor_cuota', 12, 2)->default(0);
            $t->date('fecha');
            $t->string('estado', 12)->default('activa');      // activa | anulada | retirada
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['tienda_id', 'periodo']);
        });

        Schema::create('inst_cargos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('estudiante_id')->constrained('inst_estudiantes')->cascadeOnDelete();
            $t->foreignId('matricula_id')->nullable()->constrained('inst_matriculas')->nullOnDelete();
            $t->string('tipo', 20)->default('cuota');         // inscripcion | matricula | cuota_inicial | cuota | otro
            $t->string('concepto', 150);
            $t->decimal('valor', 12, 2);
            $t->decimal('pagado', 12, 2)->default(0);
            $t->date('vence')->nullable();
            $t->string('estado', 12)->default('pendiente');   // pendiente | parcial | pagado | anulado
            $t->timestamps();
            $t->index(['tienda_id', 'estudiante_id', 'estado']);
        });

        Schema::create('inst_pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('estudiante_id')->constrained('inst_estudiantes')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->date('fecha');
            $t->decimal('valor', 12, 2);
            $t->decimal('saldo_favor', 12, 2)->default(0);
            $t->string('metodo', 40)->default('Efectivo');
            $t->string('referencia', 60)->nullable();
            $t->string('nota', 255)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('anulado')->default(false);
            $t->timestamps();
            $t->index(['tienda_id', 'fecha']);
        });

        Schema::create('inst_pago_cargo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pago_id')->constrained('inst_pagos')->cascadeOnDelete();
            $t->foreignId('cargo_id')->constrained('inst_cargos')->cascadeOnDelete();
            $t->decimal('valor', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inst_pago_cargo');
        Schema::dropIfExists('inst_pagos');
        Schema::dropIfExists('inst_cargos');
        Schema::dropIfExists('inst_matriculas');
        Schema::dropIfExists('inst_estudiantes');
        Schema::dropIfExists('inst_programas');
        Schema::table('tiendas', function (Blueprint $t) {
            $t->dropColumn(['inst_dia_pago', 'consecutivo_inscripcion']);
        });
    }
};
