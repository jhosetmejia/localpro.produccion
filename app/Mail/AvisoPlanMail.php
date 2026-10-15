<?php

namespace App\Mail;

use App\Models\Plan;
use App\Models\Tienda;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

// Correos de fin de prueba: por vencer, vencida (pague antes de X), último aviso y datos eliminados
class AvisoPlanMail extends Mailable
{
    use Queueable;

    public function __construct(public Tienda $tienda, public string $tipo, public ?Carbon $fechaBorrado = null)
    {
    }

    public function asunto(): string
    {
        $n = $this->tienda->nombre;
        $f = fn (?Carbon $d) => $d ? $d->locale('es')->isoFormat('D [de] MMMM') : '';
        return match ($this->tipo) {
            'por_vencer' => "Su prueba gratis de LocalProo termina el " . $f($this->tienda->plan_vence),
            'vencida' => "Su prueba gratis terminó: active su plan antes del " . $f($this->fechaBorrado?->copy()->subDay()),
            'aviso_final' => "Último aviso: el " . $f($this->fechaBorrado) . " se elimina la información de $n",
            'datos_eliminados' => "Se eliminó la información de $n en LocalProo",
            default => 'Aviso sobre su plan de LocalProo',
        };
    }

    public function build()
    {
        $dueno = $this->tienda->usuarios()->orderBy('id')->first();
        return $this->subject($this->asunto())->view('emails.aviso-plan', [
            't' => $this->tienda,
            'tipo' => $this->tipo,
            'fechaBorrado' => $this->fechaBorrado,
            'urlPago' => $this->tienda->url_pago,
            'planes' => Plan::where('activo', true)->where('precio', '>', 0)->orderBy('orden')->get(),
            'aceptoEl' => $dueno?->terminos_aceptados_at,
        ]);
    }
}
