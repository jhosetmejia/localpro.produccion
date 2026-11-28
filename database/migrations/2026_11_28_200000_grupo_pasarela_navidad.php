<?php

use App\Models\LpchatUsuario as U;
use App\Support\LpChat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// 2026-11-28 (Jhoset): grupo de LP Chat con todos los que recibieron el mensaje masivo de Andrés Guerrero invitándolos
// a la pasarela de Navidad. Administradores: Andrés y Jhoset (cuenta del súper administrador). Se corre una sola vez.
return new class extends Migration
{
    private const NOMBRE = '🎄 Pasarela de Navidad';

    public function up(): void
    {
        try {
            $norm = fn ($t) => mb_strtolower(strtr((string) $t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']));

            // Cuenta(s) de Andrés Guerrero.
            $andres = U::all(['id', 'nombre'])->filter(fn ($u) => str_contains($norm($u->nombre), 'guerrero') && str_contains($norm($u->nombre), 'andr'))->pluck('id')->all();
            if (! $andres) {
                Log::warning('Grupo Pasarela de Navidad: no encontré la cuenta de Andrés Guerrero en LP Chat.');

                return;
            }

            // Sus mensajes en chats directos (últimos 30 días) que hablan de la pasarela / evento de Navidad.
            $porQuien = []; // usuario de Andrés => [chat_id...]
            DB::table('lpchat_mensajes as m')->join('lpchat_chats as c', 'c.id', '=', 'm.chat_id')
                ->where('c.tipo', 'directo')->whereIn('m.usuario_id', $andres)->where('m.created_at', '>=', now()->subDays(30))
                ->orderBy('m.id')->select(['m.id', 'm.chat_id', 'm.usuario_id', 'm.texto'])
                ->chunk(500, function ($filas) use (&$porQuien, $norm) {
                    foreach ($filas as $f) {
                        $t = $norm(LpChat::leer($f->texto));
                        if (str_contains($t, 'navid') && preg_match('/pasarela|invitad|evento|particip/u', $t)) {
                            $porQuien[$f->usuario_id][$f->chat_id] = true;
                        }
                    }
                });
            if (! $porQuien) {
                Log::warning('Grupo Pasarela de Navidad: no encontré el mensaje masivo de Andrés sobre la Navidad.');

                return;
            }
            // La cuenta de Andrés que mandó el masivo (la que más chats tiene con ese mensaje).
            uasort($porQuien, fn ($a, $b) => count($b) <=> count($a));
            $andresId = (int) array_key_first($porQuien);
            $chats = collect($porQuien)->flatMap(fn ($x) => array_keys($x))->unique()->values()->all();

            $destinatarios = DB::table('lpchat_miembros')->whereIn('chat_id', $chats)->whereNotIn('usuario_id', $andres)
                ->pluck('usuario_id')->unique()->values()->all();

            $yo = U::find($andresId);
            $jhoset = LpChat::cuentaAdmin();

            // Si ya existe (por si se corre otra vez), se completa ese mismo grupo.
            $gid = (int) DB::table('lpchat_chats')->where('tipo', 'grupo')->where('nombre', self::NOMBRE)->where('creador_id', $yo->id)->value('id');
            if (! $gid) {
                $gid = LpChat::crearGrupo($yo, self::NOMBRE, []);
            }
            $n = 0;
            foreach ($destinatarios as $uid) {
                if (($u = U::find((int) $uid)) && LpChat::agregar($gid, $u, $yo)) {
                    $n++;
                }
            }
            if ($jhoset) {
                LpChat::agregar($gid, $jhoset, $yo);
                DB::table('lpchat_miembros')->where('chat_id', $gid)->where('usuario_id', $jhoset->id)->update(['rol' => 'admin', 'estado' => 'activo']);
            }
            DB::table('lpchat_miembros')->where('chat_id', $gid)->where('usuario_id', $yo->id)->update(['rol' => 'admin', 'estado' => 'activo']);

            Log::info('Grupo Pasarela de Navidad (chat '.$gid.'): '.$n.' agregados de '.count($destinatarios).' que recibieron el mensaje de Andrés. Admins: Andrés ('.$yo->id.') y Jhoset ('.($jhoset?->id ?? '—').').');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
    }
};
