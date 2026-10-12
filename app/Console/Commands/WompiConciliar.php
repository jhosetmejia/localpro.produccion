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
        $n = Wompi::verificarPendientes(null, 200);
        $this->info("Pagos aprobados ahora: $n");
        return self::SUCCESS;
    }
}
