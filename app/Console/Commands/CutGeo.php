<?php

namespace App\Console\Commands;

use App\Models\CutVisita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Ubica (ciudad aproximada) las visitas nuevas de LocalProCut y borra su IP. Cada 5 minutos. */
class CutGeo extends Command
{
    protected $signature = 'localpro:cut-geo';
    protected $description = 'Ubica las visitas de LocalProCut por la IP y luego borra la IP';

    public function handle(): int
    {
        if (!Schema::hasTable('cut_visitas')) return self::SUCCESS;
        \Illuminate\Support\Facades\Cache::forget('cut_geo_intento');
        $this->info(CutVisita::ubicarPendientes(300) . ' visita(s) ubicadas.');
        return self::SUCCESS;
    }
}
