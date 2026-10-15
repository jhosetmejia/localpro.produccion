<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Nómina: empleados, periodos de pago, liquidación por empleado y nómina electrónica DIAN (opcional por negocio)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'nomina_modo')) {
                $t->string('nomina_modo', 12)->nullable();             // null = no ha respondido · no · interna · electronica
                $t->string('nomina_periodicidad', 10)->default('mensual'); // mensual · quincenal
                $t->boolean('nomina_exonerada')->default(true);       // art. 114-1 E.T.
                $t->string('nomina_prefijo', 10)->default('NE');
                $t->unsignedInteger('consecutivo_ne')->default(0);
            }
        });

        Schema::create('empleados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('tipo_documento', 4)->default('13'); // códigos DIAN: 13 CC, 22 CE, 41 PA, 47 PEP, 48 PPT
            $t->string('documento', 20);
            $t->string('nombres', 60);
            $t->string('apellidos', 60);
            $t->string('email', 120)->nullable();
            $t->string('telefono', 20)->nullable();
            $t->string('direccion', 120)->nullable();
            $t->string('municipio_dane', 5)->nullable();
            $t->string('cargo', 60)->nullable();
            $t->string('tipo', 20)->default('dependiente');
            $t->string('tipo_contrato', 20)->default('indefinido'); // indefinido · fijo · obra · aprendizaje · practicas
            $t->decimal('salario', 14, 2);
            $t->boolean('salario_integral')->default(false);
            $t->boolean('aux_transporte')->nullable(); // null = automático (hasta 2 SMMLV)
            $t->unsignedTinyInteger('riesgo_arl')->default(1);
            // Liquidación personalizada: pago pactado libre (más o menos del mínimo) y el negocio decide qué aplica
            $t->boolean('personalizado')->default(false);
            $t->boolean('seguridad_social')->default(true);
            $t->boolean('prestaciones')->default(true);
            $t->boolean('enviar_dian')->default(true);
            $t->date('fecha_ingreso');
            $t->date('fecha_retiro')->nullable();
            $t->string('eps', 60)->nullable();
            $t->string('afp', 60)->nullable();
            $t->string('caja', 60)->nullable();
            $t->string('forma_pago', 20)->default('transferencia'); // transferencia · efectivo · cheque
            $t->string('banco', 40)->nullable();
            $t->string('tipo_cuenta', 10)->nullable();
            $t->string('numero_cuenta', 30)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->unique(['tienda_id', 'documento']);
        });

        Schema::create('nomina_periodos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('nombre', 60);
            $t->string('periodicidad', 10);
            $t->date('fecha_inicio');
            $t->date('fecha_fin');
            $t->date('fecha_pago');
            $t->string('estado', 12)->default('borrador'); // borrador · liquidada · pagada
            $t->decimal('total_devengado', 14, 2)->default(0);
            $t->decimal('total_deducido', 14, 2)->default(0);
            $t->decimal('total_neto', 14, 2)->default(0);
            $t->decimal('total_aportes', 14, 2)->default(0);
            $t->decimal('total_provisiones', 14, 2)->default(0);
            $t->string('nota', 255)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['tienda_id', 'fecha_fin']);
        });

        Schema::create('nomina_liquidaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('nomina_periodo_id')->constrained('nomina_periodos')->cascadeOnDelete();
            $t->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $t->decimal('salario', 14, 2);
            $t->json('novedades')->nullable();
            $t->json('resultado')->nullable();
            $t->unsignedSmallInteger('dias')->default(0);
            $t->decimal('ibc', 14, 2)->default(0);
            $t->decimal('total_devengado', 14, 2)->default(0);
            $t->decimal('total_deducido', 14, 2)->default(0);
            $t->decimal('neto', 14, 2)->default(0);
            $t->timestamps();
            $t->unique(['nomina_periodo_id', 'empleado_id']);
        });

        Schema::create('nomina_electronicas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $t->string('mes', 7); // 2026-10
            $t->string('tipo_xml', 3)->default('102'); // 102 nómina individual · 103 nota de ajuste
            $t->unsignedTinyInteger('tipo_nota')->nullable(); // 1 reemplazar · 2 eliminar
            $t->foreignId('referencia_id')->nullable()->constrained('nomina_electronicas')->nullOnDelete();
            $t->string('prefijo', 10);
            $t->unsignedInteger('consecutivo');
            $t->string('numero', 30);
            $t->string('cune', 96)->nullable();
            $t->string('estado', 12)->default('pendiente');
            $t->string('ambiente', 12)->default('pruebas');
            $t->string('proveedor', 30)->nullable();
            $t->decimal('devengado', 14, 2)->default(0);
            $t->decimal('deducido', 14, 2)->default(0);
            $t->decimal('total', 14, 2)->default(0);
            $t->unsignedSmallInteger('intentos')->default(0);
            $t->text('ultimo_error')->nullable();
            $t->json('payload')->nullable();
            $t->json('respuesta')->nullable();
            $t->timestamp('enviado_en')->nullable();
            $t->timestamp('validado_en')->nullable();
            $t->timestamps();
            $t->unique(['tienda_id', 'numero']);
            $t->index(['tienda_id', 'mes']);
        });

        // Permiso para manejar la nómina: lo tienen quienes administran el negocio
        if (Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'gestionar-nomina', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_electronicas');
        Schema::dropIfExists('nomina_liquidaciones');
        Schema::dropIfExists('nomina_periodos');
        Schema::dropIfExists('empleados');
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['nomina_modo', 'nomina_periodicidad', 'nomina_exonerada', 'nomina_prefijo', 'consecutivo_ne']));
        if (Schema::hasTable('permissions')) DB::table('permissions')->where('name', 'gestionar-nomina')->delete();
    }
};
