<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Permiso "Mis enlaces": el dueño se lo puede dar a un vendedor para que cree y edite los enlaces
return new class extends Migration
{
    public function up(): void
    {
        $p = Permission::firstOrCreate(['name' => 'gestionar-enlaces', 'guard_name' => 'web']);
        foreach (Role::all() as $rol) {
            if (in_array($rol->name, ['dueno', 'administrador'], true) || $rol->hasPermissionTo('editar-negocio')) {
                $rol->givePermissionTo($p);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'gestionar-enlaces')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
