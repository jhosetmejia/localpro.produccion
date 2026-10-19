<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

// Código de 6 números para crear una contraseña nueva
class CodigoRecuperarMail extends Mailable
{
    use Queueable;

    public function __construct(public User $user, public string $codigo, public int $minutos)
    {
    }

    public function build()
    {
        return $this->subject("Su código para recuperar la contraseña: {$this->codigo}")
            ->view('emails.codigo-recuperar', [
                'nombre' => $this->user->name,
                'codigo' => $this->codigo,
                'minutos' => $this->minutos,
                'email' => $this->user->email,
            ]);
    }
}
