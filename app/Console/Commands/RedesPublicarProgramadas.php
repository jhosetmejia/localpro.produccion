<?php

namespace App\Console\Commands;

use App\Models\RedPublicacion;
use App\Support\Meta;
use App\Support\Publicador;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Publica lo que los negocios dejaron programado (fotos) y avanza los videos: Reels de Facebook/Instagram y YouTube
class RedesPublicarProgramadas extends Command
{
    protected $signature = 'redes:publicar-programadas {--minutos=8 : Tiempo máximo de esta vuelta}';
    protected $description = 'Publica las publicaciones programadas que ya llegaron a su hora y avanza la subida de los videos';

    public function handle(): int
    {
        @set_time_limit(0);
        $hasta = microtime(true) + 60 * max(1, (int) $this->option('minutos'));
        $n = 0;

        // Fotos programadas (como siempre: se publican de una)
        if (Meta::activo()) {
            RedPublicacion::withoutGlobalScopes()->with(['cuenta' => fn ($q) => $q->withoutGlobalScopes()])
                ->where(fn ($w) => $w->whereNull('tipo')->orWhere('tipo', 'foto'))
                ->where('estado', 'programada')->where('programada_para', '<=', now())->orderBy('programada_para')->limit(20)->get()
                ->each(function ($p) use (&$n) {
                    TiendaActual::set($p->tienda_id);
                    $p->update(['estado' => 'pendiente']);
                    if (!$p->cuenta || $p->cuenta->estado === 'desconectada') {
                        $p->update(['estado' => 'error', 'error' => 'La cuenta fue desconectada.']);
                        return;
                    }
                    Meta::publicar($p);
                    $n++;
                });
        }

        // Videos: los que llegaron a su hora, los de "publicar ahora" y los que van a medias (Instagram procesando, YouTube subiendo)
        $videos = RedPublicacion::withoutGlobalScopes()->with(['cuenta' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('tipo', 'video')
            ->where(fn ($w) => $w->whereIn('estado', ['pendiente', 'procesando'])
                ->orWhere(fn ($x) => $x->where('estado', 'programada')->where('programada_para', '<=', now())))
            ->orderByRaw("estado = 'procesando' DESC")->orderBy('programada_para')->orderBy('id')->limit(15)->get();
        foreach ($videos as $p) {
            if (microtime(true) > $hasta) break;
            TiendaActual::set($p->tienda_id);
            try {
                if (Publicador::avanzar($p, $hasta)) $n++;
            } catch (Throwable $e) {
                report($e);
                $p->forceFill(['estado' => 'error', 'error' => mb_substr(Meta::sinLlaves($e->getMessage()), 0, 500)])->save();
            }
        }
        TiendaActual::set(null);

        $this->limpiar();
        $this->info("Terminadas: $n");
        return self::SUCCESS;
    }

    // Los videos pesan: se borran del servidor cuando ya salieron (2 días después) o si quedaron con error mucho tiempo (15 días).
    // También las subidas a medias que nadie terminó.
    private function limpiar(): void
    {
        RedPublicacion::withoutGlobalScopes()->where('tipo', 'video')->whereNotNull('video')
            ->where(fn ($w) => $w->where(fn ($x) => $x->where('estado', 'publicada')->where('publicada_en', '<', now()->subDays(2)))
                ->orWhere(fn ($x) => $x->whereIn('estado', ['error', 'parcial'])->where('updated_at', '<', now()->subDays(15))))
            ->limit(50)->get()
            ->each(function ($p) {
                Storage::disk('public')->delete($p->video);
                $p->forceFill(['video' => null])->save();
            });

        $disco = Storage::disk('local');
        foreach ($disco->files('redes-subidas') as $f) {
            if ($disco->lastModified($f) < now()->subDay()->getTimestamp()) $disco->delete($f);
        }
    }
}
