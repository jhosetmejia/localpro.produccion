<?php

namespace App\Console\Commands;

use App\Models\RedPublicacion;
use App\Support\Meta;
use App\Support\TiendaActual;
use Illuminate\Console\Command;

// Publica en Facebook/Instagram lo que los negocios dejaron programado
class RedesPublicarProgramadas extends Command
{
    protected $signature = 'redes:publicar-programadas';
    protected $description = 'Publica las publicaciones programadas que ya llegaron a su hora';

    public function handle(): int
    {
        if (!Meta::activo()) return self::SUCCESS;
        $n = 0;
        RedPublicacion::withoutGlobalScopes()->with(['cuenta' => fn ($q) => $q->withoutGlobalScopes()])
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
        TiendaActual::set(null);
        $this->info("Publicadas: $n");
        return self::SUCCESS;
    }
}
