<?php

namespace App\Support;

use App\Models\RedConversacion;
use App\Models\RedCuenta;
use App\Models\RedMensaje;
use App\Models\RedPublicacion;
use Illuminate\Support\Carbon;
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

    public static function v(): string
    {
        return config('services.meta.version') ?: 'v24.0';
    }

    private static function api(string $ruta): string
    {
        return 'https://graph.facebook.com/' . self::v() . '/' . ltrim($ruta, '/');
    }

    // ---------- Conexión (inicio de sesión con Facebook) ----------
    public static function urlLogin(string $redirect, string $state): string
    {
        $p = ['client_id' => config('services.meta.app_id'), 'redirect_uri' => $redirect, 'state' => $state, 'response_type' => 'code'];
        if ($cfg = config('services.meta.config_id')) $p['config_id'] = $cfg;
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

    private const CAMPOS_MSJ = 'id,message,from,created_time,attachments{mime_type,image_data,file_url}';

    public static function sincronizar(RedCuenta $c, int $limite = 25): int
    {
        TiendaActual::set($c->tienda_id);
        $n = 0;
        foreach (array_filter(['facebook' => 'messenger', 'instagram' => $c->ig_id ? 'instagram' : null]) as $plat => $param) {
            $propio = $plat === 'instagram' ? $c->ig_id : $c->page_id;
            try {
                $convs = self::listaConversaciones($c, $param, $plat === 'instagram' ? min($limite, 15) : $limite);
            } catch (\Throwable $e) {
                Log::error('Meta sincronizar ' . $plat . ': ' . $e->getMessage());
                self::$avisos[] = ($plat === 'instagram' ? 'Instagram' : 'Facebook') . ': ' . $e->getMessage();
                continue;
            }
            foreach ($convs as $conv) {
                $otro = collect($conv['participants']['data'] ?? [])->first(fn ($p) => (string) ($p['id'] ?? '') !== (string) $propio && ($p['username'] ?? null) !== $c->ig_usuario);
                if (!$otro) continue;
                $cv = self::conversacion($c, $plat, (string) $otro['id'], $otro['name'] ?? null, $otro['username'] ?? null);
                // Los mensajes de cada chat se piden aparte (pedirlos todos juntos hace que Instagram se demore y falle)
                $mensajes = $conv['messages']['data'] ?? null;
                if ($mensajes === null) {
                    try {
                        $mensajes = self::get((string) $conv['id'], ['fields' => 'messages.limit(15){' . self::CAMPOS_MSJ . '}', 'access_token' => $c->page_token])['messages']['data'] ?? [];
                    } catch (\Throwable $e) {
                        Log::error('Meta mensajes ' . $plat . ' ' . $conv['id'] . ': ' . $e->getMessage());
                        continue;
                    }
                }
                foreach (array_reverse($mensajes) as $m) {
                    $delCliente = (string) ($m['from']['id'] ?? '') !== (string) $propio && ($m['from']['username'] ?? null) !== $c->ig_usuario;
                    $adj = $m['attachments']['data'][0] ?? null;
                    $n += self::guardarMensaje($cv, (string) $m['id'], $delCliente, $m['message'] ?? null,
                        $adj ? ($adj['image_data']['url'] ?? $adj['file_url'] ?? null) : null, $adj ? 'imagen' : null,
                        Carbon::parse($m['created_time'] ?? now()), null, false) ? 1 : 0;
                }
            }
        }
        $c->update(['sincronizado_en' => now(), 'estado' => 'activa', 'error' => null]);
        return $n;
    }

    // Lista de conversaciones; si Meta se demora, vuelve a intentar pidiendo menos
    private static function listaConversaciones(RedCuenta $c, string $plataforma, int $limite): array
    {
        $ultimo = null;
        foreach (array_unique([$limite, 8, 3]) as $lim) {
            try {
                $r = self::get($c->page_id . '/conversations', [
                    'platform' => $plataforma, 'limit' => $lim, 'access_token' => $c->page_token,
                    'fields' => $plataforma === 'instagram' ? 'id,updated_time,participants' : 'id,updated_time,participants,messages.limit(15){' . self::CAMPOS_MSJ . '}',
                ]);
                return $r['data'] ?? [];
            } catch (\Throwable $e) {
                $ultimo = $e;
                if (!str_contains(strtolower($e->getMessage()), 'timeout') && !str_contains($e->getMessage(), 'tard')) throw $e;
            }
        }
        throw $ultimo;
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
    private static function get(string $ruta, array $q): array
    {
        $r = Http::timeout(20)->acceptJson()->get(self::api($ruta), $q);
        return self::respuesta($r);
    }

    private static function post(string $ruta, array $d, bool $json = false): array
    {
        $h = Http::timeout(30)->acceptJson();
        $r = $json ? $h->asJson()->post(self::api($ruta), $d) : $h->asForm()->post(self::api($ruta), $d);
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
            throw new RuntimeException($msg);
        }
        return $j;
    }
}
