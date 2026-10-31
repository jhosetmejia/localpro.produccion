<?php

/*
 * Página pública "Funciones de LocalProo" (/funciones): todas las herramientas del sistema explicadas con calma.
 * Cada grupo es una sección con su ancla (#id). Cada función: [icono, título, descripción, [detalles], slug de guía del blog (opcional)].
 * Al agregar una herramienta nueva al sistema, agréguela aquí también (sale en la página, en el sitemap y en los datos para Google).
 */

return [
    'grupos' => [
        'ventas' => [
            'titulo' => 'Ventas y caja (POS)',
            'icono' => 'fa-cash-register',
            'intro' => 'Venda rápido desde el celular o el computador, con la caja siempre cuadrada.',
            'items' => [
                ['fa-cash-register', 'Caja POS rápida', 'Registre ventas en segundos buscando el producto por nombre o escaneando su código. Funciona en el celular, la tableta o el computador.', [
                    'Varios medios de pago en una misma venta (efectivo, Nequi, Daviplata, transferencia, tarjeta…)',
                    'Cálculo automático del cambio y descuentos por producto o por venta',
                    'Tiquete o factura enviada al cliente por WhatsApp',
                    'Apertura y cierre de caja con el efectivo esperado y la diferencia',
                ], null],
                ['fa-barcode', 'Escanear con la cámara del celular', 'No necesita lector: la cámara del celular lee los códigos de barras y QR de sus productos.', [
                    'Venta escaneando en la caja',
                    'Impresión de etiquetas con código de barras para la mercancía que no lo trae',
                ], 'codigo-de-barras-escanee-con-el-celular'],
                ['fa-rotate-left', 'Corregir ventas y ventas olvidadas', '¿Se equivocó? Corrija una venta o registre una que se le olvidó y la caja vuelve a cuadrar, con constancia de quién lo hizo.', [
                    'Historial de cambios de cada venta',
                    'Devoluciones de clientes que reponen el inventario',
                ], 'corregir-venta-y-venta-olvidada-caja-cuadrada'],
                ['fa-box-archive', 'Separados y abonos', 'El cliente separa la prenda y paga por partes. Cada abono con su recibo y lo que le falta.', [
                    'La mercancía queda apartada a nombre del cliente',
                    'Recibo de cada abono por WhatsApp',
                ], null],
                ['fa-file-signature', 'Cotizaciones en PDF', 'Arme cotizaciones con sus productos o con líneas libres, con el logo de su negocio, y envíelas por WhatsApp con un enlace.', [
                    'Precio del producto o cambiado a mano',
                    'Para alquileres: muestra la disponibilidad en las fechas pedidas',
                ], null],
                ['fa-ruler-combined', 'Venta por metro, kilo o litro', 'Telas, vidrio, cables, carnes o granos: venda 1,5 m o 0,250 kg y el inventario queda exacto.', [], null],
                ['fa-bullseye', 'Vendedores con metas', 'Cada vendedor tiene su usuario y ve sus ventas y cuánto le falta para su meta, sin ver las ganancias del negocio.', [], null],
            ],
        ],
        'inventario' => [
            'titulo' => 'Inventario y productos',
            'icono' => 'fa-boxes-stacked',
            'intro' => 'Sepa siempre qué tiene, qué se acaba y cuánto le cuesta.',
            'items' => [
                ['fa-boxes-stacked', 'Stock actualizado al instante', 'Cada venta descuenta el inventario y cada compra lo suma, con el costo de cada entrada.', [
                    'Productos con variantes de talla y color',
                    'Alertas de lo que se está acabando',
                    'Categorías, marcas y presentaciones',
                ], null],
                ['fa-clipboard-check', 'Conteo de inventario con el celular', 'Escanee lo que tiene en el estante, toque "Terminé" y el sistema le dice qué falta y qué sobra.', [], 'conteo-de-inventario-con-el-celular'],
                ['fa-tags', 'Etiquetas para mercancía nueva', 'Imprima etiquetas con código de barras antes de crear los productos y venda escaneando desde el primer día.', [], 'etiquetas-codigo-de-barras-mercancia-nueva'],
                ['fa-file-excel', 'Importar desde Excel o Google', 'Suba productos, clientes y proveedores desde Excel, CSV o una hoja de Google en un minuto.', [
                    'También puede enviarnos el archivo y nuestro equipo se lo carga',
                ], 'importar-proveedores-excel-google-sheets'],
                ['fa-truck-ramp-box', 'Compras y proveedores', 'Registre lo que le compra a cada proveedor: la mercancía entra al inventario con su costo.', [], null],
                ['fa-image', 'Fotos livianas automáticas', 'Suba las fotos como salgan del celular: LocalProo las optimiza para que carguen rápido y se vean nítidas.', [], 'fotos-livianas-automaticas'],
            ],
        ],
        'tienda-en-linea' => [
            'titulo' => 'Tienda en línea y redes',
            'icono' => 'fa-globe',
            'intro' => 'Su negocio en internet sin pagar una página web aparte.',
            'items' => [
                ['fa-globe', 'Tienda en línea que aparece en Google', 'Su propia página con los productos por categorías y el botón "Comprar por WhatsApp". Cada producto tiene su página para que lo encuentren en Google.', [
                    'Muestra solo lo que tiene disponible',
                    'Promociones y liquidaciones',
                ], 'tienda-en-linea-solo-productos-con-existencias'],
                ['fa-store', 'Marketplace LocalProo', 'Sus productos también salen en el Marketplace, al lado de las tiendas locales de su ciudad, sin comisión por venta.', [], 'marketplace-localproo-tiendas-locales'],
                ['fa-utensils', 'Carta virtual para restaurantes', 'Menú digital con diseño bonito, código QR para las mesas y pedidos por WhatsApp.', [], 'carta-virtual-para-restaurantes-con-pedidos-por-whatsapp'],
                ['fa-link', 'Mis enlaces (tipo Linktree)', 'Una página con todos los enlaces de su negocio (WhatsApp, redes, tienda, ubicación) para poner en la biografía de Instagram o TikTok.', [], 'linktree-gratis-para-negocios-mis-enlaces'],
                ['fa-hashtag', 'Facebook e Instagram en un solo lugar', 'Responda los chats de Messenger e Instagram desde LocalProo, envíe productos con foto y precio en un toque y publique en las dos redes al tiempo.', [], null],
            ],
        ],
        'clientes' => [
            'titulo' => 'Clientes y WhatsApp',
            'icono' => 'fa-people-group',
            'intro' => 'Conozca a sus clientes y véndales otra vez.',
            'items' => [
                ['fa-address-book', 'Clientes con historial', 'Guarde sus clientes con lo que han comprado. Agréguelos desde los contactos del celular o súbalos desde Excel.', [], null],
                ['fa-whatsapp', 'WhatsApp con todo su equipo', 'Comparta la base de clientes con sus vendedores para que todos escriban por WhatsApp sin repetir a nadie.', [
                    'Lista de clientes para contactar cada día',
                    'Mensajes y promociones listos para enviar',
                ], 'whatsapp-marketing-compartir-base-de-datos-vendedores'],
                ['fa-gift', 'Comparta y gane', 'Recomiende LocalProo a otros empresarios con su código y gane descuentos en su plan.', [], 'comparta-localproo-y-gane-descuentos-plan'],
            ],
        ],
        'administracion' => [
            'titulo' => 'Cuentas, equipo y reportes',
            'icono' => 'fa-chart-line',
            'intro' => 'Lleve las cuentas claras y controle quién hace qué.',
            'items' => [
                ['fa-chart-line', 'Reportes y ganancias', 'Sepa cuánto vendió, cuánto ganó, su hora pico y sus productos estrella. Exporte todo a Excel.', [], null],
                ['fa-money-bill-transfer', 'Control de egresos', 'Registre los gastos del negocio (arriendo, servicios, pagos) y sepa en qué se va la plata.', [], 'control-de-gastos-egresos-negocio'],
                ['fa-user-shield', 'Roles y permisos', 'Cada persona ve solo lo que necesita: caja, inventario, reportes o configuración. Usted decide qué puede hacer cada empleado.', [], 'roles-y-permisos-para-el-equipo-de-su-negocio'],
                ['fa-users-gear', 'Nómina interna o electrónica', 'Liquide la quincena o el mes de sus empleados y saque los desprendibles. Solo interna o con nómina electrónica DIAN.', [], 'nomina-interna-o-electronica-dian'],
                ['fa-file-invoice-dollar', 'Factura electrónica DIAN', 'IVA separado en cada venta, resoluciones de numeración con aviso de vencimiento, POS electrónico y factura electrónica con CUFE y código QR, a través de un proveedor autorizado.', [], null],
                ['fa-sliders', 'Personalizar menú', 'Esconda lo que su negocio no usa y deje el sistema solo con lo que necesita.', [], 'personalizar-menu-solo-lo-que-usa'],
                ['fa-mobile-screen', 'App en su celular', 'Instálela como app en el celular sin tiendas de aplicaciones, o úsela desde el computador. Todo queda guardado en la nube.', [], null],
            ],
        ],
        'por-negocio' => [
            'titulo' => 'Herramientas por tipo de negocio',
            'icono' => 'fa-shapes',
            'intro' => 'Al crear la cuenta elige su tipo de negocio y se activan las herramientas que le sirven.',
            'items' => [
                ['fa-burger', 'Restaurantes: pedidos de mesero, caja y cocina', 'El mesero toma el pedido en el celular, la cocina lo ve en su pantalla y la caja cobra. Todo conectado.', [], 'sistema-de-pedidos-para-restaurantes-mesero-caja-cocina'],
                ['fa-ticket-simple', 'Tiquetera de almuerzos', 'Venda almuerzos por adelantado y descuéntelos con un toque cada día.', [], 'tiquetera-de-almuerzos-para-restaurantes'],
                ['fa-scissors', 'Negocios de servicios', 'Peluquerías, barberías, talleres y reparaciones: venda servicios sin llevar inventario.', [], 'software-para-negocios-de-servicios-sin-inventario'],
                ['fa-calendar-days', 'Alquileres', 'Sonido, trajes, mobiliario, carpas y equipos: cotice, vea la disponibilidad por fecha y controle lo que sale y vuelve.', [], 'como-crear-mi-negocio-de-alquiler-en-localproo'],
                ['fa-camera-rotate', 'Plataforma 360', 'App para su plataforma 360: graba y edita el video en el celular con cámara lenta, logo del evento y música, y guarda el WhatsApp de cada invitado.', [], 'alquiler-de-plataforma-360-app-para-videos-360'],
                ['fa-user-doctor', 'Consultorios: historia clínica', 'Agenda de citas con recordatorio por WhatsApp, historia clínica, fórmulas y firma desde el celular.', [], 'historia-clinica-para-consultorios-particulares'],
                ['fa-graduation-cap', 'Institutos y academias', 'Inscripciones, matrículas de contado o financiadas, cuotas y cartera de estudiantes.', [], 'software-para-institutos-tecnicos-matriculas-y-cuotas'],
                ['fa-dove', 'Funerarias: obituarios digitales', 'Obituarios con foto, lugar de velación y el eslogan de la funeraria, para compartir y para el televisor de cada sala.', [], 'obituarios-digitales-para-funerarias'],
                ['fa-tv', 'Pantallas para televisores', 'Muestre promociones, productos e imágenes en los televisores de su negocio, manejados desde LocalProo.', [], null],
                ['fa-ticket', 'Eventos: venta de entradas con QR', 'Página del evento, registro, pago por Nequi con comprobante, aprobación y entrada con QR único que se valida en la puerta.', [], 'venta-de-entradas-con-qr-para-eventos-y-fiestas'],
                ['fa-building', 'Conjuntos residenciales', 'Unidades, cobro de administración, pagos y recibos, cartera, gastos, comunicados, PQRS y reservas.', [], null],
                ['fa-calculator', 'Contadores', 'Cruce de facturas de la DIAN con la contabilidad de sus clientes, IVA, retenciones, borrador del formulario 350 y calendario tributario con avisos.', [], 'eres-contador-localproo-lo-hace-por-ti'],
            ],
        ],
    ],

    'faq' => [
        ['¿LocalProo sirve para cualquier tipo de negocio?', 'Sí. Funciona para tiendas de ropa, calzado, minimercados, ferreterías, restaurantes, cafeterías, negocios de servicios, alquileres, consultorios, institutos, funerarias, eventos, conjuntos residenciales y contadores. Al crear la cuenta elige su tipo de negocio y se activan las herramientas que le sirven.'],
        ['¿Necesito instalar algún programa?', 'No. LocalProo funciona en la nube desde el navegador del celular o del computador, y se puede instalar como app en el celular sin pasar por las tiendas de aplicaciones.'],
        ['¿Puedo probarlo gratis?', 'Sí. Al crear la cuenta tiene una prueba gratis con todas las funciones. Si le sirve, sigue con el plan anual.'],
        ['¿Necesito lector de código de barras?', 'No es obligatorio. La cámara del celular lee los códigos de barras de sus productos, y si tiene un lector también funciona.'],
        ['¿Puedo subir mis productos desde Excel?', 'Sí. Puede importar productos, clientes y proveedores desde Excel, CSV o una hoja de Google. También puede enviarnos el archivo y nuestro equipo se lo carga.'],
        ['¿Mi tienda en línea aparece en Google?', 'Sí. Cada negocio tiene su página con sus productos y cada producto tiene su propia página, preparada para que Google la encuentre.'],
    ],
];
