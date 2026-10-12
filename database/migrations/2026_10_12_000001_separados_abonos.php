<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Separados (apartados) con abonos: el cliente deja la prenda separada, abona por partes y al completar se la lleva
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('separados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->string('cliente_nombre', 80);
            $t->string('cliente_telefono', 20)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('total', 14, 2);
            $t->decimal('abonado', 14, 2)->default(0);
            $t->string('estado', 12)->default('activo'); // activo, entregado, cancelado
            $t->date('vence_en')->nullable();
            $t->string('nota', 255)->nullable();
            $t->foreignId('pos_venta_id')->nullable()->constrained('pos_ventas')->nullOnDelete();
            $t->dateTime('entregado_en')->nullable();
            $t->dateTime('cancelado_en')->nullable();
            $t->string('motivo_cancelacion', 255)->nullable();
            $t->timestamps();
            $t->unique(['tienda_id', 'numero']);
            $t->index(['tienda_id', 'estado']);
        });

        Schema::create('separado_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('separado_id')->constrained('separados')->cascadeOnDelete();
            $t->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $t->string('descripcion', 120);
            $t->string('talla', 20)->nullable();
            $t->string('color', 30)->nullable();
            $t->decimal('cantidad', 12, 3);
            $t->decimal('precio', 14, 2);
            $t->decimal('subtotal', 14, 2);
            $t->timestamps();
        });

        Schema::create('separado_abonos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('separado_id')->constrained('separados')->cascadeOnDelete();
            $t->string('numero', 20);
            $t->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $t->decimal('monto', 14, 2);
            $t->decimal('saldo_despues', 14, 2);
            $t->string('nota', 255)->nullable();
            $t->boolean('anulado')->default(false);
            $t->timestamps();
            $t->unique(['tienda_id', 'numero']);
            $t->index(['caja_id', 'anulado']);
        });

        // Método interno "Pagado con abonos": se usa al entregar un separado (el dinero ya entró con cada abono)
        Schema::table('metodos_pago', function (Blueprint $t) {
            $t->boolean('interno')->default(false)->after('activo');
        });

        // Permiso para manejar separados: lo tienen quienes venden
        if (Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'usar-separados', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('usar-pos')) $rol->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('separado_abonos');
        Schema::dropIfExists('separado_items');
        Schema::dropIfExists('separados');
        Schema::table('metodos_pago', fn (Blueprint $t) => $t->dropColumn('interno'));
        DB::table('permissions')->where('name', 'usar-separados')->delete();
    }
};
