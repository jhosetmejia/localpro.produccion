<?php

namespace App\Console\Commands;

use App\Mail\ProspectoMail;
use App\Models\Prospecto;
use App\Models\ProspectoEnvio;
use App\Models\User;
use App\Support\Prospectos;
use Illuminate\Console\Command;

/**
 * Envía la secuencia comercial a los prospectos, repartida en el día (corre cada 15 minutos de 8 a 5, lunes a viernes)
 * y sin pasar el límite diario. Primero los seguimientos que tocan hoy, luego los nuevos.
 * Días entre correos: 1.º → 4 días → 2.º → 6 días → 3.º. Se detiene si responde, se registra o pide baja.
 */
class ProspectosEnviar extends Command
{
    protected $signature = 'prospectos:enviar {--simular : Muestra a quién se escribiría, sin enviar} {--todo : Envía de una vez hasta el límite del día}';

    protected $description = 'Envía los correos del día a los prospectos (firmas contables)';

    public const ESPERA = [1 => 4, 2 => 6];
    private const CORRIDAS_DIA = 36; // cada 15 minutos entre 8:00 y 16:45

    public function handle(): int
    {
        $simular = (bool) $this->option('simular');
        if (!$simular && !Prospectos::activo()) {
            $this->line('Envío automático apagado (actívelo en Administrador → Prospectos).');
            return self::SUCCESS;
        }
        if (!$simular && !Prospectos::buzonListo()) {
            $this->warn('Falta el buzón de envío (correo y clave de aplicación) en Administrador → Prospectos.');
            return self::SUCCESS;
        }

        $limite = Prospectos::limiteDiario();
        $hoy = ProspectoEnvio::whereDate('created_at', today())->where('estado', 'enviado')->count();
        $cupo = $limite - $hoy;
        if ($cupo <= 0) {
            $this->line("Ya se enviaron $hoy de $limite correos hoy.");
            return self::SUCCESS;
        }
        $tanda = $this->option('todo') || $simular ? $cupo : min($cupo, (int) ceil($limite / self::CORRIDAS_DIA));

        $lista = Prospecto::where('estado', 'en_secuencia')->whereNotNull('correo')->where('paso', '<', 3)
            ->whereDate('proximo_envio', '<=', today())->orderBy('proximo_envio')->limit($tanda)->get();
        if ($lista->count() < $tanda) {
            $lista = $lista->concat(Prospecto::where('estado', 'nuevo')->whereNotNull('correo')->orderBy('id')
                ->limit($tanda - $lista->count())->get());
        }
        if ($lista->isEmpty()) {
            $this->line('No hay prospectos por escribir hoy. Se buscan más con prospectos:buscar.');
            return self::SUCCESS;
        }

        [$deCorreo, $deNombre] = Prospectos::remitente();
        $mailer = $simular ? null : Prospectos::mailer();
        $ok = 0;
        foreach ($lista as $p) {
            // Ya es usuario de LocalProo: no se le vende
            if (User::where('email', $p->correo)->exists()) {
                $p->update(['estado' => 'registrado', 'proximo_envio' => null]);
                continue;
            }
            $paso = $p->paso + 1;
            $mail = new ProspectoMail($p, $paso, $deCorreo, $deNombre);
            if ($simular) {
                $this->line("[simulado] {$p->correo} · paso $paso · {$mail->asunto()} · {$p->empresa}");
                continue;
            }
            try {
                $mailer->to($p->correo)->send($mail);
                ProspectoEnvio::create(['prospecto_id' => $p->id, 'paso' => $paso, 'correo' => $p->correo, 'asunto' => $mail->asunto(), 'estado' => 'enviado']);
                $p->update([
                    'paso' => $paso, 'ultimo_envio' => now(),
                    'estado' => $paso >= 3 ? 'terminado' : 'en_secuencia',
                    'proximo_envio' => $paso >= 3 ? null : today()->addDays(self::ESPERA[$paso]),
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $msg = mb_substr($e->getMessage(), 0, 480);
                ProspectoEnvio::create(['prospecto_id' => $p->id, 'paso' => $paso, 'correo' => $p->correo, 'asunto' => $mail->asunto(), 'estado' => 'fallido', 'detalle' => $msg]);
                // Dirección que no existe: no se insiste. Si falla el buzón (clave, límite de Gmail), se para la tanda.
                if (preg_match('/\b55[0-3]\b|does not exist|no such user|user unknown|invalid recipient/i', $msg)) {
                    $p->update(['estado' => 'rebotado', 'proximo_envio' => null]);
                    continue;
                }
                $this->error("No se pudo enviar a {$p->correo}: $msg");
                break;
            }
        }
        $this->info("$ok correos enviados en esta tanda · " . ($hoy + $ok) . " de $limite hoy.");
        return self::SUCCESS;
    }
}
