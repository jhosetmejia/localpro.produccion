<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Separados por vencer: correo del cliente y constancia de los avisos enviados
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('separados', function (Blueprint $t) {
            if (!Schema::hasColumn('separados', 'cliente_correo')) $t->string('cliente_correo', 120)->nullable()->after('cliente_telefono');
            if (!Schema::hasColumn('separados', 'avisado_previo_at')) $t->timestamp('avisado_previo_at')->nullable();
            if (!Schema::hasColumn('separados', 'avisado_hoy_at')) $t->timestamp('avisado_hoy_at')->nullable();
            if (!Schema::hasColumn('separados', 'avisado_vencido_at')) $t->timestamp('avisado_vencido_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('separados', function (Blueprint $t) {
            $t->dropColumn(['cliente_correo', 'avisado_previo_at', 'avisado_hoy_at', 'avisado_vencido_at']);
        });
    }
};
