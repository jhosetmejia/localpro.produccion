<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Costo y ganancia, productos sin control de inventario (comidas, servicios) y entradas de mercancía.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_costo', 14, 2)->default(0)->after('precio_venta');
            $table->boolean('controla_inventario')->default(true)->after('precio_costo');
        });
        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->decimal('costo', 14, 2)->nullable()->after('precio'); // vacío = costo del producto
        });
        Schema::table('pos_venta_items', function (Blueprint $table) {
            $table->decimal('costo', 14, 2)->default(0)->after('precio'); // costo unitario al momento de vender
        });

        // En restaurantes y cafeterías normalmente no se cuenta el inventario de lo que se prepara
        $ids = DB::table('tiendas')->whereIn('tipo_negocio', ['restaurante', 'cafeteria'])->pluck('id');
        DB::table('productos')->whereIn('tienda_id', $ids)->update(['controla_inventario' => false]);

        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->foreignId('proveedore_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('numero_factura', 40)->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->string('nota', 255)->nullable();
            $table->timestamps();
        });
        Schema::create('entrada_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id')->constrained('entradas')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->string('descripcion', 140);
            $table->integer('cantidad');
            $table->decimal('costo', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        $nuevos = ['ver-entradas', 'crear-entradas', 'ver-reportes'];
        foreach ($nuevos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        foreach (['dueno', 'administrador'] as $r) {
            Role::where('name', $r)->first()?->givePermissionTo($nuevos);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada_items');
        Schema::dropIfExists('entradas');
        Schema::table('pos_venta_items', fn (Blueprint $t) => $t->dropColumn('costo'));
        Schema::table('producto_variantes', fn (Blueprint $t) => $t->dropColumn('costo'));
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn(['precio_costo', 'controla_inventario']));
    }
};
