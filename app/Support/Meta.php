<?php

namespace App\Support;

use App\Models\RedConversacion;
use App\Models\RedCuenta;
use App\Models\RedMensaje;
use App\Models\RedPublicacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Facebook e Instagram (Meta Graph API) para "Mis redes".
 * .env: META_APP_ID, META_APP_SECRET, META_VERIFY_TOKEN, META_GRAPH_VERSION (opcional), META_CONFIG_ID (opcional, Facebook Login for Business).
 * Instagram se maneja a través de la página de Facebook a la que está vinculada la cuenta profesional.
 */
class Meta
{
    public const PERMISOS = [
        'pages_show_list', 'pages_manage_metadata', 'pages_read_engagement', 'pages_messaging', 'pages_manage_posts',
        'instagram_basic', 'instagram_manage_messages', 'instagram_content_publish', 'business_management',
    ];

    public static function activo(): bool
    {
        return filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret'));
    }

    // La app de Meta ya pasó la revisión y está "en vivo": cualquier negocio puede conectar su página.
    // Mientras está en desarrollo, Facebook solo deja entrar a las personas agregadas como evaluadoras en la app,
    // a los demás les sale "La app no está disponible… podrás iniciar sesión cuando esta se reactive".
    public static function enVivo(): bool
    {
        return (string) \App\Models\Ajuste::valor('meta_en_vivo') === '1';
    }

    // Tiendas con acceso de prueba mientras la app no está en vivo (su dueño ya fue agregado como evaluador en Meta)
    public static function tiendaConAcceso(?\App\Models\Tienda $t): bool
    {
        if (self::enVivo()) return true;
        if (!$t) return false;
        $codigos = array_filter(array_map(fn ($c) => strtoupper(trim($c)), explode(',', (string) \App\Models\Ajuste::valor('meta_tiendas_prueba'))));
        return in_array(strtoupper((string) $t->codigo), $codigos, true);
    }

    // Versión de la API: la del panel (Admin → Planes y cobros) o la del .env
    public static function v(): string
    {
        $v = trim((string) \App\Models\Ajuste::valor('meta_graph_version'));
        return preg_match('/^v\d+\.\d+$/', $v) ? $v : (config('services.meta.version') ?: 'v24.0');
    }

    // Configuración de "Inicio de sesión con Facebook para empresas": la del panel o la del .env
    public static function configId(): ?string
    {
        $c = preg_replace('/\D/', '', (string) \App\Models\Ajuste::valor('meta_config_id'));
        return $c !== '' ? $c : (config('services.meta.config_id') ?: null);
    }

    /**
     * Revisión de la conexión con Meta para el administrador (sin consola): qué está bien y qué falta.
     * Cada fila: [ok (true|false|null=aviso), título, detalle].
     */
    public static function diagnostico(): array
    {
        $f = [];
        $id = (string) config('services.meta.app_id');
        $secret = (string) config('services.meta.app_secret');
        $f[] = [filled($id), 'ID de la app (META_APP_ID)', $id ? 'App ' . $id : 'Falta en el .env del servidor.'];
        $f[] = [filled($secret), 'Clave secreta (META_APP_SECRET)', $secret ? 'Configurada' : 'Falta en el .env del servidor.'];
        if ($id && $secret) {
            try {
                $r = Http::timeout(15)->get(self::api($id), ['fields' => 'name,link', 'access_token' => $id . '|' . $secret]);
                $f[] = $r->successful()
                    ? [true, 'Facebook reconoce la app', ($r->json('name') ?: 'App') . ' · las llaves son correctas.']
                    : [false, 'Facebook rechazó las llaves', (string) ($r->json('error.message') ?: 'HTTP ' . $r->status()) . ' → revise META_APP_ID y META_APP_SECRET.'];
            } catch (\Throwable $e) {
                $f[] = [false, 'No se pudo hablar con Facebook', $e->getMessage()];
            }
        }
        $cfg = self::configId();
        $f[] = [filled($cfg), 'Configuración de inicio de sesión (config_id)', $cfg
            ? $cfg . ' · sin esto Facebook muestra "permisos no válidos" en apps de tipo Empresa.'
            : 'Falta: péguela abajo. Está en developers.facebook.com → su app → Inicio de sesión con Facebook para empresas → Configuraciones.'];
        $f[] = [true, 'Versión de la API', self::v()];
        $f[] = [filled(config('services.meta.verify_token')), 'Token del webhook (META_VERIFY_TOKEN)', config('services.meta.verify_token') ? 'Configurado' : 'Falta: sin esto no llegan los mensajes en tiempo real.'];
        $f[] = [null, 'URI de retorno que debe estar en Meta', route('redes.callback') . ' (Inicio de sesión con Facebook para empresas → Configuración → URI de redireccionamiento de OAuth válidos).'];
        if (self::enVivo()) {
            $f[] = [true, 'Modo de la app', 'En vivo: cualquier negocio puede conectar.'];
        } else {
            $prueba = array_filter(array_map('trim', explode(',', (string) \App\Models\Ajuste::valor('meta_tiendas_prueba'))));
            $f[] = [null, 'Modo de la app', 'En desarrollo: solo conectan los negocios de prueba (' . ($prueba ? implode(', ', $prueba) : 'ninguno') . ') y su dueño debe estar agregado como Evaluador en la app de Meta. Para todos: verificar el negocio y pedir la revisión de la app.'];
        }
        $hook = \App\Models\Ajuste::valor('meta_ultimo_webhook');
        $f[] = [$hook ? (str_contains($hook, 'INVÁLIDA') ? false : true) : null, 'Último aviso de Meta recibido (webhook)', $hook ?: 'Nunca ha llegado un aviso de Meta. Revise en Meta → Webhooks que la URL sea ' . url('/webhooks/meta') . ' y que esté suscrita a "messages" (Page e Instagram).'];
        // Prueba de lectura con cada cuenta conectada: el chat más reciente que entrega Meta vs. el último guardado
        foreach (RedCuenta::withoutGlobalScopes()->where('estado', '<>', 'desconectada')->get() as $c) {
            foreach (array_filter(['facebook' => 'messenger', 'instagram' => $c->ig_id ? 'instagram' : null]) as $plat => $param) {
                $nombre = $c->page_nombre . ' · ' . ($plat === 'instagram' ? 'Instagram' : 'Facebook');
                try {
                    $r = self::get($c->page_id . '/conversations', ['platform' => $param, 'limit' => 1, 'fields' => 'updated_time,messages.limit(1){message,created_time}', 'access_token' => $c->page_token], 20);
                    $cv = $r['data'][0] ?? null;
                    $enMeta = $cv ? Carbon::parse($cv['updated_time'])->setTimezone(config('app.timezone')) : null;
                    $guardado = RedConversacion::withoutGlobalScopes()->where('cuenta_id', $c->id)->where('plataforma', $plat)->max('ultimo_en');
                    $guardado = $guardado ? Carbon::parse($guardado) : null;
                    $atrasado = $enMeta && (!$guardado || $enMeta->gt($guardado->copy()->addMinutes(5)));
                    $f[] = [$atrasado ? false : true, $nombre, 'Último chat en Meta: ' . ($enMeta ? $enMeta->format('d/m/Y H:i') : 'ninguno')
                        . ' · último guardado en LocalPro: ' . ($guardado ? $guardado->format('d/m/Y H:i') : 'ninguno')
                        . ($atrasado ? ' → hay mensajes en Meta que no se han guardado.' : '')];
                } catch (\Throwable $e) {
                    $f[] = [false, $nombre, 'Meta no deja leer los chats: ' . self::sinLlaves($e->getMessage())];
                }
            }
        }
        $ultimo = \App\Models\Ajuste::valor('meta_ultimo_error');
        if ($ultimo) $f[] = [false, 'Último error al conectar', $ultimo];
        return $f;
    }

    // Guarda el último error de conexión para verlo en el panel del administrador
    public static function anotarError(string $donde, string $msg): void
    {
        try {
            \App\Models\Ajuste::guardar('meta_ultimo_error', now()->format('d/m/Y H:i') . ' · ' . $donde . ' · ' . mb_substr($msg, 0, 400));
        } catch (\Throwable $e) {
        }
    }

    private static function api(string $ruta): string
    {
        return 'https://graph.facebook.com/' . self::v() . '/' . ltrim($ruta, '/');
    }

    // ---------- Conexión (inicio de sesión con Facebook) ----------
    public static function urlLogin(string $redirect, string $state): string
    {
        $p = ['client_id' => config('services.meta.app_id'), 'redirect_uri' => $redirect, 'state' => $state, 'response_type' => 'code'];
        if ($cfg = self::configId()) $p['config_id'] = $cfg;
        else $p['scope'] = implode(',', self::PERMISOS);
        return 'https://www.facebook.com/' . self::v() . '/dialog/oauth?' . http_build_query($p);
    }

    public static function tokenDeUsuario(string $code, string $redirect): string
    {
        $r = self::get('oauth/access_token', ['client_id' => config('services.meta.app_id'), 'client_secret' => config('services.meta.app_secret'), 'redirect_uri' => $redirect, 'code' => $code]);
        $corto = $r['access_token'] ?? throw new RuntimeException('Facebook no entregó el acceso.');
        // Token largo: así los tokens de las páginas no vencen
        $l = self::get('oauth/access_token', ['grant_type' => 'fb_exchange_token', 'client_id' => config('services.meta.app_id'), 'client_secret' => config('services.meta.app_secret'), 'fb_exchange_token' => $corto]);
        return $l['access_token'] ?? $corto;
    }

    /** Páginas que administra la persona, con su Instagram vinculado. */
    public static function paginas(string $tokenUsuario): array
    {
        $r = self::get('me/accounts', ['access_token' => $tokenUsuario, 'limit' => 100, 'fields' => 'id,name,access_token,picture{url},tasks,instagram_business_account{id,username,profile_picture_url}']);
        return collect($r['data'] ?? [])->map(fn ($p) => [
            'id' => (string) $p['id'],
            'nombre' => $p['name'] ?? 'Página',
            'token' => $p['access_token'] ?? '',
            'foto' => $p['picture']['data']['url'] ?? null,
            'ig_id' => $p['instagram_business_account']['id'] ?? null,
            'ig_usuario' => $p['instagram_business_account']['username'] ?? null,
            'ig_foto' => $p['instagram_business_account']['profile_picture_url'] ?? null,
        ])->filter(fn ($p) => $p['token'] !== '')->values()->all();
    }

    /** Suscribe la página a la app para que lleguen los mensajes (Messenger e Instagram). */
    public static function suscribir(RedCuenta $c): void
    {
        self::post($c->page_id . '/subscribed_apps', ['subscribed_fields' => 'messages,messaging_postbacks,message_echoes', 'access_token' => $c->page_token]);
    }

    public static function desuscribir(RedCuenta $c): void
    {
        try {
            Http::timeout(15)->delete(self::api($c->page_id . '/subscribed_apps'), ['access_token' => $c->page_token]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ---------- Mensajes ----------
    /** Trae las conversaciones recientes (al conectar o con el botón "Actualizar"). */
    /** Avisos de la última sincronización (para mostrarlos en pantalla). */
    public static array $avisos = [];

    // Hora límite (microtime) para sincronizar; pasada esa hora se detiene y sigue en la próxima actualización
    public static ?float $hasta = null;

    private static function sinTiempo(): bool
    {
        return self::$hasta !== null && microtime(true) > self::$hasta;
    }

    private const CAMPOS_MSJ = 'id,message,from,created_time,attachments{mime_type,image_data,file_url}';

    public static function sincronizar(RedCuenta $c, int $limite = 25, bool $comoNuevos = false): int
    {
        TiendaActual::set($c->tienda_id);
        $n = 0;
        foreach (array_filter(['facebook' => 'messenger', 'instagram' => $c->ig_id ? 'instagram' : null]) as $plat => $param) {
            $propio = $plat === 'instagram' ? $c->ig_id : $c->page_id;
            // Si Meta viene fallando con esta cuenta, la revision automatica (cada 5 min) espera un rato
            // en vez de insistir y llenar el log; los mensajes nuevos igual llegan por el webhook.
            $clavePausa = 'meta_pausa:' . $c->id . ':' . $plat;
            if ($comoNuevos && Cache::has($clavePausa)) continue;
            try {
                $convs = self::listaConversaciones($c, $param, $plat === 'instagram' ? min($limite, 15) : $limite);
                Cache::forget($clavePausa);
                Cache::forget($clavePausa . ':fallos');
            } catch (\Throwable $e) {
                $fallos = (int) Cache::get($clavePausa . ':fallos', 0) + 1;
                Cache::put($clavePausa . ':fallos', $fallos, now()->addDay());
                // Pausa creciente: 15, 30, 60 min... hasta 3 horas
                Cache::put($clavePausa, true, now()->addMinutes(min(180, 15 * 2 ** min(4, $fallos - 1))));
                // En el log solo el primer fallo y luego uno de cada 6 (no cada 5 minutos)
                if ($fallos === 1 || $fallos % 6 === 0) {
                    Log::warning('Meta sincronizar ' . $plat . ' (cuenta ' . $c->id . ', fallo #' . $fallos . '): ' . $e->getMessage());
                }
                self::$avisos[] = ($plat === 'instagram' ? 'Instagram' : 'Facebook') . ': ' . $e->getMessage();
                continue;
            }
            foreach ($convs as $conv) {
                if (self::sinTiempo()) break 2;
                $otro = collect($conv['participants']['data'] ?? [])->first(fn ($p) => (string) ($p['id'] ?? '') !== (string) $propio && ($p['username'] ?? null) !== $c->ig_usuario);
                if (!$otro) continue;
                $cv = self::conversacion($c, $plat, (string) $otro['id'], $otro['name'] ?? null, $otro['username'] ?? null);
                // Los mensajes de cada chat se piden aparte (pedirlos todos juntos hace que Instagram se demore y falle)
                $mensajes = $conv['messages']['data'] ?? null;
                if ($mensajes === null) {
                    try {
                        $mensajes = self::get((string) $conv['id'], ['fields' => 'messages.limit(15){' . self::CAMPOS_MSJ . '}', 'access_token' => $c->page_token], 30)['messages']['data'] ?? [];
                    } catch (\Throwable $e) {
                        try {
                            $mensajes = self::get((string) $conv['id'], ['fields' => 'messages.limit(5){id,created_time,from,message}', 'access_token' => $c->page_token], 30)['messages']['data'] ?? [];
                        } catch (\Throwable $e2) {
                            Log::error('Meta mensajes ' . $plat . ' ' . $conv['id'] . ': ' . $e2->getMessage());
                            continue;
                        }
                    }
                }
                foreach (array_reverse($mensajes) as $m) {
                    $delCliente = (string) ($m['from']['id'] ?? '') !== (string) $propio && ($m['from']['username'] ?? null) !== $c->ig_usuario;
                    $adj = $m['attachments']['data'][0] ?? null;
                    $n += self::guardarMensaje($cv, (string) $m['id'], $delCliente, $m['message'] ?? null,
                        $adj ? ($adj['image_data']['url'] ?? $adj['file_url'] ?? null) : null, $adj ? 'imagen' : null,
                        Carbon::parse($m['created_time'] ?? now()), null, $comoNuevos && $delCliente) ? 1 : 0;
                }
            }
        }
        // Si algo falló (ej. Instagram tardó), queda anotado para mostrarlo en la bandeja; la cuenta sigue activa
        $c->update(['sincronizado_en' => now(), 'estado' => 'activa',
            'error' => self::$avisos ? mb_substr(self::sinLlaves(implode(' · ', array_unique(self::$avisos))), 0, 250) : null]);
        return $n;
    }

    // Lista de conversaciones; si Meta se demora o pide "menos datos", vuelve a intentar pidiendo menos
    private static function listaConversaciones(RedCuenta $c, string $plataforma, int $limite): array
    {
        $ig = $plataforma === 'instagram';
        $intentos = $ig
            ? [[$limite, 'id,updated_time,participants'], [3, 'id,updated_time'], [2, 'id']]
            : [[$limite, 'id,updated_time,participants,messages.limit(15){' . self::CAMPOS_MSJ . '}'], [5, 'id,updated_time,participants']];
        $ultimo = null;
        foreach ($intentos as [$lim, $campos]) {
            if (self::sinTiempo()) break;
            try {
                $r = self::get($c->page_id . '/conversations', [
                    'platform' => $plataforma, 'limit' => $lim, 'access_token' => $c->page_token, 'fields' => $campos,
                ], 20);
                $convs = $r['data'] ?? [];
                // Si no vinieron los participantes, se piden chat por chat
                foreach ($convs as &$cv) {
                    if (!isset($cv['participants'])) {
                        try {
                            $cv['participants'] = self::get((string) $cv['id'], ['fields' => 'participants', 'access_token' => $c->page_token], 30)['participants'] ?? [];
                        } catch (\Throwable $e) {
                            $cv['participants'] = [];
                        }
                    }
                }
                return $convs;
            } catch (\Throwable $e) {
                $ultimo = $e;
                $m = strtolower($e->getMessage());
                if (!str_contains($m, 'timeout') && !str_contains($m, 'reduce the amount') && !str_contains($m, 'unknown error')
                    && !str_contains($m, 'fatal') && !str_contains($m, 'no hubo conexión')) throw $e;
            }
        }
        throw new RuntimeException('Meta está tardando demasiado en entregar los chats antiguos. Los mensajes nuevos sí le llegan solos; intente Actualizar más tarde.');
    }

    public static function conversacion(RedCuenta $c, string $plat, string $contacto, ?string $nombre = null, ?string $usuario = null): RedConversacion
    {
        $cv = RedConversacion::firstOrCreate(
            ['cuenta_id' => $c->id, 'plataforma' => $plat, 'contacto_id' => $contacto],
            ['tienda_id' => $c->tienda_id, 'contacto_nombre' => $nombre, 'contacto_usuario' => $usuario, 'etiqueta' => 'nuevo']
        );
        if ((!$cv->contacto_nombre && !$cv->contacto_usuario) || $cv->wasRecentlyCreated) {
            $perfil = self::perfil($c, $plat, $contacto);
            $cv->fill(array_filter([
                'contacto_nombre' => $cv->contacto_nombre ?: $perfil['nombre'],
                'contacto_usuario' => $cv->contacto_usuario ?: $perfil['usuario'],
                'contacto_foto' => $perfil['foto'],
            ]))->save();
        }
        return $cv;
    }

    private static function perfil(RedCuenta $c, string $plat, string $id): array
    {
        try {
            $r = self::get($id, ['access_token' => $c->page_token, 'fields' => $plat === 'instagram' ? 'name,username,profile_pic' : 'first_name,last_name,profile_pic']);
            return [
                'nombre' => $plat === 'instagram' ? ($r['name'] ?? null) : (trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: null),
                'usuario' => $r['username'] ?? null,
                'foto' => $r['profile_pic'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['nombre' => null, 'usuario' => null, 'foto' => null];
        }
    }

    public static function guardarMensaje(RedConversacion $cv, string $mid, bool $delCliente, ?string $texto, ?string $adjunto, ?string $tipo, Carbon $cuando, ?int $user = null, bool $contarNoLeido = true): bool
    {
        if ($mid !== '' && RedMensaje::where('conversacion_id', $cv->id)->where('mid', $mid)->exists()) return false;
        RedMensaje::create([
            'tienda_id' => $cv->tienda_id, 'conversacion_id' => $cv->id, 'mid' => $mid ?: null, 'del_cliente' => $delCliente,
            'texto' => $texto, 'adjunto_url' => $adjunto, 'adjunto_tipo' => $tipo, 'user_id' => $user, 'enviado_en' => $cuando,
        ]);
        if (!$cv->ultimo_en || $cuando->gte($cv->ultimo_en)) {
            $cv->ultimo_en = $cuando;
            $cv->ultimo_texto = mb_substr($texto ?: ($adjunto ? '📷 Imagen' : ''), 0, 250);
        }
        if ($delCliente) {
            if (!$cv->ultimo_del_cliente_en || $cuando->gt($cv->ultimo_del_cliente_en)) $cv->ultimo_del_cliente_en = $cuando;
            if ($contarNoLeido) $cv->no_leidos++;
        }
        $cv->save();
        return true;
    }

    /** Envía un mensaje de texto (o una imagen) al cliente. */
    public static function enviar(RedConversacion $cv, ?string $texto, ?string $imagenUrl = null): string
    {
        $c = $cv->cuenta;
        $msg = $imagenUrl ? ['attachment' => ['type' => 'image', 'payload' => ['url' => $imagenUrl, 'is_reusable' => true]]] : ['text' => $texto];
        $p = ['recipient' => ['id' => $cv->contacto_id], 'message' => $msg];
        if ($cv->plataforma === 'facebook') $p['messaging_type'] = 'RESPONSE';
        $r = self::post($c->page_id . '/messages', $p + ['access_token' => $c->page_token], true);
        return (string) ($r['message_id'] ?? '');
    }

    // ---------- Webhook (mensajes en tiempo real) ----------
    public static function firmaValida(string $cuerpo, ?string $firma): bool
    {
        $secreto = (string) config('services.meta.app_secret');
        if ($secreto === '' || !$firma || !str_starts_with($firma, 'sha256=')) return false;
        return hash_equals('sha256=' . hash_hmac('sha256', $cuerpo, $secreto), $firma);
    }

    public static function procesarEvento(array $ev): void
    {
        $esIg = ($ev['object'] ?? '') === 'instagram';
        foreach ($ev['entry'] ?? [] as $entry) {
            $propio = (string) ($entry['id'] ?? '');
            $cuentas = RedCuenta::withoutGlobalScopes()->where($esIg ? 'ig_id' : 'page_id', $propio)->where('estado', '<>', 'desconectada')->get();
            foreach ($cuentas as $c) {
                TiendaActual::set($c->tienda_id);
                foreach ($entry['messaging'] ?? [] as $m) {
                    if (!isset($m['message'])) continue;
                    $eco = (bool) ($m['message']['is_echo'] ?? false);
                    $contacto = (string) ($eco ? ($m['recipient']['id'] ?? '') : ($m['sender']['id'] ?? ''));
                    if ($contacto === '' || $contacto === $propio) continue;
                    $adj = $m['message']['attachments'][0] ?? null;
                    $cv = self::conversacion($c, $esIg ? 'instagram' : 'facebook', $contacto);
                    self::guardarMensaje($cv, (string) ($m['message']['mid'] ?? ''), !$eco, $m['message']['text'] ?? null,
                        $adj['payload']['url'] ?? null, $adj['type'] ?? null,
                        Carbon::createFromTimestampMs((int) ($m['timestamp'] ?? now()->getTimestampMs())));
                }
            }
        }
        TiendaActual::set(null);
    }

    // ---------- Publicaciones ----------
    public static function publicar(RedPublicacion $p): RedPublicacion
    {
        $c = $p->cuenta;
        $errores = [];
        $img = $p->imagen_url;
        if ($p->en_facebook && !$p->fb_post_id) {
            try {
                if ($img) {
                    $r = self::post($c->page_id . '/photos', ['url' => $img, 'message' => (string) $p->texto, 'access_token' => $c->page_token]);
                    $id = (string) ($r['post_id'] ?? $r['id'] ?? '');
                } else {
                    $r = self::post($c->page_id . '/feed', ['message' => (string) $p->texto, 'access_token' => $c->page_token]);
                    $id = (string) ($r['id'] ?? '');
                }
                $p->fb_post_id = $id;
                $p->fb_url = $id ? 'https://www.facebook.com/' . $id : null;
            } catch (\Throwable $e) {
                $errores[] = 'Facebook: ' . $e->getMessage();
            }
        }
        if ($p->en_instagram && !$p->ig_media_id) {
            try {
                if (!$c->ig_id) throw new RuntimeException('la página no tiene Instagram vinculado.');
                if (!$img) throw new RuntimeException('Instagram necesita una foto.');
                $cont = self::post($c->ig_id . '/media', ['image_url' => $img, 'caption' => (string) $p->texto, 'access_token' => $c->page_token]);
                $creacion = $cont['id'] ?? throw new RuntimeException('no se pudo subir la foto.');
                // Espera a que Instagram termine de procesar la foto
                for ($i = 0; $i < 10; $i++) {
                    $st = self::get($creacion, ['fields' => 'status_code', 'access_token' => $c->page_token]);
                    if (($st['status_code'] ?? 'FINISHED') === 'FINISHED') break;
                    if (($st['status_code'] ?? '') === 'ERROR') throw new RuntimeException('Instagram no aceptó la foto (use JPG, mínimo 320 px).');
                    sleep(2);
                }
                $pub = self::post($c->ig_id . '/media_publish', ['creation_id' => $creacion, 'access_token' => $c->page_token]);
                $p->ig_media_id = (string) ($pub['id'] ?? '');
                try {
                    $p->ig_url = self::get($p->ig_media_id, ['fields' => 'permalink', 'access_token' => $c->page_token])['permalink'] ?? null;
                } catch (\Throwable $e) {
                }
            } catch (\Throwable $e) {
                $errores[] = 'Instagram: ' . $e->getMessage();
            }
        }
        $ok = ($p->en_facebook ? (bool) $p->fb_post_id : true) && ($p->en_instagram ? (bool) $p->ig_media_id : true);
        $alguno = $p->fb_post_id || $p->ig_media_id;
        $p->estado = $ok ? 'publicada' : ($alguno ? 'parcial' : 'error');
        $p->error = $errores ? mb_substr(implode(' · ', $errores), 0, 500) : null;
        if ($alguno) $p->publicada_en = now();
        $p->save();
        return $p;
    }

    // ---------- HTTP ----------
    private static function get(string $ruta, array $q, int $espera = 20): array
    {
        try {
            $r = Http::timeout($espera)->acceptJson()->get(self::api($ruta), $q);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Nunca se muestra el mensaje original: trae la dirección con la llave de acceso
            throw new RuntimeException(str_contains(strtolower($e->getMessage()), 'timed out') ? 'Timeout: Meta no respondió a tiempo' : 'No hubo conexión con Meta');
        }
        return self::respuesta($r);
    }

    private static function post(string $ruta, array $d, bool $json = false): array
    {
        $h = Http::timeout(30)->acceptJson();
        try {
            $r = $json ? $h->asJson()->post(self::api($ruta), $d) : $h->asForm()->post(self::api($ruta), $d);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException(str_contains(strtolower($e->getMessage()), 'timed out') ? 'Timeout: Meta no respondió a tiempo' : 'No hubo conexión con Meta');
        }
        return self::respuesta($r);
    }

    private static function respuesta($r): array
    {
        $j = $r->json() ?: [];
        if ($r->failed() || isset($j['error'])) {
            $e = $j['error'] ?? [];
            $msg = $e['message'] ?? $e['error_user_msg'] ?? ('error ' . $r->status());
            if (in_array((int) ($e['code'] ?? 0), [190, 102], true)) $msg = 'La conexión venció o se quitó el permiso. Vuelva a conectar la cuenta.';
            if ((int) ($e['code'] ?? 0) === -2 || (int) ($e['error_subcode'] ?? 0) === 2534084) $msg = 'Timeout: Meta se demoró en responder';
            if (in_array((int) ($e['error_subcode'] ?? 0), [2018278, 2534022], true)) $msg = 'Ya pasaron más de 24 horas desde el último mensaje del cliente: Meta no deja responder por aquí.';
            throw new RuntimeException(self::sinLlaves($msg));
        }
        return $j;
    }

    public static function sinLlaves(string $txt): string
    {
        return preg_replace('/(access_token=|EAA)[A-Za-z0-9_\-]+/', '$1***', $txt);
    }
}
