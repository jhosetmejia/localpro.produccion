<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\RedCuenta;
use App\Models\RedPublicacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * YouTube para "Mis redes": cada negocio conecta su canal con su cuenta de Google y LocalPro sube los videos
 * (Short si es vertical y de máximo 3 minutos; si no, video normal).
 * Credenciales del proyecto de Google Cloud: Admin → Planes y cobros, o YOUTUBE_CLIENT_ID / YOUTUBE_CLIENT_SECRET en el .env.
 * La subida es reanudable: si se corta, la siguiente vuelta del programador sigue desde donde quedó.
 */
class YouTube
{
    public const PERMISOS = ['https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube.readonly'];
    public const MAX_CANALES = 2;
    private const PARTE = 8 * 1024 * 1024; // múltiplo de 256 KB, como pide Google

    public static function clientId(): string
    {
        return trim((string) (Ajuste::valor('youtube_client_id') ?: config('services.youtube.client_id')));
    }

    public static function clientSecret(): string
    {
        $g = (string) Ajuste::valor('youtube_client_secret');
        if ($g !== '') {
            try { return Crypt::decryptString($g); } catch (Throwable $e) { return ''; }
        }
        return trim((string) config('services.youtube.client_secret'));
    }

    public static function activo(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function urlRetorno(): string
    {
        return route('redes.youtube.retorno');
    }

    public static function urlLogin(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => self::clientId(), 'redirect_uri' => self::urlRetorno(), 'response_type' => 'code',
            'scope' => implode(' ', self::PERMISOS), 'access_type' => 'offline', 'prompt' => 'consent select_account',
            'include_granted_scopes' => 'true', 'state' => $state,
        ]);
    }

    // Cambia el código que devuelve Google por las llaves (con refresh_token para que no venza)
    public static function tokenDesdeCodigo(string $code): array
    {
        $j = self::pedirToken(['code' => $code, 'grant_type' => 'authorization_code', 'redirect_uri' => self::urlRetorno()]);
        if (empty($j['refresh_token'])) throw new RuntimeException('Google no entregó el acceso permanente. Vuelva a conectar y acepte todos los permisos.');
        return ['access' => $j['access_token'], 'refresh' => $j['refresh_token'], 'vence' => time() + (int) ($j['expires_in'] ?? 3600) - 120];
    }

    private static function pedirToken(array $d): array
    {
        try {
            $r = Http::timeout(20)->asForm()->acceptJson()->post('https://oauth2.googleapis.com/token', $d + ['client_id' => self::clientId(), 'client_secret' => self::clientSecret()]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('No hubo conexión con Google.');
        }
        $j = $r->json() ?: [];
        if ($r->failed() || empty($j['access_token'])) {
            $e = (string) ($j['error'] ?? '');
            if ($e === 'invalid_grant') throw new RuntimeException('La conexión con YouTube venció o se quitó el permiso. Vuelva a conectar el canal.');
            if ($e === 'invalid_client' || $e === 'unauthorized_client') throw new RuntimeException('Las credenciales de Google de LocalPro no son válidas (revíselas en Admin).');
            throw new RuntimeException('Google respondió: ' . ($j['error_description'] ?? $e ?: 'error ' . $r->status()));
        }
        return $j;
    }

    // Llave de acceso vigente (la renueva sola con el refresh_token)
    public static function acceso(RedCuenta $c): string
    {
        $t = $c->youtube_token;
        if (empty($t['refresh'])) throw new RuntimeException('El canal no tiene acceso guardado. Vuelva a conectarlo.');
        if (!empty($t['access']) && (int) ($t['vence'] ?? 0) > time()) return $t['access'];
        try {
            $j = self::pedirToken(['refresh_token' => $t['refresh'], 'grant_type' => 'refresh_token']);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'venció')) $c->update(['estado' => 'error', 'error' => $e->getMessage()]);
            throw $e;
        }
        $t['access'] = $j['access_token'];
        $t['vence'] = time() + (int) ($j['expires_in'] ?? 3600) - 120;
        $c->guardarYoutubeToken($t);
        return $t['access'];
    }

    // Canal de la cuenta que dio el permiso: [id, nombre, foto, usuario]
    public static function canal(string $access): array
    {
        $r = Http::timeout(20)->withToken($access)->acceptJson()->get('https://www.googleapis.com/youtube/v3/channels', ['part' => 'snippet', 'mine' => 'true']);
        $j = self::revisar($r);
        $ch = $j['items'][0] ?? null;
        if (!$ch) throw new RuntimeException('Esa cuenta de Google no tiene canal de YouTube. Cree el canal en youtube.com y vuelva a conectar.');
        return [
            'id' => (string) $ch['id'],
            'nombre' => mb_substr((string) ($ch['snippet']['title'] ?? 'Mi canal'), 0, 120),
            'foto' => $ch['snippet']['thumbnails']['default']['url'] ?? null,
            'usuario' => isset($ch['snippet']['customUrl']) ? ltrim((string) $ch['snippet']['customUrl'], '@') : null,
        ];
    }

    /**
     * Sube el video de la publicación (o sigue la subida que quedó a medias). Devuelve true cuando YouTube ya lo tiene.
     * $hasta: hora límite (microtime) para no pasarse del tiempo de la vuelta del programador.
     */
    public static function subir(RedPublicacion $p, float $hasta): bool
    {
        // Sin cupo del día en el proyecto de Google: no se gasta otro intento hasta dentro de un rato
        if (Cache::has('youtube_sin_cupo')) throw new RuntimeException('YouTube llegó al límite de subidas de hoy. Se reintenta solo más tarde.');
        $c = $p->ytCuenta()->withoutGlobalScopes()->first();
        if (!$c || $c->estado === 'desconectada') throw new RuntimeException('El canal de YouTube fue desconectado.');
        $ruta = $p->video ? Storage::disk('public')->path($p->video) : null;
        if (!$ruta || !is_file($ruta)) throw new RuntimeException('No se encontró el archivo del video.');
        $total = filesize($ruta);
        $access = self::acceso($c);

        $sesion = $p->yt_subida;
        $desde = 0;
        if ($sesion) {
            // Sigue la subida anterior: se pregunta cuánto alcanzó a recibir YouTube
            $r = Http::timeout(30)->withToken($access)->withHeaders(['Content-Range' => 'bytes */' . $total])->withBody('', 'video/mp4')->put($sesion);
            if (in_array($r->status(), [200, 201], true)) return self::terminar($p, $r->json() ?: [], $access);
            if ($r->status() === 308) {
                $desde = preg_match('/bytes=0-(\d+)/', (string) $r->header('Range'), $m) ? (int) $m[1] + 1 : 0;
            } else {
                $sesion = null; // la sesión venció (dura una semana): se empieza de nuevo
            }
        }
        if (!$sesion) {
            $r = Http::timeout(30)->withToken($access)->acceptJson()
                ->withHeaders(['X-Upload-Content-Length' => (string) $total, 'X-Upload-Content-Type' => 'video/mp4'])
                ->withBody(json_encode(self::datos($p), JSON_UNESCAPED_UNICODE), 'application/json; charset=UTF-8')
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status');
            self::revisar($r);
            $sesion = (string) $r->header('Location');
            if ($sesion === '') throw new RuntimeException('YouTube no abrió la subida. Intente de nuevo.');
            $p->forceFill(['yt_subida' => $sesion])->save();
            $desde = 0;
        }

        $f = fopen($ruta, 'rb');
        try {
            while ($desde < $total) {
                if (microtime(true) > $hasta) return false; // sigue en la próxima vuelta
                fseek($f, $desde);
                $trozo = fread($f, self::PARTE);
                $fin = $desde + strlen($trozo) - 1;
                $access = self::acceso($c);
                try {
                    $r = Http::timeout(300)->withToken($access)->withHeaders(['Content-Range' => "bytes $desde-$fin/$total"])->withBody($trozo, 'video/mp4')->put($sesion);
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    return false; // se cortó la conexión: la próxima vuelta pregunta dónde quedó y sigue
                }
                if (in_array($r->status(), [200, 201], true)) return self::terminar($p, $r->json() ?: [], $access);
                if ($r->status() === 308) {
                    $desde = preg_match('/bytes=0-(\d+)/', (string) $r->header('Range'), $m) ? (int) $m[1] + 1 : $fin + 1;
                    continue;
                }
                if ($r->status() >= 500) return false; // YouTube tuvo un problema: se reintenta después
                self::revisar($r);
                throw new RuntimeException('YouTube no recibió el video (error ' . $r->status() . ').');
            }
        } finally {
            fclose($f);
        }
        return false;
    }

    private static function terminar(RedPublicacion $p, array $j, string $access): bool
    {
        $id = (string) ($j['id'] ?? '');
        if ($id === '') throw new RuntimeException('YouTube recibió el video pero no devolvió su enlace.');
        $p->forceFill([
            'yt_video_id' => $id, 'yt_subida' => null,
            'yt_url' => $p->es_short ? 'https://youtube.com/shorts/' . $id : 'https://youtu.be/' . $id,
        ])->save();
        // Portada propia: YouTube solo la deja poner en canales verificados (con número de celular); si no, deja la automática
        if (!$p->es_short && $p->portada && Storage::disk('public')->exists($p->portada)) {
            try {
                Http::timeout(60)->withToken($access)->withBody(Storage::disk('public')->get($p->portada), 'image/jpeg')
                    ->post('https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId=' . urlencode($id));
            } catch (Throwable $e) {
            }
        }
        return true;
    }

    // Título, descripción y privacidad del video
    public static function datos(RedPublicacion $p): array
    {
        $titulo = self::limpiar((string) ($p->titulo ?: mb_substr(strtok((string) $p->texto, "\n") ?: 'Video', 0, 90)));
        $desc = self::limpiar((string) $p->texto);
        if ($p->es_short && !str_contains(mb_strtolower($titulo . ' ' . $desc), '#shorts')) {
            if (mb_strlen($titulo) <= 91) $titulo .= ' #Shorts';
            else $desc = trim($desc . "\n\n#Shorts");
        }
        return [
            'snippet' => ['title' => mb_substr($titulo, 0, 100), 'description' => mb_strcut($desc, 0, 4900, 'UTF-8'), 'categoryId' => '22', 'defaultLanguage' => 'es'],
            'status' => ['privacyStatus' => array_key_exists((string) $p->yt_privacidad, RedPublicacion::PRIVACIDAD) ? $p->yt_privacidad : 'public', 'selfDeclaredMadeForKids' => false],
        ];
    }

    // YouTube no acepta "<" ni ">" en el título ni en la descripción
    private static function limpiar(string $t): string
    {
        return trim(str_replace(['<', '>'], ['‹', '›'], $t));
    }

    private static function revisar($r): array
    {
        $j = $r->json() ?: [];
        if ($r->successful()) return $j;
        $e = $j['error'] ?? [];
        $razon = (string) ($e['errors'][0]['reason'] ?? '');
        if ($razon === 'quotaExceeded') Cache::put('youtube_sin_cupo', 1, now()->addHour());
        $msg = match (true) {
            $razon === 'quotaExceeded' || $razon === 'rateLimitExceeded' => 'YouTube llegó al límite de subidas de hoy. Se reintenta solo más tarde.',
            $razon === 'uploadLimitExceeded' => 'Su canal llegó al límite de videos que YouTube deja subir por día.',
            $razon === 'youtubeSignupRequired' || $razon === 'channelNotFound' => 'Esa cuenta de Google no tiene canal de YouTube.',
            $r->status() === 401 => 'La conexión con YouTube venció. Vuelva a conectar el canal.',
            $r->status() === 403 && str_contains((string) ($e['message'] ?? ''), 'insufficient') => 'Falta el permiso para subir videos. Vuelva a conectar el canal y acepte todos los permisos.',
            default => 'YouTube respondió: ' . strip_tags((string) ($e['message'] ?? 'error ' . $r->status())),
        };
        throw new RuntimeException($msg);
    }

    // Revisión para el administrador: qué está bien y qué falta
    public static function diagnostico(): array
    {
        $f = [];
        $f[] = [self::clientId() !== '', 'ID de cliente de Google', self::clientId() ?: 'Falta: péguelo arriba (Google Cloud → APIs y servicios → Credenciales).'];
        $f[] = [self::clientSecret() !== '', 'Secreto del cliente', self::clientSecret() !== '' ? 'Configurado' : 'Falta.'];
        $f[] = [null, 'URI de redireccionamiento autorizado', self::urlRetorno() . ' (debe estar agregado en la credencial de Google, tal cual)'];
        if (self::activo()) {
            try {
                // Con un refresh_token falso, Google responde invalid_grant si el cliente es válido e invalid_client si no
                $err = Http::timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', ['client_id' => self::clientId(), 'client_secret' => self::clientSecret(), 'grant_type' => 'refresh_token', 'refresh_token' => 'x'])->json('error');
                $f[] = $err === 'invalid_client' || $err === 'unauthorized_client'
                    ? [false, 'Prueba con Google', 'Google no reconoce el ID o el secreto: revíselos.']
                    : [true, 'Prueba con Google', 'Google reconoce las credenciales.'];
            } catch (Throwable $e) {
                $f[] = [false, 'Prueba con Google', 'No hubo conexión con Google desde el servidor.'];
            }
        }
        $f[] = [null, 'Canales conectados', (string) RedCuenta::withoutGlobalScopes()->youtube()->where('estado', '<>', 'desconectada')->count()];
        return $f;
    }
}
