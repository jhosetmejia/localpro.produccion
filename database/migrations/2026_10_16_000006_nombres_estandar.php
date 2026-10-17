<?php

use App\Support\Textos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Deja todos los nombres de productos y categorías con el mismo estilo:
// "CHAQUETA DE CUERO DAMA" / "chaqueta de cuero dama" → "Chaqueta de Cuero Dama"
return new class extends Migration
{
    public function up(): void
    {
        DB::table('productos')->select('id', 'nombre')->orderBy('id')->chunkById(500, function ($lote) {
            foreach ($lote as $p) {
                $n = Textos::titulo($p->nombre);
                if ($n !== '' && $n !== $p->nombre) DB::table('productos')->where('id', $p->id)->update(['nombre' => $n]);
            }
        });
        $cats = DB::table('categorias as c')->join('caracteristicas as k', 'k.id', '=', 'c.caracteristica_id')->select('k.id', 'k.nombre')->get();
        foreach ($cats as $k) {
            $n = Textos::titulo($k->nombre);
            if ($n !== '' && $n !== $k->nombre) DB::table('caracteristicas')->where('id', $k->id)->update(['nombre' => $n]);
        }
    }

    public function down(): void
    {
        // No se puede volver al texto anterior
    }
};
