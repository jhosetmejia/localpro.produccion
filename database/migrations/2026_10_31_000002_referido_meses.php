<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "LocalPro gratis": por cada emprendedor que se une con el link del negocio y cumple los primeros pasos,
// el negocio que lo invitó gana 1 mes gratis de su plan. Un solo mes por cada invitado (referida_id único).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('referido_meses')) return;
        Schema::create('referido_meses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->index();        // negocio que invitó y gana el mes
            $t->foreignId('referida_id')->unique();     // negocio invitado que cumplió los pasos
            $t->unsignedSmallInteger('dias')->default(30);
            $t->date('vence_antes')->nullable();
            $t->date('vence_nuevo')->nullable();
            $t->string('nota', 255)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referido_meses');
    }
};
