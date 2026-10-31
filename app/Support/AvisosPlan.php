<?php

namespace App\Support;

use App\Console\Commands\DepurarPruebas;
use App\Mail\AvisoPlanMail;
use App\Models\AvisoPlan;
use App\Models\PagoSuscripcion;
use App\Models\Tienda;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos de fin de prueba y la fecha en que se borra la información.
 * Regla: al terminar la prueba se bloquea el sistema y se muestra "Pague su plan". Los 15 días para pagar
 * se cuentan desde que se le NOTIFICÓ (correo enviado o pantalla vista por el negocio), nunca antes.
 */
class AvisosPlan
{
    // Está en prueba gratis (o sin plan) y nunca ha pagado: a este negocio se le puede borrar la información
    public static function esPrueba(Tienda $t): bool
    {
        $t->loadMissing('plan');
        if ($t->plan && (float) $t->plan->precio > 0) {
            return false;
        }
        return !$t->pagos()->where('estado', 'aprobado')->where('referencia', 'not like', PagoSuscripcion::PREFIJO_PRUEBA . '%')->exists();
    }

    // Primer aviso de "prueba terminada" que efectivamente le llegó (correo enviado o pantalla vista) en este periodo
    public static function primeraNotificacion(Tienda $t): ?AvisoPlan
    {
        if (!$t->plan_vence) {
            return null;
        }
        return AvisoPlan::where('tienda_id', $t->id)->where('tipo', 'vencida')
            ->whereDate('plan_vence', $t->plan_vence)->whereIn('estado', ['enviado', 'visto'])
            ->orderBy('created_at')->first();
    }

    // Día en que se borra la información (si aún no se le ha notificado, se calcula como si fuera hoy)
    public static function fechaBorrado(Tienda $t): ?Carbon
    {
        if (!$t->plan_vence || !self::esPrueba($t) || !DepurarPruebas::activo()) {
            return null;
        }
        $dias = DepurarPruebas::diasGracia();
        $aviso = self::primeraNotificacion($t);
        $desde = $aviso ? $aviso->created_at->copy()->startOfDay() : today();
        $minimo = $t->plan_vence->copy()->addDays($dias + 1); // nunca antes de 15 días después de vencer
        return $desde->copy()->addDays($dias)->max($minimo);
    }

    // El dueño/administrador vio la pantalla de "Pague su plan": queda registrado una vez por periodo
    public static function registrarVisto(Tienda $t, $user, ?string $ip): void
    {
        if (!$t->plan_vencido || !self::esPrueba($t)) {
            return;
        }
        $ya = AvisoPlan::where('tienda_id', $t->id)->where('tipo', 'vencida')->where('canal', 'sistema')
            ->whereDate('plan_vence', $t->plan_vence)->exists();
        if (!$ya) {
            AvisoPlan::create([
                'tienda_id' => $t->id, 'tipo' => 'vencida', 'canal' => 'sistema', 'estado' => 'visto',
                'plan_vence' => $t->plan_vence, 'fecha_borrado' => self::fechaBorrado($t),
                'destinatario' => $user?->email, 'user_id' => $user?->id, 'ip' => $ip,
                'asunto' => 'Pantalla "Su prueba gratis terminó" vista en el sistema',
            ]);
        }
    }

    // Ya se envió (con éxito) este tipo de correo en el periodo actual
    public static function yaEnviado(Tienda $t, string $tipo): bool
    {
        return AvisoPlan::where('tienda_id', $t->id)->where('tipo', $tipo)->where('canal', 'correo')
            ->where('estado', 'enviado')->whereDate('plan_vence', $t->plan_vence)->exists();
    }

    // Correos del negocio y de sus dueños/administradores
    public static function destinatarios(Tienda $t): array
    {
        $correos = [$t->email];
        foreach ($t->usuarios()->get() as $u) {
            if (method_exists($u, 'hasAnyRole') && $u->hasAnyRole(['dueno', 'administrador'])) {
                $correos[] = $u->email;
            }
        }
        return array_values(array_unique(array_filter(array_map(fn ($c) => strtolower(trim((string) $c)), $correos), fn ($c) => filter_var($c, FILTER_VALIDATE_EMAIL))));
    }

    public static function correoConfigurado(): bool
    {
        return !in_array(config('mail.default'), ['log', 'array', null, ''], true)
            && !in_array(config('mail.mailers.smtp.host'), ['mailhog', 'mailpit'], true);
    }

    /**
     * Envía el correo a cada destinatario y deja constancia de cada envío. Devuelve cuántos se enviaron.
     */
    public static function enviar(Tienda $t, string $tipo, ?Carbon $fechaBorrado = null): int
    {
        $ok = 0;
        $fechaBorrado ??= self::fechaBorrado($t);
        foreach (self::destinatarios($t) as $correo) {
            $mail = new AvisoPlanMail($t, $tipo, $fechaBorrado);
            $registro = [
                'tienda_id' => $t->id, 'tipo' => $tipo, 'canal' => 'correo', 'plan_vence' => $t->plan_vence,
                'fecha_borrado' => $fechaBorrado, 'destinatario' => $correo, 'asunto' => $mail->asunto(),
            ];
            // Sin servidor de correo real no se envía nada: no se registra como enviado (no serviría de constancia)
            if (!self::correoConfigurado()) {
                AvisoPlan::create($registro + ['estado' => 'fallido', 'detalle' => 'El servidor de correo no está configurado (MAIL_MAILER=' . config('mail.default') . ').']);
                continue;
            }
            try {
                Mail::to($correo)->send($mail);
                AvisoPlan::create($registro + ['estado' => 'enviado', 'detalle' => 'Enviado por ' . config('mail.default')]);
                $ok++;
            } catch (\Throwable $e) {
                AvisoPlan::create($registro + ['estado' => 'fallido', 'detalle' => mb_substr($e->getMessage(), 0, 500)]);
                Log::warning('Aviso de plan no enviado', ['tienda' => $t->id, 'tipo' => $tipo, 'correo' => $correo, 'error' => $e->getMessage()]);
            }
        }
        return $ok;
    }
}
