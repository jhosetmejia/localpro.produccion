<?php

// Anuncios de LocalProo para todos los usuarios (salen en la campanita de notificaciones).
// Para mandar uno nuevo: agregue una entrada arriba con una 'clave' nueva (no se repite) y suba el cambio.
// 'desde' / 'hasta' (Y-m-d) son opcionales: el anuncio sale solo entre esas fechas.
return [
    [
        'clave' => 'anuncio-lpchat-2026-11',
        'icono' => 'fa-comments',
        'color' => 'verde',
        'texto' => '<b>¡Nuevo! LP Chat</b>, la red social nariñense: chatea como en WhatsApp, sube estados y conoce gente. Y tu negocio ya aparece en <b>Empresas</b> para que tus clientes te escriban.',
        'cuando' => 'Novedad de LocalProo',
        'url' => '/chat',
        'desde' => '2026-11-17',
        'hasta' => '2027-01-31',
    ],
    [
        'clave' => 'anuncio-mia-2026-11',
        'icono' => 'fa-robot',
        'color' => 'verde',
        'texto' => '<b>¡Hola! Soy Mía 👋</b>, tu asistente virtual de LocalProo. Escríbeme cualquier duda, por texto o por audio, y te colaboro en lo que necesites.',
        'cuando' => 'Novedad de LocalProo',
        'url' => '/?mia',
        'desde' => '2026-11-13',
        'hasta' => '2026-12-31',
    ],
    [
        'clave' => 'anuncio-localprocut-2026-11',
        'icono' => 'fa-scissors',
        'color' => 'morado',
        'texto' => '<b>¡Nuevo!</b> LocalProo te da gratis <b>LocalProCut</b> para que edites tus videos: subtítulos automáticos, transiciones, música y sin marca de agua.',
        'cuando' => 'Novedad de LocalProo',
        'url' => '/cut',
        'desde' => '2026-11-07',
        'hasta' => '2026-12-31',
    ],
];
