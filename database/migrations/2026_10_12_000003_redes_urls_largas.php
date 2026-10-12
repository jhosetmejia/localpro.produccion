<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Las direcciones de fotos de Facebook/Instagram pueden pasar de 500 caracteres
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redes_cuentas', function (Blueprint $t) {
            $t->text('page_foto')->nullable()->change();
            $t->text('ig_foto')->nullable()->change();
        });
        Schema::table('redes_conversaciones', function (Blueprint $t) {
            $t->text('contacto_foto')->nullable()->change();
        });
        Schema::table('redes_mensajes', function (Blueprint $t) {
            $t->text('adjunto_url')->nullable()->change();
        });
        Schema::table('redes_publicaciones', function (Blueprint $t) {
            $t->text('error')->nullable()->change();
        });
    }

    public function down(): void
    {
    }
};
