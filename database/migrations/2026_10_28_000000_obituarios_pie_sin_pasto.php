<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Pedido de la funeraria: en todas sus plantillas quitar "Pasto" del nombre del pie y poner abajo el eslogan
// "Excelencia en servicios... Más humana". Va antes de la copia fija de plantillas (2026_10_28_000001).
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('obituario_plantillas')) return;
        $eslogan = 'Excelencia en servicios... Más humana';
        foreach (DB::table('obituario_plantillas')->get() as $p) {
            $tienda = DB::table('tiendas')->where('id', $p->tienda_id)->value('nombre');
            $nombre = trim((string) $p->pie_nombre) !== '' ? $p->pie_nombre : (string) $tienda;
            if (stripos($nombre, 'pasto') === false) continue;
            $modo = $p->pie_modo === 'ninguno' ? 'eslogan' : (in_array($p->pie_modo, ['eslogan', 'ambos'], true) ? $p->pie_modo : 'ambos');
            DB::table('obituario_plantillas')->where('id', $p->id)->update([
                'pie_nombre' => trim(preg_replace('/[\s,\-]*\bpasto\b/iu', '', $nombre)),
                'pie_eslogan' => $eslogan,
                'pie_modo' => $modo,
                'pie_mostrar' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
