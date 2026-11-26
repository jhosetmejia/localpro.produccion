<?php

use App\Support\LpChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 2026-11-25 (Jhoset): la invitación al Christmas Show es SOLO para el participante: se quita "y familia" de los mensajes ya enviados.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('lpchat_mensajes')->where('created_at', '>=', '2026-11-25 00:00:00')->whereNotNull('texto')
            ->orderBy('id')->select('id', 'texto')->chunkById(500, function ($filas) {
                foreach ($filas as $f) {
                    $t = (string) LpChat::leer($f->texto);
                    if (str_contains($t, 'Christmas Show') && str_contains($t, ' y familia! ✨🎄')) {
                        DB::table('lpchat_mensajes')->where('id', $f->id)->update(['texto' => LpChat::cifrar(str_replace(' y familia! ✨🎄', '! ✨🎄', $t))]);
                    }
                }
            });
    }

    public function down(): void
    {
    }
};
