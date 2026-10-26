<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Lugar de velación escrito por la funeraria (sale en el obituario). La "sala" ahora es solo el proyector donde se muestra.
// Los obituarios que ya existían quedan con el nombre de su sala como lugar de velación, para que se sigan viendo igual.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obituarios') || Schema::hasColumn('obituarios', 'lugar_velacion')) return;
        Schema::table('obituarios', fn (Blueprint $t) => $t->string('lugar_velacion', 150)->nullable()->after('sala_id'));
        if (Schema::hasTable('obituario_salas')) {
            DB::table('obituarios')->whereNotNull('sala_id')->whereNull('lugar_velacion')->update([
                'lugar_velacion' => DB::raw('(SELECT s.nombre FROM obituario_salas s WHERE s.id = obituarios.sala_id)'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'lugar_velacion')) Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn('lugar_velacion'));
    }
};
