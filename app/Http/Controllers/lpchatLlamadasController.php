<?php

namespace App\Http\Controllers;

use App\Models\LpchatUsuario as U;
use App\Support\LpChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 2026-11-28 (Jhoset): llamadas de voz y videollamadas de LP Chat (uno a uno, como WhatsApp).
 * La voz y el video van directo entre los dos (WebRTC). El servidor solo guarda la llamada y pasa las señales
 * (offer / answer / ice) para que los dos se encuentren. Si hay servidor TURN (LPCHAT_TURN_URL + LPCHAT_TURN_SECRET en
 * el .env, lo instala deploy/activar-llamadas.sh) también funciona con datos móviles que no dejan conectar directo.
 */
class lpchatLlamadasController extends lpchatController
{
    private const TIMBRE = 45;      // segundos que suena antes de "perdida"
    private const SIN_SENAL = 25;   // segundos sin noticias de uno de los dos = se cayó

    /** Servidores para conectar: STUN gratis de Google y, si está, el TURN propio con clave temporal. */
    public static function ice(int $uid): array
    {
        $s = [['urls' => ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302']]];
        $url = trim((string) config('lpchat.turn_url'));
        $sec = (string) config('lpchat.turn_secret');
        if ($url !== '' && $sec !== '') {
            $user = (time() + 6 * 3600).':'.$uid;
            $s[] = ['urls' => ['turn:'.$url.':3478?transport=udp', 'turn:'.$url.':3478?transport=tcp'],
                'username' => $user, 'credential' => base64_encode(hash_hmac('sha1', $user, $sec, true))];
        }

        return $s;
    }

    /** Cierra solas las que se quedaron sonando o se cayeron, y deja el aviso en el chat. */
    public static function vencer(): void
    {
        foreach (DB::table('lpchat_llamadas')->where('estado', 'llamando')->where('created_at', '<', now()->subSeconds(self::TIMBRE))->get() as $l) {
            self::cerrar($l, 'perdida');
        }
        foreach (DB::table('lpchat_llamadas')->where('estado', 'aceptada')->where(fn ($q) => $q->where('de_visto_at', '<', now()->subSeconds(self::SIN_SENAL))
            ->orWhere('para_visto_at', '<', now()->subSeconds(self::SIN_SENAL)))->get() as $l) {
            self::cerrar($l, 'terminada');
        }
    }

    private static function cerrar(object $l, string $estado): void
    {
        $n = DB::table('lpchat_llamadas')->where('id', $l->id)->whereIn('estado', ['llamando', 'aceptada'])->update(['estado' => $estado, 'fin_at' => now(), 'updated_at' => now()]);
        if (! $n) {
            return; // ya la cerró el otro
        }
        $tipo = $l->video ? 'Videollamada' : 'Llamada de voz';
        if ($estado === 'terminada' && $l->inicio_at) {
            $s = max(1, Carbon::parse($l->inicio_at)->diffInSeconds(now(), true));
            $txt = ($l->video ? '🎥 ' : '📞 ').$tipo.' · '.($s >= 3600 ? gmdate('G:i:s', (int) $s) : gmdate('i:s', (int) $s));
        } elseif ($estado === 'rechazada') {
            $txt = ($l->video ? '🎥 ' : '📞 ').$tipo.' no contestada';
        } else {
            $de = U::find($l->de_id);
            $txt = ($l->video ? '🎥 ' : '📞 ').($l->video ? 'Videollamada perdida' : 'Llamada perdida').($de ? ' de '.$de->nombre : '');
        }
        DB::table('lpchat_llamada_senales')->where('llamada_id', $l->id)->delete();
        LpChat::sistema((int) $l->chat_id, $txt);
    }

    private function mia(int $id, U $yo): object
    {
        $l = DB::table('lpchat_llamadas')->where('id', $id)->first();
        abort_unless($l && in_array($yo->id, [(int) $l->de_id, (int) $l->para_id], true), 404);

        return $l;
    }

    private function enLlamada(int $uid): bool
    {
        return DB::table('lpchat_llamadas')->whereIn('estado', ['llamando', 'aceptada'])
            ->where(fn ($q) => $q->where('de_id', $uid)->orWhere('para_id', $uid))->exists();
    }

    /** Llamar a alguien desde un chat directo. */
    public function llamar(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $d = $request->validate(['chat_id' => 'required|integer', 'video' => 'nullable|boolean']);
        self::vencer();
        $chat = DB::table('lpchat_chats')->where('id', $d['chat_id'])->first();
        if (! $chat || $chat->tipo !== 'directo' || ! ($m = LpChat::miembro($chat->id, $yo->id)) || $m->estado !== 'activo') {
            return response()->json(['ok' => false, 'mensaje' => 'Por ahora solo se puede llamar en chats de dos personas.'], 422);
        }
        $otroId = (int) DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', '!=', $yo->id)->value('usuario_id');
        $otro = $otroId ? U::find($otroId) : null;
        if (! $otro || LpChat::bloqueado($yo->id, $otro->id)) {
            return response()->json(['ok' => false, 'mensaje' => 'No se puede llamar a esta persona.'], 422);
        }
        if ($this->enLlamada($yo->id)) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya estás en una llamada.'], 422);
        }
        if ($this->enLlamada($otro->id)) {
            return response()->json(['ok' => false, 'mensaje' => $otro->nombre.' está en otra llamada. Intenta en un momento.'], 422);
        }
        $id = DB::table('lpchat_llamadas')->insertGetId(['chat_id' => $chat->id, 'de_id' => $yo->id, 'para_id' => $otro->id, 'video' => (bool) ($d['video'] ?? false),
            'estado' => 'llamando', 'de_visto_at' => now(), 'para_visto_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        \App\Support\WebPush::avisarLuego([$otro->id]);

        return response()->json(['ok' => true, 'id' => $id, 'ice' => self::ice($yo->id),
            'otro' => ['nombre' => $otro->nombre, 'foto' => $otro->foto_url]]);
    }

    /** ¿Me están llamando? (la app lo pregunta cada 2 s). */
    public function entrante(): JsonResponse
    {
        $p = $this->persona();
        if (! $p) {
            return response()->json(['ok' => false]);
        }
        self::vencer();
        $l = DB::table('lpchat_llamadas')->where('para_id', $p->id)->where('estado', 'llamando')->orderByDesc('id')->first();
        if (! $l) {
            return response()->json(['ok' => true, 'llamada' => null]);
        }
        $de = U::find($l->de_id);

        return response()->json(['ok' => true, 'llamada' => ['id' => $l->id, 'chat_id' => $l->chat_id, 'video' => (bool) $l->video,
            'de' => ['nombre' => $de?->nombre ?? 'Alguien', 'foto' => $de?->foto_url], 'ice' => self::ice($p->id)]]);
    }

    /** Estado de la llamada + señales nuevas del otro. También dice "sigo aquí". */
    public function estado(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $l = $this->mia($id, $yo);
        DB::table('lpchat_llamadas')->where('id', $id)->update([((int) $l->de_id === $yo->id ? 'de_visto_at' : 'para_visto_at') => now()]);
        self::vencer();
        $l = DB::table('lpchat_llamadas')->where('id', $id)->first();
        $senales = DB::table('lpchat_llamada_senales')->where('llamada_id', $id)->where('de_id', '!=', $yo->id)
            ->where('id', '>', (int) $request->query('desde', 0))->orderBy('id')->get(['id', 'tipo', 'datos']);

        return response()->json(['ok' => true, 'estado' => $l->estado, 'inicio' => $l->inicio_at ? Carbon::parse($l->inicio_at)->timestamp : null,
            'senales' => $senales]);
    }

    public function senal(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $l = $this->mia($id, $yo);
        $d = $request->validate(['tipo' => 'required|in:offer,answer,ice', 'datos' => 'required|string|max:60000']);
        if (! in_array($l->estado, ['llamando', 'aceptada'], true)) {
            return response()->json(['ok' => false, 'estado' => $l->estado]);
        }
        DB::table('lpchat_llamada_senales')->insert(['llamada_id' => $id, 'de_id' => $yo->id, 'tipo' => $d['tipo'], 'datos' => $d['datos'], 'created_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function accion(Request $request, int $id, string $accion): JsonResponse
    {
        $yo = $this->yo();
        $l = $this->mia($id, $yo);
        if ($accion === 'aceptar') {
            $n = DB::table('lpchat_llamadas')->where('id', $id)->where('para_id', $yo->id)->where('estado', 'llamando')
                ->update(['estado' => 'aceptada', 'inicio_at' => now(), 'para_visto_at' => now(), 'de_visto_at' => now(), 'updated_at' => now()]);

            return response()->json($n ? ['ok' => true] : ['ok' => false, 'mensaje' => 'La llamada ya terminó.']);
        }
        if ($accion === 'rechazar' && (int) $l->para_id === $yo->id && $l->estado === 'llamando') {
            self::cerrar($l, 'rechazada');
        } elseif ($accion === 'colgar') {
            self::cerrar($l, $l->estado === 'aceptada' ? 'terminada' : 'perdida');
        }

        return response()->json(['ok' => true]);
    }
}
