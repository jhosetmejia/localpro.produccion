<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Cambios y devoluciones (con saldo a favor), y clientes desde el POS.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $table->string('codigo', 20); // NC-000001
            $table->foreignId('pos_venta_id')->constrained('pos_ventas')->cascadeOnDelete();
            $table->foreignId('caja_id')->constrained('cajas');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 12); // reembolso | cambio
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete(); // cómo se devolvió el dinero
            $table->decimal('total', 14, 2);
            $table->decimal('saldo_disponible', 14, 2)->default(0); // en cambios: lo que falta por usar
            $table->string('motivo', 255)->nullable();
            $table->timestamps();
            $table->unique(['tienda_id', 'codigo']);
        });
        Schema::create('devolucion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('pos_venta_item_id')->constrained('pos_venta_items')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::table('tiendas', function (Blueprint $table) {
            $table->unsignedInteger('consecutivo_nc')->default(0)->after('consecutivo_pos');
        });
        // Método especial para pagar con saldo a favor (no cuenta como dinero que entra a la caja)
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->boolean('es_credito')->default(false)->after('es_efectivo');
        });
        Schema::table('personas', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('direccion');
            $table->string('email', 120)->nullable()->after('telefono');
        });

        // Tipos de documento usados en Colombia y la región
        foreach (['Cédula de ciudadanía', 'NIT', 'Cédula de extranjería', 'Tarjeta de identidad', 'Pasaporte'] as $doc) {
            if (!DB::table('documentos')->where('tipo_documento', $doc)->exists()) {
                DB::table('documentos')->insert(['tipo_documento' => $doc, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $nuevos = ['devolver-venta'];
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
        Schema::table('personas', fn (Blueprint $t) => $t->dropColumn(['telefono', 'email']));
        Schema::table('metodos_pago', fn (Blueprint $t) => $t->dropColumn('es_credito'));
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('consecutivo_nc'));
        Schema::dropIfExists('devolucion_items');
        Schema::dropIfExists('devoluciones');
    }
};
