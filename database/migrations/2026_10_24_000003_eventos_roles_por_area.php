<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Eventos: permisos por área (Contabilidad, Logística, Diseño) y roles por defecto para los negocios que ya tienen Eventos.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) return;
        $nuevos = [];
        foreach (['eventos-pagos', 'eventos-informacion', 'eventos-diseno'] as $n) {
            $nuevos[] = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $n, 'guard_name' => 'web']);
        }
        foreach (\Spatie\Permission\Models\Role::all() as $rol) {
            if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo($nuevos);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        if (!Schema::hasTable('negocio_roles') || !Schema::hasColumn('tiendas', 'mod_eventos')) return;
        foreach (\App\Models\Tienda::where('mod_eventos', true)->get() as $t) {
            try {
                \App\Support\RolesNegocio::crearEventosPorDefecto($t);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', ['eventos-pagos', 'eventos-informacion', 'eventos-diseno'])->delete();
        }
    }
};
