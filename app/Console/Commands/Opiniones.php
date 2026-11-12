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
    protected $signature = 'localpro:opiniones {--avisar} {--resumen} {--resolver= : archivo con los IDs ya arreglados (E12 2026-11-11 22:30)}';
    protected $description = 'Avisa por correo de las opiniones/fallas nuevas y resume lo pendiente';

    public function handle(): int
    {
        if (!Schema::hasTable('opiniones')) return self::SUCCESS;
        if ($this->option('resolver')) return $this->resolver($this->option('resolver'));
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
        foreach (ErrorCliente::where('resuelto', false)->orderByDesc('ultima_vez')->limit(20)->get() as $e) {
            $this->line('E' . $e->id . ' ' . $e->ultima_vez?->format('m-d H:i') . ' [' . $e->modulo . '] x' . $e->veces . ' (' . $e->usuarios . ' pers.) ' . Str::limit($e->mensaje, 200) . ($e->donde ? ' @ ' . $e->donde : ''));
            // Detalle para poder arreglarlo sin entrar al panel (sin datos de contacto; cada línea corta para el reporte)
            $limpiar = fn ($t) => trim(preg_replace('/\s+/', ' ', (string) $t));
            if ($e->pagina) $this->line('   página: ' . Str::limit(preg_replace('/[?#].*$/', '', $e->pagina), 200));
            if ($e->navegador) $this->line('   equipo: ' . Str::limit($limpiar($e->navegador), 220));
            if ($e->datos) $this->line('   datos: ' . Str::limit(json_encode($e->datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 260));
            foreach (array_slice(array_filter(array_map('trim', explode("\n", (string) $e->pila))), 0, 4) as $l) $this->line('   pila: ' . Str::limit($l, 220));
            $this->line('   primera vez: ' . $e->primera_vez?->format('Y-m-d H:i'));
        }
        return self::SUCCESS;
    }

    /** Marca como resueltos los errores/opiniones que Claude ya arregló (deploy/fallas-resueltas.txt, lo lee el piloto).
     *  Formato por línea: "E12 2026-11-11 22:30 comentario" o "#34 2026-11-11 22:30". Solo se cierra si no volvió a pasar
     *  después de esa fecha (si vuelve a pasar, queda abierto: así se nota si el arreglo no sirvió). */
    private function resolver(string $archivo): int
    {
        if (!is_file($archivo)) return self::SUCCESS;
        $n = 0;
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
            if (!preg_match('/^\s*([E#])(\d+)\s+(\d{4}-\d{2}-\d{2} \d{1,2}:\d{2})/', $l, $m)) continue;
            try { $hasta = \Carbon\Carbon::parse($m[3], 'America/Bogota')->setTimezone(config('app.timezone')); } catch (\Throwable $e) { continue; }
            if ($m[1] === 'E') {
                $n += ErrorCliente::where('id', $m[2])->where('resuelto', false)->where('ultima_vez', '<=', $hasta)->update(['resuelto' => true]);
            } else {
                $n += Opinion::where('id', $m[2])->whereIn('estado', ['nuevo', 'revisando'])->where('created_at', '<=', $hasta)->update(['estado' => 'resuelto']);
            }
        }
        if ($n) $this->line("Marcadas como resueltas: $n");
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
