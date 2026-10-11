<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Códigos únicos: asesores LCP-00001 y negocios LCT-00001.
// Los códigos viejos de los asesores (ELIANA723) siguen funcionando en los enlaces ya compartidos.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('asesores', 'codigo_anterior')) {
            Schema::table('asesores', fn (Blueprint $t) => $t->string('codigo_anterior', 20)->nullable()->index()->after('codigo'));
        }
        if (!Schema::hasColumn('tiendas', 'codigo')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->string('codigo', 12)->nullable()->unique()->after('id'));
        }

        $n = 0;
        foreach (DB::table('asesores')->orderBy('id')->get(['id', 'codigo', 'codigo_anterior']) as $a) {
            $n++;
            if (str_starts_with((string) $a->codigo, 'LCP-')) continue;
            DB::table('asesores')->where('id', $a->id)->update([
                'codigo_anterior' => $a->codigo_anterior ?: $a->codigo,
                'codigo' => 'LCP-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            ]);
        }
        $n = 0;
        foreach (DB::table('tiendas')->orderBy('id')->get(['id', 'codigo']) as $t) {
            $n++;
            if ($t->codigo) continue;
            DB::table('tiendas')->where('id', $t->id)->update(['codigo' => 'LCT-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
    }
};
