<?php

namespace App\Support;

use App\Models\Ajuste;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-18 (Jhoset): avisos al celular de LP Chat aunque la app esté cerrada (Web Push con VAPID, sin librerías).
 * El aviso va vacío; el service worker (public/sw.js) pregunta a /chat/api/aviso quién escribió y lo muestra.
 * Las llaves VAPID se crean solas la primera vez y quedan en ajustes (vapid_privada / vapid_publica).
 */
class WebPush
{
    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    /** [pem privada, pública en base64url (65 bytes sin comprimir)] */
    public static function llaves(): array
    {
        $pem = (string) Ajuste::valor('vapid_privada');
        $pub = (string) Ajuste::valor('vapid_publica');
        if ($pem === '' || $pub === '') {
            $k = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
            openssl_pkey_export($k, $pem);
            $d = openssl_pkey_get_details($k);
            $pub = self::b64("\x04".str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT));
            Ajuste::guardar('vapid_privada', $pem);
            Ajuste::guardar('vapid_publica', $pub);
        }

        return [$pem, $pub];
    }

    public static function clavePublica(): string
    {
        return self::llaves()[1];
    }

    /** Firma ES256 (DER de openssl -> r||s de 64 bytes). */
    private static function jwt(string $aud, string $pem): string
    {
        $h = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $p = self::b64(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => 'mailto:soporte@localproo.com']));
        openssl_sign($h.'.'.$p, $der, openssl_pkey_get_private($pem), OPENSSL_ALGO_SHA256);
        $o = 3;
        $lr = ord($der[$o]);
        $r = substr($der, $o + 1, $lr);
        $o += 2 + $lr;
        $ls = ord($der[$o]);
        $s = substr($der, $o + 1, $ls);
        $fix = fn ($x) => str_pad(ltrim($x, "\0"), 32, "\0", STR_PAD_LEFT);

        return $h.'.'.$p.'.'.self::b64($fix($r).$fix($s));
    }

    /** Manda un aviso vacío a una suscripción. false = la suscripción ya no sirve (se borra). */
    public static function enviar(string $endpoint): bool
    {
        if (! preg_match('#^https://#', $endpoint)) {
            return false;
        }
        [$pem, $pub] = self::llaves();
        $u = parse_url($endpoint);
        $aud = $u['scheme'].'://'.$u['host'];
        try {
            $r = Http::timeout(8)->withHeaders([
                'Authorization' => 'vapid t='.self::jwt($aud, $pem).', k='.$pub,
                'TTL' => '86400',
                'Urgency' => 'high',
            ])->withBody('', 'application/octet-stream')->post($endpoint);

            return ! in_array($r->status(), [404, 410], true);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** Avisa a estas cuentas de LP Chat (máximo un aviso cada 10 s por cuenta). */
    public static function avisar(array $usuarioIds): void
    {
        if (! Schema::hasTable('lpchat_push')) {
            return;
        }
        foreach (array_unique(array_map('intval', $usuarioIds)) as $uid) {
            if (! $uid || ! Cache::add('lpchat_push_'.$uid, 1, 10)) {
                continue;
            }
            foreach (DB::table('lpchat_push')->where('usuario_id', $uid)->get(['id', 'endpoint']) as $s) {
                if (! self::enviar($s->endpoint)) {
                    DB::table('lpchat_push')->where('id', $s->id)->delete();
                }
            }
        }
    }

    /** Igual que avisar(), pero después de responder (para no demorar al que escribe). */
    public static function avisarLuego(array $usuarioIds): void
    {
        if (! $usuarioIds) {
            return;
        }
        app()->terminating(function () use ($usuarioIds) {
            try {
                self::avisar($usuarioIds);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
