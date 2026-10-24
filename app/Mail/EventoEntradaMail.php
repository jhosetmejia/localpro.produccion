<?php

namespace App\Mail;

use App\Models\EventoEntrada;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

// Al aprobar el pago: "Su entrada está lista" con el enlace para ver el QR
class EventoEntradaMail extends Mailable
{
    use Queueable;

    public function __construct(public EventoEntrada $entrada)
    {
    }

    public function build()
    {
        $ev = $this->entrada->evento;
        $tienda = \App\Models\Tienda::find($this->entrada->tienda_id);
        return $this->subject('Tu entrada para ' . $ev->nombre . ' está lista')
            ->view('emails.evento-entrada', [
                'e' => $this->entrada, 'ev' => $ev, 'a' => $this->entrada->asistente, 'tienda' => $tienda,
                'cantidad' => $this->entrada->delGrupo()->where('estado', 'aprobada')->count(),
                'url' => route('mis-entradas.ver', $this->entrada->uuid),
            ]);
    }
}
