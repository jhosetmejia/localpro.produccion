<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Consultorio (salud particular): pacientes, agenda de citas e historia clínica.
// Reglas (Res. 1995/1999): la historia es reservada y no se borra ni se modifica una vez cerrada; las correcciones
// van como notas aclaratorias con fecha y autor. Los textos clínicos se guardan cifrados y cada acceso queda registrado.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'mod_consultorio')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->boolean('mod_consultorio')->default(false);
                $t->unsignedInteger('consecutivo_hc')->default(0);
            });
        }
        if (!Schema::hasColumn('users', 'registro_profesional')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('registro_profesional', 60)->nullable();
                $t->string('especialidad', 80)->nullable();
                $t->string('firma_path')->nullable();
            });
        }

        if (!Schema::hasTable('pacientes')) {
            Schema::create('pacientes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->string('tipo_documento', 5)->default('CC');
                $t->string('documento', 30)->nullable()->index();
                $t->string('nombres', 80);
                $t->string('apellidos', 80)->nullable();
                $t->date('fecha_nacimiento')->nullable();
                $t->string('sexo', 1)->nullable(); // F, M, I
                $t->string('telefono', 30)->nullable();
                $t->string('correo', 120)->nullable();
                $t->string('direccion', 160)->nullable();
                $t->string('ciudad', 80)->nullable();
                $t->string('aseguradora', 80)->nullable(); // EPS o prepagada (informativo)
                $t->string('ocupacion', 80)->nullable();
                $t->string('acompanante', 160)->nullable(); // contacto de emergencia / acudiente
                $t->text('alergias')->nullable();      // cifrado
                $t->text('antecedentes')->nullable();  // cifrado (json)
                $t->text('notas')->nullable();         // cifrado
                $t->timestamp('autoriza_datos_at')->nullable(); // autorización de tratamiento de datos (Ley 1581)
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('citas')) {
            Schema::create('citas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->foreignId('profesional_id')->nullable()->index();
                $t->date('fecha')->index();
                $t->time('hora');
                $t->unsignedSmallInteger('duracion')->default(30);
                $t->string('motivo', 160)->nullable();
                $t->string('estado', 15)->default('programada'); // programada, confirmada, atendida, no_asistio, cancelada
                $t->decimal('valor', 14, 2)->nullable();
                $t->string('nota', 255)->nullable();
                $t->foreignId('atencion_id')->nullable();
                $t->foreignId('user_id')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('atenciones')) {
            Schema::create('atenciones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('paciente_id')->index();
                $t->foreignId('profesional_id')->index();
                $t->foreignId('cita_id')->nullable();
                $t->unsignedInteger('numero')->default(0);
                $t->string('plantilla', 30)->default('general');
                $t->dateTime('fecha');
                $t->longText('contenido')->nullable(); // cifrado (json): motivo, enfermedad actual, examen, plan…
                $t->text('diagnosticos')->nullable();  // cifrado (json): [{codigo, texto, tipo}]
                $t->longText('formula')->nullable();   // cifrado
                $t->unsignedSmallInteger('incapacidad_dias')->nullable();
                $t->date('incapacidad_desde')->nullable();
                $t->string('profesional_nombre', 120)->nullable();
                $t->string('profesional_registro', 60)->nullable();
                $t->string('profesional_especialidad', 80)->nullable();
                $t->timestamp('cerrada_at')->nullable(); // cerrada = firmada, ya no se edita
                $t->string('huella', 64)->nullable();    // SHA-256 del contenido al cerrar
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('atencion_notas')) {
            Schema::create('atencion_notas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('atencion_id')->index();
                $t->foreignId('user_id')->nullable();
                $t->string('autor', 120)->nullable();
                $t->text('texto'); // cifrado
                $t->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('clinica_accesos')) {
            Schema::create('clinica_accesos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('user_id')->nullable();
                $t->foreignId('paciente_id')->nullable()->index();
                $t->foreignId('atencion_id')->nullable();
                $t->string('accion', 20); // ver, crear, editar, cerrar, nota, imprimir
                $t->string('ip', 45)->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }

        // Permisos: agenda (recepción) e historia clínica (profesional de salud)
        $agenda = Permission::firstOrCreate(['name' => 'agenda-consultorio', 'guard_name' => 'web']);
        $hc = Permission::firstOrCreate(['name' => 'historia-clinica', 'guard_name' => 'web']);
        foreach (Role::all() as $rol) {
            if ($rol->name === 'dueno' || $rol->name === 'administrador' || $rol->hasPermissionTo('editar-negocio')) {
                $rol->givePermissionTo([$agenda, $hc]);
            } elseif ($rol->hasPermissionTo('usar-pos')) {
                $rol->givePermissionTo($agenda);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica_accesos');
        Schema::dropIfExists('atencion_notas');
        Schema::dropIfExists('atenciones');
        Schema::dropIfExists('citas');
        Schema::dropIfExists('pacientes');
        if (Schema::hasColumn('users', 'registro_profesional')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['registro_profesional', 'especialidad', 'firma_path']));
        }
        if (Schema::hasColumn('tiendas', 'mod_consultorio')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['mod_consultorio', 'consecutivo_hc']));
        }
        Permission::whereIn('name', ['agenda-consultorio', 'historia-clinica'])->delete();
    }
};
