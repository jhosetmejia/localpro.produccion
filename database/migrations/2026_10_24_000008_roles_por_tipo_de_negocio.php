<?php

use App\Models\NegocioRol;
use App\Models\Tienda;
use App\Support\RolesNegocio;
use App\Support\TiendaActual;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Roles por defecto según el tipo de negocio (Vendedor, Bodega, Recepción, Profesional de salud…)
// para los negocios que todavía no tienen ningún rol. Los que ya armaron sus roles no se tocan.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('negocio_roles') || !Schema::hasTable('permissions')) return;
        foreach (['usar-separados', 'usar-alquileres', 'registrar-egresos', 'responder-redes', 'gestionar-obituarios', 'agenda-consultorio'] as $n) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $n, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Tienda::whereNotNull('tipo_negocio')->orderBy('id')->each(function (Tienda $t) {
            try {
                if (TiendaActual::como($t->id, fn () => NegocioRol::exists())) return;
                RolesNegocio::crearPorDefecto($t);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function down(): void
    {
    }
};
