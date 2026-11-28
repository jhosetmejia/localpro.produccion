<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2026-11-28 (Jhoset): llamadas de voz y videollamadas en LP Chat (como WhatsApp). La conexión es directa entre los
// dos celulares (WebRTC); aquí solo se guarda la llamada y las "señales" para que se encuentren.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_llamadas')) {
            Schema::create('lpchat_llamadas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('chat_id')->index();
                $t->unsignedBigInteger('de_id');
                $t->unsignedBigInteger('para_id');
                $t->boolean('video')->default(false);
                $t->string('estado', 12)->default('llamando'); // llamando | aceptada | rechazada | perdida | terminada
                $t->timestamp('inicio_at')->nullable();
                $t->timestamp('fin_at')->nullable();
                $t->timestamp('de_visto_at')->nullable();
                $t->timestamp('para_visto_at')->nullable();
                $t->timestamps();
                $t->index(['para_id', 'estado']);
                $t->index(['de_id', 'estado']);
            });
        }
        if (! Schema::hasTable('lpchat_llamada_senales')) {
            Schema::create('lpchat_llamada_senales', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('llamada_id')->index();
                $t->unsignedBigInteger('de_id');
                $t->string('tipo', 8); // offer | answer | ice
                $t->mediumText('datos');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_llamada_senales');
        Schema::dropIfExists('lpchat_llamadas');
    }
};
