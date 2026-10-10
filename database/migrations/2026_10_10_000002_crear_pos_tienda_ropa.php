<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// POS para tienda de ropa: tallas/colores con stock, métodos de pago, cajas por vendedor y ventas POS.
return new class extends Migration
{
    public function up(): void
    {
        // Precio de venta base del producto; marca y presentación pasan a ser opcionales
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_venta', 14, 2)->default(0)->after('nombre');
            $table->unsignedBigInteger('marca_id')->nullable()->change();
            $table->unsignedBigInteger('presentacione_id')->nullable()->change();
        });

        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('talla', 20)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('codigo_barras', 60)->nullable()->unique();
            $table->integer('stock')->default(0);
            $table->decimal('precio', 14, 2)->nullable(); // vacío = usa el precio del producto
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();
            $table->unique(['producto_id', 'talla', 'color']);
        });

        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 40)->unique();
            $table->boolean('es_efectivo')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('abierta_en');
            $table->dateTime('cerrada_en')->nullable();
            $table->decimal('monto_inicial', 14, 2)->default(0);
            $table->decimal('total_ventas', 14, 2)->nullable();
            $table->decimal('efectivo_esperado', 14, 2)->nullable();
            $table->decimal('efectivo_contado', 14, 2)->nullable();
            $table->decimal('diferencia', 14, 2)->nullable();
            $table->string('nota_apertura', 255)->nullable();
            $table->string('nota_cierre', 255)->nullable();
            $table->string('estado', 10)->default('abierta');
            $table->timestamps();
            $table->index(['user_id', 'estado']);
        });

        Schema::create('pos_ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('caja_id')->constrained('cajas');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('cliente_nombre', 80)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('cambio', 14, 2)->default(0);
            $table->string('estado', 10)->default('pagada');
            $table->dateTime('anulada_en')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pos_venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_venta_id')->constrained('pos_ventas')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->string('descripcion', 120);
            $table->string('talla', 20)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('cantidad');
            $table->decimal('precio', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::create('pos_venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_venta_id')->constrained('pos_ventas')->cascadeOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->decimal('monto', 14, 2);
            $table->string('referencia', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_variante_id')->constrained('producto_variantes')->cascadeOnDelete();
            $table->string('tipo', 15); // entrada, salida, ajuste, venta, anulacion
            $table->integer('cantidad'); // positivo suma, negativo resta
            $table->integer('stock_resultante');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referencia', 40)->nullable();
            $table->string('nota', 255)->nullable();
            $table->timestamps();
        });

        // Métodos de pago iniciales (el negocio puede editarlos)
        $ahora = now();
        DB::table('metodos_pago')->insert([
            ['nombre' => 'Efectivo', 'es_efectivo' => true, 'activo' => true, 'orden' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Nequi', 'es_efectivo' => false, 'activo' => true, 'orden' => 2, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Daviplata', 'es_efectivo' => false, 'activo' => true, 'orden' => 3, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Transferencia', 'es_efectivo' => false, 'activo' => true, 'orden' => 4, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Tarjeta', 'es_efectivo' => false, 'activo' => true, 'orden' => 5, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        // Permisos nuevos; se asignan al administrador si ya existe
        $permisos = [
            'usar-pos', 'anular-venta-pos', 'ver-cajas', 'ver-cajas-todas',
            'ver-inventario', 'ajustar-inventario',
            'ver-metodo-pago', 'crear-metodo-pago', 'editar-metodo-pago',
        ];
        $permisoModel = \Spatie\Permission\Models\Permission::class;
        foreach ($permisos as $p) {
            $permisoModel::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $admin = \Spatie\Permission\Models\Role::where('name', 'administrador')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
        Schema::dropIfExists('pos_venta_pagos');
        Schema::dropIfExists('pos_venta_items');
        Schema::dropIfExists('pos_ventas');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('metodos_pago');
        Schema::dropIfExists('producto_variantes');
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });
    }
};
