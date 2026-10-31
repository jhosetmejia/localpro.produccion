<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

// Correo de bienvenida al crear la cuenta: prueba gratis, cómo entrar, primeros pasos, cómo recuperar la contraseña y soporte
class BienvenidaMail extends Mailable
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function build()
    {
        $t = $this->user->tienda;
        $nombre = trim(strtok(trim((string) $this->user->name), ' ')) ?: 'emprendedor';
        return $this->subject('¡Bienvenido a LocalProo, ' . $nombre . '! 🎉 Su cuenta ya está lista')
            ->view('emails.bienvenida', [
                'nombre' => $nombre,
                'user' => $this->user,
                't' => $t,
                'contador' => $t?->tipo_negocio === 'contable',
                'soporte' => preg_replace('/\D/', '', (string) \App\Models\Ajuste::valor('soporte_whatsapp')),
            ]);
    }
}
