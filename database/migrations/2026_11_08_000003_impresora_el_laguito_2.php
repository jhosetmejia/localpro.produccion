<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Ajuste puntual de UN negocio: El Laguito (NIT 36753135), 2.ª prueba: con 58 mm salía cortado a la izquierda
// y con espacio a la derecha → se corre a la derecha y se angosta un poco.
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
            'impresora' => json_encode(['papel' => 58, 'ancho' => 44, 'margen' => 8.5, 'letra' => 11, 'negrita' => false, 'final' => 8]),
        ]);
    }

    public function down(): void
    {
    }
};
