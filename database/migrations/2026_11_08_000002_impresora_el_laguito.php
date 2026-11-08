<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Ajuste puntual de UN negocio: El Laguito (NIT 36753135) imprime en rollo de 58 mm y el tiquete salía corrido y cortado.
// No toca ningún otro negocio. Ellos lo pueden seguir ajustando en Mi negocio → Impresora.
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('tiendas')
            ->whereRaw("LOWER(TRIM(nombre)) LIKE 'el laguito%'")
            ->where('nit', 'like', '36753135%')
            ->pluck('id');
        if ($ids->count() !== 1) return; // si no lo encuentra exacto, no cambia nada
        DB::table('tiendas')->where('id', $ids->first())->update([
            'impresora' => json_encode(['papel' => 58, 'ancho' => 48, 'margen' => 0, 'letra' => 12, 'negrita' => false, 'final' => 8]),
        ]);
    }

    public function down(): void
    {
    }
};
