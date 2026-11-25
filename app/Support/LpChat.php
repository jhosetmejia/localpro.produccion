<?php

namespace App\Support;

use App\Models\LpchatUsuario as U;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 2026-11-17 (Jhoset): reglas de LP Chat.
 * - Pestañas: "Mis chats" (amigos: link compartido, correo/celular exacto, grupos) y "General" (gente de Conocer gente y Empresas).
 * - Si viene de "Conocer gente" llega a "Solicitudes" (oculto, sin aviso). El que recibe acepta o descarta;
 *   si descarta, ya no le llegan más mensajes de esa persona. Si responde, queda aceptada.
 * - Conocer gente: máximo LIMITE_CONOCER personas nuevas por día; se muestran máximo MUESTRA perfiles al día (contra la descarga de la base).
 * - Todo lo de los chats dura 7 días (fotos, videos, audios y mensajes); los estados 24 horas. Ver lpchat:limpiar.
 * - Mientras la solicitud no se acepta, el que la mandó puede escribir máximo config('lpchat.max_solicitud') mensajes.
 * - Grupos: el creador es admin; se agregan contactos o por correo/celular; link para unirse.
 * - Bloqueos: si alguien te bloqueó (o lo bloqueaste) no se pueden escribir.
 */
class LpChat
{
    public const LIMITE_CONOCER = 5;   // personas nuevas por día desde Conocer gente

    public const LIMITE_EMPRESAS = 20; // empresas nuevas por día

    public const MUESTRA = 60;         // perfiles que ve cada persona al día en Conocer gente

    public const DIAS = 7;             // lo de los chats se borra a los 7 días

    /** 2026-11-18: ¿tiene Plan Pro vigente? */
    public static function esPro(U $u): bool
    {
        return $u->sin_limite || ($u->pro_hasta && $u->pro_hasta->isFuture());
    }

    /** Personas nuevas que puede saludar al día en Conocer gente (null = sin límite). Gratis 5, Pro 100. */
    public static function limiteConocer(U $u): ?int
    {
        if ($u->sin_limite) {
            return null;
        }

        return self::esPro($u) ? (int) config('lpchat.pro.limite', 100) : self::LIMITE_CONOCER;
    }

    /** Cuántas personas nuevas contactó hoy por $via (conocer | empresa). */
    public static function contactosHoy(U $yo, string $via): int
    {
        return DB::table('lpchat_chats')->where('creador_id', $yo->id)->where('via', $via)->where('created_at', '>=', now('America/Bogota')->startOfDay()->utc())->count();
    }

    public static function bloqueado(int $a, int $b): bool
    {
        return DB::table('lpchat_bloqueos')->where(fn ($q) => $q->where('usuario_id', $a)->where('bloqueado_id', $b))
            ->orWhere(fn ($q) => $q->where('usuario_id', $b)->where('bloqueado_id', $a))->exists();
    }

    /**
     * 2026-11-17 (Jhoset): cuentas de menores (las maneja el papá/mamá): solo hablan con el equipo del evento
     * (cuentas sin límite: equipo de NES/PFN) y con quien ya tenían chat. Nadie desconocido les puede escribir.
     */
    public static function puedeContactar(U $yo, U $otro): bool
    {
        if (! $yo->menor && ! $otro->menor) {
            return true;
        }

        return $yo->sin_limite || $otro->sin_limite || (bool) self::directo($yo->id, $otro->id);
    }

    public const AVISO_MENOR = 'Esta cuenta es de un menor de edad: solo puede chatear con el equipo del evento.';

    public static function claveDirecto(int $a, int $b): string
    {
        return 'd:'.min($a, $b).':'.max($a, $b);
    }

    public static function directo(int $a, int $b): ?object
    {
        return DB::table('lpchat_chats')->where('clave', self::claveDirecto($a, $b))->first();
    }

    public static function miembro(int $chatId, int $uid): ?object
    {
        return DB::table('lpchat_miembros')->where('chat_id', $chatId)->where('usuario_id', $uid)->first();
    }

    /**
     * Abre (o crea) el chat directo de $yo con $otro.
     * $via: link | contacto (correo/celular exacto) -> "Mis chats" sin solicitud;
     *       conocer -> "General" y le llega como solicitud; empresa -> "General" sin solicitud.
     */
    public static function abrirDirecto(U $yo, U $otro, string $via): int
    {
        $conocido = $via !== 'conocer';
        $seccion = in_array($via, ['link', 'contacto'], true) ? 'mis' : 'general';
        if ($c = self::directo($yo->id, $otro->id)) {
            if ($seccion === 'mis') {
                // Si ya hablaban por Conocer gente y luego se pasaron el link, quedan como amigos.
                DB::table('lpchat_miembros')->where('chat_id', $c->id)->update(['seccion' => 'mis']);
            }
            // Si yo lo había descartado o me salí, al escribirle vuelvo a quedar activo.
            DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', $yo->id)->whereIn('estado', ['descartado', 'salio', 'solicitud', 'oculto'])
                ->update(['estado' => 'activo', 'updated_at' => now()]);
            if ($conocido) {
                DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', $otro->id)->where('estado', 'solicitud')
                    ->update(['estado' => 'activo', 'updated_at' => now()]);
            }

            return (int) $c->id;
        }

        return DB::transaction(function () use ($yo, $otro, $conocido, $via, $seccion) {
            $id = DB::table('lpchat_chats')->insertGetId(['tipo' => 'directo', 'via' => $via, 'clave' => self::claveDirecto($yo->id, $otro->id),
                'creador_id' => $yo->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('lpchat_miembros')->insert([
                ['chat_id' => $id, 'usuario_id' => $yo->id, 'rol' => 'miembro', 'estado' => 'activo', 'seccion' => $seccion, 'leido_id' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['chat_id' => $id, 'usuario_id' => $otro->id, 'rol' => 'miembro', 'estado' => $conocido ? 'activo' : 'solicitud', 'seccion' => $seccion, 'leido_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);

            return $id;
        });
    }

    public static function crearGrupo(U $yo, string $nombre, array $ids): int
    {
        return DB::transaction(function () use ($yo, $nombre, $ids) {
            $id = DB::table('lpchat_chats')->insertGetId(['tipo' => 'grupo', 'via' => 'contacto', 'nombre' => mb_substr(trim($nombre), 0, 80),
                'codigo' => self::codigoNuevo(), 'creador_id' => $yo->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('lpchat_miembros')->insert(['chat_id' => $id, 'usuario_id' => $yo->id, 'rol' => 'admin', 'estado' => 'activo', 'leido_id' => 0, 'created_at' => now(), 'updated_at' => now()]);
            self::sistema($id, $yo->nombre.' creó el grupo "'.mb_substr(trim($nombre), 0, 80).'"');
            foreach (array_unique($ids) as $uid) {
                if ((int) $uid !== $yo->id && ($u = U::find((int) $uid)) && self::puedeContactar($yo, $u)) {
                    self::agregar($id, $u, $yo);
                }
            }

            return $id;
        });
    }

    public static function codigoNuevo(): string
    {
        do {
            $c = Str::lower(Str::random(10));
        } while (DB::table('lpchat_chats')->where('codigo', $c)->exists());

        return $c;
    }

    /** Agrega (o reactiva) a alguien en un grupo. $por = quien lo agregó (null = entró con el link). */
    public static function agregar(int $chatId, U $u, ?U $por = null): bool
    {
        $m = self::miembro($chatId, $u->id);
        if ($m && $m->estado === 'activo') {
            return false;
        }
        $ultimo = (int) DB::table('lpchat_chats')->where('id', $chatId)->value('ultimo_id');
        if ($m) {
            DB::table('lpchat_miembros')->where('id', $m->id)->update(['estado' => 'activo', 'rol' => 'miembro', 'leido_id' => $ultimo, 'updated_at' => now()]);
        } else {
            DB::table('lpchat_miembros')->insert(['chat_id' => $chatId, 'usuario_id' => $u->id, 'rol' => 'miembro', 'estado' => 'activo', 'leido_id' => $ultimo, 'created_at' => now(), 'updated_at' => now()]);
        }
        self::sistema($chatId, $por ? $por->nombre.' agregó a '.$u->nombre : $u->nombre.' se unió con el link del grupo');

        return true;
    }

    public static function sistema(int $chatId, string $texto): int
    {
        $id = DB::table('lpchat_mensajes')->insertGetId(['chat_id' => $chatId, 'usuario_id' => null, 'texto' => $texto, 'tipo' => 'sistema', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lpchat_chats')->where('id', $chatId)->update(['ultimo_id' => $id, 'updated_at' => now()]);

        return $id;
    }

    /** El otro de un chat directo. */
    public static function otro(int $chatId, int $yo): ?U
    {
        $id = DB::table('lpchat_miembros')->where('chat_id', $chatId)->where('usuario_id', '!=', $yo)->value('usuario_id');

        return $id ? U::find($id) : null;
    }

    /** @return array{0: bool, 1: ?string} */
    public static function puedeEscribir(object $chat, U $yo): array
    {
        $m = self::miembro($chat->id, $yo->id);
        if (! $m || $m->estado === 'salio') {
            return [false, $chat->tipo === 'grupo' ? 'Ya no eres parte de este grupo.' : 'Esta conversación no existe.'];
        }
        if ($chat->tipo === 'grupo') {
            return [true, null];
        }
        $otro = self::otro($chat->id, $yo->id);
        if (! $otro) {
            return [false, 'Esta conversación no existe.'];
        }
        if (self::bloqueado($yo->id, $otro->id)) {
            return [false, 'No puedes escribir en esta conversación.'];
        }
        $mo = self::miembro($chat->id, $otro->id);
        if ($mo && $mo->estado === 'descartado') {
            return [false, 'Esta persona no aceptó tu solicitud. Tus mensajes ya no le llegan.'];
        }
        if ($mo && $mo->estado === 'solicitud') {
            $enviados = DB::table('lpchat_mensajes')->where('chat_id', $chat->id)->where('usuario_id', $yo->id)->count();
            if ($enviados >= (int) config('lpchat.max_solicitud', 3)) {
                return [false, 'Le enviaste una solicitud. Cuando la acepte podrán seguir hablando.'];
            }
        }

        return [true, null];
    }

    public static function enviar(object $chat, U $yo, ?string $texto, ?string $adjunto = null, ?string $adjTipo = null, bool $unaVez = false): int
    {
        $texto = $texto !== null ? trim(mb_substr($texto, 0, 4000)) : null;
        $id = DB::table('lpchat_mensajes')->insertGetId(['chat_id' => $chat->id, 'usuario_id' => $yo->id, 'texto' => self::cifrar($texto ?: null),
            'adjunto' => $adjunto, 'adjunto_tipo' => $adjunto ? $adjTipo : null, 'una_vez' => $adjunto && $unaVez,
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('lpchat_chats')->where('id', $chat->id)->update(['ultimo_id' => $id, 'updated_at' => now()]);
        DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', $yo->id)->update(['leido_id' => $id, 'estado' => 'activo', 'updated_at' => now()]);
        if ($chat->tipo === 'directo') {
            // (Responder una solicitud = aceptarla: arriba mi fila queda "activo".)
            // Si el otro había borrado el chat, le vuelve a aparecer como en WhatsApp.
            DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', '!=', $yo->id)->whereIn('estado', ['salio', 'oculto'])
                ->update(['estado' => 'activo', 'updated_at' => now()]);
        }
        Cache::forget('lpchat_esc_'.$chat->id.'_'.$yo->id);
        // 2026-11-18: si el otro es de NES/PFN, se le avisa a ese sistema de una vez (no espera su cron): así llega allá y Mía responde rápido
        try {
            self::avisarOrigenes($chat->id, $yo->id);
        } catch (\Throwable $e) {
            report($e);
        }
        // 2026-11-18: aviso al celular de los demás del chat (aunque tengan LP Chat cerrado)
        try {
            \App\Support\WebPush::avisarLuego(DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', '!=', $yo->id)
                ->whereIn('estado', ['activo', 'solicitud'])->pluck('usuario_id')->all());
        } catch (\Throwable $e) {
            report($e);
        }

        return $id;
    }

    public static function enLinea(?Carbon $visto): bool
    {
        return $visto && $visto->gt(now()->subSeconds(70));
    }

    public static function visto(?Carbon $visto): string
    {
        if (! $visto) {
            return '';
        }
        if (self::enLinea($visto)) {
            return 'en línea';
        }
        $v = $visto->copy()->timezone('America/Bogota');
        $hoy = now('America/Bogota');
        if ($v->isSameDay($hoy)) {
            return 'últ. vez hoy a las '.$v->format('g:i a');
        }
        if ($v->isSameDay($hoy->copy()->subDay())) {
            return 'últ. vez ayer a las '.$v->format('g:i a');
        }

        return 'últ. vez el '.$v->format('d/m/Y');
    }

    public static function hora($fecha): string
    {
        $f = Carbon::parse($fecha)->timezone('America/Bogota');
        $hoy = now('America/Bogota');
        if ($f->isSameDay($hoy)) {
            return $f->format('g:i a');
        }
        if ($f->isSameDay($hoy->copy()->subDay())) {
            return 'Ayer';
        }

        return $f->diffInDays($hoy) < 7 ? ucfirst($f->locale('es')->translatedFormat('l')) : $f->format('d/m/y');
    }

    /** Lista de chats o de solicitudes de alguien, la más reciente primero. */
    /** $tab: mis | general | solicitudes */
    public static function lista(U $yo, string $tab = 'mis'): array
    {
        $filas = DB::table('lpchat_miembros as m')->join('lpchat_chats as c', 'c.id', '=', 'm.chat_id')
            ->where('m.usuario_id', $yo->id)->where('m.estado', $tab === 'solicitudes' ? 'solicitud' : 'activo')
            ->when($tab !== 'solicitudes', fn ($q) => $q->where('m.seccion', $tab === 'general' ? 'general' : 'mis'))
            ->where(fn ($q) => $q->where('c.tipo', 'grupo')->orWhere('c.ultimo_id', '>', 0))
            ->orderByDesc('c.ultimo_id')->orderByDesc('c.id')->limit(300)
            ->get(['c.id', 'c.tipo', 'c.nombre', 'c.foto', 'c.ultimo_id', 'm.leido_id', 'c.updated_at', 'c.via']);
        if ($filas->isEmpty()) {
            return [];
        }
        $ultimos = DB::table('lpchat_mensajes')->whereIn('id', $filas->pluck('ultimo_id')->filter())->get()->keyBy('id');
        $sinLeer = DB::table('lpchat_mensajes as x')->join('lpchat_miembros as m', function ($j) use ($yo) {
            $j->on('m.chat_id', '=', 'x.chat_id')->where('m.usuario_id', '=', $yo->id);
        })->whereIn('x.chat_id', $filas->pluck('id'))->whereColumn('x.id', '>', 'm.leido_id')
            ->where(fn ($q) => $q->whereNull('x.usuario_id')->orWhere('x.usuario_id', '!=', $yo->id))
            ->whereNull('x.tipo')->groupBy('x.chat_id')->selectRaw('x.chat_id, count(*) n')->pluck('n', 'x.chat_id');
        $directos = $filas->where('tipo', 'directo')->pluck('id');
        $otros = $directos->isEmpty() ? collect() : DB::table('lpchat_miembros')->whereIn('chat_id', $directos)->where('usuario_id', '!=', $yo->id)->pluck('usuario_id', 'chat_id');
        $usuarios = U::whereIn('id', $otros->values()->merge($ultimos->pluck('usuario_id')->filter())->unique())->get()->keyBy('id');

        return $filas->map(function ($f) use ($yo, $ultimos, $sinLeer, $otros, $usuarios) {
            $u = $f->tipo === 'directo' ? $usuarios->get($otros->get($f->id)) : null;
            $ult = $ultimos->get($f->ultimo_id);
            $prev = '';
            if ($ult) {
                $adj = ['foto' => '📷 Foto', 'video' => '🎥 Video', 'audio' => '🎤 Audio', 'sticker' => '🧩 Sticker'][$ult->adjunto_tipo ?? ''] ?? ($ult->adjunto ? '📎 Archivo' : '');
                if ($ult->una_vez) {
                    $adj = '① '.($ult->adjunto_tipo === 'foto' ? 'Foto' : ($ult->adjunto_tipo === 'video' ? 'Video' : 'Audio')).' para ver una vez';
                }
                $tx = self::leer($ult->texto);
                $prev = $tx ?: $adj;
                if ($adj && $tx && ! $ult->una_vez) {
                    $prev = $adj.' · '.$tx;
                }
                if ($f->tipo === 'grupo' && $ult->usuario_id && $ult->usuario_id !== $yo->id && $ult->tipo === null) {
                    $prev = (Str::before($usuarios->get($ult->usuario_id)?->nombre ?? '', ' ') ?: 'Alguien').': '.$prev;
                }
            }

            return [
                'id' => $f->id,
                'tipo' => $f->tipo,
                'nombre' => $f->tipo === 'grupo' ? $f->nombre : ($u->nombre ?? 'Usuario'),
                'foto' => $f->tipo === 'grupo' ? ($f->foto ? asset('storage/'.$f->foto) : null) : $u?->foto_url,
                'etiqueta' => $u?->etiqueta,
                'usuario' => $f->tipo === 'grupo' ? null : ($u->usuario ?? null), // 2026-11-25: para la URL /chat/{usuario}
                'empresa' => (bool) $u?->tienda_id,
                'en_linea' => $u ? self::enLinea($u->visto_at) : false,
                'ultimo' => mb_strimwidth($prev, 0, 80, '…'),
                'mio' => $ult && $ult->usuario_id === $yo->id,
                'hora' => $ult ? self::hora($ult->created_at) : '',
                'sin_leer' => (int) ($sinLeer[$f->id] ?? 0),
            ];
        })->values()->all();
    }

    public static function sinLeerTotal(U $yo, ?string $seccion = null): int
    {
        return (int) DB::table('lpchat_mensajes as x')->join('lpchat_miembros as m', function ($j) use ($yo) {
            $j->on('m.chat_id', '=', 'x.chat_id')->where('m.usuario_id', '=', $yo->id)->where('m.estado', '=', 'activo');
        })->when($seccion, fn ($q) => $q->where('m.seccion', $seccion))
            ->whereColumn('x.id', '>', 'm.leido_id')->where('x.usuario_id', '!=', $yo->id)->whereNull('x.tipo')->count();
    }

    public static function solicitudesTotal(U $yo): int
    {
        return (int) DB::table('lpchat_miembros as m')->join('lpchat_chats as c', 'c.id', '=', 'm.chat_id')
            ->where('m.usuario_id', $yo->id)->where('m.estado', 'solicitud')->where('c.ultimo_id', '>', 0)->count();
    }

    /** Personas con las que tengo chat directo activo (para armar grupos). */
    public static function contactos(U $yo): array
    {
        $chats = DB::table('lpchat_miembros as m')->join('lpchat_chats as c', 'c.id', '=', 'm.chat_id')
            ->where('m.usuario_id', $yo->id)->where('m.estado', 'activo')->where('m.seccion', 'mis')->where('c.tipo', 'directo')->pluck('c.id');
        $ids = DB::table('lpchat_miembros')->whereIn('chat_id', $chats)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->pluck('usuario_id');

        return U::whereIn('id', $ids)->orderBy('nombre')->get()->map(fn ($u) => self::tarjeta($u))->all();
    }

    public static function tarjeta(U $u): array
    {
        return ['id' => $u->id, 'nombre' => $u->nombre, 'usuario' => $u->usuario ?? null, 'foto' => $u->foto_url, 'bio' => $u->bio, 'ciudad' => $u->ciudad,
            'etiqueta' => $u->etiqueta, 'en_linea' => self::enLinea($u->visto_at), 'empresa' => (bool) $u->tienda_id];
    }

    /** Busca o crea la cuenta de alguien que llega desde otro sistema (PFN, NES) y la deja al día. */
    public static function desdeOrigen(string $origen, array $p): ?U
    {
        $oid = mb_substr((string) ($p['id'] ?? ''), 0, 40);
        $nombre = trim(mb_substr((string) ($p['nombre'] ?? ''), 0, 80));
        if ($oid === '' || $nombre === '') {
            return null;
        }
        $email = filter_var($p['email'] ?? null, FILTER_VALIDATE_EMAIL) ? mb_strtolower($p['email']) : null;
        $cel = U::normalizarCelular($p['celular'] ?? null);
        $uid = DB::table('lpchat_vinculos')->where('origen', $origen)->where('origen_id', $oid)->value('usuario_id');
        $u = $uid ? U::find($uid) : null;
        // 2026-11-18 (Jhoset): el administrador de NES / el Equipo PFN es la misma cuenta de LP Chat del administrador de LocalProo:
        // lo que le escriban allá le llega a su LP Chat (con aviso al celular). Si ya tenía una cuenta aparte, se une.
        if (! empty($p['admin']) && ($admin = self::cuentaAdmin())) {
            if ($u && $u->id !== $admin->id) {
                self::fusionar($u, $admin);
            }
            DB::table('lpchat_vinculos')->updateOrInsert(['origen' => $origen, 'origen_id' => $oid], ['usuario_id' => $admin->id, 'updated_at' => now(), 'created_at' => now()]);
            if (! $admin->sin_limite) {
                $admin->sin_limite = true;
                $admin->save();
            }

            return $admin;
        }
        if (! $u && $email) {
            $u = U::where('email', $email)->first();
        }
        if (! $u && $cel) {
            $u = U::where('celular', $cel)->first();
        }
        $datos = array_filter([
            // Enlace remoto (a veces firmado y con vencimiento): lpchat:fotos lo copia aquí en pocos minutos.
            'foto' => isset($p['foto']) && preg_match('#^https://#', (string) $p['foto']) ? mb_substr($p['foto'], 0, 255) : null,
            'etiqueta' => isset($p['etiqueta']) ? mb_substr((string) $p['etiqueta'], 0, 60) : null,
            'bio' => isset($p['bio']) ? mb_substr((string) $p['bio'], 0, 160) : null,
            'ciudad' => isset($p['ciudad']) ? mb_substr((string) $p['ciudad'], 0, 60) : null,
        ], fn ($v) => $v !== null && $v !== '');
        if (! $u) {
            $u = U::create($datos + ['nombre' => $nombre, 'origen' => $origen,
                'email' => $email && ! U::where('email', $email)->exists() ? $email : null,
                'celular' => $cel && ! U::where('celular', $cel)->exists() ? $cel : null,
                'conocer' => (bool) ($p['conocer'] ?? true)]);
        } else {
            // Solo completa lo que la persona no haya puesto ella misma.
            foreach ($datos as $k => $v) {
                if (! $u->$k || ($k === 'foto' && (preg_match('#^https?://#', (string) $u->foto) || str_starts_with((string) $u->foto, 'lpchat/origen/'))) || $k === 'etiqueta') {
                    $u->$k = $v;
                }
            }
            if (! $u->email && $email && ! U::where('email', $email)->exists()) {
                $u->email = $email;
            }
            if (! $u->celular && $cel && ! U::where('celular', $cel)->exists()) {
                $u->celular = $cel;
            }
            $u->save();
        }
        // Plan Pro o sin límite que ya tiene en NES/PFN (se queda con la fecha más lejana).
        $cambio = false;
        // 2026-11-17 (Jhoset): cuenta con inscripciones de menores (la maneja el papá/mamá): fuera de Conocer gente.
        $esMenor = ! empty($p['menor']);
        if ($esMenor !== (bool) $u->menor || ($esMenor && $u->conocer)) {
            $u->menor = $esMenor;
            if ($esMenor) {
                $u->conocer = false;
            }
            $cambio = true;
        }
        if (array_key_exists('sin_limite', $p) && (bool) $p['sin_limite'] !== (bool) $u->sin_limite) {
            $u->sin_limite = (bool) $p['sin_limite'];
            $cambio = true;
        }
        if (! empty($p['pro_hasta']) && ($h = Carbon::parse($p['pro_hasta'])) && (! $u->pro_hasta || $h->gt($u->pro_hasta))) {
            $u->pro_hasta = $h;
            $cambio = true;
        }
        if ($cambio) {
            $u->save();
        }
        DB::table('lpchat_vinculos')->updateOrInsert(['origen' => $origen, 'origen_id' => $oid], ['usuario_id' => $u->id, 'updated_at' => now(), 'created_at' => now()]);

        return $u;
    }

    /**
     * Las fotos de PFN y NES vienen con enlaces firmados de Backblaze que se vencen: se copian aquí (disco público)
     * y solo se vuelven a bajar si cambia el archivo de origen. Devuelve la ruta local o null.
     */
    public static function fotoRemota(string $origen, string $oid, ?string $url): ?string
    {
        if (! $url || ! preg_match('#^https://#', $url)) {
            return null;
        }
        $base = strtok($url, '?'); // sin la firma, que cambia cada vez
        $ruta = 'lpchat/origen/'.$origen.'-'.preg_replace('/[^a-z0-9]/i', '', $oid).'-'.substr(md5($base), 0, 10).'.jpg';
        $disco = \Illuminate\Support\Facades\Storage::disk('public');
        if ($disco->exists($ruta)) {
            return $ruta;
        }
        try {
            $r = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            $bytes = $r->successful() ? $r->body() : '';
            if ($bytes === '' || strlen($bytes) > 8 * 1024 * 1024 || ! @getimagesizefromstring($bytes)) {
                return null;
            }
            $img = @imagecreatefromstring($bytes);
            if ($img) {
                // Se guarda liviana: máximo 600 px de ancho, JPG.
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > 600) {
                    $n = imagescale($img, 600, (int) round($h * 600 / $w));
                    imagedestroy($img);
                    $img = $n;
                }
                ob_start();
                imagejpeg($img, null, 82);
                $bytes = ob_get_clean();
                imagedestroy($img);
            }
            foreach (glob($disco->path('lpchat/origen/'.$origen.'-'.preg_replace('/[^a-z0-9]/i', '', $oid).'-*')) ?: [] as $viejo) {
                @unlink($viejo);
            }
            $disco->put($ruta, $bytes);

            return $ruta;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Cuenta de LP Chat de un negocio de LocalProo (la atiende su equipo desde "Usar como empresa"). */
    public static function cuentaEmpresa(\App\Models\Tienda $t): U
    {
        $u = U::where('tienda_id', $t->id)->first() ?: new U(['tienda_id' => $t->id, 'conocer' => false, 'origen' => 'localproo']);
        $u->nombre = mb_substr($t->nombre ?: 'Empresa', 0, 80);
        $u->foto = $t->logo_path ?: null;
        $u->bio = mb_substr((string) ($t->descripcion ?: $t->actividad ?: ''), 0, 160) ?: null;
        $u->ciudad = $t->ciudad ? mb_substr($t->ciudad, 0, 60) : null;
        $u->etiqueta = 'Empresa · '.mb_substr((\App\Models\Tienda::TIPOS[$t->tipo_negocio][0] ?? 'Negocio'), 0, 40);
        $u->save();

        return $u;
    }

    /**
     * Los perfiles que esta persona puede ver HOY en Conocer gente (máximo MUESTRA, mezclados con una semilla por persona y día).
     * Así nadie puede recorrer toda la base: cada día ve una muestra distinta y sin correos ni celulares.
     */
    public static function muestraDelDia(U $yo): array
    {
        if ($yo->menor) {
            return []; // 2026-11-17: las cuentas de menores no usan Conocer gente
        }
        $dia = now('America/Bogota')->toDateString();

        $tam = self::esPro($yo) ? 200 : self::MUESTRA; // Pro ve más perfiles al día
        return Cache::remember('lpchat_muestra_'.$yo->id.'_'.$dia.'_'.$tam, now()->addHours(26), function () use ($yo, $dia, $tam) {
            $bloq = DB::table('lpchat_bloqueos')->where('usuario_id', $yo->id)->pluck('bloqueado_id')
                ->merge(DB::table('lpchat_bloqueos')->where('bloqueado_id', $yo->id)->pluck('usuario_id'));
            $ya = DB::table('lpchat_miembros as a')->join('lpchat_chats as c', 'c.id', '=', 'a.chat_id')->join('lpchat_miembros as b', 'b.chat_id', '=', 'c.id')
                ->where('c.tipo', 'directo')->where('a.usuario_id', $yo->id)->where('b.usuario_id', '!=', $yo->id)->pluck('b.usuario_id');
            $base = U::where('conocer', true)->where('menor', false)->whereNull('tienda_id')->where('id', '!=', $yo->id)->whereNotIn('id', $bloq->merge($ya)->unique());
            $conFoto = (clone $base)->whereNotNull('foto')->orderByDesc('visto_at')->orderByDesc('id')->limit(4000)->pluck('id')->all();
            $sinFoto = count($conFoto) < $tam ? (clone $base)->whereNull('foto')->orderByDesc('visto_at')->limit(500)->pluck('id')->all() : [];
            mt_srand(crc32($yo->id.'|'.$dia));
            shuffle($conFoto);
            shuffle($sinFoto);
            mt_srand();

            return array_slice(array_merge($conFoto, $sinFoto), 0, $tam);
        });
    }

    /**
     * 2026-11-18: trae mensajes de NES/PFN (chats que ya existían). $usuarios = [origen_id => perfil], $mensajes = [[id, de, para, texto, adj, fecha, leido]].
     * Cada mensaje entra una sola vez (origen "nes:m{id}"), en el chat directo de los dos, en "Mis chats" (ya se conocían).
     */
    /** Cuenta de LP Chat del administrador de LocalProo (el primer súper administrador). */
    public static function cuentaAdmin(): ?U
    {
        $w = \App\Models\User::where('es_superadmin', true)->orderBy('id')->first();
        if (! $w) {
            return null;
        }
        $u = U::where('user_id', $w->id)->first() ?: ($w->email ? U::where('email', mb_strtolower($w->email))->first() : null);
        if (! $u) {
            $u = U::create(['nombre' => mb_substr($w->name ?: 'LocalProo', 0, 80), 'email' => $w->email ? mb_strtolower($w->email) : null,
                'user_id' => $w->id, 'origen' => 'localproo', 'sin_limite' => true]);
        } elseif (! $u->user_id) {
            $u->user_id = $w->id;
            $u->save();
        }
        // 2026-11-24 (Jhoset): sus mensajes desde el súper admin de NES/PFN salían con otro nombre; siempre su nombre.
        $nombre = trim((string) config('lpchat.admin_nombre'));
        if ($nombre !== '' && $u->nombre !== $nombre) {
            $u->nombre = mb_substr($nombre, 0, 80);
            $u->save();
        }

        return $u;
    }

    /** Pasa todo lo de la cuenta $de (chats, mensajes, estados, vínculos) a la cuenta $a. */
    public static function fusionar(U $de, U $a): void
    {
        if ($de->id === $a->id) {
            return;
        }
        DB::transaction(function () use ($de, $a) {
            foreach (DB::table('lpchat_miembros')->where('usuario_id', $de->id)->get() as $m) {
                $chat = DB::table('lpchat_chats')->where('id', $m->chat_id)->first();
                if (! $chat) {
                    continue;
                }
                if ($chat->tipo === 'directo') {
                    $otro = (int) DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', '!=', $de->id)->value('usuario_id');
                    if (! $otro || $otro === $a->id) {
                        DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', $de->id)->delete();
                        continue;
                    }
                    if ($ya = self::directo($a->id, $otro)) {
                        // Ya hablaban: los mensajes se pasan al chat que ya existe
                        DB::table('lpchat_mensajes')->where('chat_id', $chat->id)->update(['chat_id' => $ya->id]);
                        $ult = (int) DB::table('lpchat_mensajes')->where('chat_id', $ya->id)->max('id');
                        DB::table('lpchat_chats')->where('id', $ya->id)->update(['ultimo_id' => $ult, 'updated_at' => now()]);
                        DB::table('lpchat_miembros')->where('chat_id', $ya->id)->update(['estado' => 'activo']);
                        DB::table('lpchat_miembros')->where('chat_id', $chat->id)->delete();
                        DB::table('lpchat_chats')->where('id', $chat->id)->delete();
                        continue;
                    }
                    DB::table('lpchat_miembros')->where('id', $m->id)->update(['usuario_id' => $a->id, 'estado' => $m->estado === 'oculto' ? 'oculto' : 'activo']);
                    DB::table('lpchat_chats')->where('id', $chat->id)->update(['clave' => self::claveDirecto($a->id, $otro)]);
                } elseif (self::miembro($chat->id, $a->id)) {
                    DB::table('lpchat_miembros')->where('id', $m->id)->delete();
                } else {
                    DB::table('lpchat_miembros')->where('id', $m->id)->update(['usuario_id' => $a->id]);
                }
            }
            DB::table('lpchat_mensajes')->where('usuario_id', $de->id)->update(['usuario_id' => $a->id]);
            DB::table('lpchat_vinculos')->where('usuario_id', $de->id)->update(['usuario_id' => $a->id]);
            foreach (['lpchat_estados', 'lpchat_push'] as $t) {
                if (\Illuminate\Support\Facades\Schema::hasTable($t)) {
                    DB::table($t)->where('usuario_id', $de->id)->update(['usuario_id' => $a->id]);
                }
            }
            DB::table('lpchat_chats')->where('creador_id', $de->id)->update(['creador_id' => $a->id]);
        });
    }

    public static function importar(string $origen, array $usuarios, array $mensajes): int
    {
        $mapa = [];
        foreach (array_slice($usuarios, 0, 1500, true) as $oid => $p) {
            if (! is_array($p)) {
                continue;
            }
            $existe = DB::table('lpchat_vinculos')->where('origen', $origen)->where('origen_id', (string) $oid)->exists();
            $u = self::desdeOrigen($origen, ['id' => (string) $oid] + $p + ($existe ? [] : ['conocer' => (bool) ($p['conocer'] ?? false)]));
            if ($u) {
                $mapa[(string) $oid] = $u;
            }
        }
        $n = 0;
        $avisar = [];
        foreach (array_slice($mensajes, 0, 3000) as $m) {
            $clave = $origen.':m'.(int) ($m['id'] ?? 0);
            $de = $mapa[(string) ($m['de'] ?? '')] ?? null;
            $para = $mapa[(string) ($m['para'] ?? '')] ?? null;
            if (! $de || ! $para || $de->id === $para->id) {
                continue;
            }
            // 2026-11-18: si ya estaba, igual se pone al día: que se vea el chat y si ya lo leyeron allá, aquí también.
            if ($ya = DB::table('lpchat_mensajes')->where('origen', $clave)->first(['id', 'chat_id'])) {
                self::ponerAlDia((int) $ya->chat_id, $de, $para, (int) $ya->id, ! empty($m['leido']));
                continue;
            }
            if (! empty($m['solo_leido'])) {
                continue; // solo venía a marcar leído algo que ya estaba
            }
            $texto = trim(str_replace('**', '', mb_substr((string) ($m['texto'] ?? ''), 0, 4000))); // 2026-11-18: sin los ** de negrilla de Mía
            $adj = ['audio' => '🎤 Nota de voz', 'foto' => '📷 Foto', 'video' => '🎥 Video'][$m['adj'] ?? ''] ?? null;
            if ($adj) {
                $texto = trim($adj.' (enviada en '.strtoupper($origen).')'.($texto !== '' ? "\n".$texto : ''));
            }
            if ($texto === '') {
                continue;
            }
            $c = self::directo($de->id, $para->id);
            $cid = $c ? (int) $c->id : self::abrirDirecto($de, $para, 'contacto');
            if (! $c) {
                // Ya se conocían en el otro sistema: queda en Mis chats.
                DB::table('lpchat_miembros')->where('chat_id', $cid)->update(['seccion' => 'mis']);
            }
            self::visibilidad($cid, $de, $para, ! $c);
            // 2026-11-18: NES/PFN mandan la hora en UTC; aquí se guarda en la hora del sistema (si no, salía 5 horas adelantada).
            $fecha = (! empty($m['fecha']) ? Carbon::parse($m['fecha']) : now())->timezone(config('app.timezone'));
            $id = DB::table('lpchat_mensajes')->insertGetId(['chat_id' => $cid, 'usuario_id' => $de->id, 'texto' => self::cifrar($texto), 'origen' => $clave,
                'created_at' => $fecha, 'updated_at' => $fecha]);
            DB::table('lpchat_chats')->where('id', $cid)->where('ultimo_id', '<', $id)->update(['ultimo_id' => $id, 'updated_at' => now()]);
            DB::table('lpchat_miembros')->where('chat_id', $cid)->where('usuario_id', $de->id)->where('leido_id', '<', $id)->update(['leido_id' => $id]);
            if (! empty($m['leido'])) {
                DB::table('lpchat_miembros')->where('chat_id', $cid)->where('usuario_id', $para->id)->where('leido_id', '<', $id)->update(['leido_id' => $id]);
            }
            // Cuando ya hablaron los dos, el chat aparece (y si alguno lo había borrado, le vuelve a aparecer).
            if (self::hablaronLosDos($cid)) {
                DB::table('lpchat_miembros')->where('chat_id', $cid)->whereIn('estado', ['salio', 'oculto'])->update(['estado' => 'activo']);
            }
            if (empty($m['leido']) && $fecha->gt(now()->subMinutes(30)) && (! $de->sin_limite || self::hablaronLosDos($cid))) {
                $avisar[] = $para->id;
            }
            $n++;
        }
        if ($avisar) {
            \App\Support\WebPush::avisarLuego($avisar);
        }

        return $n;
    }

    /** Mensaje que ya se había traído: que el chat se vea igual que allá y la marca de leído quede igual. */
    private static function ponerAlDia(int $cid, U $de, U $para, int $mid, bool $leido): void
    {
        self::visibilidad($cid, $de, $para, false);
        if ($leido) {
            DB::table('lpchat_miembros')->where('chat_id', $cid)->where('usuario_id', $para->id)->where('leido_id', '<', $mid)->update(['leido_id' => $mid]);
        }
    }

    /**
     * 2026-11-18 (Jhoset): los chats traídos de NES/PFN se ven igual que allá, en Mis chats.
     * Única excepción: a quien manda mensajes masivos (equipo del evento, sin límite) no se le llena la lista con los
     * que nadie respondió; a quien los recibe sí le aparecen (como en NES/PFN).
     */
    private static function visibilidad(int $cid, U $de, U $para, bool $nuevo): void
    {
        // Entre dos cuentas del equipo (p. ej. el administrador y Mía) siempre se ve.
        $todos = ! $de->sin_limite || $para->sin_limite || self::hablaronLosDos($cid);
        $q = DB::table('lpchat_miembros')->where('chat_id', $cid)->when(! $todos, fn ($x) => $x->where('usuario_id', $para->id));
        (clone $q)->whereIn('estado', ['oculto', 'salio', 'solicitud'])->update(['estado' => 'activo']);
        (clone $q)->update(['seccion' => 'mis']);
        if (! $todos && $nuevo) {
            DB::table('lpchat_miembros')->where('chat_id', $cid)->where('usuario_id', $de->id)->update(['estado' => 'oculto']);
        }
    }

    /** ¿Los dos del chat se han escrito al menos una vez? */
    public static function hablaronLosDos(int $chatId): bool
    {
        return DB::table('lpchat_mensajes')->where('chat_id', $chatId)->whereNotNull('usuario_id')->distinct()->count('usuario_id') >= 2;
    }

    /**
     * 2026-11-18: mensajes escritos en LP Chat entre dos personas de NES/PFN, para que también les lleguen allá.
     * Devuelve [mensajes, último id revisado] (el cursor avanza aunque no haya ninguno de ese sistema).
     */
    public static function exportar(string $origen, int $desde): array
    {
        $filas = DB::table('lpchat_mensajes as m')->join('lpchat_chats as c', 'c.id', '=', 'm.chat_id')
            ->where('m.id', '>', $desde)->whereNull('m.origen')->whereNull('m.tipo')->whereNotNull('m.usuario_id')->where('c.tipo', 'directo')
            ->orderBy('m.id')->limit(1000)->get(['m.id', 'm.chat_id', 'm.usuario_id', 'm.texto', 'm.adjunto_tipo', 'm.una_vez', 'm.created_at']);
        $ultimo = (int) ($filas->max('id') ?? $desde);
        if ($filas->isEmpty()) {
            return [[], $ultimo];
        }
        $otros = DB::table('lpchat_miembros')->whereIn('chat_id', $filas->pluck('chat_id')->unique())->get(['chat_id', 'usuario_id'])->groupBy('chat_id');
        $vin = DB::table('lpchat_vinculos')->where('origen', $origen)
            ->whereIn('usuario_id', $otros->flatten(1)->pluck('usuario_id')->unique())->pluck('origen_id', 'usuario_id');
        $out = [];
        foreach ($filas as $m) {
            $para = optional(($otros[$m->chat_id] ?? collect())->firstWhere('usuario_id', '!=', $m->usuario_id))->usuario_id;
            if (! $para || ! isset($vin[$m->usuario_id], $vin[$para])) {
                continue;
            }
            $texto = (string) self::leer($m->texto);
            if ($m->adjunto_tipo) {
                $nom = ['foto' => '📷 Foto', 'video' => '🎥 Video', 'audio' => '🎤 Nota de voz', 'sticker' => '🧩 Sticker'][$m->adjunto_tipo] ?? '📎 Archivo';
                $texto = trim($nom.($m->una_vez ? ' para ver una vez' : '').' en LP Chat: '.route('lpchat.index', ['chat' => $m->chat_id]).($texto !== '' ? "\n".$texto : ''));
            }
            $out[] = ['id' => $m->id, 'de' => $vin[$m->usuario_id], 'para' => $vin[$para], 'texto' => $texto, 'fecha' => Carbon::parse($m->created_at)->toIso8601String()];
        }

        return [$out, $ultimo];
    }

    /**
     * 2026-11-18: mensajes traídos de NES/PFN que la persona ya leyó aquí en LP Chat (últimos 7 días),
     * para marcarlos leídos allá también. Devuelve los ids del otro sistema.
     */
    public static function leidosAqui(string $origen): array
    {
        return DB::table('lpchat_mensajes as x')
            ->join('lpchat_miembros as m', fn ($j) => $j->on('m.chat_id', '=', 'x.chat_id')->on('m.usuario_id', '!=', 'x.usuario_id'))
            ->where('x.origen', 'like', $origen.':m%')->where('x.created_at', '>=', now()->subDays(7))
            ->whereColumn('m.leido_id', '>=', 'x.id')->orderByDesc('x.id')->limit(3000)->pluck('x.origen')
            ->map(fn ($o) => (int) substr($o, strlen($origen) + 2))->filter()->values()->all();
    }

    /** Pide a NES/PFN que traigan ya lo nuevo de LP Chat (después de responder, firmado). */
    public static function avisarOrigenes(int $chatId, int $yoId): void
    {
        $otros = DB::table('lpchat_miembros')->where('chat_id', $chatId)->where('usuario_id', '!=', $yoId)->pluck('usuario_id');
        $origenes = DB::table('lpchat_vinculos')->whereIn('usuario_id', $otros)->distinct()->pluck('origen')
            ->intersect(DB::table('lpchat_vinculos')->where('usuario_id', $yoId)->pluck('origen'))->values()->all();
        if (! $origenes) {
            return;
        }
        app()->terminating(function () use ($origenes) {
            foreach ($origenes as $o) {
                $base = rtrim((string) config('lpchat.origenes.'.$o.'.volver'), '/');
                $s = (string) config('lpchat.origenes.'.$o.'.secreto');
                if ($base === '' || $s === '') {
                    continue;
                }
                $ts = (string) time();
                try {
                    \Illuminate\Support\Facades\Http::timeout(4)->get($base.'/lpchat/ping', ['ts' => $ts, 'f' => hash_hmac('sha256', 'ping:'.$ts, $s)]);
                } catch (\Throwable $e) {
                    // si no contesta, igual lo trae su cron
                }
            }
        });
    }

    /**
     * 2026-11-25 (Jhoset): seguridad de los chats. Los mensajes se guardan cifrados (AES-256 con la llave del sistema):
     * en la base de datos no se pueden leer, y solo los ven las personas que están en ese chat (cada consulta revisa que seas miembro).
     */
    public static function cifrar(?string $texto): ?string
    {
        if ($texto === null || $texto === '' || str_starts_with($texto, 'enc:')) {
            return $texto;
        }

        return 'enc:'.\Illuminate\Support\Facades\Crypt::encryptString($texto);
    }

    /** Texto legible de un mensaje (los viejos sin cifrar pasan igual). */
    public static function leer(?string $texto): ?string
    {
        if ($texto === null || ! str_starts_with($texto, 'enc:')) {
            return $texto;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString(substr($texto, 4));
        } catch (\Throwable $e) {
            return '🔒 Mensaje no disponible';
        }
    }

    /** Firma HMAC que comparten LP Chat y el otro sistema. */
    public static function firmaValida(string $origen, string $datos, ?string $firma): bool
    {
        $s = config('lpchat.origenes.'.$origen.'.secreto');

        return $s && $firma && hash_equals(hash_hmac('sha256', $datos, $s), $firma);
    }
}
