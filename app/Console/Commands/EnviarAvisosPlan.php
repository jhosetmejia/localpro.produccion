<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use App\Support\AvisosPlan as Avisos;
use Illuminate\Console\Command;

/**
 * Correos de fin de prueba (corre cada mañana):
 *  - por_vencer: faltan 5 días o menos para que termine la prueba
 *  - vencida: la prueba terminó → "pague antes de X o se borra la información" (se reintenta cada día si falla)
 *  - aviso_final: faltan 3 días o menos para borrar
 * Cada envío queda registrado en avisos_plan como constancia.
 */
class EnviarAvisosPlan extends Command
{
    protected $signature = 'localpro:avisos-plan {--simular : Solo muestra qué correos enviaría}';
    protected $description = 'Envía los correos de fin de prueba gratis y deja constancia de cada envío';

    public function handle(): int
    {
        $tiendas = Tienda::with('plan')
            ->whereNull('datos_depurados_at')
            ->whereNotNull('plan_vence')
            ->whereDate('plan_vence', '<=', today()->addDays(5))
            ->where('estado', 'activa')
            ->whereDoesntHave('usuarios', fn ($u) => $u->where('es_superadmin', true))
            ->get();

        $n = 0;
        foreach ($tiendas as $t) {
            if (!Avisos::esPrueba($t)) {
                continue;
            }
            $tipo = null;
            if (!$t->plan_vencido) {
                $tipo = Avisos::yaEnviado($t, 'por_vencer') ? null : 'por_vencer';
            } elseif (!Avisos::yaEnviado($t, 'vencida')) {
                $tipo = 'vencida';
            } else {
                $borrar = Avisos::fechaBorrado($t);
                if ($borrar && today()->diffInDays($borrar, false) <= 3 && !Avisos::yaEnviado($t, 'aviso_final')) {
                    $tipo = 'aviso_final';
                }
            }
            if (!$tipo) {
                continue;
            }
            if ($this->option('simular')) {
                $this->line("Enviaría '$tipo' a $t->codigo $t->nombre: " . implode(', ', Avisos::destinatarios($t)));
                continue;
            }
            $ok = Avisos::enviar($t, $tipo);
            $this->line("$t->codigo $t->nombre · $tipo · enviados: $ok");
            $n += $ok;
        }
        $this->info("Correos enviados: $n");
        return self::SUCCESS;
    }
}
