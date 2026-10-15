<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Obituarios para funerarias: salas de velación, plantillas de pantalla (diseño, medida, fondo, video, animación)
// y obituarios que se muestran en el televisor de cada sala.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mod_obituarios')) {
                $t->boolean('mod_obituarios')->default(false);
                $t->string('obituarios_token', 40)->nullable()->unique(); // pantalla general (recepción) que rota todos
                $t->unsignedSmallInteger('obituarios_rotar')->default(12); // segundos por obituario en la pantalla general
            }
        });
        // Las funerarias que ya existan quedan con el módulo encendido
        DB::table('tiendas')->where('tipo_negocio', 'funeraria')->update(['mod_obituarios' => true]);

        if (!Schema::hasTable('obituario_salas')) {
            Schema::create('obituario_salas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('nombre', 60);
                $t->string('token', 40)->unique(); // dirección de la pantalla de la sala (sin iniciar sesión)
                $t->boolean('activa')->default(true);
                $t->unsignedInteger('orden')->default(0);
                $t->timestamps();
                $t->index(['tienda_id', 'orden']);
            });
        }

        if (!Schema::hasTable('obituario_plantillas')) {
            Schema::create('obituario_plantillas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('nombre', 60);
                $t->string('diseno', 20)->default('clasico');
                $t->string('medida', 20)->default('fhd');
                $t->string('fondo_tipo', 10)->default('diseno'); // diseno · color · imagen · video
                $t->string('fondo_color', 9)->nullable();
                $t->string('fondo_color2', 9)->nullable();       // segundo color (degradado)
                $t->string('fondo_path')->nullable();
                $t->string('video_path')->nullable();
                $t->unsignedTinyInteger('oscurecer')->default(0); // capa oscura sobre la imagen/video (0–80 %)
                $t->string('animacion', 20)->default('ninguna');
                $t->string('color_acento', 9)->nullable();
                $t->string('color_texto', 9)->nullable();
                $t->boolean('mostrar_logo')->default(true);
                $t->string('titulo', 60)->default('Obituario');
                $t->string('frase', 120)->nullable();
                $t->string('pie', 150)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('obituarios')) {
            Schema::create('obituarios', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('plantilla_id')->nullable()->constrained('obituario_plantillas')->nullOnDelete();
                $t->foreignId('sala_id')->nullable()->constrained('obituario_salas')->nullOnDelete();
                $t->string('nombre', 120);
                $t->string('foto_path')->nullable();
                $t->date('nacimiento')->nullable();
                $t->date('fallecimiento')->nullable();
                $t->string('frase', 150)->nullable();       // si está vacía se usa la de la plantilla
                $t->date('fecha_exequias')->nullable();
                $t->time('hora_exequias')->nullable();
                $t->string('lugar_exequias', 150)->nullable();
                $t->string('destino_final', 150)->nullable();
                $t->text('invitan')->nullable();           // una línea por grupo: "Su madre: Rosa…"
                $t->dateTime('mostrar_hasta')->nullable(); // después de esta hora sale de la pantalla solo
                $t->string('estado', 12)->default('activo'); // activo · finalizado
                $t->timestamps();
                $t->index(['tienda_id', 'estado']);
            });
        }

        // Permiso para crear obituarios: administradores y quienes atienden en la caja (recepción)
        if (Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'gestionar-obituarios', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio') || $rol->hasPermissionTo('usar-pos')) $rol->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) DB::table('permissions')->where('name', 'gestionar-obituarios')->delete();
        Schema::dropIfExists('obituarios');
        Schema::dropIfExists('obituario_plantillas');
        Schema::dropIfExists('obituario_salas');
        if (Schema::hasColumn('tiendas', 'mod_obituarios')) {
            Schema::table('tiendas', function (Blueprint $t) {
                $t->dropUnique(['obituarios_token']);
                $t->dropColumn(['mod_obituarios', 'obituarios_token', 'obituarios_rotar']);
            });
        }
    }
};
