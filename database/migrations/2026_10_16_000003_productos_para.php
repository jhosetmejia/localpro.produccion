<?php

use App\Http\Controllers\catalogoController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Para quién es el producto (mujer, hombre, ninos o vacío = para todos). Lo usa el filtro del catálogo.
// Además deja parejos los nombres de las categorías que ya existen.
// Se llena de una vez según el nombre y las categorías de cada producto; el dueño lo cambia en el producto.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'para')) {
            Schema::table('productos', fn (Blueprint $t) => $t->string('para', 10)->nullable()->after('nombre'));
        }

        $cats = DB::table('categoria_producto as cp')
            ->join('categorias as c', 'c.id', '=', 'cp.categoria_id')
            ->join('caracteristicas as k', 'k.id', '=', 'c.caracteristica_id')
            ->select('cp.producto_id', 'k.nombre')->get()->groupBy('producto_id');

        // Nombres de categorías parejos: "CHAQUETA NACIONAL" → "Chaqueta nacional" (solo si no choca con otra)
        $cats2 = DB::table('categorias as c')->join('caracteristicas as k', 'k.id', '=', 'c.caracteristica_id')
            ->select('k.id', 'k.nombre', 'k.tienda_id')->get();
        foreach ($cats2 as $k) {
            $nuevo = \App\Support\Textos::nombre($k->nombre);
            if ($nuevo === '' || $nuevo === $k->nombre) continue;
            $choca = $cats2->first(fn ($o) => $o->id !== $k->id && $o->tienda_id === $k->tienda_id && mb_strtolower($o->nombre) === mb_strtolower($nuevo) && $o->nombre !== $k->nombre);
            if (!$choca) DB::table('caracteristicas')->where('id', $k->id)->update(['nombre' => $nuevo]);
        }

        DB::table('productos')->whereNull('para')->select('id', 'nombre')->orderBy('id')->chunkById(500, function ($lote) use ($cats) {
            foreach ($lote as $p) {
                $texto = $p->nombre . ' ' . ($cats[$p->id] ?? collect())->pluck('nombre')->implode(' ');
                if ($para = catalogoController::para($texto)) {
                    DB::table('productos')->where('id', $p->id)->update(['para' => $para]);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'para')) {
            Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('para'));
        }
    }
};
