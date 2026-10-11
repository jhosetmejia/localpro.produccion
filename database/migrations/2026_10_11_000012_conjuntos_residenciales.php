<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Módulo de administración de conjuntos residenciales, torres y edificios
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'conj_dia_vence')) {
                $t->unsignedTinyInteger('conj_dia_vence')->default(10);
                $t->decimal('conj_interes_mora', 5, 2)->default(0); // % mensual sobre el saldo vencido
                $t->text('conj_zonas')->nullable();                // zonas comunes reservables, separadas por coma
                $t->unsignedInteger('consecutivo_rc')->default(0);  // recibos de caja
            }
        });

        Schema::create('unidades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('torre', 30)->nullable();
            $t->string('numero', 20);
            $t->string('tipo', 20)->default('apartamento'); // apartamento | casa | local | parqueadero | otro
            $t->decimal('coeficiente', 9, 5)->nullable();
            $t->decimal('cuota', 12, 2)->default(0);         // cuota de administración mensual
            $t->string('propietario', 80)->nullable();
            $t->string('propietario_tel', 20)->nullable();
            $t->string('propietario_email', 120)->nullable();
            $t->string('propietario_doc', 20)->nullable();
            $t->string('residente', 80)->nullable();
            $t->string('residente_tel', 20)->nullable();
            $t->string('notas', 255)->nullable();
            $t->boolean('activa')->default(true);
            $t->timestamps();
            $t->unique(['tienda_id', 'torre', 'numero']);
        });

        Schema::create('conjunto_cargos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $t->char('periodo', 7);                           // 2026-10
            $t->string('tipo', 20)->default('administracion'); // administracion | extraordinaria | multa | interes | otro
            $t->string('concepto', 120);
            $t->decimal('valor', 12, 2);
            $t->decimal('pagado', 12, 2)->default(0);
            $t->date('vence')->nullable();
            $t->string('estado', 12)->default('pendiente');   // pendiente | parcial | pagado | anulado
            $t->timestamps();
            $t->index(['tienda_id', 'unidad_id', 'estado']);
            $t->index(['tienda_id', 'periodo', 'tipo']);
        });

        Schema::create('conjunto_pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->date('fecha');
            $t->decimal('valor', 12, 2);
            $t->decimal('saldo_favor', 12, 2)->default(0);    // lo que sobró después de cubrir los cargos
            $t->string('metodo', 40)->default('Efectivo');
            $t->string('referencia', 60)->nullable();
            $t->string('nota', 255)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('anulado')->default(false);
            $t->timestamps();
        });

        Schema::create('conjunto_pago_cargo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pago_id')->constrained('conjunto_pagos')->cascadeOnDelete();
            $t->foreignId('cargo_id')->constrained('conjunto_cargos')->cascadeOnDelete();
            $t->decimal('valor', 12, 2);
        });

        Schema::create('conjunto_gastos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->date('fecha');
            $t->string('categoria', 40);
            $t->string('concepto', 160);
            $t->decimal('valor', 12, 2);
            $t->string('proveedor', 80)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('conjunto_comunicados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('titulo', 120);
            $t->text('mensaje');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('conjunto_pqrs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $t->string('tipo', 20)->default('peticion'); // peticion | queja | reclamo | sugerencia | dano
            $t->string('asunto', 120);
            $t->text('detalle')->nullable();
            $t->string('estado', 12)->default('abierta'); // abierta | en_proceso | cerrada
            $t->text('respuesta')->nullable();
            $t->timestamps();
        });

        Schema::create('conjunto_reservas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('unidad_id')->constrained('unidades')->cascadeOnDelete();
            $t->string('zona', 60);
            $t->date('fecha');
            $t->time('hora_inicio');
            $t->time('hora_fin');
            $t->string('estado', 12)->default('confirmada'); // confirmada | cancelada
            $t->string('nota', 255)->nullable();
            $t->timestamps();
            $t->index(['tienda_id', 'zona', 'fecha']);
        });
    }

    public function down(): void
    {
        foreach (['conjunto_reservas', 'conjunto_pqrs', 'conjunto_comunicados', 'conjunto_gastos', 'conjunto_pago_cargo', 'conjunto_pagos', 'conjunto_cargos', 'unidades'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['conj_dia_vence', 'conj_interes_mora', 'conj_zonas', 'consecutivo_rc']));
    }
};
