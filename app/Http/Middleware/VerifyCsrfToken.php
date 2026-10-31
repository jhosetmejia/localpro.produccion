<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhooks/wompi',
        'webhooks/meta',
        'baja/*',
        '360/codigo', // código del operador del 360 (mismo caso que 360/cliente)
        '360/cliente', // formulario público de la máquina 360: queda abierto toda la noche, sin sesión que proteger
        //
    ];
}
