<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Datos del negocio para tiquete/factura (con logo) y teléfono del cliente para enviar la factura por WhatsApp.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->default('Mi Tienda');
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 120)->nullable();
            $table->string('ciudad', 60)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('mensaje_tiquete', 255)->nullable();
            $table->timestamps();
        });

        DB::table('negocio')->insert([
            'nombre' => 'Mi Tienda',
            'mensaje_tiquete' => '¡Gracias por su compra! Cambios dentro de los 8 días con este comprobante.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('pos_ventas', function (Blueprint $table) {
            $table->string('cliente_telefono', 20)->nullable()->after('cliente_nombre');
        });

        $p = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'editar-negocio', 'guard_name' => 'web']);
        $admin = \Spatie\Permission\Models\Role::where('name', 'administrador')->first();
        if ($admin) {
            $admin->givePermissionTo($p);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('pos_ventas', function (Blueprint $table) {
            $table->dropColumn('cliente_telefono');
        });
        Schema::dropIfExists('negocio');
    }
};
