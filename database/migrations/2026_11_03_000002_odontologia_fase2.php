<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Consultorio odontológico, fase 2: recordatorios y confirmación de citas, citas en línea, portal del paciente,
// ortodoncia (cuotas y controles), periodontograma, laboratorio dental, insumos por procedimiento y comisiones.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $t) {
            if (!Schema::hasColumn('citas', 'origen')) $t->string('origen', 15)->nullable(); // null = consultorio, en_linea
            if (!Schema::hasColumn('citas', 'token')) $t->string('token', 40)->nullable()->index();
            if (!Schema::hasColumn('citas', 'recordada_at')) $t->timestamp('recordada_at')->nullable();
            if (!Schema::hasColumn('citas', 'confirmada_at')) $t->timestamp('confirmada_at')->nullable();
        });

        if (!Schema::hasColumn('pacientes', 'portal_token')) {
            Schema::table('pacientes', fn (Blueprint $t) => $t->string('portal_token', 40)->nullable()->index());
        }

        Schema::table('tratamiento_planes', function (Blueprint $t) {
            if (!Schema::hasColumn('tratamiento_planes', 'tipo')) $t->string('tipo', 15)->default('general'); // general, ortodoncia
            if (!Schema::hasColumn('tratamiento_planes', 'cuota_inicial')) $t->decimal('cuota_inicial', 14, 2)->default(0);
            if (!Schema::hasColumn('tratamiento_planes', 'cuota_mensual')) $t->decimal('cuota_mensual', 14, 2)->default(0);
            if (!Schema::hasColumn('tratamiento_planes', 'cuotas')) $t->unsignedSmallInteger('cuotas')->default(0);
            if (!Schema::hasColumn('tratamiento_planes', 'inicio')) $t->date('inicio')->nullable();
        });

        if (!Schema::hasColumn('tratamiento_items', 'hecho_user_id')) {
            Schema::table('tratamiento_items', fn (Blueprint $t) => $t->foreignId('hecho_user_id')->nullable());
        }

        if (!Schema::hasTable('ortodoncia_controles')) {
            Schema::create('ortodoncia_controles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('plan_id')->index();
                $t->foreignId('paciente_id')->index();
                $t->date('fecha');
                $t->string('arco_sup', 60)->nullable();
                $t->string('arco_inf', 60)->nullable();
                $t->string('ligaduras', 60)->nullable();
                $t->string('elasticos', 80)->nullable();
                $t->text('notas')->nullable(); // cifrado
                $t->date('proximo')->nullable();
                $t->foreignId('user_id')->nullable();
                $t->string('profesional_nombre', 120)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('periodontogramas')) {
            Schema::create('periodontogramas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->foreignId('user_id')->nullable();
                $t->string('profesional_nombre', 120)->nullable();
                $t->longText('datos')->nullable(); // cifrado (json): {"16": {"p": [3,2,3,...6 sitios], "s": [0,1,...], "mov": 1, "furca": 0}}
                $t->text('resumen')->nullable();   // json: promedio, sitios >= 4 mm, % sangrado
                $t->text('notas')->nullable();     // cifrado
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('laboratorio_ordenes')) {
            Schema::create('laboratorio_ordenes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->foreignId('plan_id')->nullable();
                $t->string('numero', 20);
                $t->string('laboratorio', 120);
                $t->string('trabajo', 160);
                $t->string('pieza', 30)->nullable();
                $t->string('color', 30)->nullable();
                $t->string('material', 80)->nullable();
                $t->date('enviado');
                $t->date('entrega')->nullable();
                $t->date('recibido')->nullable();
                $t->decimal('costo', 14, 2)->default(0);
                $t->string('estado', 12)->default('enviado'); // enviado, recibido, instalado, devuelto
                $t->string('notas', 500)->nullable();
                $t->foreignId('user_id')->nullable();
                $t->timestamps();
                $t->unique(['tienda_id', 'numero']);
            });
        }

        if (!Schema::hasTable('procedimiento_insumos')) {
            Schema::create('procedimiento_insumos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('producto_id')->index();          // el servicio (resina, exodoncia…)
                $t->foreignId('producto_variante_id');          // el insumo que se gasta
                $t->decimal('cantidad', 12, 3)->default(1);
                $t->timestamps();
            });
        }

        if (!Schema::hasColumn('tratamiento_items', 'insumos_descontados')) {
            Schema::table('tratamiento_items', fn (Blueprint $t) => $t->boolean('insumos_descontados')->default(false));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procedimiento_insumos');
        Schema::dropIfExists('laboratorio_ordenes');
        Schema::dropIfExists('periodontogramas');
        Schema::dropIfExists('ortodoncia_controles');
    }
};
