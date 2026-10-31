<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use App\Support\ReferidosMes;
use Illuminate\Console\Command;

// Cada día: entrega el mes gratis a los negocios cuyos invitados ya cumplieron las reglas
class ReferidosMeses extends Command
{
    protected $signature = 'localpro:referidos-meses';
    protected $description = 'Entrega 1 mes gratis por cada emprendedor invitado que cumplió los pasos';

    public function handle(): int
    {
        $ids = Tienda::whereNotNull('referida_por_tienda_id')->where('created_at', '>=', now()->subDays(365))
            ->whereNotIn('id', fn ($q) => $q->select('referida_id')->from('referido_meses'))
            ->distinct()->pluck('referida_por_tienda_id');
        $total = 0;
        foreach (Tienda::whereIn('id', $ids)->get() as $ref) {
            $r = ReferidosMes::revisar($ref);
            if ($r['nuevos']) { $total += $r['nuevos']; $this->info($ref->nombre . ': +' . $r['nuevos'] . ' mes(es)'); }
        }
        $this->info('Meses entregados: ' . $total);
        return self::SUCCESS;
    }
}
