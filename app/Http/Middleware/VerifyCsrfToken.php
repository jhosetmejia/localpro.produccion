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
        'opinion', // botón Opinar: LocalProCut queda abierto horas (la sesión vence) y no hay nada que proteger
        'opinion/error', // errores del navegador que se reportan solos
        'cut/visita', // contador de LocalProCut (sin sesión que proteger)
        'cut/evento',
        'visita/latido', // latido del contador de visitas
        'asistente/ia', 'asistente/voz', // chat de Mía: la página puede llevar horas abierta (sesión vencida = 419) y solo pregunta
        //
    ];
}
