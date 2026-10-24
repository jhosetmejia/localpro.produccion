<?php

use App\Models\NegocioRol;
use App\Support\RolesNegocio;
use App\Support\TiendaActual;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Eventos: "Mis redes" vuelve a verse en el menú de los organizadores, y el rol "Administrador del evento"
// también maneja Mis enlaces, publicar en redes y ver los egresos.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tiendas', 'menu_oculto')) {
            foreach (DB::table('tiendas')->where('tipo_negocio', 'eventos')->whereNotNull('menu_oculto')->get(['id', 'menu_oculto']) as $t) {
                $ocultos = array_values(array_diff(json_decode((string) $t->menu_oculto, true) ?: [], ['redes']));
                DB::table('tiendas')->where('id', $t->id)->update(['menu_oculto' => $ocultos ? json_encode($ocultos) : null]);
            }
        }
        if (!Schema::hasTable('negocio_roles')) return;
        $extra = ['gestionar-enlaces', 'publicar-redes', 'ver-egresos-todos'];
        foreach (DB::table('negocio_roles')->where('nombre', 'Administrador del evento')->get(['id', 'tienda_id']) as $r) {
            TiendaActual::como($r->tienda_id, function () use ($r, $extra) {
                $rol = NegocioRol::find($r->id);
                if (!$rol) return;
                $rol->permisos = RolesNegocio::limpiar(array_merge((array) $rol->permisos, $extra));
                $rol->save();
                RolesNegocio::sincronizar($rol);
            });
        }
    }

    public function down(): void
    {
    }
};
