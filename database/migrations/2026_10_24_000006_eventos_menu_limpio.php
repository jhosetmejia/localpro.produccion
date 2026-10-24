<?php

use App\Models\Tienda;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Organizadores de eventos: se ocultan del menú caja, ventas, inventario, facturación DIAN y lo demás que no usan.
// Se suma a lo que ya tenían oculto; lo pueden volver a mostrar en Personalizar menú.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tiendas', 'menu_oculto')) return;
        $ocultar = Tienda::MENU_OCULTO_POR_TIPO['eventos'];
        foreach (DB::table('tiendas')->where('tipo_negocio', 'eventos')->get(['id', 'menu_oculto']) as $t) {
            $ya = json_decode((string) $t->menu_oculto, true) ?: [];
            DB::table('tiendas')->where('id', $t->id)->update(['menu_oculto' => json_encode(array_values(array_unique(array_merge($ya, $ocultar))))]);
        }
    }

    public function down(): void
    {
    }
};
