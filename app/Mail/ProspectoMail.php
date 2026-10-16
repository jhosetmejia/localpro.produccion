<?php

namespace App\Mail;

use App\Models\Prospecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;

// Secuencia comercial para contadores: paso 1 (presentación), 2 (seguimiento) y 3 (cierre)
class ProspectoMail extends Mailable
{
    use Queueable;

    public function __construct(public Prospecto $p, public int $paso, public string $remitenteCorreo = '', public string $remitenteNombre = 'LocalProo')
    {
    }

    public function asunto(): string
    {
        return match ($this->paso) {
            1 => 'Cruce DIAN vs. contabilidad en minutos',
            2 => 'Re: Cruce DIAN vs. contabilidad en minutos',
            default => '¿Lo dejo aquí?',
        };
    }

    public function enlaceRegistro(): string
    {
        return route('registro.contadores.asesor', ['codigo' => 'LOCALPRO']) . '?desde=correo:p' . $this->p->id;
    }

    public function enlaceBaja(): string
    {
        return route('prospectos.baja', $this->p->token);
    }

    public function headers(): Headers
    {
        $baja = '<' . $this->enlaceBaja() . '>' . ($this->remitenteCorreo ? ', <mailto:' . $this->remitenteCorreo . '?subject=baja>' : '');
        return new Headers(text: ['List-Unsubscribe' => $baja, 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function build()
    {
        $datos = ['p' => $this->p, 'paso' => $this->paso, 'registro' => $this->enlaceRegistro(), 'baja' => $this->enlaceBaja(), 'firma' => $this->remitenteNombre];
        $m = $this->subject($this->asunto())->view('emails.prospecto', $datos)->text('emails.prospecto-texto', $datos);
        if ($this->remitenteCorreo) {
            $m->from($this->remitenteCorreo, $this->remitenteNombre)->replyTo($this->remitenteCorreo, $this->remitenteNombre);
        }
        return $m;
    }
}
