<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Lugar de velación: por defecto debajo de las exequias (fecha, hora, lugar) y se puede ocultar.
// "En memoria de" debajo de la foto: oculto por defecto (el nombre ya sale arriba), se activa en la plantilla.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas')) return;
        if (!Schema::hasColumn('obituario_plantillas', 'mostrar_memoria')) {
            Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->boolean('mostrar_memoria')->default(false));
        }
        DB::table('obituario_plantillas')->whereIn('velacion_pos', ['foto', 'debajo'])->update(['velacion_pos' => 'datos', 'velacion_x' => 0, 'velacion_y' => 0]);

        // Copias guardadas en cada obituario
        if (!Schema::hasColumn('obituarios', 'plantilla_fija')) return;
        DB::table('obituarios')->whereNotNull('plantilla_fija')->orderBy('id')->select('id', 'plantilla_fija')->chunkById(200, function ($filas) {
            foreach ($filas as $f) {
                $a = json_decode($f->plantilla_fija, true);
                if (!is_array($a)) continue;
                if (in_array($a['velacion_pos'] ?? 'foto', ['foto', 'debajo'], true)) { $a['velacion_pos'] = 'datos'; $a['velacion_x'] = 0; $a['velacion_y'] = 0; }
                $a['mostrar_memoria'] = $a['mostrar_memoria'] ?? 0;
                DB::table('obituarios')->where('id', $f->id)->update(['plantilla_fija' => json_encode($a)]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'mostrar_memoria')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn('mostrar_memoria'));
    }
};
