<?php

namespace App\Console\Commands;

use App\Models\ErrorCliente;
use App\Models\Opinion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Opiniones y fallas que manda la gente (botón "Opinar") y errores del navegador.
 *  --avisar   (cada 10 min) correo al súper administrador con lo nuevo desde el último aviso.
 *  --resumen  texto corto para el reporte de estado del servidor (sin datos de contacto).
 */
class Opiniones extends Command
{
    protected $signature = 'localpro:opiniones {--avisar} {--resumen}';
    protected $description = 'Avisa por correo de las opiniones/fallas nuevas y resume lo pendiente';

    public function handle(): int
    {
        if (!Schema::hasTable('opiniones')) return self::SUCCESS;
        if ($this->option('resumen')) return $this->resumen();
        if ($this->option('avisar')) return $this->avisar();
        $this->info('Use --avisar o --resumen');
        return self::SUCCESS;
    }

    private function resumen(): int
    {
        $abiertas = Opinion::whereIn('estado', ['nuevo', 'revisando'])->latest()->limit(15)->get();
        $this->line('Abiertas: ' . Opinion::whereIn('estado', ['nuevo', 'revisando'])->count() . ' · nuevas: ' . Opinion::where('estado', 'nuevo')->count());
        foreach ($abiertas as $o) {
            $this->line('#' . $o->id . ' ' . $o->created_at?->format('m-d H:i') . ' [' . $o->tipo . '/' . $o->modulo . '] ' . Str::limit(preg_replace('/\s+/', ' ', $o->mensaje), 200) . ($o->pagina ? ' · ' . $o->pagina : ''));
        }
        $this->line('Errores del navegador sin resolver: ' . ErrorCliente::where('resuelto', false)->count());
        foreach (ErrorCliente::where('resuelto', false)->orderByDesc('ultima_vez')->limit(12)->get() as $e) {
            $this->line('E' . $e->id . ' ' . $e->ultima_vez?->format('m-d H:i') . ' [' . $e->modulo . '] x' . $e->veces . ' (' . $e->usuarios . ' pers.) ' . Str::limit($e->mensaje, 160) . ($e->donde ? ' @ ' . $e->donde : ''));
        }
        return self::SUCCESS;
    }

    private function avisar(): int
    {
        $desde = (int) Cache::get('opiniones_aviso_id', -1);
        $ultimo = (int) Opinion::max('id');
        if ($desde < 0) { Cache::forever('opiniones_aviso_id', $ultimo); return self::SUCCESS; } // primera vez: desde ahora
        $desdeErr = Cache::get('errores_cliente_aviso', now()->subMinutes(10)->toDateTimeString());
        $nuevas = Opinion::where('id', '>', $desde)->orderBy('id')->limit(30)->get();
        $errores = ErrorCliente::where('resuelto', false)->where('created_at', '>', $desdeErr)->orderByDesc('id')->limit(15)->get();
        Cache::forever('opiniones_aviso_id', max($desde, $ultimo));
        Cache::forever('errores_cliente_aviso', now()->toDateTimeString());
        if ($nuevas->isEmpty() && $errores->isEmpty()) return self::SUCCESS;

        $para = User::where('es_superadmin', true)->whereNotNull('email')->pluck('email')->all();
        if (!$para) return self::SUCCESS;
        $html = '<div style="font-family:Arial,sans-serif;color:#151a17"><h2 style="margin:0 0 8px">💬 Lo que la gente reportó en LocalProo</h2>';
        foreach ($nuevas as $o) {
            $html .= '<div style="border:1px solid #e3efe5;border-radius:12px;padding:10px 12px;margin:8px 0"><b>' . (Opinion::ICONOS[$o->tipo] ?? '💬') . ' ' . e(Opinion::TIPOS[$o->tipo] ?? $o->tipo) . '</b> · ' . e($o->modulo)
                . '<br>' . nl2br(e(Str::limit($o->mensaje, 600))) . '<br><small style="color:#5b665e">#' . $o->id . ' · ' . e($o->pagina ?: '') . ($o->foto ? ' · 📷 con captura' : '') . '</small></div>';
        }
        if ($errores->isNotEmpty()) {
            $html .= '<h3 style="margin:14px 0 6px">⚠️ Errores nuevos en el navegador</h3>';
            foreach ($errores as $e) {
                $html .= '<div style="border:1px solid #f3d7e1;border-radius:12px;padding:8px 12px;margin:6px 0"><b>' . e(Str::limit($e->mensaje, 240)) . '</b><br><small style="color:#5b665e">' . e($e->modulo) . ' · ' . e($e->donde ?: 'sin archivo') . ' · ' . $e->veces . ' vez/veces</small></div>';
            }
        }
        $html .= '<p><a href="' . e(route('admin.opiniones')) . '" style="background:#16b84e;color:#fff;padding:10px 16px;border-radius:99px;text-decoration:none;font-weight:bold">Ver todo en el panel</a></p></div>';
        try {
            Mail::html($html, fn ($m) => $m->to($para)->subject('💬 LocalProo: ' . ($nuevas->count() ? $nuevas->count() . ' reporte(s) de la gente' : '') . ($nuevas->count() && $errores->count() ? ' y ' : '') . ($errores->count() ? $errores->count() . ' error(es) del navegador' : '')));
        } catch (\Throwable $ex) {
            $this->warn('No se pudo enviar el aviso: ' . $ex->getMessage());
        }
        return self::SUCCESS;
    }
}
