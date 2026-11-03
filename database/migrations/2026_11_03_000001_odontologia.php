<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Consultorio odontológico: odontograma por versiones, planes de tratamiento con abonos a la caja,
// consentimientos informados firmados en pantalla y radiografías / fotos del paciente.
// Todo lo clínico se conserva (Res. 1995/1999): no se borra con la depuración de pruebas.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('odontogramas')) {
            Schema::create('odontogramas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->foreignId('atencion_id')->nullable();
                $t->foreignId('user_id')->nullable();
                $t->string('profesional_nombre', 120)->nullable();
                $t->string('denticion', 12)->default('permanente'); // permanente, temporal, mixta
                $t->longText('piezas')->nullable(); // cifrado (json): {"16": {"caras": {"O": "caries"}, "pieza": "corona"}}
                $t->text('notas')->nullable();      // cifrado
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('tratamiento_planes')) {
            Schema::create('tratamiento_planes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->string('numero', 20);
                $t->string('nombre', 120);
                $t->string('estado', 15)->default('propuesto'); // propuesto, aprobado, terminado, cancelado
                $t->decimal('descuento', 14, 2)->default(0);
                $t->decimal('abonado', 14, 2)->default(0);
                $t->timestamp('aprobado_at')->nullable();
                $t->string('aprobado_nota', 160)->nullable();
                $t->text('notas')->nullable();
                $t->foreignId('profesional_id')->nullable();
                $t->foreignId('user_id')->nullable();
                $t->timestamps();
                $t->unique(['tienda_id', 'numero']);
            });
        }

        if (!Schema::hasTable('tratamiento_items')) {
            Schema::create('tratamiento_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('plan_id')->index();
                $t->string('pieza', 5)->nullable();
                $t->string('caras', 10)->nullable();
                $t->foreignId('producto_id')->nullable();
                $t->string('descripcion', 160);
                $t->unsignedSmallInteger('cantidad')->default(1);
                $t->decimal('valor', 14, 2)->default(0); // valor unitario
                $t->string('estado', 12)->default('pendiente'); // pendiente, hecho
                $t->timestamp('hecho_at')->nullable();
                $t->string('hecho_por', 120)->nullable();
                $t->unsignedSmallInteger('orden')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('tratamiento_abonos')) {
            Schema::create('tratamiento_abonos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('plan_id')->index();
                $t->string('numero', 20);
                $t->foreignId('caja_id')->nullable();
                $t->foreignId('user_id')->nullable();
                $t->foreignId('metodo_pago_id');
                $t->decimal('monto', 14, 2);
                $t->decimal('saldo_despues', 14, 2)->default(0);
                $t->string('nota', 255)->nullable();
                $t->boolean('anulado')->default(false);
                $t->timestamps();
                $t->unique(['tienda_id', 'numero']);
                $t->index(['caja_id', 'anulado']);
            });
        }

        if (!Schema::hasTable('consentimientos')) {
            Schema::create('consentimientos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->string('plantilla', 30);
                $t->string('titulo', 160);
                $t->string('procedimiento', 200)->nullable();
                $t->longText('texto'); // cifrado
                $t->string('firmante_nombre', 160);
                $t->string('firmante_documento', 40)->nullable();
                $t->string('firmante_relacion', 40)->default('Paciente');
                $t->string('firma_path')->nullable();
                $t->timestamp('firmado_at')->nullable();
                $t->string('ip', 45)->nullable();
                $t->foreignId('profesional_id')->nullable();
                $t->string('profesional_nombre', 120)->nullable();
                $t->string('profesional_registro', 60)->nullable();
                $t->string('huella', 64)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('paciente_adjuntos')) {
            Schema::create('paciente_adjuntos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->string('tipo', 20)->default('foto'); // radiografia, foto, documento
                $t->string('titulo', 120)->nullable();
                $t->string('path');
                $t->string('mime', 80)->nullable();
                $t->unsignedInteger('tamano')->default(0);
                $t->foreignId('user_id')->nullable();
                $t->timestamps();
            });
        }

        // Consultorios que ya usaban la plantilla de odontología como predeterminada: no cambian de tipo,
        // pero ya ven el odontograma (Tienda::es_odontologia). Las tiendas de tipo "odontologia" se crean desde ahora.
        // Ajuste de la plantilla dental para las tiendas odontológicas que ya existan (por si se crearon antes de esta versión)
        if (Schema::hasTable('ajustes')) {
            foreach (DB::table('tiendas')->where('tipo_negocio', 'odontologia')->pluck('id') as $id) {
                if (!DB::table('ajustes')->where('clave', 't' . $id . '_hc_plantilla')->exists()) {
                    DB::table('ajustes')->insert(['clave' => 't' . $id . '_hc_plantilla', 'valor' => 'odontologia', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_adjuntos');
        Schema::dropIfExists('consentimientos');
        Schema::dropIfExists('tratamiento_abonos');
        Schema::dropIfExists('tratamiento_items');
        Schema::dropIfExists('tratamiento_planes');
        Schema::dropIfExists('odontogramas');
    }
};
