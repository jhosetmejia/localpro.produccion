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
            DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', $yo->id)->whereIn('estado', ['descartado', 'salio', 'solicitud'])
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
                if ((int) $uid !== $yo->id && ($u = U::find((int) $uid))) {
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
        $id = DB::table('lpchat_mensajes')->insertGetId(['chat_id' => $chat->id, 'usuario_id' => $yo->id, 'texto' => $texto ?: null,
            'adjunto' => $adjunto, 'adjunto_tipo' => $adjunto ? $adjTipo : null, 'una_vez' => $adjunto && $unaVez,
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('lpchat_chats')->where('id', $chat->id)->update(['ultimo_id' => $id, 'updated_at' => now()]);
        DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', $yo->id)->update(['leido_id' => $id, 'estado' => 'activo', 'updated_at' => now()]);
        if ($chat->tipo === 'directo') {
            // (Responder una solicitud = aceptarla: arriba mi fila queda "activo".)
            // Si el otro había borrado el chat, le vuelve a aparecer como en WhatsApp.
            DB::table('lpchat_miembros')->where('chat_id', $chat->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'salio')
                ->update(['estado' => 'activo', 'updated_at' => now()]);
        }
        Cache::forget('lpchat_esc_'.$chat->id.'_'.$yo->id);

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
                $adj = ['foto' => '📷 Foto', 'video' => '🎥 Video', 'audio' => '🎤 Audio'][$ult->adjunto_tipo ?? ''] ?? ($ult->adjunto ? '📎 Archivo' : '');
                if ($ult->una_vez) {
                    $adj = '① '.($ult->adjunto_tipo === 'foto' ? 'Foto' : ($ult->adjunto_tipo === 'video' ? 'Video' : 'Audio')).' para ver una vez';
                }
                $prev = $ult->texto ?: $adj;
                if ($adj && $ult->texto && ! $ult->una_vez) {
                    $prev = $adj.' · '.$ult->texto;
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
        return ['id' => $u->id, 'nombre' => $u->nombre, 'foto' => $u->foto_url, 'bio' => $u->bio, 'ciudad' => $u->ciudad,
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
        if (! $u && $email) {
            $u = U::where('email', $email)->first();
        }
        if (! $u && $cel) {
            $u = U::where('celular', $cel)->first();
        }
        $datos = array_filter([
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
                if (! $u->$k || ($k === 'foto' && preg_match('#^https?://#', (string) $u->foto)) || $k === 'etiqueta') {
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
        DB::table('lpchat_vinculos')->updateOrInsert(['origen' => $origen, 'origen_id' => $oid], ['usuario_id' => $u->id, 'updated_at' => now(), 'created_at' => now()]);

        return $u;
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
        $dia = now('America/Bogota')->toDateString();

        return Cache::remember('lpchat_muestra_'.$yo->id.'_'.$dia, now()->addHours(26), function () use ($yo, $dia) {
            $bloq = DB::table('lpchat_bloqueos')->where('usuario_id', $yo->id)->pluck('bloqueado_id')
                ->merge(DB::table('lpchat_bloqueos')->where('bloqueado_id', $yo->id)->pluck('usuario_id'));
            $ya = DB::table('lpchat_miembros as a')->join('lpchat_chats as c', 'c.id', '=', 'a.chat_id')->join('lpchat_miembros as b', 'b.chat_id', '=', 'c.id')
                ->where('c.tipo', 'directo')->where('a.usuario_id', $yo->id)->where('b.usuario_id', '!=', $yo->id)->pluck('b.usuario_id');
            $base = U::where('conocer', true)->whereNull('tienda_id')->where('id', '!=', $yo->id)->whereNotIn('id', $bloq->merge($ya)->unique());
            $conFoto = (clone $base)->whereNotNull('foto')->orderByDesc('visto_at')->orderByDesc('id')->limit(4000)->pluck('id')->all();
            $sinFoto = count($conFoto) < self::MUESTRA ? (clone $base)->whereNull('foto')->orderByDesc('visto_at')->limit(500)->pluck('id')->all() : [];
            mt_srand(crc32($yo->id.'|'.$dia));
            shuffle($conFoto);
            shuffle($sinFoto);
            mt_srand();

            return array_slice(array_merge($conFoto, $sinFoto), 0, self::MUESTRA);
        });
    }

    /** Firma HMAC que comparten LP Chat y el otro sistema. */
    public static function firmaValida(string $origen, string $datos, ?string $firma): bool
    {
        $s = config('lpchat.origenes.'.$origen.'.secreto');

        return $s && $firma && hash_equals(hash_hmac('sha256', $datos, $s), $firma);
    }
}
