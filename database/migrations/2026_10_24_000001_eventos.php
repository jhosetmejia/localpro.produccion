<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// LocalProo Eventos: página del evento (landing), registro del asistente con clave, pago por Nequi con
// comprobante por WhatsApp, aprobación manual en el panel y entrada con QR único que se valida en la puerta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mod_eventos')) {
                $t->boolean('mod_eventos')->default(false);
            }
        });

        if (!Schema::hasTable('eventos')) {
            Schema::create('eventos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('nombre', 120);
                $t->string('slug', 80)->unique();          // localproo.com/evento/{slug}
                $t->string('subtitulo', 120)->nullable();  // "El despertar"
                $t->text('descripcion')->nullable();
                $t->dateTime('fecha_evento')->nullable();
                $t->string('lugar', 150)->nullable();
                $t->string('ciudad', 80)->nullable();
                $t->string('imagen_path')->nullable();     // afiche principal
                $t->string('imagen2_path')->nullable();    // afiche con la información
                $t->string('etapa', 60)->nullable();       // "Primera etapa"
                $t->decimal('precio', 14, 2)->default(0);
                $t->dateTime('venta_hasta')->nullable();   // vigencia de la etapa / cierre de la venta
                $t->unsignedInteger('cupo')->nullable();   // máximo de entradas (vacío = sin límite)
                $t->string('pago_metodo', 40)->default('Nequi');
                $t->string('pago_numero', 40)->nullable(); // número de Nequi / cuenta
                $t->string('pago_titular', 80)->nullable();
                $t->string('whatsapp', 30)->nullable();    // a dónde se manda el comprobante
                $t->string('mensaje_whatsapp', 255)->nullable();
                $t->string('instagram', 80)->nullable();
                $t->string('tiktok', 80)->nullable();
                $t->boolean('pedir_colegio')->default(true);
                $t->unsignedTinyInteger('edad_minima')->nullable();
                $t->text('condiciones')->nullable();
                $t->string('color', 9)->default('#a855f7');
                $t->string('estado', 12)->default('borrador'); // borrador · publicado · cerrado
                $t->timestamps();
                $t->index(['tienda_id', 'estado']);
            });
        }

        // Personas que compran entradas (cuenta propia con correo y clave, distinta de los usuarios del negocio)
        if (!Schema::hasTable('evento_asistentes')) {
            Schema::create('evento_asistentes', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 120);
                $t->string('email', 150)->unique();
                $t->string('password');
                $t->string('tipo_documento', 4)->default('CC'); // CC · TI · CE · PP
                $t->string('documento', 30);
                $t->date('fecha_nacimiento')->nullable();
                $t->string('colegio', 120)->nullable();
                $t->string('celular', 30)->nullable();
                $t->timestamp('acepta_datos_at')->nullable();   // Ley 1581 de 2012
                $t->boolean('autoriza_acudiente')->default(false); // menores de edad
                $t->rememberToken();
                $t->timestamps();
                $t->index('documento');
            });
        }

        if (!Schema::hasTable('evento_entradas')) {
            Schema::create('evento_entradas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
                $t->foreignId('asistente_id')->constrained('evento_asistentes')->cascadeOnDelete();
                $t->uuid('uuid')->unique();
                $t->string('codigo', 24)->unique();          // lo que lleva el QR
                $t->decimal('precio', 14, 2)->default(0);
                $t->string('etapa', 60)->nullable();
                $t->string('estado', 12)->default('pendiente'); // pendiente · aprobada · rechazada · anulada
                $t->timestamp('aviso_pago_at')->nullable();  // tocó "Enviar comprobante de pago"
                $t->timestamp('aprobada_at')->nullable();
                $t->foreignId('aprobada_por')->nullable()->constrained('users')->nullOnDelete();
                $t->string('referencia_pago', 80)->nullable();
                $t->string('nota', 255)->nullable();
                $t->timestamp('ingreso_at')->nullable();     // pasó por la puerta
                $t->foreignId('ingreso_por')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->unique(['evento_id', 'asistente_id']);
                $t->index(['evento_id', 'estado']);
            });
        }

        if (Schema::hasTable('permissions')) {
            $g = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'gestionar-eventos', 'guard_name' => 'web']);
            $p = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'eventos-ingreso', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo([$g, $p]);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) DB::table('permissions')->whereIn('name', ['gestionar-eventos', 'eventos-ingreso'])->delete();
        Schema::dropIfExists('evento_entradas');
        Schema::dropIfExists('evento_asistentes');
        Schema::dropIfExists('eventos');
        if (Schema::hasColumn('tiendas', 'mod_eventos')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_eventos'));
        }
    }
};
