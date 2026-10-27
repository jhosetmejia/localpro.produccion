<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pie de la tarjeta: elegir frase, eslogan o las dos (o quitarlo). La frase sigue en "pie" y el eslogan va aparte.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituario_plantillas') || Schema::hasColumn('obituario_plantillas', 'pie_modo')) return;
        Schema::table('obituario_plantillas', function (Blueprint $t) {
            $t->string('pie_modo', 10)->default('frase')->after('pie');
            $t->string('pie_eslogan', 150)->nullable()->after('pie_modo');
        });
        if (Schema::hasColumn('obituario_plantillas', 'pie_mostrar')) {
            DB::table('obituario_plantillas')->where('pie_mostrar', false)->update(['pie_modo' => 'ninguno']);
        }
        // Monte de los Olivos: el eslogan pasa a su campo y se recupera la frase de fábrica de cada tarjeta
        $eslogan = 'Excelencia en servicios... Más humana';
        $frases = \App\Models\ObituarioPlantilla::PREDISENADAS;
        foreach (DB::table('obituario_plantillas')->where('pie', $eslogan)->get(['id', 'base']) as $pl) {
            DB::table('obituario_plantillas')->where('id', $pl->id)->update([
                'pie_eslogan' => $eslogan, 'pie_modo' => 'eslogan', 'pie' => $frases[$pl->base]['pie'] ?? null,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituario_plantillas', 'pie_modo')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn(['pie_modo', 'pie_eslogan']));
    }
};
