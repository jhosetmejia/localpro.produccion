<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset):
 * - lpchat_push: suscripciones para avisar al celular aunque LP Chat esté cerrado.
 * - Los chats traídos de NES/PFN donde una PERSONA escribió y nadie le había respondido quedaban ocultos:
 *   ahora se muestran (solo se dejan ocultos los mensajes masivos del equipo del evento sin respuesta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_push')) {
            Schema::create('lpchat_push', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('usuario_id')->index();
                $t->string('hash', 64)->unique(); // sha256 del endpoint
                $t->text('endpoint');
                $t->string('agente', 190)->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('lpchat_miembros')) {
            return;
        }
        $chats = DB::table('lpchat_miembros')->where('estado', 'oculto')->distinct()->pluck('chat_id');
        foreach ($chats->chunk(500) as $lote) {
            $autores = DB::table('lpchat_mensajes as m')->join('lpchat_usuarios as u', 'u.id', '=', 'm.usuario_id')
                ->whereIn('m.chat_id', $lote)->select('m.chat_id', 'u.sin_limite')->distinct()->get()->groupBy('chat_id');
            $mostrar = $lote->filter(fn ($id) => ($autores[$id] ?? collect())->contains(fn ($a) => ! $a->sin_limite))->values();
            if ($mostrar->isNotEmpty()) {
                DB::table('lpchat_miembros')->whereIn('chat_id', $mostrar)->where('estado', 'oculto')->update(['estado' => 'activo']);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lpchat_push');
    }
};
