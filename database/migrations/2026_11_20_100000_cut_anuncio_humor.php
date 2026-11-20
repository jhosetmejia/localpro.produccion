<?php

use App\Support\AnunciosCut;
use Illuminate\Database\Migrations\Migration;

/**
 * 2026-11-20 (Jhoset): en los anuncios de LocalProCut se quita el anuncio viejo de comedia y queda el del
 * Festival del Humor: "El mejor comediante de Pasto · 3 de enero · Regístrate y participa" (de primero).
 * Si los anuncios son los automáticos, ya sale solo (AnunciosCut::humor).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $g = json_decode((string) \App\Models\Ajuste::valor('cut_anuncios', ''), true);
        } catch (\Throwable $e) {
            return;
        }
        if (! is_array($g)) {
            return;
        }
        $g = array_values(array_filter($g, fn ($a) => ! preg_match('/humor|comedi|humorist/i', ($a['t'] ?? '').' '.($a['s'] ?? '').' '.($a['u'] ?? ''))));
        array_unshift($g, AnunciosCut::humor());
        AnunciosCut::guardar($g);
    }

    public function down(): void
    {
    }
};
