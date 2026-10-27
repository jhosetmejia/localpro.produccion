<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Obituarios: la frase y las fechas (nació / falleció) ahora son opcionales con "Sí / No" (por defecto no salen).
// Plantillas: el pie (nombre + eslogan) se puede editar o quitar.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('obituarios') && !Schema::hasColumn('obituarios', 'mostrar_frase')) {
            Schema::table('obituarios', function (Blueprint $t) {
                $t->boolean('mostrar_frase')->default(false)->after('frase');
                $t->boolean('mostrar_fechas')->default(false)->after('fallecimiento');
            });
            // Quien ya había escrito su propia frase la sigue viendo; la frase de la plantilla ya no sale sola
            DB::table('obituarios')->whereNotNull('frase')->where('frase', '<>', '')->update(['mostrar_frase' => true]);
        }
        if (Schema::hasTable('obituario_plantillas') && !Schema::hasColumn('obituario_plantillas', 'pie_mostrar')) {
            Schema::table('obituario_plantillas', function (Blueprint $t) {
                $t->boolean('pie_mostrar')->default(true)->after('pie');
                $t->string('pie_nombre', 80)->nullable()->after('pie_mostrar');
            });
        }
        // Monte de los Olivos: su eslogan en el pie en vez del mensaje de la plantilla
        if (Schema::hasTable('tiendas') && Schema::hasTable('obituario_plantillas')) {
            $ids = DB::table('tiendas')->where('nombre', 'like', '%Monte de los Olivos%')->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('obituario_plantillas')->whereIn('tienda_id', $ids)->update([
                    'pie_mostrar' => true, 'pie_nombre' => 'Monte de los Olivos', 'pie' => 'Excelencia en servicios... Más humana',
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('obituarios', 'mostrar_frase')) Schema::table('obituarios', fn (Blueprint $t) => $t->dropColumn(['mostrar_frase', 'mostrar_fechas']));
        if (Schema::hasColumn('obituario_plantillas', 'pie_mostrar')) Schema::table('obituario_plantillas', fn (Blueprint $t) => $t->dropColumn(['pie_mostrar', 'pie_nombre']));
    }
};
