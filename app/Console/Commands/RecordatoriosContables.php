<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioContableMail;
use App\Models\Asesor;
use App\Models\ContableCliente;
use App\Models\Tienda;
use App\Support\AvisosPlan;
use App\Support\CalendarioTributario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Correo diario al contador: declaraciones de sus clientes que vencen hoy o en 3 días y no están marcadas como presentadas
class RecordatoriosContables extends Command
{
    protected $signature = 'contabilidad:recordatorios {--probar : Muestra a quién se enviaría, sin enviar}';

    protected $description = 'Envía a los contadores el recordatorio de vencimientos tributarios de sus clientes';

    public function handle(): int
    {
        $clientes = ContableCliente::withoutGlobalScopes()->where('activo', true)->where('recordatorios', true)->get();
        $lista = array_values(array_filter(
            CalendarioTributario::proximos($clientes, 3, 0, true),
            fn ($v) => !$v['presentado'] && in_array($v['dias'], [0, 3], true)
        ));
        $grupos = collect($lista)->groupBy(fn ($v) => $v['cliente']->tienda_id ? 't' . $v['cliente']->tienda_id : 'a' . $v['cliente']->asesor_id);

        $enviados = 0;
        foreach ($grupos as $clave => $items) {
            [$nombre, $correos] = $this->destino($clave);
            if (!$correos) continue;
            $mail = new RecordatorioContableMail($nombre, $items->all());
            foreach ($correos as $correo) {
                if ($this->option('probar') || !AvisosPlan::correoConfigurado()) {
                    $this->line("[sin enviar] $correo · " . $mail->asunto());
                    continue;
                }
                try {
                    Mail::to($correo)->send($mail);
                    $enviados++;
                } catch (\Throwable $e) {
                    Log::warning('Recordatorio contable no enviado', ['correo' => $correo, 'error' => $e->getMessage()]);
                }
            }
        }
        $this->info(count($lista) . " vencimientos por avisar · $enviados correos enviados.");
        return self::SUCCESS;
    }

    /** [nombre, correos] del dueño de los datos: el negocio (con Contabilidad activa) o el asesor */
    private function destino(string $clave): array
    {
        $id = (int) substr($clave, 1);
        if ($clave[0] === 't') {
            $t = Tienda::find($id);
            if (!$t || !$t->mod_contable || !$t->activa) return ['', []];
            return [$t->nombre, AvisosPlan::destinatarios($t)];
        }
        $a = Asesor::with('user')->find($id);
        $correo = $a?->activo ? strtolower(trim((string) $a->user?->email)) : '';
        return [$a?->user?->name ?? 'Asesor', filter_var($correo, FILTER_VALIDATE_EMAIL) ? [$correo] : []];
    }
}
