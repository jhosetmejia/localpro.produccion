<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\Tienda;
use App\Support\AvisosPlan;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Correo a los pacientes con cita mañana: fecha, hora y enlace para confirmar o cancelar
class RecordatoriosCitas extends Command
{
    protected $signature = 'consultorio:recordatorios {--simular : Muestra a quién se enviaría, sin enviar}';

    protected $description = 'Envía el recordatorio de la cita de mañana a los pacientes con correo';

    public function handle(): int
    {
        $simular = $this->option('simular') || !AvisosPlan::correoConfigurado();
        $manana = today()->addDay()->toDateString();
        $enviados = 0; $total = 0;
        foreach (Tienda::where('mod_consultorio', true)->where('estado', 'activa')->get() as $t) {
            if ($t->plan_vencido) continue;
            TiendaActual::como($t->id, function () use ($t, $manana, $simular, &$enviados, &$total) {
                $citas = Cita::with('paciente')->whereDate('fecha', $manana)->whereIn('estado', ['programada', 'confirmada'])->whereNull('recordada_at')->get();
                foreach ($citas as $c) {
                    $correo = $c->paciente?->correo;
                    if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) continue;
                    $total++;
                    if ($simular) { $this->line("[sin enviar] {$t->nombre} → {$correo} · {$c->hora_corta}"); continue; }
                    try {
                        $html = view('emails.cita-recordatorio', ['t' => $t, 'c' => $c])->render();
                        Mail::html($html, fn ($m) => $m->to($correo)->subject('Recordatorio de su cita en ' . $t->nombre . ' mañana a las ' . $c->hora_corta));
                        $c->forceFill(['recordada_at' => now()])->save();
                        $enviados++;
                    } catch (\Throwable $e) {
                        Log::warning('Recordatorio de cita no enviado', ['cita' => $c->id, 'error' => $e->getMessage()]);
                    }
                }
            });
        }
        $this->info("$total citas de mañana con correo · $enviados recordatorios enviados.");
        return self::SUCCESS;
    }
}
