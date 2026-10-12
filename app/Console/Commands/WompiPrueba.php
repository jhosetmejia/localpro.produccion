<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Tienda;
use App\Support\Wompi;
use Illuminate\Console\Command;

/**
 * Genera un enlace de pago de PRUEBA (valor bajo, ej. 1.500) para probar Wompi de punta a punta.
 * Al aprobarse activa el plan de la tienda igual que un pago real, pero:
 *  - NO genera comisiones para la red comercial.
 *  - NO cuenta como "pago anterior" (la primera venta real sigue siendo VENTA NUEVA).
 * Uso:  php artisan wompi:prueba "Abril Store"          (o el id, o el código LCT-00001)
 *       php artisan wompi:prueba "Abril Store" --monto=1500 --plan=Anual
 */
class WompiPrueba extends Command
{
    protected $signature = 'wompi:prueba {tienda : id, código LCT-##### o nombre} {--monto=1500} {--plan= : nombre o id del plan (por defecto el anual)}';
    protected $description = 'Crea un pago de prueba con Wompi para una tienda y muestra el enlace para pagarlo';

    public function handle(): int
    {
        if (!Wompi::activo()) {
            $this->error('Faltan WOMPI_PUBLIC_KEY / WOMPI_INTEGRITY_SECRET en el .env (y corra php artisan config:cache).');
            return self::FAILURE;
        }

        $q = trim((string) $this->argument('tienda'));
        $tienda = ctype_digit($q) ? Tienda::find((int) $q) : null;
        $tienda ??= Tienda::where('codigo', strtoupper($q))->first();
        if (!$tienda) {
            $lista = Tienda::where('nombre', 'like', '%' . $q . '%')->get();
            if ($lista->count() > 1) {
                $this->warn('Hay varias tiendas con ese nombre; use el id:');
                foreach ($lista as $t) $this->line("  {$t->id} · {$t->codigo} · {$t->nombre}");
                return self::FAILURE;
            }
            $tienda = $lista->first();
        }
        if (!$tienda) {
            $this->error("No encontré la tienda \"$q\".");
            return self::FAILURE;
        }

        $p = trim((string) $this->option('plan'));
        $plan = $p === ''
            ? Wompi::planAnual()
            : (ctype_digit($p) ? Plan::find((int) $p) : Plan::where('nombre', 'like', '%' . $p . '%')->first());
        if (!$plan || $plan->es_gratis) {
            $this->error('No encontré un plan pagado (use --plan=).');
            return self::FAILURE;
        }

        $pago = Wompi::crearPagoPrueba($tienda, $plan, (int) $this->option('monto'));
        $monto = (int) $pago->monto;

        $url = Wompi::urlCheckout($pago, route('pagar.retorno', ['ref' => $pago->referencia]), $tienda->email, $tienda->nombre, $tienda->telefono);

        $this->info("Tienda: {$tienda->nombre} ({$tienda->codigo}) · Plan: {$plan->nombre} ({$plan->dias} días) · Valor: $" . number_format($monto, 0, ',', '.'));
        $this->info("Referencia: {$pago->referencia}" . (Wompi::esPruebas() ? '  [SANDBOX]' : '  [PRODUCCIÓN: cobro real]'));
        $this->line('');
        $this->line('Abra este enlace para pagar:');
        $this->line($url);

        return self::SUCCESS;
    }
}
