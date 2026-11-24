<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// 2026-11-24 (Jhoset): la cuenta del administrador en LP Chat (la de NES/PFN) salía con otro nombre: se deja con su nombre ya.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lpchat_usuarios') || Schema::hasTable('users')) {
            try {
                \App\Support\LpChat::cuentaAdmin();
            } catch (\Throwable $e) {
                // No frena el despliegue: se corrige igual la próxima vez que el admin escriba desde NES/PFN.
            }
        }
    }

    public function down(): void
    {
    }
};
