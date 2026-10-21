<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// 1) Roles propios de cada negocio (Caja, Mesero, Cocina, Entradas…): el dueño los crea y elige sus permisos.
// 2) Pedidos de restaurante: el mesero toma el pedido, caja cobra (antes o al final), cada estación
//    (cocina, cafetería, entradas…) ve en su pantalla lo que le toca, marca "listo" y el mesero entrega.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('negocio_roles')) {
            Schema::create('negocio_roles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->index();
                $t->string('nombre', 40);
                $t->string('icono', 40)->nullable();
                $t->json('permisos')->nullable();
                $t->json('estaciones')->nullable();     // estaciones que ve en su pantalla (vacío = todas)
                $t->string('inicio', 20)->nullable();   // pantalla al entrar: mesero | caja | estacion | pos | panel
                $t->unsignedSmallInteger('orden')->default(0);
                $t->timestamps();
            });
        }
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'negocio_rol_id')) $t->foreignId('negocio_rol_id')->nullable()->index();
        });

        if (!Schema::hasTable('pedido_estaciones')) {
            Schema::create('pedido_estaciones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->index();
                $t->string('nombre', 40);
                $t->string('icono', 40)->nullable();
                $t->boolean('imprimir')->default(false);   // imprimir la comanda cuando llega
                $t->boolean('activo')->default(true);
                $t->unsignedSmallInteger('orden')->default(0);
                $t->timestamps();
            });
        }
        Schema::table('productos', function (Blueprint $t) {
            if (!Schema::hasColumn('productos', 'estacion_id')) $t->foreignId('estacion_id')->nullable();
        });

        if (!Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id');
                $t->unsignedInteger('numero');
                $t->string('cliente_nombre', 60)->nullable();
                $t->string('mesa', 20)->nullable();
                $t->string('nota', 255)->nullable();
                $t->foreignId('user_id')->nullable();          // mesero
                $t->string('modo', 10)->default('antes');      // antes: paga y luego se prepara | despues: paga al final
                $t->string('estado', 15)->default('por_cobrar'); // por_cobrar | en_preparacion | listo | entregado | cerrado | anulado
                $t->boolean('cuenta_pedida')->default(false);
                $t->decimal('total', 14, 2)->default(0);
                $t->boolean('pagado')->default(false);
                $t->foreignId('pos_venta_id')->nullable();
                $t->foreignId('cobrado_por')->nullable();
                $t->timestamp('pagado_at')->nullable();
                $t->timestamp('listo_at')->nullable();
                $t->timestamp('entregado_at')->nullable();
                $t->timestamp('cerrado_at')->nullable();
                $t->timestamps();
                $t->index(['tienda_id', 'estado']);
                $t->index(['tienda_id', 'created_at']);
            });
        }
        if (!Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pedido_id')->index();
                $t->foreignId('producto_id')->nullable();
                $t->foreignId('producto_variante_id')->nullable();
                $t->foreignId('estacion_id')->nullable()->index();
                $t->string('nombre', 120);
                $t->string('etiqueta', 60)->nullable();
                $t->decimal('cantidad', 10, 3)->default(1);
                $t->decimal('precio', 14, 2)->default(0);
                $t->decimal('subtotal', 14, 2)->default(0);
                $t->string('nota', 160)->nullable();
                $t->string('estado', 12)->default('pendiente'); // pendiente | listo | entregado | anulado
                $t->unsignedTinyInteger('ronda')->default(1);
                $t->timestamp('enviado_at')->nullable();         // desde cuándo lo ve la estación
                $t->timestamp('listo_at')->nullable();
                $t->timestamp('entregado_at')->nullable();
                $t->timestamps();
            });
        }

        Schema::table('tiendas', function (Blueprint $t) {
            if (!Schema::hasColumn('tiendas', 'mod_pedidos')) $t->boolean('mod_pedidos')->default(false);
            if (!Schema::hasColumn('tiendas', 'pedidos_cobro')) $t->string('pedidos_cobro', 10)->default('antes');
            if (!Schema::hasColumn('tiendas', 'pedidos_token')) $t->string('pedidos_token', 40)->nullable();
            if (!Schema::hasColumn('tiendas', 'consecutivo_pedido')) $t->unsignedInteger('consecutivo_pedido')->default(0);
            if (!Schema::hasColumn('tiendas', 'consecutivo_pedido_fecha')) $t->date('consecutivo_pedido_fecha')->nullable();
        });
        // Restaurantes y cafeterías: pedidos encendidos
        DB::table('tiendas')->whereIn('tipo_negocio', ['restaurante', 'cafeteria'])->update(['mod_pedidos' => true]);

        // Permisos nuevos
        $nuevos = ['pedidos-tomar', 'pedidos-cobrar', 'pedidos-preparar', 'pedidos-administrar'];
        foreach ($nuevos as $p) Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        foreach (Role::all() as $rol) {
            if (in_array($rol->name, ['dueno', 'administrador'], true) || $rol->hasPermissionTo('editar-negocio')) {
                $rol->givePermissionTo($nuevos);
            }
        }
        // Rol base de los empleados con rol propio del negocio: lo demás se lo da su rol
        $base = array_values(array_filter(['ver-perfil', 'editar-perfil'], fn ($p) => Permission::where('name', $p)->exists()));
        Role::firstOrCreate(['name' => 'empleado', 'guard_name' => 'web'])->syncPermissions($base);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('pedido_estaciones');
        Schema::dropIfExists('negocio_roles');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('negocio_rol_id'));
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('estacion_id'));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['mod_pedidos', 'pedidos_cobro', 'pedidos_token', 'consecutivo_pedido', 'consecutivo_pedido_fecha']));
        Permission::whereIn('name', ['pedidos-tomar', 'pedidos-cobrar', 'pedidos-preparar', 'pedidos-administrar'])->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
