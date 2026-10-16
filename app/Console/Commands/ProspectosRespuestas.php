<?php

namespace App\Console\Commands;

use App\Models\Prospecto;
use App\Support\Prospectos;
use Illuminate\Console\Command;

/**
 * Revisa la bandeja del buzón de envío (IMAP): si un prospecto respondió, se le deja de escribir y queda
 * marcado "Respondió" para atenderlo; si un correo rebotó (no existe), queda "Correo no existe".
 */
class ProspectosRespuestas extends Command
{
    protected $signature = 'prospectos:respuestas {--dias=3}';

    protected $description = 'Marca los prospectos que respondieron o cuyo correo rebotó';

    public function handle(): int
    {
        if (!function_exists('imap_open')) {
            $this->line('El servidor no tiene la extensión IMAP de PHP: las respuestas se marcan a mano en el panel.');
            return self::SUCCESS;
        }
        if (!Prospectos::buzonListo()) return self::SUCCESS;
        $host = Prospectos::ajuste('imap_host', 'imap.gmail.com');
        $buzon = @imap_open('{' . $host . ':993/imap/ssl}INBOX', Prospectos::ajuste('smtp_usuario'), Prospectos::secreto('smtp_clave'), OP_READONLY, 1);
        if (!$buzon) {
            $this->warn('No se pudo abrir la bandeja: ' . imap_last_error());
            return self::SUCCESS;
        }
        $desde = now()->subDays((int) $this->option('dias'))->format('d-M-Y');
        $ids = imap_search($buzon, 'SINCE "' . $desde . '"') ?: [];
        $respondieron = $rebotes = 0;
        $activos = Prospecto::whereIn('estado', ['en_secuencia', 'terminado'])->whereNotNull('correo')->pluck('id', 'correo');
        foreach ($ids as $n) {
            $h = imap_headerinfo($buzon, $n);
            $de = strtolower(($h->from[0]->mailbox ?? '') . '@' . ($h->from[0]->host ?? ''));
            if (isset($activos[$de])) {
                Prospecto::whereKey($activos[$de])->update(['estado' => 'respondio', 'proximo_envio' => null]);
                $respondieron++;
                continue;
            }
            if (preg_match('/mailer-daemon|postmaster/i', $de)) {
                $cuerpo = strtolower((string) imap_body($buzon, $n, FT_PEEK));
                foreach ($activos as $correo => $id) {
                    if (str_contains($cuerpo, $correo)) {
                        Prospecto::whereKey($id)->update(['estado' => 'rebotado', 'proximo_envio' => null]);
                        $rebotes++;
                    }
                }
            }
        }
        imap_close($buzon);
        $this->info("$respondieron respondieron · $rebotes rebotaron.");
        return self::SUCCESS;
    }
}
