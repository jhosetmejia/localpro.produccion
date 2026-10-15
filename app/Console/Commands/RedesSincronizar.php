<?php

namespace App\Console\Commands;

use App\Models\RedCuenta;
use App\Support\Meta;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Throwable;

/**
 * Plan B de los mensajes de Facebook e Instagram: cada pocos minutos le pregunta a Meta por chats nuevos.
 * Así llegan aunque los avisos en tiempo real (webhook) fallen o la app de Meta no los esté enviando.
 */
class RedesSincronizar extends Command
{
    protected $signature = 'redes:sincronizar {--limite=5 : Chats recientes a revisar por cuenta}';
    protected $description = 'Trae los mensajes nuevos de Facebook e Instagram de todas las cuentas conectadas';

    public function handle(): int
    {
        if (!Meta::activo()) return self::SUCCESS;
        Meta::$hasta = microtime(true) + 200; // deja margen antes de la siguiente corrida
        $n = 0;
        $cuentas = RedCuenta::withoutGlobalScopes()->where('estado', 'activa')->orderBy('sincronizado_en')->get();
        foreach ($cuentas as $c) {
            if (microtime(true) > Meta::$hasta) break;
            Meta::$avisos = [];
            try {
                $n += Meta::sincronizar($c, (int) $this->option('limite'), true);
            } catch (Throwable $e) {
                // Token vencido o permisos quitados: la cuenta queda marcada para que el negocio la vuelva a conectar
                $m = Meta::sinLlaves($e->getMessage());
                if (preg_match('/token|session|permission|OAuthException|190/i', $m)) {
                    $c->update(['estado' => 'error', 'error' => mb_substr($m, 0, 250)]);
                }
            }
        }
        TiendaActual::set(null);
        $this->line("Mensajes nuevos: $n");
        return self::SUCCESS;
    }
}
