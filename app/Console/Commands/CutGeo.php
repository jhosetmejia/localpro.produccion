<?php

namespace App\Console\Commands;

use App\Models\CutVisita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Ubica (ciudad aproximada) las visitas nuevas de LocalProCut y borra su IP. Cada 5 minutos. */
class CutGeo extends Command
{
    protected $signature = 'localpro:cut-geo';
    protected $description = 'Ubica las visitas (LocalProCut y todo el sitio) por la IP, borra la IP y limpia recorridos viejos';

    public function handle(): int
    {
        if (!Schema::hasTable('cut_visitas')) return self::SUCCESS;
        \Illuminate\Support\Facades\Cache::forget('cut_geo_intento');
        $this->info(CutVisita::ubicarPendientes(300) . ' visita(s) de LocalProCut ubicadas.');
        if (Schema::hasTable('visitas_sitio')) {
            \Illuminate\Support\Facades\Cache::forget('sitio_geo_intento');
            $this->info(\App\Models\VisitaSitio::ubicarPendientes(300) . ' visita(s) del sitio ubicadas.');
            // El recorrido página por página se guarda 60 días (las visitas con sus totales se quedan)
            \App\Models\VisitaPagina::where('created_at', '<', now()->subDays(60))->limit(5000)->delete();
        }
        return self::SUCCESS;
    }
}
