<?php

use App\Support\LpChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 2026-11-27 (Jhoset): el mensaje a los patrocinadores ("…eternamente agradecido…") salió de su cuenta jmmg1994
// y no de la de superadmin. Se quitan esos (los que llegaron de NES antes de las 19:19 de hoy); NES los vuelve a
// mandar desde la cuenta superadmin.
return new class extends Migration
{
    public function up(): void
    {
        try {
            $filas = DB::table('lpchat_mensajes')->where('origen', 'like', 'nes:m%')
                ->whereBetween('created_at', ['2026-11-27 18:30:00', '2026-11-27 19:19:10'])
                ->get(['id', 'chat_id', 'texto']);
            $chats = [];
            foreach ($filas as $m) {
                if (str_contains((string) LpChat::leer($m->texto), 'eternamente agradecido de que apoyes estas iniciativas')) {
                    DB::table('lpchat_mensajes')->where('id', $m->id)->delete();
                    $chats[$m->chat_id] = true;
                }
            }
            foreach (array_keys($chats) as $cid) {
                $max = (int) DB::table('lpchat_mensajes')->where('chat_id', $cid)->max('id');
                if ($max === 0 && DB::table('lpchat_chats')->where('id', $cid)->value('tipo') === 'directo') {
                    DB::table('lpchat_miembros')->where('chat_id', $cid)->delete(); // chat que solo tenía ese mensaje
                    DB::table('lpchat_chats')->where('id', $cid)->delete();
                } else {
                    DB::table('lpchat_chats')->where('id', $cid)->update(['ultimo_id' => $max]);
                }
            }
            \Illuminate\Support\Facades\Log::info('Quitado mensaje a patrocinadores de la cuenta equivocada: '.count($chats).' chats.');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
    }
};
