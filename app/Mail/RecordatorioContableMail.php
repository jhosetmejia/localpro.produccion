<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

// Recordatorio del calendario tributario para el contador: vencimientos de hoy y de los próximos días
class RecordatorioContableMail extends Mailable
{
    use Queueable;

    /** @param array $vencimientos filas de CalendarioTributario::proximos */
    public function __construct(public string $nombre, public array $vencimientos)
    {
    }

    public function asunto(): string
    {
        $hoy = count(array_filter($this->vencimientos, fn ($v) => $v['dias'] === 0));
        $n = count($this->vencimientos);
        return $hoy
            ? "Vencen hoy: $hoy " . ($hoy === 1 ? 'declaración' : 'declaraciones') . ' de sus clientes'
            : "Recordatorio: $n " . ($n === 1 ? 'declaración vence' : 'declaraciones vencen') . ' en 3 días';
    }

    public function build()
    {
        return $this->subject($this->asunto())->view('emails.recordatorio-contable', ['nombre' => $this->nombre, 'lista' => $this->vencimientos]);
    }
}
