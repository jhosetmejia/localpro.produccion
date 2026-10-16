<?php

// Páginas del blog por tipo de cliente (/blog/para/{rol}): cada una atrae búsquedas de ese público
// y lo lleva a registrarse con el tipo de negocio ya elegido.
return [
    'contadores' => [
        'nombre' => 'Contadores',
        'seo_titulo' => 'Software para contadores: DIAN, IVA y 350 | LocalProo',
        'descripcion' => 'Pruebe gratis la herramienta para contadores: cruce de facturas DIAN con la contabilidad, IVA, retenciones, borrador del 350 y calendario tributario.',
        'h1' => 'LocalProo para contadores y analistas contables',
        'intro' => 'Deje de cuadrar factura por factura en Excel. Suba el listado de la DIAN y la contabilidad de su cliente y en minutos sabe qué falta, qué no cuadra y qué vence.',
        'beneficios' => [
            'Facturas de la DIAN sin contabilizar y contabilizadas sin factura electrónica',
            'Diferencias de IVA y de base, y retenciones que faltaron o sobraron según la tabla vigente',
            'Borrador del Formulario 350, resumen de IVA, Excel de papel de trabajo y certificados de retención',
            'Calendario tributario de todos sus clientes por NIT, con aviso al correo',
            'Funciona con Contapyme, Siigo, World Office o cualquier Excel',
        ],
        'tipo' => 'contable',
        'cta' => 'Empezar mi prueba gratis como contador',
        'nota' => 'Pruébelo con datos de ejemplo apenas entre, sin subir nada suyo.',
        'icono' => 'fa-calculator',
        'imagen' => 'img/og/contadores.png',
    ],
    'funerarias' => [
        'nombre' => 'Funerarias',
        'seo_titulo' => 'Software para funerarias y obituarios digitales | LocalProo',
        'descripcion' => 'Obituarios digitales en el televisor de cada sala de velación, plantillas listas, descarga para redes y además ventas, caja e inventario para su funeraria.',
        'h1' => 'LocalProo para funerarias',
        'intro' => 'El obituario sale solo en el televisor de cada sala, con plantillas dignas y listas para usar, y su funeraria lleva sus ventas y planes en el mismo sistema.',
        'beneficios' => [
            'Obituario en el televisor de cada sala de velación, sin equipos especiales',
            'Plantillas prediseñadas con flores, palomas y fondos; solo cambia nombre y foto',
            'Descarga en imagen o video para WhatsApp y redes sociales',
            'Planes y servicios sin inventario, caja y reportes',
        ],
        'tipo' => 'funeraria',
        'cta' => 'Empezar la prueba gratis de mi funeraria',
        'nota' => 'Prueba gratis, sin tarjeta.',
        'icono' => 'fa-dove',
        'imagen' => 'img/og/funerarias.png',
    ],
    'negocios' => [
        'nombre' => 'Negocios',
        'seo_titulo' => 'Programa de ventas, inventario y tienda en línea | LocalProo',
        'descripcion' => 'Sistema POS para pequeños negocios en Colombia: ventas, caja, inventario, factura por WhatsApp, tienda en línea y página de enlaces. Desde el celular.',
        'h1' => 'LocalProo para su negocio',
        'intro' => 'Venda, controle su inventario y su caja desde el celular o el computador, con su tienda en línea y su página de enlaces incluidas.',
        'beneficios' => [
            'Caja (POS) rápida con lector de código de barras y factura por WhatsApp',
            'Inventario que se descuenta solo y avisa lo que se acaba',
            'Tienda en línea con pedidos por WhatsApp y que aparece en Google',
            'Página de enlaces tipo Linktree para Instagram y TikTok',
            'Nómina interna o electrónica DIAN si tiene empleados',
        ],
        'tipo' => null,
        'cta' => 'Empezar la prueba gratis de mi negocio',
        'nota' => 'Prueba gratis, sin tarjeta. Funciona en el celular.',
        'icono' => 'fa-store',
        'imagen' => 'img/og/negocios.png',
    ],
];
