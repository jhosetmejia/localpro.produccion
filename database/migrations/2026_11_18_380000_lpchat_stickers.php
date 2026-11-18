<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 2026-11-18: LP Chat — stickers propios de cada persona (no se borran a los 7 días; los mensajes sí). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_stickers')) {
            Schema::create('lpchat_stickers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('archivo', 255);
                $t->boolean('favorito')->default(false);
                $t->timestamp('usado_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_stickers');
    }
};
