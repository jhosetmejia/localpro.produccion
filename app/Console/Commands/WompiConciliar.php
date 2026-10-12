<?php

namespace App\Console\Commands;

use App\Models\PagoSuscripcion;
use App\Support\Wompi;
use Illuminate\Console\Command;

// Revisa en Wompi los pagos que quedaron pendientes (por si el cliente cerró la ventana antes de volver)
class WompiConciliar extends Command
{
    protected $signature = 'wompi:conciliar';
    protected $description = 'Confirma con Wompi los pagos de planes que siguen pendientes';

    public function handle(): int
    {
        if (!Wompi::activo() || !config('services.wompi.private_key')) {
            $this->info('Falta WOMPI_PRIVATE_KEY: no se puede conciliar.');
            return self::SUCCESS;
        }
        $n = 0;
        PagoSuscripcion::where('metodo', 'wompi')->where('estado', 'pendiente')
            ->where('created_at', '>=', now()->subDays(3))->orderBy('id')
            ->each(function ($pago) use (&$n) {
                if ($tx = Wompi::porReferencia($pago->referencia)) {
                    $n += Wompi::procesar($tx)?->estado === 'aprobado' ? 1 : 0;
                }
            });
        $this->info("Pagos aprobados ahora: $n");
        return self::SUCCESS;
    }
}
