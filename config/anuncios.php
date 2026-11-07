<?php

// Anuncios de LocalProo para todos los usuarios (salen en la campanita de notificaciones).
// Para mandar uno nuevo: agregue una entrada arriba con una 'clave' nueva (no se repite) y suba el cambio.
// 'desde' / 'hasta' (Y-m-d) son opcionales: el anuncio sale solo entre esas fechas.
return [
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
