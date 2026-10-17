<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Egresos del negocio (pagos, compras menores, servicios…).
// Los vendedores anotan egresos y ven los suyos y los de sus compañeros; los del dueño/administrador solo los ve quien
// tiene "ver-egresos-todos". Si se pagan en efectivo con la caja abierta, se descuentan del efectivo esperado.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('egresos')) {
            Schema::create('egresos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->nullable()->index();
                $t->foreignId('user_id')->nullable()->index();
                $t->date('fecha')->index();
                $t->string('concepto', 160);
                $t->string('categoria', 40)->default('otros');
                $t->decimal('valor', 14, 2);
                $t->foreignId('metodo_pago_id')->nullable();
                $t->foreignId('caja_id')->nullable()->index();
                $t->boolean('de_admin')->default(false)->index();
                $t->string('soporte_path')->nullable();
                $t->string('nota', 255)->nullable();
                $t->timestamps();
            });
        }

        $registrar = Permission::firstOrCreate(['name' => 'registrar-egresos', 'guard_name' => 'web']);
        $todos = Permission::firstOrCreate(['name' => 'ver-egresos-todos', 'guard_name' => 'web']);
        foreach (Role::all() as $rol) {
            if ($rol->hasPermissionTo('editar-negocio') || $rol->name === 'administrador') {
                $rol->givePermissionTo([$registrar, $todos]);
            } elseif ($rol->hasPermissionTo('usar-pos')) {
                $rol->givePermissionTo($registrar);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
        Permission::whereIn('name', ['registrar-egresos', 'ver-egresos-todos'])->delete();
    }
};
