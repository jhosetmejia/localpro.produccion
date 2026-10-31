<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Giro 360 (localproo.com/360): clientes que dejan nombre y WhatsApp después de grabar su video en la máquina 360.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('giro360_clientes')) return;
        Schema::create('giro360_clientes', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 80);
            $t->string('whatsapp', 20);
            $t->string('evento', 60)->nullable();
            $t->string('efecto', 20)->nullable();
            $t->boolean('acepta')->default(false);
            $t->string('ip', 45)->nullable();
            $t->timestamps();
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giro360_clientes');
    }
};
