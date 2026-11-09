<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ubicación aproximada de una IP (país, departamento, ciudad, operador, si son datos móviles o VPN).
 * Igual que en ECO: ip-api.com por lotes de hasta 100 IP (gratis, sin registro). Cada IP queda en caché 30 días.
 * No se llama en el momento de la visita (para no demorar nada): lo completa `localpro:cut-geo` cada 5 minutos
 * y el panel al abrirse.
 */
class GeoIp
{
    /**
     * @param  list<string>  $ips
     * @return array<string, array<string, mixed>>  (solo las que se pudieron ubicar)
     */
    public static function consultar(array $ips): array
    {
        $res = [];
        $faltan = [];
        foreach (array_unique(array_filter($ips)) as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $res[$ip] = ['pais' => 'Red local', 'pais_codigo' => null, 'region' => null, 'ciudad' => null, 'operador' => null, 'datos_moviles' => false, 'vpn' => false];
                continue;
            }
            $c = Cache::get('geoip2:' . $ip);
            is_array($c) ? $res[$ip] = $c : $faltan[] = $ip;
        }
        foreach (array_chunk($faltan, 100) as $lote) {
            try {
                $r = Http::timeout(6)->acceptJson()
                    ->post('http://ip-api.com/batch?fields=status,country,countryCode,regionName,city,isp,mobile,proxy,hosting,query&lang=es', $lote);
                if (!$r->successful()) continue;
                foreach ((array) $r->json() as $f) {
                    $ip = $f['query'] ?? null;
                    if (!$ip) continue;
                    $ok = ($f['status'] ?? '') === 'success';
                    $geo = [
                        'pais' => $ok ? mb_substr((string) ($f['country'] ?? ''), 0, 60) ?: null : 'Desconocido',
                        'pais_codigo' => $ok ? ($f['countryCode'] ?? null) : null,
                        'region' => $ok ? mb_substr((string) ($f['regionName'] ?? ''), 0, 80) ?: null : null,
                        'ciudad' => $ok ? mb_substr((string) ($f['city'] ?? ''), 0, 80) ?: null : null,
                        'operador' => $ok ? mb_substr((string) ($f['isp'] ?? ''), 0, 120) ?: null : null,
                        'datos_moviles' => (bool) ($f['mobile'] ?? false),
                        'vpn' => (bool) (($f['proxy'] ?? false) || ($f['hosting'] ?? false)),
                    ];
                    Cache::put('geoip2:' . $ip, $geo, now()->addDays(30));
                    $res[$ip] = $geo;
                }
            } catch (\Throwable $e) {
                Log::warning('GeoIp: no se pudo ubicar IPs: ' . $e->getMessage());
            }
        }
        return $res;
    }
}
