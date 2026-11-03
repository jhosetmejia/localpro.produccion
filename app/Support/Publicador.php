<?php

namespace App\Support;

use App\Models\RedPublicacion;
use Throwable;

/**
 * Publica los videos de "Mis redes" desde el programador (cada minuto), red por red y sin dejar la página esperando:
 * - Facebook: Reel (o video de la página) en una sola vuelta.
 * - Instagram: entrega el video y en las siguientes vueltas revisa si ya lo procesó para publicarlo.
 * - YouTube: subida reanudable por partes; si se corta, sigue donde quedó.
 * La red que falla queda anotada en "fallos" y no se reintenta sola (sí con el botón Reintentar).
 */
class Publicador
{
    public const ESPERA_MAX_MIN = 45; // si Instagram no termina en este tiempo, se da por fallido

    // Avanza la publicación lo que pueda antes de $hasta. Devuelve true si ya terminó (bien o mal).
    public static function avanzar(RedPublicacion $p, float $hasta): bool
    {
        $fallos = (array) ($p->fallos ?? []);
        if (!$p->empezo_en) $p->empezo_en = now();
        $p->estado = 'procesando';
        $p->save();

        foreach ($p->redes() as $red) {
            if ($p->listaEn($red) || isset($fallos[$red])) continue;
            if (microtime(true) > $hasta) break;
            try {
                match ($red) {
                    'facebook' => self::facebook($p),
                    'instagram' => self::instagram($p),
                    'youtube' => YouTube::subir($p, $hasta),
                };
            } catch (Throwable $e) {
                $msg = $red === 'youtube' ? $e->getMessage() : Meta::sinLlaves($e->getMessage());
                // YouTube sin cupo del día: no es culpa del video, se reintenta solo en la siguiente vuelta (hasta 2 días)
                if ($red === 'youtube' && str_contains($msg, 'límite de subidas de hoy') && $p->empezo_en->gt(now()->subDays(2))) {
                    $p->error = $msg;
                    $p->save();
                    continue;
                }
                $fallos[$red] = mb_substr($msg, 0, 300);
            }
            $p->save();
        }

        // Instagram procesando hace demasiado: se corta
        if ($p->en_instagram && !$p->ig_media_id && !isset($fallos['instagram']) && $p->ig_contenedor && $p->empezo_en->lt(now()->subMinutes(self::ESPERA_MAX_MIN))) {
            $fallos['instagram'] = 'Instagram se demoró demasiado procesando el video. Toque Reintentar.';
        }

        $p->fallos = $fallos ?: null;
        $pendientes = array_filter($p->redes(), fn ($r) => !$p->listaEn($r) && !isset($fallos[$r]));
        if ($pendientes) {
            $p->save();
            return false;
        }
        return self::cerrar($p);
    }

    private static function facebook(RedPublicacion $p): void
    {
        if (!$p->cuenta || $p->cuenta->estado === 'desconectada') throw new \RuntimeException('La página de Facebook fue desconectada.');
        Meta::publicarVideoFacebook($p);
    }

    private static function instagram(RedPublicacion $p): void
    {
        if (!$p->cuenta || $p->cuenta->estado === 'desconectada') throw new \RuntimeException('La cuenta de Instagram fue desconectada.');
        if (!$p->ig_contenedor) {
            $p->ig_contenedor = Meta::crearReelInstagram($p);
            $p->save();
        }
        // Los Reels cortos casi siempre quedan listos en pocos segundos: se espera un rato antes de dejarlo para la otra vuelta
        for ($i = 0; $i < 6; $i++) {
            if (Meta::terminarReelInstagram($p)) return;
            sleep(5);
        }
    }

    private static function cerrar(RedPublicacion $p): bool
    {
        $redes = $p->redes();
        $bien = array_filter($redes, fn ($r) => $p->listaEn($r));
        $nombres = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'];
        $p->estado = count($bien) === count($redes) ? 'publicada' : ($bien ? 'parcial' : 'error');
        $p->error = $p->fallos ? mb_substr(implode(' · ', array_map(fn ($r, $m) => $nombres[$r] . ': ' . $m, array_keys($p->fallos), $p->fallos)), 0, 900) : null;
        if ($bien && !$p->publicada_en) $p->publicada_en = now();
        $p->save();
        return true;
    }

    // Botón Reintentar: vuelve a intentar solo las redes que fallaron
    public static function reintentar(RedPublicacion $p): void
    {
        $p->forceFill(['fallos' => null, 'error' => null, 'estado' => 'pendiente', 'empezo_en' => null, 'ig_contenedor' => $p->ig_media_id ? $p->ig_contenedor : null])->save();
    }
}
