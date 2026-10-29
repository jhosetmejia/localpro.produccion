<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cada obituario guarda una copia de la plantilla con la que se hizo: si después alguien edita
// esa plantilla (o usa "aplicar a todas"), los obituarios ya hechos no cambian de diseño.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituarios') || Schema::hasColumn('obituarios', 'plantilla_fija')) return;
        Schema::table('obituarios', fn (Blueprint $t) => $t->json('plantilla_fija')->nullable());
        // Los obituarios que ya existen quedan con la plantilla como está hoy
        foreach (DB::table('obituario_plantillas')->get() as $p) {
            DB::table('obituarios')->where('plantilla_id', $p->id)->update(['plantilla_fija' => json_encode((array) $p)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'plantilla_fija')) Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn('plantilla_fija'));
    }
};
