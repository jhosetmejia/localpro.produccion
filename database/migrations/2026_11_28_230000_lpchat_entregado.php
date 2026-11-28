<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 2026-11-28 (Jhoset): vistos como WhatsApp: ✓ enviado · ✓✓ entregado (le llegó al celular) · ✓✓ azul leído.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lpchat_miembros', 'entregado_id')) {
            Schema::table('lpchat_miembros', function (Blueprint $t) {
                $t->unsignedBigInteger('entregado_id')->default(0);
            });
            DB::table('lpchat_miembros')->update(['entregado_id' => DB::raw('leido_id')]);
        }
    }

    public function down(): void
    {
    }
};
