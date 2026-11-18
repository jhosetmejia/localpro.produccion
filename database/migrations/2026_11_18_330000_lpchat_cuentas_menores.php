<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-17 (Jhoset): las cuentas de NES/PFN con inscripciones de menores las crea y maneja el papá o la mamá.
 * Entran a LP Chat para hablar con el equipo del evento, pero protegidas: no salen en Conocer gente y
 * nadie desconocido les puede escribir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lpchat_usuarios') && ! Schema::hasColumn('lpchat_usuarios', 'menor')) {
            Schema::table('lpchat_usuarios', fn (Blueprint $t) => $t->boolean('menor')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lpchat_usuarios', 'menor')) {
            Schema::table('lpchat_usuarios', fn (Blueprint $t) => $t->dropColumn('menor'));
        }
    }
};
