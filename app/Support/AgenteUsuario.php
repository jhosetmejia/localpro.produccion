<?php

namespace App\Support;

/**
 * Lectura sencilla del User-Agent (sin paquetes externos, igual que en ECO): dispositivo, sistema,
 * navegador, app desde la que se abrió (Instagram, TikTok…), marca del equipo y si es robot.
 */
class AgenteUsuario
{
    /** Robots y "vistas previas" de enlaces (no son personas dando clic). */
    private const BOTS = '/bot\b|bot\/|crawl|spider|slurp|facebookexternalhit|facebookcatalog|meta-externalagent|'
        . 'whatsapp\/|telegrambot|twitterbot|linkedinbot|slackbot|discordbot|skypeuripreview|pinterest\/|'
        . 'embedly|quora link preview|vkshare|bingpreview|googleother|google-inspectiontool|headlesschrome|'
        . 'lighthouse|python-requests|python-urllib|curl\/|wget\/|go-http-client|okhttp|java\/|axios|node-fetch|'
        . 'postmanruntime|insomnia|scrapy|petalbot|applebot|yandex|baiduspider|semrush|ahrefs|mj12bot|dotbot|'
        . 'uptime|monitor|preview/i';

    /** @return array{dispositivo:string,sistema:?string,navegador:?string,app:?string,marca_equipo:?string,es_bot:bool} */
    public static function analizar(?string $ua): array
    {
        $ua = (string) $ua;

        return [
            'dispositivo'  => self::dispositivo($ua),
            'sistema'      => self::sistema($ua),
            'navegador'    => self::navegador($ua),
            'app'          => self::app($ua),
            'marca_equipo' => self::marca($ua),
            'es_bot'       => $ua === '' || (bool) preg_match(self::BOTS, $ua),
        ];
    }

    private static function dispositivo(string $ua): string
    {
        $esMovil = (bool) preg_match('/mobi|iphone|ipod/i', $ua);

        if (preg_match('/ipad|tablet|kindle|silk|playbook|\bsm-[tx]\d/i', $ua)
            || (! $esMovil && preg_match('/android/i', $ua))) {
            return 'Tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $ua)) {
            return 'Celular';
        }

        return 'Computador';
    }

    private static function sistema(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/windows phone/i', $ua)            => 'Windows Phone',
            (bool) preg_match('/android/i', $ua)                  => 'Android',
            (bool) preg_match('/iphone|ipad|ipod|ios/i', $ua)     => 'iOS',
            (bool) preg_match('/mac os x|macintosh/i', $ua)       => 'macOS',
            (bool) preg_match('/windows/i', $ua)                  => 'Windows',
            (bool) preg_match('/cros/i', $ua)                     => 'ChromeOS',
            (bool) preg_match('/linux/i', $ua)                    => 'Linux',
            default                                                => null,
        };
    }

    private static function navegador(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/edg(e|a|ios)?\//i', $ua)          => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua)              => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua)           => 'Samsung Internet',
            (bool) preg_match('/miuibrowser|xiaomi\/miuibrowser/i', $ua) => 'Mi Browser',
            (bool) preg_match('/huaweibrowser/i', $ua)            => 'Huawei Browser',
            (bool) preg_match('/firefox|fxios/i', $ua)            => 'Firefox',
            (bool) preg_match('/crios|chrome\//i', $ua)           => 'Chrome',
            (bool) preg_match('/safari/i', $ua)                   => 'Safari',
            default                                                => null,
        };
    }

    /** App que abrió el enlace con su navegador interno. */
    private static function app(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/instagram/i', $ua)                            => 'Instagram',
            (bool) preg_match('/musical_ly|bytedancewebview|tiktok|trill/i', $ua) => 'TikTok',
            (bool) preg_match('/barcelona/i', $ua)                            => 'Threads',
            (bool) preg_match('/fban|fbav|fb_iab|fbios|messenger/i', $ua)     => 'Facebook',
            (bool) preg_match('/snapchat/i', $ua)                             => 'Snapchat',
            (bool) preg_match('/twitter|x-client/i', $ua)                     => 'X (Twitter)',
            (bool) preg_match('/linkedinapp/i', $ua)                          => 'LinkedIn',
            (bool) preg_match('/telegram/i', $ua)                             => 'Telegram',
            (bool) preg_match('/pinterest/i', $ua)                            => 'Pinterest',
            (bool) preg_match('/gsa\//i', $ua)                                => 'App de Google',
            default                                                            => null,
        };
    }

    private static function marca(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/iphone/i', $ua)                               => 'iPhone',
            (bool) preg_match('/ipad/i', $ua)                                 => 'iPad',
            (bool) preg_match('/samsung|sm-[a-z0-9]+|gt-[a-z0-9]+/i', $ua)    => 'Samsung',
            (bool) preg_match('/xiaomi|redmi|poco|\bmi \d|2\d{3}[a-z0-9]{4,}/i', $ua) => 'Xiaomi',
            (bool) preg_match('/motorola|moto [a-z0-9]|\bxt\d{4}/i', $ua)     => 'Motorola',
            (bool) preg_match('/huawei|honor|\b[a-z]{3}-l[x0-9]{2}/i', $ua)   => 'Huawei/Honor',
            (bool) preg_match('/oppo|cph\d{4}/i', $ua)                        => 'Oppo',
            (bool) preg_match('/realme|rmx\d{4}/i', $ua)                      => 'Realme',
            (bool) preg_match('/vivo/i', $ua)                                 => 'Vivo',
            (bool) preg_match('/tecno|infinix|itel/i', $ua)                   => 'Tecno/Infinix',
            (bool) preg_match('/pixel/i', $ua)                                => 'Google Pixel',
            (bool) preg_match('/zte/i', $ua)                                  => 'ZTE',
            (bool) preg_match('/nokia/i', $ua)                                => 'Nokia',
            (bool) preg_match('/\blg[-\s]/i', $ua)                            => 'LG',
            (bool) preg_match('/macintosh/i', $ua)                            => 'Mac',
            default                                                            => null,
        };
    }
}
