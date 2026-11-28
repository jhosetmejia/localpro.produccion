<?php

namespace App\Http\Controllers;

use App\Models\LpchatUsuario as U;
use App\Support\LpChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * 2026-11-28 (Jhoset): al seleccionar un mensaje (mantener presionado), como WhatsApp: reaccionar con emoji, responder,
 * info (enviado / leído), eliminar (para mí o para todos), copiar y reenviar a otros chats.
 */
class lpchatMensajesController extends lpchatController
{
    private const HORAS_BORRAR_TODOS = 48;

    /** ¿Ya corrió la migración? (para no romper nada mientras se despliega) */
    public static function listo(): bool
    {
        if (Cache::get('lpchat_reac_listo')) {
            return true;
        }
        $ok = Schema::hasTable('lpchat_reacciones') && Schema::hasTable('lpchat_ocultos') && Schema::hasColumn('lpchat_mensajes', 'eliminado_at');
        if ($ok) {
            Cache::put('lpchat_reac_listo', true, 3600);
        }

        return $ok;
    }

    /** Reacciones del chat (mensajes de los últimos 3 meses): {mensaje_id: [{e, n, mio, quien[]}]} */
    public static function reaccionesDe(int $chatId, int $yo): array
    {
        if (! self::listo()) {
            return [];
        }
        $filas = DB::table('lpchat_reacciones')->where('chat_id', $chatId)->orderBy('id')->limit(2000)->get(['mensaje_id', 'usuario_id', 'emoji']);
        if ($filas->isEmpty()) {
            return [];
        }
        $nombres = U::whereIn('id', $filas->pluck('usuario_id')->unique())->pluck('nombre', 'id');
        $out = [];
        foreach ($filas->groupBy('mensaje_id') as $mid => $g) {
            $out[$mid] = $g->groupBy('emoji')->map(fn ($x, $e) => ['e' => $e, 'n' => $x->count(), 'mio' => $x->contains('usuario_id', $yo),
                'quien' => $x->map(fn ($r) => $r->usuario_id === $yo ? 'Tú' : ($nombres[$r->usuario_id] ?? 'Alguien'))->values()])->values();
        }

        return $out;
    }

    public static function eliminadosDe(int $chatId): array
    {
        if (! self::listo()) {
            return [];
        }

        return DB::table('lpchat_mensajes')->where('chat_id', $chatId)->whereNotNull('eliminado_at')->where('eliminado_at', '>=', now()->subDays(3))
            ->orderByDesc('id')->limit(300)->pluck('id')->all();
    }

    /** Lo que se muestra arriba de una respuesta (el mensaje citado). */
    public static function cita(int $id, U $yo): ?array
    {
        $m = DB::table('lpchat_mensajes')->where('id', $id)->first();
        if (! $m) {
            return ['id' => $id, 'autor' => '', 'texto' => 'Mensaje no disponible', 'adj' => null];
        }
        $autor = $m->usuario_id === $yo->id ? 'Tú' : (U::whereKey($m->usuario_id)->value('nombre') ?? '');
        $adj = ['foto' => '📷 Foto', 'video' => '🎥 Video', 'audio' => '🎤 Audio', 'sticker' => '🧩 Sticker'][$m->adjunto_tipo ?? ''] ?? ($m->adjunto ? '📎 Archivo' : null);

        return ['id' => $m->id, 'autor' => $autor, 'adj' => $m->eliminado_at ? null : $adj,
            'texto' => $m->eliminado_at ? '🚫 Se eliminó este mensaje' : mb_substr((string) LpChat::leer($m->texto), 0, 160)];
    }

    private function mensaje(int $id, U $yo): array
    {
        $m = DB::table('lpchat_mensajes')->where('id', $id)->first();
        abort_unless($m && $m->tipo !== 'sistema', 404);
        $c = $this->chat((int) $m->chat_id, $yo);

        return [$m, $c];
    }

    public function reaccion(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        [$m, $c] = $this->mensaje($id, $yo);
        $e = trim((string) $request->input('emoji', ''));
        abort_if(mb_strlen($e) > 16, 422);
        $ya = DB::table('lpchat_reacciones')->where('mensaje_id', $m->id)->where('usuario_id', $yo->id)->first();
        if ($e === '' || ($ya && $ya->emoji === $e)) {
            DB::table('lpchat_reacciones')->where('mensaje_id', $m->id)->where('usuario_id', $yo->id)->delete(); // tocar la misma la quita
        } elseif ($ya) {
            DB::table('lpchat_reacciones')->where('id', $ya->id)->update(['emoji' => $e, 'updated_at' => now()]);
        } else {
            DB::table('lpchat_reacciones')->insert(['chat_id' => $c->id, 'mensaje_id' => $m->id, 'usuario_id' => $yo->id, 'emoji' => $e, 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    /** Eliminar para mí (cualquiera) o para todos (solo los míos, hasta 48 h después). */
    public function borrar(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $d = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer', 'todos' => 'nullable|boolean']);
        $n = 0;
        foreach ($d['ids'] as $id) {
            [$m, $c] = $this->mensaje((int) $id, $yo);
            if (! empty($d['todos'])) {
                if ($m->usuario_id !== $yo->id || Carbon::parse($m->created_at)->lt(now()->subHours(self::HORAS_BORRAR_TODOS))) {
                    return response()->json(['ok' => false, 'mensaje' => 'Solo puedes eliminar para todos tus mensajes de las últimas 48 horas.'], 422);
                }
                if ($m->adjunto) {
                    $usado = DB::table('lpchat_mensajes')->where('adjunto', $m->adjunto)->where('id', '!=', $m->id)->exists();
                    if (! $usado) {
                        Storage::disk('local')->delete($m->adjunto);
                    }
                }
                DB::table('lpchat_mensajes')->where('id', $m->id)->update(['texto' => null, 'adjunto' => null, 'adjunto_tipo' => null, 'una_vez' => false, 'eliminado_at' => now(), 'updated_at' => now()]);
                DB::table('lpchat_reacciones')->where('mensaje_id', $m->id)->delete();
            } else {
                DB::table('lpchat_ocultos')->insertOrIgnore(['mensaje_id' => $m->id, 'usuario_id' => $yo->id]);
            }
            $n++;
        }

        return response()->json(['ok' => true, 'n' => $n]);
    }

    /** Reenviar mensajes a otros chats (hasta 5 chats y 30 mensajes). */
    public function reenviar(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $d = $request->validate(['ids' => 'required|array|min:1|max:30', 'ids.*' => 'integer', 'chats' => 'required|array|min:1|max:5', 'chats.*' => 'integer']);
        $msgs = [];
        foreach ($d['ids'] as $id) {
            [$m] = $this->mensaje((int) $id, $yo);
            if (! $m->eliminado_at && ! $m->una_vez) {
                $msgs[] = $m;
            }
        }
        usort($msgs, fn ($a, $b) => $a->id <=> $b->id);
        abort_unless($msgs, 422, 'Esos mensajes no se pueden reenviar.');
        $disco = Storage::disk('local');
        $ok = 0;
        foreach (array_unique($d['chats']) as $cid) {
            $c = $this->chat((int) $cid, $yo, true);
            [$puede, $motivo] = LpChat::puedeEscribir($c, $yo);
            if (! $puede) {
                continue;
            }
            foreach ($msgs as $m) {
                $adj = null;
                if ($m->adjunto && $disco->exists($m->adjunto)) {
                    $adj = 'lpchat/adjuntos/'.date('Y/m/d').'/fw_'.bin2hex(random_bytes(10)).'.'.pathinfo($m->adjunto, PATHINFO_EXTENSION);
                    $disco->copy($m->adjunto, $adj);
                }
                $nid = LpChat::enviar($c, $yo, LpChat::leer($m->texto), $adj, $adj ? $m->adjunto_tipo : null);
                DB::table('lpchat_mensajes')->where('id', $nid)->update(['reenviado' => true]);
            }
            $ok++;
        }

        return response()->json($ok ? ['ok' => true, 'n' => $ok] : ['ok' => false, 'mensaje' => 'No se pudo reenviar a esos chats.'], $ok ? 200 : 422);
    }

    /** Info del mensaje: cuándo se envió y quién lo leyó. */
    public function info(int $id): JsonResponse
    {
        $yo = $this->yo();
        [$m, $c] = $this->mensaje($id, $yo);
        $miembros = DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->get(['usuario_id', 'leido_id']);
        $nombres = U::whereIn('id', $miembros->pluck('usuario_id'))->pluck('nombre', 'id');
        $leyeron = $miembros->filter(fn ($x) => $x->leido_id >= $m->id)->map(fn ($x) => $nombres[$x->usuario_id] ?? 'Alguien')->values();
        $faltan = $miembros->filter(fn ($x) => $x->leido_id < $m->id)->map(fn ($x) => $nombres[$x->usuario_id] ?? 'Alguien')->values();

        return response()->json(['ok' => true, 'enviado' => Carbon::parse($m->created_at)->timezone('America/Bogota')->locale('es')->translatedFormat('j \d\e F, g:i a'),
            'mio' => $m->usuario_id === $yo->id, 'grupo' => $c->tipo === 'grupo', 'leyeron' => $leyeron, 'faltan' => $faltan]);
    }
}
