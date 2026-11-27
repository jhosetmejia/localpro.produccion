<?php

namespace App\Http\Controllers;

use App\Models\LpchatUsuario as U;
use App\Models\Tienda;
use App\Support\LpChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 2026-11-17 (Jhoset): LP Chat — pantalla tipo WhatsApp y su API (la app pregunta cada pocos segundos).
 * Pestañas: Mis chats · General · Conocer gente · Empresas. Abajo: Mensajes · Estados · Tienda (Marketplace).
 */
class lpchatController extends Controller
{
    /** La cuenta personal con la que entró (nunca la de empresa). */
    private function persona(): ?U
    {
        return Auth::guard('lpchat')->user() ?: lpchatCuentaController::desdeLocalProo();
    }

    /** La cuenta de empresa que puede atender esta persona (si es del equipo de un negocio de LocalProo). */
    private function empresaDe(U $p): ?Tienda
    {
        if (! $p->user_id) {
            return null;
        }
        $tid = DB::table('users')->where('id', $p->user_id)->value('tienda_id');

        return $tid ? Tienda::find($tid) : null;
    }

    /** Quién está chateando: la persona o, si cambió a "Usar como empresa", la cuenta del negocio. */
    private function yo(): U
    {
        $p = $this->persona();
        abort_unless($p, 401, 'Inicia sesión en LP Chat.');
        $u = $p;
        if ($eid = session('lpchat_empresa')) {
            $e = U::find($eid);
            $t = $this->empresaDe($p);
            if ($e && $t && (int) $e->tienda_id === (int) $t->id) {
                $u = $e;
            } else {
                session()->forget('lpchat_empresa');
            }
        }
        if (! $u->visto_at || $u->visto_at->lt(now()->subSeconds(30))) {
            $u->visto_at = now();
            $u->saveQuietly();
        }

        return $u;
    }

    private function chat(int $id, U $yo, bool $activo = false): object
    {
        $c = DB::table('lpchat_chats')->where('id', $id)->first();
        $m = $c ? LpChat::miembro($c->id, $yo->id) : null;
        abort_unless($c && $m && ($m->estado !== 'salio' || $c->tipo === 'directo'), 404);
        abort_if($activo && $m->estado !== 'activo', 403);
        $c->yo = $m;

        return $c;
    }

    public function index(Request $request)
    {
        $u = $this->persona();
        if (! $u) {
            return redirect()->route('lpchat.entrar', array_filter(['volver' => $request->getRequestUri() !== '/chat' ? $request->getRequestUri() : null]));
        }
        $yo = $this->yo();

        return view('lpchat.app', ['yo' => $yo, 'persona' => $u, 'empresa' => $this->empresaDe($u), 'abrir' => (int) $request->query('chat', 0),
            'abrirUrl' => $request->attributes->get('lpchat_url'),
            'texto' => mb_substr(trim((string) $request->query('texto', '')), 0, 500)]);
    }

    public function terminos()
    {
        return view('lpchat.terminos');
    }

    /** Link personal "Comparte este link e inicia a chatear". Quien lo abre queda en "Mis chats" (sin solicitud). */
    public function link(Request $request, string $codigo)
    {
        // 2026-11-25: sirve con el código viejo o con el usuario único (@rafael-gomez).
        $otro = U::where('codigo', $codigo)->first() ?: U::where('usuario', strtolower($codigo))->first();
        if (! $otro) {
            return response()->view('lpchat.aviso', ['titulo' => 'Este link no existe', 'texto' => 'Pídele a la persona que te lo comparta de nuevo.', 'volver' => route('lpchat.index')], 404);
        }
        if (! $this->persona()) {
            return view('lpchat.entrar', ['volver' => '/chat/'.($otro->usuario ?: 'u/'.$codigo), 'invita' => $otro]);
        }
        $yo = $this->yo();
        if ($yo->id === $otro->id || LpChat::bloqueado($yo->id, $otro->id)) {
            return redirect()->route('lpchat.index');
        }
        if (! LpChat::puedeContactar($yo, $otro)) {
            return response()->view('lpchat.aviso', ['titulo' => 'No disponible', 'texto' => LpChat::AVISO_MENOR, 'volver' => route('lpchat.index')], 403);
        }

        // 2026-11-25 (Jhoset): se queda en localproo.com/chat/{usuario} (como Instagram), sin pasar a ?chat=número.
        $request->query->set('chat', LpChat::abrirDirecto($yo, $otro, 'link'));
        if ($otro->usuario) {
            $request->attributes->set('lpchat_url', '/chat/'.$otro->usuario);
        }

        return $this->index($request);
    }

    public function unirse(Request $request, string $codigo)
    {
        $g = DB::table('lpchat_chats')->where('tipo', 'grupo')->where('codigo', $codigo)->first();
        if (! $g) {
            return response()->view('lpchat.aviso', ['titulo' => 'Este link del grupo ya no sirve', 'texto' => 'El administrador lo cambió. Pídele el nuevo.', 'volver' => route('lpchat.index')], 404);
        }
        if (! $this->persona()) {
            return view('lpchat.entrar', ['volver' => '/chat/g/'.$codigo, 'grupo' => $g]);
        }
        LpChat::agregar($g->id, $this->yo());

        return redirect()->route('lpchat.index', ['chat' => $g->id]);
    }

    /** 2026-11-18: avisos al celular. Llave pública para suscribirse. */
    public function pushClave(): JsonResponse
    {
        return response()->json(['ok' => true, 'clave' => \App\Support\WebPush::clavePublica()]);
    }

    /** Guarda la suscripción de este celular/computador para avisarle los mensajes nuevos. */
    public function pushGuardar(Request $request): JsonResponse
    {
        $p = $this->persona();
        abort_unless($p, 401, 'Inicia sesión en LP Chat.');
        $d = $request->validate(['endpoint' => 'required|string|max:1000|starts_with:https://']);
        DB::table('lpchat_push')->updateOrInsert(['hash' => hash('sha256', $d['endpoint'])], [
            'usuario_id' => $p->id, 'endpoint' => $d['endpoint'], 'agente' => mb_substr((string) $request->userAgent(), 0, 190),
            'updated_at' => now(), 'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Lo pide el service worker cuando llega un aviso: quién escribió y qué dijo (solo a la persona con sesión). */
    public function aviso(): JsonResponse
    {
        $p = $this->persona();
        if (! $p) {
            return response()->json(['ok' => false]);
        }
        foreach (['mis', 'general', 'solicitudes'] as $tab) {
            foreach (LpChat::lista($p, $tab) as $c) {
                if ($c['sin_leer'] || $tab === 'solicitudes') {
                    $total = LpChat::sinLeerTotal($p) + LpChat::solicitudesTotal($p);

                    return response()->json(['ok' => true, 'titulo' => $c['nombre'].($tab === 'solicitudes' ? ' quiere chatear contigo' : ''),
                        'texto' => $c['ultimo'] ?: 'Te escribió en LP Chat', 'url' => route('lpchat.index', ['chat' => $c['id']]),
                        'tag' => 'lpchat-'.$c['id'], 'total' => $total]);
                }
            }
        }

        return response()->json(['ok' => true, 'titulo' => 'LP Chat', 'texto' => 'Tienes mensajes nuevos', 'url' => route('lpchat.index'), 'tag' => 'lpchat']);
    }

    /** 2026-11-18: con qué cuentas de NES/PFN está unida mi cuenta (solo las mías). */
    public function misVinculos(): JsonResponse
    {
        $p = $this->persona();
        abort_unless($p, 401);

        return response()->json(['ok' => true, 'id' => $p->id, 'nombre' => $p->nombre, 'sin_limite' => (bool) $p->sin_limite,
            'vinculos' => DB::table('lpchat_vinculos')->where('usuario_id', $p->id)->orderByDesc('updated_at')->get(['origen', 'origen_id', 'updated_at'])]);
    }

    public function chats(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $tab = in_array($request->query('tab'), ['general', 'solicitudes'], true) ? $request->query('tab') : 'mis';

        // 2026-11-19 (Jhoset): la app pide esta lista dos veces cada 6 s (Mis chats y General); los contadores
        // (cuentas con muchos chats, como el equipo) se calculan una sola vez cada pocos segundos.
        $cont = Cache::remember('lpchat_cont_'.$yo->id, 5, fn () => ['solicitudes' => LpChat::solicitudesTotal($yo),
            'sin_leer_mis' => LpChat::sinLeerTotal($yo, 'mis'), 'sin_leer_general' => LpChat::sinLeerTotal($yo, 'general'),
            'estados_nuevos' => $this->estadosNuevos($yo)]);

        return response()->json(['ok' => true, 'chats' => LpChat::lista($yo, $tab)] + $cont);
    }

    public function mensajes(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo);
        $desde = (int) $request->query('desde', 0);
        $q = DB::table('lpchat_mensajes')->where('chat_id', $c->id);
        $msgs = $desde ? $q->where('id', '>', $desde)->orderBy('id')->limit(200)->get() : $q->orderByDesc('id')->limit(80)->get()->reverse()->values();
        // Al leer una solicitud no se marca como leída (como Instagram: el otro no sabe que la vio).
        if ($c->yo->estado === 'activo' && $c->ultimo_id > $c->yo->leido_id) {
            DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['leido_id' => $c->ultimo_id]);
        }
        $autores = U::whereIn('id', $msgs->pluck('usuario_id')->filter()->unique())->get()->keyBy('id');
        $leidoOtros = (int) DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->min('leido_id');
        [$puede, $motivo] = LpChat::puedeEscribir($c, $yo);
        $escribiendo = [];
        foreach (DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->limit(50)->pluck('usuario_id') as $uid) {
            if ($n = Cache::get('lpchat_esc_'.$c->id.'_'.$uid)) {
                $escribiendo[] = $n;
            }
        }

        return response()->json([
            'ok' => true,
            'chat' => $this->info($c, $yo),
            'mensajes' => $msgs->map(fn ($m) => $this->mensajeJson($m, $c, $yo, $autores) + ['leido' => $m->usuario_id === $yo->id && $leidoOtros >= $m->id])->values(),
            'leido_otros' => $leidoOtros,
            'puede' => $puede,
            'motivo' => $motivo,
            'solicitud' => $c->yo->estado === 'solicitud',
            'seccion' => $c->yo->seccion,
            'escribiendo' => $escribiendo,
        ]);
    }

    private function mensajeJson(object $m, object $c, U $yo, $autores): array
    {
        $mio = $m->usuario_id === $yo->id;
        $f = Carbon::parse($m->created_at)->timezone('America/Bogota');
        $r = ['id' => $m->id, 'mio' => $mio, 'sistema' => $m->tipo === 'sistema',
            'autor' => $c->tipo === 'grupo' && $m->usuario_id && ! $mio ? ($autores->get($m->usuario_id)->nombre ?? 'Alguien') : null,
            'autor_id' => $m->usuario_id, 'texto' => LpChat::leer($m->texto), 'hora' => $f->format('g:i a'), 'dia' => $f->toDateString(),
            'adj' => $m->adjunto_tipo ?: ($m->adjunto ? 'foto' : null), 'url' => null, 'una_vez' => (bool) $m->una_vez, 'abierto' => (bool) $m->abierto_at];
        if ($m->adjunto && ! $m->una_vez) {
            $r['url'] = route('lpchat.adjunto', $m->id);
        }

        return $r;
    }

    private function info(object $c, U $yo): array
    {
        if ($c->tipo === 'grupo') {
            $n = DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('estado', 'activo')->count();

            return ['id' => $c->id, 'tipo' => 'grupo', 'nombre' => $c->nombre, 'foto' => $c->foto ? asset('storage/'.$c->foto) : null,
                'sub' => $n.' participante'.($n === 1 ? '' : 's'), 'admin' => $c->yo->rol === 'admin', 'descripcion' => $c->descripcion,
                'link' => $c->yo->rol === 'admin' && $c->codigo ? route('lpchat.unirse', $c->codigo) : null];
        }
        $o = LpChat::otro($c->id, $yo->id);

        return ['id' => $c->id, 'tipo' => 'directo', 'nombre' => $o->nombre ?? 'Usuario', 'foto' => $o?->foto_url,
            'sub' => $o ? ($o->tienda_id ? ($o->etiqueta ?? 'Empresa') : LpChat::visto($o->visto_at)) : '', 'otro' => $o ? LpChat::tarjeta($o) : null,
            'tienda' => $o && $o->tienda_id ? $this->urlTienda($o->tienda_id) : null];
    }

    private function urlTienda(int $tid): ?string
    {
        $slug = Tienda::whereKey($tid)->where('catalogo_activo', true)->value('slug');

        return $slug ? route('catalogo.show', $slug) : null;
    }

    /** Texto, foto, video o audio (y "ver una vez"). Todo se borra a los 3 meses. */
    public function enviar(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo);
        $request->validate(['texto' => 'nullable|string|max:4000', 'archivo' => 'nullable|file|max:30720', 'una_vez' => 'nullable|boolean', 'sticker_id' => 'nullable|integer']);
        $texto = trim((string) $request->input('texto', ''));
        $archivo = $request->file('archivo');
        $sticker = $request->filled('sticker_id') ? DB::table('lpchat_stickers')->where('id', (int) $request->input('sticker_id'))->where('usuario_id', $yo->id)->first() : null;
        abort_if($texto === '' && ! $archivo && ! $sticker, 422, 'Escribe un mensaje.');
        [$puede, $motivo] = LpChat::puedeEscribir($c, $yo);
        if (! $puede) {
            return response()->json(['ok' => false, 'mensaje' => $motivo], 422);
        }
        $adj = $tipo = null;
        if ($sticker) {
            // Se envía una copia: el mensaje se borra a los 3 meses, el sticker de la colección no.
            $disco = Storage::disk('local');
            abort_unless($disco->exists($sticker->archivo), 410, 'Ese sticker ya no está.');
            $adj = 'lpchat/adjuntos/'.date('Y/m/d').'/st_'.bin2hex(random_bytes(10)).'.'.pathinfo($sticker->archivo, PATHINFO_EXTENSION);
            $disco->copy($sticker->archivo, $adj);
            $tipo = 'sticker';
            $texto = '';
            DB::table('lpchat_stickers')->where('id', $sticker->id)->update(['usado_at' => now()]);
        } elseif ($archivo) {
            $mime = (string) $archivo->getMimeType();
            $tipo = str_starts_with($mime, 'image/') ? 'foto' : (str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') || $mime === 'application/ogg' || ($mime === 'video/webm') ? 'audio' : null));
            if ($request->input('es_audio')) {
                // Notas de voz grabadas en el navegador: a veces llegan como video/webm o sin tipo reconocible.
                $cliente = (string) $archivo->getClientMimeType();
                if (in_array($tipo, ['video', 'audio'], true) || ($mime === 'application/octet-stream' && str_starts_with($cliente, 'audio/'))) {
                    $tipo = 'audio';
                }
            }
            if (! $tipo) {
                return response()->json(['ok' => false, 'mensaje' => 'Solo se pueden enviar fotos, videos y audios.'], 422);
            }
            $max = ['foto' => 10240, 'audio' => 10240, 'video' => 30720][$tipo];
            if ($archivo->getSize() > $max * 1024) {
                return response()->json(['ok' => false, 'mensaje' => 'El archivo pesa mucho (máximo '.($max / 1024).' MB).'], 422);
            }
            $adj = $archivo->store('lpchat/adjuntos/'.date('Y/m/d'), 'local');
        }
        $unaVez = $adj && $c->tipo === 'directo' && $request->boolean('una_vez');
        $mid = LpChat::enviar($c, $yo, $texto, $adj, $tipo, $unaVez);

        return response()->json(['ok' => true, 'id' => $mid]);
    }

    /* ---------- Stickers (2026-11-18) ---------- */
    private const MAX_STICKERS = 150;

    public function stickers(): JsonResponse
    {
        $yo = $this->yo();
        $l = DB::table('lpchat_stickers')->where('usuario_id', $yo->id)
            ->orderByDesc('favorito')->orderByRaw('COALESCE(usado_at, created_at) DESC')->limit(self::MAX_STICKERS)->get(['id', 'favorito']);

        return response()->json(['ok' => true, 'max' => self::MAX_STICKERS,
            'stickers' => $l->map(fn ($x) => ['id' => $x->id, 'fav' => (bool) $x->favorito, 'url' => route('lpchat.sticker', $x->id)])->values()]);
    }

    /** La app ya lo manda cuadrado (512 px, webp/png). Aquí solo se valida y se guarda. */
    public function crearSticker(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $request->validate(['imagen' => 'required|file|max:1024|mimetypes:image/webp,image/png,image/jpeg,image/gif']);
        if (DB::table('lpchat_stickers')->where('usuario_id', $yo->id)->count() >= self::MAX_STICKERS) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya tienes '.self::MAX_STICKERS.' stickers. Borra alguno para crear otro.'], 422);
        }
        $ruta = $request->file('imagen')->store('lpchat/stickers/'.$yo->id, 'local');
        $id = DB::table('lpchat_stickers')->insertGetId(['usuario_id' => $yo->id, 'archivo' => $ruta, 'usado_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    /** Guardar en mis stickers uno que me enviaron. */
    public function guardarSticker(Request $request, int $mensaje): JsonResponse
    {
        $yo = $this->yo();
        $m = DB::table('lpchat_mensajes')->where('id', $mensaje)->where('adjunto_tipo', 'sticker')->first();
        abort_unless($m && $m->adjunto && LpChat::miembro($m->chat_id, $yo->id), 404);
        $disco = Storage::disk('local');
        abort_unless($disco->exists($m->adjunto), 410, 'Este sticker ya se borró.');
        if (DB::table('lpchat_stickers')->where('usuario_id', $yo->id)->count() >= self::MAX_STICKERS) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya tienes '.self::MAX_STICKERS.' stickers. Borra alguno.'], 422);
        }
        $ruta = 'lpchat/stickers/'.$yo->id.'/'.bin2hex(random_bytes(12)).'.'.(pathinfo($m->adjunto, PATHINFO_EXTENSION) ?: 'webp');
        $disco->copy($m->adjunto, $ruta);
        DB::table('lpchat_stickers')->insert(['usuario_id' => $yo->id, 'archivo' => $ruta, 'usado_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function stickerAccion(Request $request, int $id, string $accion): JsonResponse
    {
        $yo = $this->yo();
        $st = DB::table('lpchat_stickers')->where('id', $id)->where('usuario_id', $yo->id)->first();
        abort_unless($st, 404);
        if ($accion === 'borrar') {
            Storage::disk('local')->delete($st->archivo);
            DB::table('lpchat_stickers')->where('id', $id)->delete();
        } else {
            DB::table('lpchat_stickers')->where('id', $id)->update(['favorito' => ! $st->favorito]);
        }

        return response()->json(['ok' => true]);
    }

    public function sticker(Request $request, int $id)
    {
        $yo = $this->yo();
        $st = DB::table('lpchat_stickers')->where('id', $id)->where('usuario_id', $yo->id)->first();
        abort_unless($st && Storage::disk('local')->exists($st->archivo), 404);

        return response()->file(Storage::disk('local')->path($st->archivo), ['Cache-Control' => 'private, max-age=2592000']);
    }

    /** Foto/video/audio del chat. "Ver una vez": solo lo abre quien lo recibe, una sola vez, y se borra. */
    public function adjunto(Request $request, int $mensaje)
    {
        $yo = $this->yo();
        $m = DB::table('lpchat_mensajes')->where('id', $mensaje)->first();
        abort_unless($m && $m->adjunto && LpChat::miembro($m->chat_id, $yo->id), 404);
        $disco = Storage::disk('local');
        if ($m->una_vez) {
            abort_if($m->usuario_id === $yo->id, 403, 'Lo enviaste para ver una sola vez.');
            // Se marca de forma atómica: si dos pedidos llegan a la vez, solo uno lo ve.
            $ok = DB::table('lpchat_mensajes')->where('id', $m->id)->whereNull('abierto_at')->update(['abierto_at' => now()]);
            abort_unless($ok && $disco->exists($m->adjunto), 410, 'Ya se abrió.');
            $bytes = $disco->get($m->adjunto);
            $mime = $disco->mimeType($m->adjunto) ?: 'application/octet-stream';
            $disco->delete($m->adjunto);

            return response($bytes, 200, ['Content-Type' => $mime, 'Cache-Control' => 'no-store', 'Content-Disposition' => 'inline']);
        }
        abort_unless($disco->exists($m->adjunto), 410, 'Este archivo ya se borró (los chats guardan 3 meses).');

        return response()->file($disco->path($m->adjunto), ['Cache-Control' => 'private, max-age=604800']);
    }

    public function escribiendo(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo, true);
        Cache::put('lpchat_esc_'.$c->id.'_'.$yo->id, $request->boolean('audio') ? strtok($yo->nombre, ' ').' 🎤' : strtok($yo->nombre, ' '), now()->addSeconds(6));

        return response()->json(['ok' => true]);
    }

    /** aceptar | descartar | borrar | bloquear | desbloquear | denunciar | mover (Mis chats <-> General) */
    public function accion(Request $request, int $id, string $accion): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo);
        $otro = $c->tipo === 'directo' ? LpChat::otro($c->id, $yo->id) : null;
        switch ($accion) {
            case 'aceptar':
                DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['estado' => 'activo', 'leido_id' => $c->ultimo_id, 'updated_at' => now()]);
                break;
            case 'descartar':
                abort_unless($c->yo->estado === 'solicitud', 422);
                DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['estado' => 'descartado', 'updated_at' => now()]);
                break;
            case 'mover':
                DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['seccion' => $c->yo->seccion === 'mis' ? 'general' : 'mis']);
                break;
            case 'borrar': // Borrar chat (directo) o salir del grupo.
                if ($c->tipo === 'grupo' && $c->yo->estado === 'activo') {
                    LpChat::sistema($c->id, $yo->nombre.' salió del grupo');
                    $this->pasarAdmin($c, $yo);
                }
                DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['estado' => 'salio', 'leido_id' => $c->ultimo_id, 'updated_at' => now()]);
                break;
            case 'bloquear':
            case 'denunciar':
                abort_unless($otro, 422);
                DB::table('lpchat_bloqueos')->updateOrInsert(['usuario_id' => $yo->id, 'bloqueado_id' => $otro->id],
                    ['denuncia' => $accion === 'denunciar', 'motivo' => $accion === 'denunciar' ? mb_substr((string) $request->input('motivo', ''), 0, 300) : null, 'updated_at' => now(), 'created_at' => now()]);
                if ($c->yo->estado === 'solicitud') {
                    DB::table('lpchat_miembros')->where('id', $c->yo->id)->update(['estado' => 'descartado', 'updated_at' => now()]);
                }
                if ($accion === 'denunciar') {
                    \Illuminate\Support\Facades\Log::warning('LP Chat denuncia', ['de' => $yo->id, 'a' => $otro->id, 'chat' => $c->id, 'motivo' => $request->input('motivo')]);
                }
                break;
            case 'desbloquear':
                abort_unless($otro, 422);
                DB::table('lpchat_bloqueos')->where('usuario_id', $yo->id)->where('bloqueado_id', $otro->id)->delete();
                break;
            default:
                abort(404);
        }

        return response()->json(['ok' => true]);
    }

    private function pasarAdmin(object $c, U $yo): void
    {
        $otrosAdmin = DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->where('rol', 'admin')->exists();
        if ($c->yo->rol === 'admin' && ! $otrosAdmin) {
            $sig = DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', '!=', $yo->id)->where('estado', 'activo')->orderBy('id')->first();
            if ($sig) {
                DB::table('lpchat_miembros')->where('id', $sig->id)->update(['rol' => 'admin']);
            }
        }
    }

    /**
     * Conocer gente: solo la muestra del día (máx. 60 perfiles) y sin correos ni celulares.
     * Se puede filtrar dentro de la muestra (PFN, NES, LocalProo), pero no buscar en toda la base.
     */
    public function conocer(Request $request): JsonResponse
    {
        $yo = $this->yo();
        abort_if($yo->tienda_id, 403, 'Las empresas no usan Conocer gente.');
        $ids = LpChat::muestraDelDia($yo);
        $origen = in_array($request->query('origen'), ['pfn', 'nes', 'localproo'], true) ? $request->query('origen') : null;
        $lista = U::whereIn('id', $ids)->when($origen, fn ($q) => $q->where('origen', $origen))->get()
            ->sortBy(fn ($u) => array_search($u->id, $ids))->values();
        $hechos = LpChat::contactosHoy($yo, 'conocer');
        $limite = LpChat::limiteConocer($yo);

        return response()->json(['ok' => true, 'perfiles' => $lista->map(fn ($u) => LpChat::tarjeta($u) + ['origen' => $u->origen, 'visto' => LpChat::visto($u->visto_at)])->values(),
            'quedan' => $limite === null ? null : max(0, $limite - $hechos), 'limite' => $limite, 'pro' => LpChat::esPro($yo),
            'pro_hasta' => $yo->pro_hasta && $yo->pro_hasta->isFuture() ? $yo->pro_hasta->timezone('America/Bogota')->format('d/m/Y') : null,
            'precio' => (int) config('lpchat.pro.precio'), 'limite_pro' => (int) config('lpchat.pro.limite')]);
    }

    /** Empresas de LocalProo (las mismas del Marketplace): logo, nombre y qué hacen. */
    public function empresas(Request $request): JsonResponse
    {
        $this->yo();
        $q = trim((string) $request->query('q', ''));
        $pag = max(1, min(20, (int) $request->query('pagina', 1)));
        $consulta = Tienda::query()->where('catalogo_activo', true)->where('estado', 'activa')->where('onboarding_completo', true)
            ->when(mb_strlen($q) >= 2, function ($w) use ($q) {
                $like = '%'.str_replace(['%', '_'], '', $q).'%';
                $w->where(fn ($x) => $x->where('nombre', 'like', $like)->orWhere('descripcion', 'like', $like)->orWhere('actividad', 'like', $like)->orWhere('ciudad', 'like', $like));
            });
        $total = (clone $consulta)->count();
        $lista = $consulta->orderByRaw('logo_path IS NULL')->orderBy('nombre')->skip(($pag - 1) * 24)->take(24)
            ->get(['id', 'nombre', 'slug', 'logo_path', 'descripcion', 'actividad', 'ciudad', 'tipo_negocio']);

        return response()->json(['ok' => true, 'hay_mas' => $total > $pag * 24, 'empresas' => $lista->map(fn ($t) => [
            'id' => $t->id, 'nombre' => $t->nombre, 'logo' => $t->logo_path ? asset('storage/'.$t->logo_path) : null,
            'descripcion' => mb_strimwidth((string) ($t->descripcion ?: $t->actividad ?: (Tienda::TIPOS[$t->tipo_negocio][0] ?? '')), 0, 140, '…'),
            'ciudad' => $t->ciudad, 'tipo' => Tienda::TIPOS[$t->tipo_negocio][0] ?? null, 'tienda' => $t->slug ? route('catalogo.show', $t->slug) : null,
        ])->values()]);
    }

    public function contactarEmpresa(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $t = Tienda::where('catalogo_activo', true)->where('estado', 'activa')->findOrFail((int) $request->input('tienda_id'));
        $e = LpChat::cuentaEmpresa($t);
        abort_if($e->id === $yo->id || LpChat::bloqueado($yo->id, $e->id), 422, 'No puedes escribirle a esta empresa.');
        if (! LpChat::puedeContactar($yo, $e)) {
            return response()->json(['ok' => false, 'mensaje' => LpChat::AVISO_MENOR], 403);
        }
        if (! LpChat::directo($yo->id, $e->id) && LpChat::contactosHoy($yo, 'empresa') >= LpChat::LIMITE_EMPRESAS) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya contactaste '.LpChat::LIMITE_EMPRESAS.' empresas hoy. Mañana puedes escribirle a más.'], 429);
        }

        return response()->json(['ok' => true, 'chat' => LpChat::abrirDirecto($yo, $e, 'empresa')]);
    }

    /**
     * 2026-11-18 (Jhoset): enlaces de chat dentro de un mensaje (como un número en WhatsApp): se abren aquí mismo.
     * Sirve para localproo.com/chat/u/{codigo}, /chat/g/{codigo}, /chat?chat=N y los chats de NES / PFN (/chat?con=ID, /lpchat?con=ID).
     * Si no es un enlace de chat o la persona aún no está en LP Chat -> 'externo' (se abre normal).
     */
    public function abrirEnlace(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $url = (string) $request->input('url', '');
        $mid = (int) $request->input('mensaje', 0);
        $externo = response()->json(['ok' => false, 'externo' => true]);
        $partes = parse_url($url);
        if (! $partes || empty($partes['host'])) {
            return $externo;
        }
        $host = preg_replace('/^www\./', '', mb_strtolower($partes['host']));
        $ruta = rtrim($partes['path'] ?? '/', '/') ?: '/';
        parse_str($partes['query'] ?? '', $q);

        // El enlace tiene que venir de un mensaje de un chat donde estoy (no se puede inventar para saltarse los límites).
        $m = $mid ? DB::table('lpchat_mensajes')->where('id', $mid)->first() : null;
        $deMensaje = $m && LpChat::miembro((int) $m->chat_id, $yo->id) && str_contains((string) LpChat::leer($m->texto), $url);

        $local = parse_url(config('app.url'), PHP_URL_HOST);
        if (in_array($host, ['localproo.com', preg_replace('/^www\./', '', (string) $local)], true)) {
            if (preg_match('#^/chat/u/([a-z0-9]+)$#', $ruta, $x)) {
                $otro = U::where('codigo', $x[1])->first();

                return $otro ? $this->abrirCon($yo, $otro, 'link') : $externo;
            }
            if (preg_match('#^/chat/g/([a-z0-9]+)$#', $ruta, $x)) {
                $g = DB::table('lpchat_chats')->where('tipo', 'grupo')->where('codigo', $x[1])->first();
                if (! $g) {
                    return response()->json(['ok' => false, 'mensaje' => 'Este link del grupo ya no sirve.']);
                }
                LpChat::agregar($g->id, $yo);

                return response()->json(['ok' => true, 'chat' => (int) $g->id]);
            }
            if ($ruta === '/chat' && ($c = (int) ($q['chat'] ?? 0)) && LpChat::miembro($c, $yo->id)) {
                return response()->json(['ok' => true, 'chat' => $c]);
            }

            return $externo;
        }

        $origenes = ['narinoexposhow.com' => 'nes', 'sistema.narinoexposhow.com' => 'nes', 'pastofightnight.com' => 'pfn'];
        $origen = $origenes[$host] ?? null;
        $con = mb_strtolower(trim((string) ($q['con'] ?? '')));
        if (! $origen || ! in_array($ruta, ['/chat', '/lpchat'], true) || ! preg_match('/^[up]?\d+$/', $con)) {
            return $externo;
        }
        $oid = ctype_digit($con) ? 'u'.$con : $con;
        $uid = DB::table('lpchat_vinculos')->where('origen', $origen)->where('origen_id', $oid)->value('usuario_id');
        $otro = $uid ? U::find($uid) : null;
        if (! $otro) {
            return $externo;
        }
        if (! $otro->sin_limite && ! LpChat::directo($yo->id, $otro->id) && ! $deMensaje) {
            return $externo;
        }

        return $this->abrirCon($yo, $otro, $otro->sin_limite ? 'link' : 'contacto');
    }

    private function abrirCon(U $yo, U $otro, string $via): JsonResponse
    {
        if ($otro->id === $yo->id) {
            return response()->json(['ok' => false, 'mensaje' => 'Ese es tu propio link 🙂']);
        }
        if (LpChat::bloqueado($yo->id, $otro->id)) {
            return response()->json(['ok' => false, 'mensaje' => 'No puedes escribirle a esta persona.']);
        }
        if (! LpChat::puedeContactar($yo, $otro)) {
            return response()->json(['ok' => false, 'mensaje' => LpChat::AVISO_MENOR]);
        }

        return response()->json(['ok' => true, 'chat' => LpChat::abrirDirecto($yo, $otro, $via)]);
    }

    /** Escribirle a alguien de "Conocer gente": va a "General" y le llega como solicitud. Máximo 5 personas nuevas al día. */
    public function abrir(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $otro = U::findOrFail((int) $request->input('usuario_id'));
        abort_if($otro->id === $yo->id || LpChat::bloqueado($yo->id, $otro->id), 422, 'No puedes escribirle a esta persona.');
        if (! LpChat::puedeContactar($yo, $otro)) {
            return response()->json(['ok' => false, 'mensaje' => LpChat::AVISO_MENOR], 403);
        }
        if ($c = LpChat::directo($yo->id, $otro->id)) {
            return response()->json(['ok' => true, 'chat' => LpChat::abrirDirecto($yo, $otro, DB::table('lpchat_chats')->where('id', $c->id)->value('via') ?: 'conocer')]);
        }
        abort_unless($otro->conocer && in_array($otro->id, LpChat::muestraDelDia($yo), true), 403, 'Esta persona ya no está disponible.');
        $limite = LpChat::limiteConocer($yo);
        if ($limite !== null && LpChat::contactosHoy($yo, 'conocer') >= $limite) {
            return response()->json(['ok' => false, 'pro' => ! LpChat::esPro($yo), 'mensaje' => LpChat::esPro($yo)
                ? 'Hoy ya saludaste a '.$limite.' personas nuevas. Mañana puedes conocer a más 🙌'
                : 'Hoy ya saludaste a '.$limite.' personas nuevas. Pásate a Pro por $'.number_format((int) config('lpchat.pro.precio'), 0, ',', '.').' al mes y saluda hasta '.config('lpchat.pro.limite').' al día ⭐'], 429);
        }

        return response()->json(['ok' => true, 'chat' => LpChat::abrirDirecto($yo, $otro, 'conocer')]);
    }

    /** Nuevo chat con el correo o celular exacto (como WhatsApp: si tienes su número, se conocen). Va a "Mis chats". */
    public function nuevo(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $request->validate(['identificador' => 'required|string|max:150']);
        if (! U::identificador($request->input('identificador'))) {
            return response()->json(['ok' => false, 'mensaje' => 'Escribe un correo o un celular válido.'], 422);
        }
        $otro = U::buscarPorIdentificador($request->input('identificador'));
        if (! $otro || $otro->id === $yo->id || LpChat::bloqueado($yo->id, $otro->id)) {
            return response()->json(['ok' => false, 'mensaje' => 'Esa persona todavía no está en LP Chat. Invítala con tu link.', 'invitar' => true], 404);
        }
        if (! LpChat::puedeContactar($yo, $otro)) {
            return response()->json(['ok' => false, 'mensaje' => LpChat::AVISO_MENOR], 403);
        }

        return response()->json(['ok' => true, 'chat' => LpChat::abrirDirecto($yo, $otro, 'contacto')]);
    }

    public function contactos(Request $request): JsonResponse
    {
        return response()->json(['ok' => true, 'contactos' => LpChat::contactos($this->yo())]);
    }

    public function crearGrupo(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $d = $request->validate(['nombre' => 'required|string|min:1|max:80', 'ids' => 'array', 'ids.*' => 'integer']);
        $permitidos = collect(LpChat::contactos($yo))->pluck('id');
        $ids = collect($d['ids'] ?? [])->map(fn ($i) => (int) $i)->filter(fn ($i) => $permitidos->contains($i))->all();

        return response()->json(['ok' => true, 'chat' => LpChat::crearGrupo($yo, $d['nombre'], $ids)]);
    }

    public function grupo(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo, true);
        abort_unless($c->tipo === 'grupo', 404);
        $miembros = DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('estado', 'activo')->orderByRaw("rol = 'admin' desc")->orderBy('id')->get();
        $us = U::whereIn('id', $miembros->pluck('usuario_id'))->get()->keyBy('id');

        return response()->json(['ok' => true, 'chat' => $this->info($c, $yo), 'miembros' => $miembros->map(fn ($m) => ($us->has($m->usuario_id) ? LpChat::tarjeta($us[$m->usuario_id]) : ['id' => $m->usuario_id, 'nombre' => 'Usuario']) + ['admin' => $m->rol === 'admin', 'yo' => $m->usuario_id === $yo->id])->values()]);
    }

    /** agregar | quitar | admin | editar | link */
    public function grupoAccion(Request $request, int $id, string $accion): JsonResponse
    {
        $yo = $this->yo();
        $c = $this->chat($id, $yo, true);
        abort_unless($c->tipo === 'grupo' && $c->yo->rol === 'admin', 403, 'Solo los administradores del grupo pueden hacer esto.');
        switch ($accion) {
            case 'agregar':
                $u = $request->filled('identificador') ? U::buscarPorIdentificador($request->input('identificador')) : null;
                if (! $u && $request->filled('usuario_id') && collect(LpChat::contactos($yo))->pluck('id')->contains((int) $request->input('usuario_id'))) {
                    $u = U::find((int) $request->input('usuario_id'));
                }
                if (! $u) {
                    return response()->json(['ok' => false, 'mensaje' => 'No encontramos a esa persona en LP Chat. Compártele el link del grupo.'], 404);
                }
                LpChat::agregar($c->id, $u, $yo);
                break;
            case 'quitar':
                $uid = (int) $request->input('usuario_id');
                abort_if($uid === $yo->id, 422);
                $m = LpChat::miembro($c->id, $uid);
                if ($m && $m->estado === 'activo') {
                    DB::table('lpchat_miembros')->where('id', $m->id)->update(['estado' => 'salio', 'updated_at' => now()]);
                    LpChat::sistema($c->id, $yo->nombre.' sacó a '.(U::find($uid)->nombre ?? 'alguien'));
                }
                break;
            case 'admin':
                DB::table('lpchat_miembros')->where('chat_id', $c->id)->where('usuario_id', (int) $request->input('usuario_id'))->where('estado', 'activo')
                    ->update(['rol' => $request->boolean('quitar') ? 'miembro' : 'admin']);
                break;
            case 'editar':
                $d = $request->validate(['nombre' => 'required|string|max:80', 'descripcion' => 'nullable|string|max:200', 'foto' => 'nullable|image|max:8192']);
                $cambios = ['nombre' => trim($d['nombre']), 'descripcion' => $d['descripcion'] ?? null, 'updated_at' => now()];
                if ($request->hasFile('foto')) {
                    $cambios['foto'] = $request->file('foto')->store('lpchat/grupos', 'public');
                }
                DB::table('lpchat_chats')->where('id', $c->id)->update($cambios);
                if ($cambios['nombre'] !== $c->nombre) {
                    LpChat::sistema($c->id, $yo->nombre.' cambió el nombre del grupo a "'.$cambios['nombre'].'"');
                }
                break;
            case 'link':
                DB::table('lpchat_chats')->where('id', $c->id)->update(['codigo' => LpChat::codigoNuevo()]);
                break;
            default:
                abort(404);
        }

        return response()->json(['ok' => true]);
    }

    public function perfil(Request $request): JsonResponse
    {
        $yo = $this->yo();
        abort_if($request->isMethod('post') && $yo->tienda_id, 422, 'El perfil de la empresa se cambia en LocalProo → Datos del negocio.');
        if ($request->isMethod('post')) {
            $d = $request->validate(['nombre' => 'required|string|min:2|max:80', 'bio' => 'nullable|string|max:160', 'ciudad' => 'nullable|string|max:60',
                'conocer' => 'nullable|boolean', 'email' => 'nullable|email|max:150', 'celular' => 'nullable|string|max:20', 'foto' => 'nullable|image|max:8192',
                'password' => 'nullable|string|min:6|max:100']);
            $email = ($d['email'] ?? null) ? mb_strtolower($d['email']) : null;
            $cel = U::normalizarCelular($d['celular'] ?? null);
            if (! $email && ! $cel) {
                return response()->json(['ok' => false, 'mensaje' => 'Deja al menos tu correo o tu celular.'], 422);
            }
            if ($email && U::where('email', $email)->where('id', '!=', $yo->id)->exists()) {
                return response()->json(['ok' => false, 'mensaje' => 'Ese correo ya es de otra cuenta.'], 422);
            }
            if ($cel && U::where('celular', $cel)->where('id', '!=', $yo->id)->exists()) {
                return response()->json(['ok' => false, 'mensaje' => 'Ese celular ya es de otra cuenta.'], 422);
            }
            $yo->fill(['nombre' => trim($d['nombre']), 'bio' => $d['bio'] ?? null, 'ciudad' => $d['ciudad'] ?? null, 'conocer' => $request->boolean('conocer') && ! $yo->menor, 'email' => $email, 'celular' => $cel]);
            if (! empty($d['password'])) {
                $yo->password = \Illuminate\Support\Facades\Hash::make($d['password']);
            }
            if ($request->hasFile('foto')) {
                $yo->foto = $request->file('foto')->store('lpchat/fotos', 'public');
            }
            $yo->save();
            Cache::forget('lpchat_muestra_'.$yo->id.'_'.now('America/Bogota')->toDateString());
        }
        $p = $this->persona();
        $t = $this->empresaDe($p);

        return response()->json(['ok' => true, 'perfil' => LpChat::tarjeta($yo) + ['email' => $yo->email, 'celular' => $yo->celular, 'conocer' => $yo->conocer,
            'link' => $yo->link, 'tiene_clave' => (bool) $yo->password, 'es_empresa' => (bool) $yo->tienda_id,
            'empresa' => $t ? ['nombre' => $t->nombre] : null]]);
    }

    /** Cambiar entre "mi cuenta" y "mi empresa" (como WhatsApp Business). */
    public function cuenta(Request $request): JsonResponse
    {
        $p = $this->persona();
        abort_unless($p, 401);
        if ($request->boolean('empresa')) {
            $t = $this->empresaDe($p);
            abort_unless($t, 403, 'Tu usuario de LocalProo no tiene negocio.');
            session(['lpchat_empresa' => LpChat::cuentaEmpresa($t)->id]);
        } else {
            session()->forget('lpchat_empresa');
        }

        return response()->json(['ok' => true]);
    }

    public function perfilDe(Request $request, int $usuario): JsonResponse
    {
        $yo = $this->yo();
        $u = U::findOrFail($usuario);
        $visible = $u->id === $yo->id || LpChat::directo($yo->id, $u->id) || in_array($u->id, LpChat::muestraDelDia($yo), true) || $u->tienda_id
            || DB::table('lpchat_miembros as a')->join('lpchat_miembros as b', 'a.chat_id', '=', 'b.chat_id')->where('a.usuario_id', $yo->id)->where('a.estado', 'activo')->where('b.usuario_id', $u->id)->exists();
        abort_unless($visible, 404);

        return response()->json(['ok' => true, 'perfil' => LpChat::tarjeta($u) + ['visto' => $u->tienda_id ? '' : LpChat::visto($u->visto_at),
            'tiene_chat' => (bool) LpChat::directo($yo->id, $u->id),
            'bloqueado' => DB::table('lpchat_bloqueos')->where('usuario_id', $yo->id)->where('bloqueado_id', $u->id)->exists()]]);
    }

    /* ---------------- Plan Pro ($5.000 al mes) ---------------- */

    /** Estado del plan y datos para pagar. */
    public function pro(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $pend = DB::table('lpchat_planes')->where('usuario_id', $yo->id)->where('estado', 'pendiente')->latest('id')->first();
        $cfg = config('lpchat.pro');

        return response()->json(['ok' => true, 'pro' => LpChat::esPro($yo), 'sin_limite' => (bool) $yo->sin_limite,
            'hasta' => $yo->pro_hasta && $yo->pro_hasta->isFuture() ? $yo->pro_hasta->timezone('America/Bogota')->format('d/m/Y') : null,
            'pendiente' => (bool) $pend, 'precio' => (int) $cfg['precio'], 'limite' => (int) $cfg['limite'], 'gratis' => LpChat::LIMITE_CONOCER, 'dias' => (int) $cfg['dias'],
            'nequi' => $cfg['nequi'], 'wa' => 'https://wa.me/'.$cfg['whatsapp'].'?text='.rawurlencode('Hola, pagué el Plan Pro de LP Chat ($'.number_format((int) $cfg['precio'], 0, ',', '.').'). Mi cuenta: '.$yo->nombre.' ('.($yo->email ?: $yo->celular ?: 'id '.$yo->id).'). Envío el comprobante.')]);
    }

    /** Sube el comprobante de Nequi; queda pendiente hasta que el súper administrador lo apruebe. */
    public function pagarPro(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $request->validate(['comprobante' => 'required|image|max:10240']);
        if (DB::table('lpchat_planes')->where('usuario_id', $yo->id)->where('estado', 'pendiente')->exists()) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya enviaste un comprobante. Te avisamos apenas lo revisemos.'], 422);
        }
        $ruta = $request->file('comprobante')->store('lpchat/comprobantes', 'local');
        DB::table('lpchat_planes')->insert(['usuario_id' => $yo->id, 'comprobante' => $ruta, 'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true, 'mensaje' => 'Recibimos tu comprobante. Apenas lo revisemos se activa tu Plan Pro ⭐']);
    }

    /** Súper administrador de LocalProo: revisar comprobantes del Plan Pro. */
    private function superadmin(): \App\Models\User
    {
        $w = Auth::guard('web')->user();
        abort_unless($w && ($w->es_superadmin ?? false), 403);

        return $w;
    }

    public function proAdmin(Request $request)
    {
        $this->superadmin();
        $planes = DB::table('lpchat_planes')->orderByRaw("case when estado = 'pendiente' then 0 else 1 end")->orderByDesc('id')->limit(200)->get();
        $us = U::whereIn('id', $planes->pluck('usuario_id'))->get()->keyBy('id');

        return view('lpchat.pro-admin', ['planes' => $planes, 'us' => $us]);
    }

    public function proRevisar(Request $request, int $plan)
    {
        $w = $this->superadmin();
        $p = DB::table('lpchat_planes')->where('id', $plan)->first();
        abort_unless($p, 404);
        $u = U::findOrFail($p->usuario_id);
        if ($request->input('accion') === 'aprobar') {
            $base = $u->pro_hasta && $u->pro_hasta->isFuture() ? $u->pro_hasta : now();
            $u->pro_hasta = $base->copy()->addDays((int) config('lpchat.pro.dias', 30));
            $u->save();
            DB::table('lpchat_planes')->where('id', $p->id)->update(['estado' => 'activo', 'hasta' => $u->pro_hasta, 'revisado_por' => $w->id, 'updated_at' => now()]);
            Cache::forget('lpchat_muestra_'.$u->id.'_'.now('America/Bogota')->toDateString().'_'.LpChat::MUESTRA);
        } else {
            DB::table('lpchat_planes')->where('id', $p->id)->update(['estado' => 'rechazado', 'revisado_por' => $w->id, 'updated_at' => now()]);
        }

        return back()->with('ok', 'Listo.');
    }

    public function proComprobante(Request $request, int $plan)
    {
        $this->superadmin();
        $p = DB::table('lpchat_planes')->where('id', $plan)->first();
        abort_unless($p && $p->comprobante && Storage::disk('local')->exists($p->comprobante), 404);

        return response()->file(Storage::disk('local')->path($p->comprobante));
    }

    /* ---------------- Estados (duran 24 horas) ---------------- */

    private function estadosNuevos(U $yo): int
    {
        return Cache::remember('lpchat_estn_'.$yo->id, 60, fn () => DB::table('lpchat_estados as e')->where('e.created_at', '>=', now()->subDay())->where('e.usuario_id', '!=', $yo->id)
            ->whereNotExists(fn ($q) => $q->from('lpchat_estados_vistos as v')->whereColumn('v.estado_id', 'e.id')->where('v.usuario_id', $yo->id))
            ->distinct()->count('e.usuario_id'));
    }

    /** Estados de todos los de LP Chat en las últimas 24 h: primero los míos, luego mis chats, luego los que no he visto. */
    public function estados(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $bloq = DB::table('lpchat_bloqueos')->where('usuario_id', $yo->id)->pluck('bloqueado_id')->merge(DB::table('lpchat_bloqueos')->where('bloqueado_id', $yo->id)->pluck('usuario_id'));
        $filas = DB::table('lpchat_estados')->where('created_at', '>=', now()->subDay())->whereNotIn('usuario_id', $bloq)
            ->orderByDesc('id')->limit(1500)->get(['id', 'usuario_id', 'tipo', 'created_at']);
        $vistos = DB::table('lpchat_estados_vistos')->where('usuario_id', $yo->id)->whereIn('estado_id', $filas->pluck('id'))->pluck('estado_id')->flip();
        $amigos = collect(LpChat::contactos($yo))->pluck('id')->flip();
        $grupos = $filas->groupBy('usuario_id');
        $us = U::whereIn('id', $grupos->keys())->get()->keyBy('id');
        $lista = $grupos->map(function ($es, $uid) use ($us, $vistos, $yo, $amigos) {
            $u = $us->get($uid);

            return $u ? LpChat::tarjeta($u) + ['yo' => (int) $uid === $yo->id, 'amigo' => $amigos->has($uid), 'cantidad' => $es->count(),
                'sin_ver' => $es->filter(fn ($e) => ! $vistos->has($e->id))->count(), 'hora' => LpChat::hora($es->first()->created_at), 'ultimo' => $es->first()->id] : null;
        })->filter()->sortBy(fn ($x) => [$x['yo'] ? 0 : 1, $x['sin_ver'] ? 0 : 1, $x['amigo'] ? 0 : 1, -$x['ultimo']])->values()->take(150);

        return response()->json(['ok' => true, 'estados' => $lista->values()]);
    }

    public function estadosDe(Request $request, int $usuario): JsonResponse
    {
        $yo = $this->yo();
        abort_if(LpChat::bloqueado($yo->id, $usuario), 404);
        $u = U::findOrFail($usuario);
        $es = DB::table('lpchat_estados')->where('usuario_id', $u->id)->where('created_at', '>=', now()->subDay())->orderBy('id')->get();
        $vistos = DB::table('lpchat_estados_vistos')->where('usuario_id', $yo->id)->whereIn('estado_id', $es->pluck('id'))->pluck('estado_id')->flip();

        return response()->json(['ok' => true, 'usuario' => LpChat::tarjeta($u), 'yo' => $u->id === $yo->id, 'estados' => $es->map(fn ($e) => [
            'id' => $e->id, 'tipo' => $e->tipo, 'texto' => $e->texto, 'color' => $e->color, 'url' => $e->archivo ? route('lpchat.estado.archivo', $e->id) : null,
            'hora' => LpChat::hora($e->created_at), 'visto' => $vistos->has($e->id), 'vistas' => $u->id === $yo->id ? (int) $e->vistas : null,
        ])->values()]);
    }

    public function subirEstado(Request $request): JsonResponse
    {
        $yo = $this->yo();
        $request->validate(['archivo' => 'nullable|file|max:30720', 'texto' => 'nullable|string|max:700', 'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        if (DB::table('lpchat_estados')->where('usuario_id', $yo->id)->where('created_at', '>=', now()->subDay())->count() >= 30) {
            return response()->json(['ok' => false, 'mensaje' => 'Máximo 30 estados en 24 horas.'], 429);
        }
        $f = $request->file('archivo');
        $tipo = 'texto';
        $ruta = null;
        if ($f) {
            $mime = (string) $f->getMimeType();
            $tipo = str_starts_with($mime, 'image/') ? 'foto' : (str_starts_with($mime, 'video/') ? 'video' : null);
            if (! $tipo) {
                return response()->json(['ok' => false, 'mensaje' => 'Sube una foto o un video.'], 422);
            }
            if ($tipo === 'foto' && $f->getSize() > 10 * 1024 * 1024) {
                return response()->json(['ok' => false, 'mensaje' => 'La foto pesa mucho (máximo 10 MB).'], 422);
            }
            $ruta = $f->store('lpchat/estados/'.date('Y/m/d'), 'local');
        } elseif (trim((string) $request->input('texto')) === '') {
            return response()->json(['ok' => false, 'mensaje' => 'Escribe algo o elige una foto o un video.'], 422);
        }
        DB::table('lpchat_estados')->insert(['usuario_id' => $yo->id, 'tipo' => $tipo, 'archivo' => $ruta, 'texto' => trim((string) $request->input('texto')) ?: null,
            'color' => $request->input('color') ?: '#00a884', 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function verEstado(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $e = DB::table('lpchat_estados')->where('id', $id)->first();
        if ($e && $e->usuario_id !== $yo->id && DB::table('lpchat_estados_vistos')->insertOrIgnore(['estado_id' => $e->id, 'usuario_id' => $yo->id, 'created_at' => now()])) {
            DB::table('lpchat_estados')->where('id', $e->id)->increment('vistas');
            Cache::forget('lpchat_estn_'.$yo->id);
        }

        return response()->json(['ok' => true]);
    }

    public function borrarEstado(Request $request, int $id): JsonResponse
    {
        $yo = $this->yo();
        $e = DB::table('lpchat_estados')->where('id', $id)->where('usuario_id', $yo->id)->first();
        abort_unless($e, 404);
        if ($e->archivo) {
            Storage::disk('local')->delete($e->archivo);
        }
        DB::table('lpchat_estados_vistos')->where('estado_id', $e->id)->delete();
        DB::table('lpchat_estados')->where('id', $e->id)->delete();

        return response()->json(['ok' => true]);
    }

    public function archivoEstado(Request $request, int $id)
    {
        $yo = $this->yo();
        $e = DB::table('lpchat_estados')->where('id', $id)->where('created_at', '>=', now()->subDay())->first();
        abort_unless($e && $e->archivo && ! LpChat::bloqueado($yo->id, $e->usuario_id) && Storage::disk('local')->exists($e->archivo), 404);

        return response()->file(Storage::disk('local')->path($e->archivo), ['Cache-Control' => 'private, max-age=86400']);
    }
}
