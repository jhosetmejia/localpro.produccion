<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Contabilidad: retenciones e IVA para analistas contables.
// Cada negocio (el analista) maneja varios clientes; por cliente y mes carga las facturas de la DIAN y la base
// de su programa contable (Contapyme, Siigo, World Office…), el sistema cruza y arma el borrador del 350 y del IVA.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'mod_contable')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('mod_contable')->default(false));
        }
        DB::table('tiendas')->where('tipo_negocio', 'contable')->update(['mod_contable' => true]);

        if (!Schema::hasTable('contable_clientes')) {
            Schema::create('contable_clientes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('nombre', 150);
                $t->string('nit', 20);
                $t->string('dv', 2)->nullable();
                $t->string('tipo_persona', 10)->default('juridica'); // juridica · natural
                $t->boolean('agente_retenedor')->default(true);
                $t->boolean('autorretenedor')->default(false);
                $t->string('periodicidad_iva', 15)->default('bimestral'); // bimestral · cuatrimestral · no_responsable
                $t->string('ciudad', 80)->nullable();
                $t->text('notas')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->index(['tienda_id', 'nit']);
            });
        }

        // Proveedores y clientes de cada cliente: se recuerda su tipo y su concepto de un mes a otro
        if (!Schema::hasTable('contable_terceros')) {
            Schema::create('contable_terceros', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('cliente_id')->constrained('contable_clientes')->cascadeOnDelete();
                $t->string('nit', 20);
                $t->string('nombre', 150)->nullable();
                $t->string('tipo_persona', 10)->nullable(); // juridica · natural
                $t->boolean('declarante')->default(true);
                $t->boolean('autorretenedor')->default(false); // si el proveedor se autorretiene, no se le retiene
                $t->string('concepto', 30)->nullable();        // concepto habitual de retención
                $t->timestamps();
                $t->unique(['cliente_id', 'nit']);
            });
        }

        if (!Schema::hasTable('contable_periodos')) {
            Schema::create('contable_periodos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('cliente_id')->constrained('contable_clientes')->cascadeOnDelete();
                $t->unsignedSmallInteger('anio');
                $t->unsignedTinyInteger('mes');
                $t->string('estado', 15)->default('abierto'); // abierto · revisado · presentado
                $t->decimal('autorretencion_base', 16, 2)->default(0);
                $t->decimal('autorretencion_valor', 16, 2)->default(0);
                $t->string('archivo_dian')->nullable();
                $t->string('archivo_contable')->nullable();
                $t->json('mapeo_contable')->nullable(); // columnas elegidas del archivo contable
                $t->text('notas')->nullable();
                $t->timestamps();
                $t->unique(['cliente_id', 'anio', 'mes']);
            });
        }

        if (!Schema::hasTable('contable_documentos')) {
            Schema::create('contable_documentos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->foreignId('periodo_id')->constrained('contable_periodos')->cascadeOnDelete();
                $t->string('origen', 10);            // dian · contable
                $t->string('direccion', 10);         // recibida (compra/gasto) · emitida (venta)
                $t->string('tipo_doc', 60)->nullable();
                $t->string('documento', 60)->nullable(); // prefijo + folio como viene
                $t->string('clave', 80)->nullable();     // nit + número para cruzar
                $t->string('cufe', 120)->nullable();
                $t->date('fecha')->nullable();
                $t->string('nit_tercero', 20)->nullable();
                $t->string('nombre_tercero', 150)->nullable();
                $t->decimal('base', 16, 2)->default(0);
                $t->decimal('iva', 16, 2)->default(0);
                $t->decimal('otros_impuestos', 16, 2)->default(0);
                $t->decimal('total', 16, 2)->default(0);
                $t->decimal('rete_fuente', 16, 2)->default(0);
                $t->decimal('rete_iva', 16, 2)->default(0);
                $t->decimal('rete_ica', 16, 2)->default(0);
                $t->string('cuenta', 30)->nullable();
                $t->string('concepto', 30)->nullable();
                $t->foreignId('par_id')->nullable(); // documento del otro origen con el que cruzó
                $t->timestamps();
                $t->index(['periodo_id', 'origen', 'clave']);
            });
        }

        // Tarifas de retención con vigencia (la norma cambia: ej. Decreto 572 de 2025 y su suspensión en mayo de 2026)
        if (!Schema::hasTable('contable_tarifas')) {
            Schema::create('contable_tarifas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('concepto', 30);
                $t->string('nombre', 100);
                $t->decimal('base_uvt', 8, 2)->default(0);
                $t->decimal('tarifa_declarante', 6, 3)->default(0);    // % para declarantes / personas jurídicas
                $t->decimal('tarifa_no_declarante', 6, 3)->default(0); // % para no declarantes / personas naturales
                $t->date('desde')->nullable();
                $t->date('hasta')->nullable();
                $t->unsignedSmallInteger('orden')->default(0);
                $t->timestamps();
                $t->index(['tienda_id', 'concepto']);
            });
        }

        if (Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'contabilidad', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        foreach (['contable_documentos', 'contable_periodos', 'contable_terceros', 'contable_tarifas', 'contable_clientes'] as $t) Schema::dropIfExists($t);
        if (Schema::hasColumn('tiendas', 'mod_contable')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('mod_contable'));
        if (Schema::hasTable('permissions')) DB::table('permissions')->where('name', 'contabilidad')->delete();
    }
};
