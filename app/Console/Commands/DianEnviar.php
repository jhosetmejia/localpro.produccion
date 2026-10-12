<?php

namespace App\Console\Commands;

use App\Models\DocumentoElectronico;
use App\Support\Dian\Facturador;
use Illuminate\Console\Command;

// Envía a la DIAN (por el proveedor) los documentos en cola y reintenta los que fallaron, con espera creciente.
class DianEnviar extends Command
{
    protected $signature = 'dian:enviar {--max=50}';

    protected $description = 'Envía y reintenta facturas, POS electrónicos y notas crédito pendientes';

    public function handle(): int
    {
        if (!Facturador::proveedor()) {
            $this->line('Sin proveedor conectado: los documentos siguen en cola.');
            return self::SUCCESS;
        }
        $docs = DocumentoElectronico::withoutGlobalScopes()
            ->whereIn('estado', ['pendiente', 'error', 'enviado'])
            ->where('intentos', '<', 12)
            ->orderBy('id')->limit((int) $this->option('max'))->get()
            // Espera creciente entre intentos: 1, 2, 4, 8… minutos (máximo 6 horas)
            ->filter(fn ($d) => $d->estado === 'pendiente' || !$d->enviado_en || $d->enviado_en->lt(now()->subMinutes(min(360, 2 ** max(0, $d->intentos - 1)))));
        $n = 0;
        foreach ($docs as $d) {
            // Las notas crédito esperan a que su factura haya salido
            if ($d->tipo === 'nc' && $d->referencia && in_array($d->referencia->estado, ['pendiente', 'error'], true)) continue;
            if ($d->estado === 'enviado') {
                $r = Facturador::proveedor()->consultar($d);
                if ($r && in_array($r['estado'] ?? '', ['aceptado', 'rechazado'], true)) {
                    $d->forceFill(['estado' => $r['estado'], 'validado_en' => now(), 'ultimo_error' => $r['estado'] === 'rechazado' ? mb_substr((string) ($r['mensaje'] ?? ''), 0, 1000) : null])->save();
                }
                continue;
            }
            Facturador::enviar($d);
            $n++;
        }
        $this->info("Documentos procesados: $n");
        return self::SUCCESS;
    }
}
