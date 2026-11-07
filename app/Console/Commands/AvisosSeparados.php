<?php

namespace App\Console\Commands;

use App\Http\Controllers\separadoController;
use App\Models\Separado;
use App\Models\Tienda;
use App\Support\AvisosPlan;
use App\Support\SeparadosPorVencer;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Correo al cliente: "Su separado está por vencer" (3 días antes), "vence hoy" y "venció ayer"
class AvisosSeparados extends Command
{
    protected $signature = 'separados:avisos {--simular : Muestra a quién se enviaría, sin enviar}';

    protected $description = 'Avisa por correo a los clientes que su separado está por vencer';

    public function handle(): int
    {
        $simular = $this->option('simular') || !AvisosPlan::correoConfigurado();
        if (!AvisosPlan::correoConfigurado()) $this->warn('Correo SMTP sin configurar: solo se simula.');
        $enviados = 0; $total = 0;
        $avisos = [
            'previo' => [today()->addDays(SeparadosPorVencer::DIAS_CORREO), 'avisado_previo_at'],
            'hoy' => [today(), 'avisado_hoy_at'],
            'vencido' => [today()->subDay(), 'avisado_vencido_at'],
        ];
        $ids = Separado::withoutGlobalScope('tienda')->where('estado', 'activo')->whereNotNull('vence_en')
            ->whereDate('vence_en', '>=', today()->subDay())->whereDate('vence_en', '<=', today()->addDays(SeparadosPorVencer::DIAS_CORREO))
            ->distinct()->pluck('tienda_id');

        foreach (Tienda::whereIn('id', $ids)->where('estado', 'activa')->get() as $t) {
            if ($t->plan_vencido) continue;
            TiendaActual::como($t->id, function () use ($t, $avisos, $simular, &$enviados, &$total) {
                foreach ($avisos as $tipo => [$fecha, $col]) {
                    $lista = Separado::with('cliente.persona')->where('estado', 'activo')->whereDate('vence_en', $fecha)
                        ->whereRaw('total - abonado > 0.009')->whereNull($col)->get();
                    foreach ($lista as $s) {
                        $correo = trim((string) ($s->cliente_correo ?: $s->cliente?->persona?->email));
                        if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) continue;
                        $total++;
                        if ($simular) { $this->line("[sin enviar] {$t->nombre} · {$s->numero} · {$tipo} → {$correo}"); continue; }
                        try {
                            $html = view('emails.separado-por-vencer', ['t' => $t, 's' => $s, 'tipo' => $tipo, 'url' => separadoController::urlPublica($s)])->render();
                            $asunto = match ($tipo) {
                                'previo' => 'Su separado en ' . $t->nombre . ' está por vencer',
                                'hoy' => 'Hoy vence su separado en ' . $t->nombre,
                                default => 'Su separado en ' . $t->nombre . ' venció: acérquese a pagar',
                            };
                            Mail::html($html, function ($m) use ($correo, $asunto, $t) {
                                $m->to($correo)->subject($asunto);
                                if ($t->email ?? null) $m->replyTo($t->email, $t->nombre);
                            });
                            $s->forceFill([$col => now()])->save();
                            $enviados++;
                        } catch (\Throwable $e) {
                            Log::warning('Aviso de separado no enviado', ['separado' => $s->id, 'error' => $e->getMessage()]);
                        }
                    }
                }
            });
        }
        $this->info("$total avisos de separados por enviar · $enviados enviados.");
        return self::SUCCESS;
    }
}
