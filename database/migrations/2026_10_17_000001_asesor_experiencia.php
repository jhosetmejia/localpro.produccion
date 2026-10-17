<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Registro de asesores con datos básicos: se guarda si tiene experiencia en ventas.
// La cédula y los datos de pago se completan después en "Mi cuenta".
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('asesores', 'experiencia')) {
            Schema::table('asesores', function (Blueprint $t) {
                $t->string('experiencia', 40)->nullable()->after('documento');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('asesores', 'experiencia')) {
            Schema::table('asesores', fn (Blueprint $t) => $t->dropColumn('experiencia'));
        }
    }
};
