<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-25 (Jhoset): las fotos de PFN y NES llegan como enlaces firmados de Backblaze (más de 255 letras).
 * Se cortaban, la copia fallaba y la persona quedaba con la inicial en LP Chat aunque tuviera foto allá.
 * La columna aguanta el enlace completo; los enlaces cortados se quitan y la próxima sincronización trae el bueno.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_usuarios')) {
            return;
        }
        Schema::table('lpchat_usuarios', function (Blueprint $t) {
            $t->text('foto')->nullable()->change();
        });
        DB::table('lpchat_usuarios')->where('foto', 'like', 'http%')->whereRaw('CHAR_LENGTH(foto) >= 255')->update(['foto' => null]);
    }

    public function down(): void
    {
    }
};
