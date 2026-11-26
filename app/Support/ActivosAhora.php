<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-25 (Jhoset): cuántas personas con sesión iniciada están activas AHORA (se movieron en los últimos 5 minutos).
 * Sale en el Centro de control de LocalPro junto a los registrados ("1.234 / 12 activos").
 * Lee las sesiones (base de datos o archivos) y cuenta personas distintas; los visitantes sin cuenta no cuentan.
 */
class ActivosAhora
{
    public const MINUTOS = 5;

    /** Claves de la sesión que identifican a alguien con cuenta (además de user_id / login_web_*). */
    private const CLAVES = ['predictor_id', 'peleador_id'];

    public static function contar(): ?int
    {
        try {
            $desde = now()->subMinutes(self::MINUTOS)->timestamp;
            $quien = [];
            if (config('session.driver') === 'database') {
                $tabla = config('session.table', 'sessions');
                if (! Schema::hasTable($tabla)) {
                    return null;
                }
                DB::table($tabla)->where('last_activity', '>=', $desde)->orderBy('last_activity')
                    ->select(['user_id', 'payload'])->limit(20000)->get()
                    ->each(function ($s) use (&$quien) {
                        $k = $s->user_id ? 'u'.$s->user_id : self::deDatos(@base64_decode((string) $s->payload, true) ?: '');
                        if ($k) {
                            $quien[$k] = true;
                        }
                    });
            } elseif (config('session.driver') === 'file') {
                $carpeta = config('session.files', storage_path('framework/sessions'));
                foreach (glob(rtrim($carpeta, '/').'/*') ?: [] as $f) {
                    if (@filemtime($f) >= $desde && ($k = self::deDatos((string) @file_get_contents($f)))) {
                        $quien[$k] = true;
                    }
                }
            } else {
                return null;
            }

            return count($quien);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * 2026-11-25: registros por día de los últimos $dias días (hora de Colombia), del más viejo al de hoy.
     * Para la gráfica de tendencia del Centro de control.
     */
    public static function serieDiaria($query, string $col = 'created_at', int $dias = 30): array
    {
        try {
            $tz = 'America/Bogota';
            $inicio = now($tz)->startOfDay()->subDays($dias - 1);
            $out = array_fill(0, $dias, 0);
            (clone $query)->where($col, '>=', $inicio->copy()->setTimezone(config('app.timezone', 'UTC')))->pluck($col)
                ->each(function ($f) use (&$out, $inicio, $tz, $dias) {
                    $i = (int) floor(($inicio->diffInSeconds(\Illuminate\Support\Carbon::parse($f)->setTimezone($tz), false)) / 86400);
                    if ($i >= 0 && $i < $dias) {
                        $out[$i]++;
                    }
                });

            return $out;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private static function deDatos(string $crudo): ?string
    {
        if ($crudo === '') {
            return null;
        }
        $d = @unserialize($crudo, ['allowed_classes' => false]);
        if (! is_array($d)) {
            return null;
        }
        foreach ($d as $k => $v) {
            if (is_string($k) && str_starts_with($k, 'login_') && $v) {
                return 'u'.$v;
            }
        }
        foreach (self::CLAVES as $c) {
            if (! empty($d[$c])) {
                return $c.':'.$d[$c];
            }
        }

        return null;
    }
}
