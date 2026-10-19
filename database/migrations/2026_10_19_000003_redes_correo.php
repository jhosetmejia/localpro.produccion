<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mis redes: correo (Gmail, Outlook o el de su dominio) en la misma bandeja de mensajes
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('redes_cuentas', 'tipo')) {
            Schema::table('redes_cuentas', function (Blueprint $t) {
                $t->string('tipo', 10)->default('meta')->after('tienda_id'); // meta (Facebook/Instagram) o correo
            });
        }
        if (!Schema::hasColumn('redes_conversaciones', 'asunto')) {
            Schema::table('redes_conversaciones', function (Blueprint $t) {
                $t->string('asunto', 250)->nullable();      // correo: asunto del hilo
                $t->string('ultimo_mid', 250)->nullable();  // correo: Message-ID para responder en el mismo hilo
            });
        }
        // El correo del contacto puede ser más largo que un id de Facebook
        Schema::table('redes_conversaciones', function (Blueprint $t) {
            $t->string('contacto_id', 191)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('redes_conversaciones', 'asunto')) Schema::table('redes_conversaciones', fn (Blueprint $t) => $t->dropColumn(['asunto', 'ultimo_mid']));
        if (Schema::hasColumn('redes_cuentas', 'tipo')) Schema::table('redes_cuentas', fn (Blueprint $t) => $t->dropColumn('tipo'));
    }
};
