<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Cada 5 minutos revisa el registro de errores (storage/logs/laravel.log). Si aparecen errores nuevos,
 * le manda un correo al súper administrador con el resumen (el mismo error se avisa máximo una vez cada hora).
 */
class VigilarErrores extends Command
{
    protected $signature = 'localpro:vigilar-errores';
    protected $description = 'Avisa por correo al administrador cuando aparecen errores nuevos en el sistema';

    public function handle(): int
    {
        $log = storage_path('logs/laravel.log');
        if (!is_file($log)) return self::SUCCESS;
        $tam = filesize($log);
        $desde = (int) Cache::get('vigilar_errores_pos', -1);
        // Primera vez o el archivo se rotó: empieza desde el final
        if ($desde < 0 || $desde > $tam) { Cache::forever('vigilar_errores_pos', $tam); return self::SUCCESS; }
        if ($tam === $desde) return self::SUCCESS;

        $f = fopen($log, 'r');
        fseek($f, $desde);
        $nuevo = stream_get_contents($f, min($tam - $desde, 5 * 1024 * 1024));
        fclose($f);
        Cache::forever('vigilar_errores_pos', $tam);

        // [2026-10-31 08:40:12] production.ERROR: mensaje {"exception":"... at /ruta/archivo.php:123"}
        preg_match_all('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/m', $nuevo, $m, PREG_SET_ORDER);
        if (!$m) return self::SUCCESS;

        $grupos = [];
        foreach ($m as $x) {
            $msg = $x[3];
            $donde = preg_match('#at (/[^\s"]+?\.php):(\d+)#', $msg, $d) ? basename($d[1]) . ':' . $d[2] : '';
            $corto = trim(mb_substr(preg_replace('/\s*\{"(exception|userId)".*$/s', '', $msg), 0, 240));
            $clave = md5($corto . $donde);
            $grupos[$clave] ??= ['msg' => $corto, 'donde' => $donde, 'veces' => 0, 'ultima' => $x[1]];
            $grupos[$clave]['veces']++;
            $grupos[$clave]['ultima'] = $x[1];
        }
        // El mismo error se avisa máximo una vez por hora
        $avisar = array_filter($grupos, fn ($g, $k) => Cache::add('vigilar_errores_aviso_' . $k, 1, now()->addHour()), ARRAY_FILTER_USE_BOTH);
        $this->info(count($m) . ' error(es) nuevos, ' . count($avisar) . ' para avisar.');
        if (!$avisar) return self::SUCCESS;

        $para = User::where('es_superadmin', true)->whereNotNull('email')->pluck('email')->all();
        if (!$para) return self::SUCCESS;
        $html = '<div style="font-family:Arial,sans-serif;color:#151a17"><h2 style="margin:0 0 8px">⚠️ Errores nuevos en LocalProo</h2>'
            . '<p style="color:#5b665e">Alguien acaba de recibir un error. Mándele este correo a Claude para que lo revise y lo arregle.</p>';
        foreach ($avisar as $g) {
            $html .= '<div style="border:1px solid #e3efe5;border-radius:12px;padding:10px 12px;margin:8px 0"><b>' . e($g['msg']) . '</b><br><small style="color:#5b665e">'
                . e($g['donde'] ?: 'sin archivo') . ' · ' . $g['veces'] . ' vez/veces · último: ' . e($g['ultima']) . '</small></div>';
        }
        $html .= '<p style="color:#8a948d;font-size:12px">Revisión automática cada 5 minutos (localpro:vigilar-errores). El mismo error se avisa máximo una vez por hora.</p></div>';
        try {
            Mail::html($html, fn ($msg) => $msg->to($para)->subject('⚠️ LocalProo: ' . count($avisar) . ' error(es) nuevo(s) en el sistema'));
        } catch (\Throwable $e) {
            $this->warn('No se pudo enviar el aviso: ' . $e->getMessage());
        }
        return self::SUCCESS;
    }
}
