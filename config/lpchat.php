<?php

// 2026-11-17 (Jhoset): LP Chat (LocalProChat). Sistemas conectados que pueden entrar a sus usuarios
// con un enlace firmado (sin registrarse) y mandar sus perfiles a "Conocer gente".
// La misma clave va en el .env del otro sistema (LPCHAT_SECRETO).
return [
    'origenes' => [
        'pfn' => ['nombre' => 'Pasto Fight Night', 'secreto' => env('LPCHAT_SECRETO_PFN'), 'volver' => 'https://pastofightnight.com'],
        'nes' => ['nombre' => 'Nariño ExpoShow', 'secreto' => env('LPCHAT_SECRETO_NES'), 'volver' => 'https://narinoexposhow.com'],
    ],
    // 2026-11-24 (Jhoset): nombre con el que sale la cuenta del administrador (la misma de NES y PFN) en LP Chat.
    'admin_nombre' => env('LPCHAT_ADMIN_NOMBRE', 'Jhoset Mejía'),
    // Máximo de mensajes que alguien puede mandar mientras su solicitud no ha sido aceptada
    'max_solicitud' => 3,
    // Plan Pro: saluda hasta 100 personas nuevas al día en Conocer gente (gratis: 5)
    'pro' => [
        'precio' => 5000,
        'limite' => 100,
        'dias' => 30,
        'nequi' => env('LPCHAT_NEQUI', '304 263 4953'),
        'whatsapp' => env('LPCHAT_WHATSAPP', '573042634953'),
    ],
];
