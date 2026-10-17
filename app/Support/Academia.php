<?php

namespace App\Support;

/**
 * "Aprende LocalPro": cursos cortos para el dueño del negocio y su equipo (vendedores).
 * Cada curso tiene lecciones de 2 a 4 minutos y una prueba fácil de 3 preguntas al final.
 * 'para' => 'todos' (dueño y equipo) o 'dueno' (dueño y administrador).
 * 'accion' => [nombre de la ruta, texto del botón] para practicar en la herramienta.
 */
class Academia
{
    public static function cursos(): array
    {
        return self::cursosNegocio() + self::cursosAsesor();
    }

    private static function plata($v): string
    {
        return '$' . number_format((float) $v, 0, ',', '.');
    }

    // Cursos para los asesores de LocalPro (la red comercial)
    public static function cursosAsesor(): array
    {
        $v = Comisiones::valores();
        $plan = self::plata($v['valor_plan']);
        $com = self::plata($v['nueva']['venta']);
        $renov = self::plata($v['renovacion']['venta']);
        $mes = self::plata(ceil($v['valor_plan'] / 12 / 100) * 100);
        $dia = self::plata(ceil($v['valor_plan'] / 365 / 100) * 100);

        return [
            'asesor-prospectar' => [
                'titulo' => 'Encontrar negocios para visitar', 'icono' => 'fa-map-location-dot', 'color' => '#16b34a', 'para' => 'asesor',
                'resumen' => 'Dónde están sus clientes, a quién hablarle y cómo organizar su día para vender todos los días.',
                'lecciones' => [
                    ['titulo' => 'Quién es su cliente ideal', 'min' => 3, 'accion' => null, 'contenido' => [
                        'Su cliente es el <b>dueño de un negocio pequeño o mediano</b> que todavía anota en cuaderno o en Excel: tiendas de ropa, calzado, misceláneas, tiendas de barrio, ferreterías, papelerías, restaurantes, peluquerías.',
                        'Las mejores señales: tiene inventario (muchos productos), varios vendedores o se queja de que "no sabe cuánto gana".',
                        'Empiece por lo cercano: <b>su barrio, su familia, sus amigos con negocio</b> y los negocios donde usted ya compra.',
                    ], 'tip' => 'Haga una lista de 20 negocios conocidos antes de salir a la calle. La confianza vende el doble.'],
                    ['titulo' => 'Sus contactos y la base de LocalPro', 'min' => 3, 'accion' => ['asesor.contactos', 'Ir a Mis contactos'], 'contenido' => [
                        'En <b>Mis contactos</b> suba los contactos de su celular: cada día le salen 20 para escribirles con su enlace de prueba gratis.',
                        'Además, LocalPro le da <b>contactos de negocios para hoy</b>. A cada persona se le escribe una sola vez: si usted le escribe, no le sale a ningún otro asesor.',
                        'Si alguien se inscribe con su enlace, el negocio queda a su nombre automáticamente.',
                    ], 'tip' => 'Escriba sus 20 contactos en la mañana y dedique la tarde a visitar.'],
                    ['titulo' => 'La rutina que da resultados', 'min' => 2, 'accion' => null, 'contenido' => [
                        '<b>20 mensajes</b> por WhatsApp + <b>5 visitas</b> a negocios + <b>1 demostración</b> al día. Eso, todos los días, da ventas cada semana.',
                        'Anote a quién visitó y qué le dijo: el que dijo "más adelante" es su venta del próximo mes.',
                        'Vender es un juego de números: de cada 10 negocios que le escuchan, 2 o 3 prueban LocalPro.',
                    ], 'tip' => 'Su meta: un salario mínimo al mes son pocos planes anuales. Mire su barra de meta en el panel.'],
                ],
                'prueba' => [
                    ['p' => '¿Cuál es un buen cliente para LocalPro?', 'o' => ['Un negocio que anota todo en cuaderno', 'Una persona sin negocio', 'Un banco'], 'r' => 0],
                    ['p' => '¿Qué pasa si un negocio se inscribe con su enlace?', 'o' => ['Queda a su nombre automáticamente', 'Queda a nombre de otro asesor', 'No pasa nada'], 'r' => 0],
                    ['p' => '¿Cuál es la rutina diaria recomendada?', 'o' => ['20 mensajes, 5 visitas y 1 demostración', 'Un mensaje a la semana', 'Esperar a que lo llamen'], 'r' => 0],
                ],
            ],

            'asesor-presentar' => [
                'titulo' => 'Presentar LocalPro en 5 minutos', 'icono' => 'fa-mobile-screen-button', 'color' => '#0ea5e9', 'para' => 'asesor',
                'resumen' => 'Cómo mostrar LocalPro desde su celular para que el dueño quiera probarlo ese mismo día.',
                'lecciones' => [
                    ['titulo' => 'Abra con el dolor del dueño', 'min' => 3, 'accion' => null, 'contenido' => [
                        'No empiece hablando del sistema. Pregunte: <b>"¿Usted sabe cuánto ganó la semana pasada?"</b> o <b>"¿Cómo controla lo que venden sus empleados?"</b>',
                        'Escuche. Casi todos dicen: "más o menos", "lo anoto en un cuaderno", "se me pierde mercancía". Ese es el problema que LocalPro resuelve.',
                    ], 'tip' => 'El dueño compra tranquilidad y control, no un programa.'],
                    ['titulo' => 'La demostración', 'min' => 4, 'accion' => ['asesor.capacitacion', 'Ver la capacitación'], 'contenido' => [
                        'Muestre solo 3 cosas, en este orden: <b>1) vender en la caja</b> y mandar el tiquete por WhatsApp, <b>2) el panel</b> con ventas y ganancia del día, <b>3) la tienda en línea</b> que sale en Google.',
                        'Si tiene vendedores, muéstrele que <b>cada uno abre su caja</b> y que ellos no ven las ganancias.',
                        'Termine mostrando <b>Vender más</b>: su base de clientes y el WhatsApp marketing. Eso le trae plata nueva.',
                    ], 'tip' => 'Practique la demostración con un familiar hasta que la haga en menos de 5 minutos.'],
                    ['titulo' => 'Cierre con la prueba gratis', 'min' => 2, 'accion' => ['asesor.index', 'Ver mi enlace'], 'contenido' => [
                        'No pida la venta de una: ofrezca la <b>prueba gratis de 3 meses</b> con su enlace. Es fácil decir que sí a algo gratis.',
                        'Inscríbalo ahí mismo, con él, desde el celular. Créele 3 productos y hagan juntos la primera venta.',
                        'Un negocio que ya hizo su primera venta en LocalPro casi nunca vuelve al cuaderno.',
                    ], 'tip' => 'Nunca deje la inscripción "para después": el 80 % de los "después lo hago" nunca lo hacen.'],
                ],
                'prueba' => [
                    ['p' => '¿Con qué conviene empezar la conversación?', 'o' => ['Con una pregunta sobre el problema del dueño', 'Leyendo todas las funciones', 'Hablando del precio'], 'r' => 0],
                    ['p' => '¿Qué tres cosas se muestran en la demostración?', 'o' => ['Caja, panel y tienda en línea', 'Solo el logo', 'Los términos y condiciones'], 'r' => 0],
                    ['p' => '¿Cuándo es mejor inscribir al negocio?', 'o' => ['Ahí mismo, con él', 'Otro mes', 'Nunca'], 'r' => 0],
                ],
            ],

            'asesor-objeciones' => [
                'titulo' => 'Objeciones y cierre del plan anual', 'icono' => 'fa-comments-dollar', 'color' => '#f59e0b', 'para' => 'asesor',
                'resumen' => "Responder \"está caro\", \"ya tengo cuaderno\" y convertir la prueba gratis en plan anual de $plan.",
                'lecciones' => [
                    ['titulo' => '"Está caro"', 'min' => 3, 'accion' => null, 'contenido' => [
                        "El plan anual vale <b>$plan</b>: son unos <b>$mes al mes</b>, menos de <b>$dia al día</b>.",
                        'Compárelo: "¿Cuánto pierde al mes en mercancía que no sabe dónde quedó o en ventas que no se anotaron?" Casi siempre es más que eso.',
                        'Recuérdele que incluye la caja, el inventario, los vendedores, la tienda en línea y Vender más, todo en uno.',
                    ], 'tip' => 'Hable de "menos que un tinto al día", no de la cifra completa.'],
                    ['titulo' => '"Ya tengo cuaderno" y "no sé de tecnología"', 'min' => 3, 'accion' => null, 'contenido' => [
                        '"El cuaderno no le dice cuánto ganó, ni le avisa qué se está acabando, ni le vende por internet."',
                        'Para el que no sabe de tecnología: "Si sabe mandar un WhatsApp, sabe usar LocalPro. Y yo lo acompaño la primera semana."',
                        '"Déjeme pensarlo": pregunte con respeto <b>"¿Qué es lo que lo hace dudar?"</b>. Ahí sale la verdadera objeción.',
                    ], 'tip' => null],
                    ['titulo' => 'De prueba gratis a plan anual', 'min' => 3, 'accion' => ['asesor.index', 'Ver mis negocios'], 'contenido' => [
                        'La prueba dura 3 meses. Llame o visite al negocio en la <b>semana 1</b> (que esté usando), en el <b>mes 2</b> (que vea sus reportes) y <b>2 semanas antes</b> de que termine.',
                        "Cuando el negocio paga el plan anual cuenta como <b>venta nueva</b>: usted gana <b>$com</b>, y cada año que renueve gana <b>$renov</b> de por vida mientras usted y el negocio sigan activos.",
                        'Cobre con el botón <b>Cobrar</b> de Mis negocios: le llega al negocio un enlace para pagar con tarjeta, PSE o Nequi.',
                    ], 'tip' => 'El negocio que ve su ganancia en el panel se renueva solo. Ayúdelo a poner los costos de sus productos.'],
                ],
                'prueba' => [
                    ['p' => "El plan anual de $plan equivale aproximadamente a…", 'o' => ["$mes al mes", '$500.000 al mes', '$1.000 al año'], 'r' => 0],
                    ['p' => 'El cliente dice "déjeme pensarlo". ¿Qué pregunta?', 'o' => ['¿Qué es lo que lo hace dudar?', 'Nada, se va', 'Le baja el precio a la mitad'], 'r' => 0],
                    ['p' => '¿Cuándo gana usted la comisión de venta nueva?', 'o' => ['Cuando el negocio paga el plan anual', 'Cuando empieza la prueba gratis', 'Cuando lo saluda'], 'r' => 0],
                ],
            ],

            'asesor-equipo' => [
                'titulo' => 'Construya su equipo y suba de rango', 'icono' => 'fa-sitemap', 'color' => '#8b5cf6', 'para' => 'asesor',
                'resumen' => 'Sumar asesores con su enlace, acompañarlos y crecer de asesor a líder.',
                'lecciones' => [
                    ['titulo' => 'Sumar asesores', 'min' => 3, 'accion' => ['asesor.red', 'Ver mi red'], 'contenido' => [
                        'Comparta su enlace para sumar asesores (<b>localproo.com/unete/SU-CÓDIGO</b>) con personas que quieran un ingreso extra: estudiantes, vendedores, mamás emprendedoras.',
                        'Al registrarse hacen el curso con certificado y quedan en su línea. En su panel ve quién va haciendo el curso y puede animarlo por WhatsApp.',
                    ], 'tip' => 'Sume personas que ya venden algo: tienen contactos y saben hablar con clientes.'],
                    ['titulo' => 'Acompañe a su gente', 'min' => 3, 'accion' => ['aprende.index', 'Ver cómo va mi línea'], 'contenido' => [
                        'Su equipo gana si usted le enseña. Pídales que hagan los cursos de <b>Aprende</b>: aquí ve cómo va cada uno.',
                        'Hagan juntos las primeras visitas: el nuevo aprende mirándolo vender.',
                        'Felicite en público cada primera venta del equipo: eso motiva más que cualquier regaño.',
                    ], 'tip' => 'Una reunión corta por WhatsApp cada lunes con metas de la semana mantiene al equipo activo.'],
                    ['titulo' => 'Cómo se sube de rango', 'min' => 2, 'accion' => ['asesor.index', 'Ver mi avance'], 'contenido' => [
                        'Para subir de rango necesita negocios que <b>pagaron su plan anual</b> y asesores que usted sumó y están activos. Su barra de avance está en el panel.',
                        'Las pruebas gratis no cuentan hasta que el negocio paga.',
                        'Al subir, gana una parte de las ventas y renovaciones de su equipo. Recuerde: para cobrar comisiones debe tener al menos un negocio con pago.',
                    ], 'tip' => null],
                ],
                'prueba' => [
                    ['p' => '¿Cómo suma un asesor a su línea?', 'o' => ['Con su enlace /unete', 'Llamando a LocalPro', 'No se puede'], 'r' => 0],
                    ['p' => '¿Las pruebas gratis cuentan para subir de rango?', 'o' => ['No, hasta que el negocio pague', 'Sí, todas', 'Solo las de los lunes'], 'r' => 0],
                    ['p' => '¿Qué ayuda más a que su equipo venda?', 'o' => ['Enseñarles y acompañarlos', 'Dejarlos solos', 'Regañarlos'], 'r' => 0],
                ],
            ],
        ];
    }

    // Cursos para los negocios (dueño y vendedores)
    public static function cursosNegocio(): array
    {
        return [
            'primeros-pasos' => [
                'titulo' => 'Primeros pasos en LocalPro', 'icono' => 'fa-seedling', 'color' => '#16b34a', 'para' => 'todos',
                'resumen' => 'Conozca el panel, sus productos y cómo hacer su primera venta en 10 minutos.',
                'lecciones' => [
                    ['titulo' => 'Su panel en un vistazo', 'min' => 2, 'accion' => ['panel', 'Ir al panel'], 'contenido' => [
                        'El <b>panel</b> es la pantalla de inicio: ahí ve las ventas del día, lo que lleva del mes y los avisos importantes.',
                        'El <b>menú</b> de la izquierda (en el celular, el botón ☰ de arriba) lleva a todo: caja, productos, clientes, reportes y Vender más.',
                        'Si algo no le aparece en el menú, es porque su usuario no tiene permiso. El dueño lo activa en <b>Usuarios</b>.',
                    ], 'tip' => 'Agregue LocalPro a la pantalla de inicio de su celular: en Chrome toque ⋮ → "Agregar a la pantalla principal". Queda como una app.'],
                    ['titulo' => 'Sus productos', 'min' => 3, 'accion' => ['productos.index', 'Ver mis productos'], 'contenido' => [
                        'Cada producto tiene nombre, precio de venta, costo y cantidad en inventario. Si vende ropa o calzado, puede manejar <b>tallas y colores</b>.',
                        'El <b>costo</b> es importante: con él LocalPro le calcula la ganancia real de cada venta.',
                        '¿Ya tiene sus productos en Excel? Súbalos en <b>Productos → Importar</b> y no tiene que crearlos uno por uno.',
                    ], 'tip' => 'Póngale foto a cada producto: se ve en la caja y en su tienda en línea, y vende mucho más.'],
                    ['titulo' => 'Su primera venta', 'min' => 3, 'accion' => ['pos.index', 'Abrir la caja'], 'contenido' => [
                        'Para vender primero se <b>abre la caja</b> con la plata que hay en el cajón (la base).',
                        'Busque el producto por nombre o código, agréguelo, elija cómo pagó el cliente (efectivo, Nequi, tarjeta…) y toque <b>Cobrar</b>.',
                        'El tiquete se imprime o se envía por <b>WhatsApp</b> al cliente si no tiene impresora.',
                    ], 'tip' => 'Registre TODAS las ventas, también las pequeñas. Así sus reportes dicen la verdad.'],
                ],
                'prueba' => [
                    ['p' => '¿Qué se hace antes de empezar a vender en el día?', 'o' => ['Abrir la caja con la base', 'Borrar las ventas de ayer', 'Cambiar los precios'], 'r' => 0],
                    ['p' => '¿Para qué sirve poner el costo de cada producto?', 'o' => ['Para que LocalPro calcule la ganancia', 'No sirve para nada', 'Para que el cliente lo vea'], 'r' => 0],
                    ['p' => 'Si el cliente no quiere tiquete de papel, ¿qué puede hacer?', 'o' => ['Enviárselo por WhatsApp', 'No registrar la venta', 'Anotarla en un cuaderno'], 'r' => 0],
                ],
            ],

            'caja' => [
                'titulo' => 'La caja paso a paso', 'icono' => 'fa-cash-register', 'color' => '#0ea5e9', 'para' => 'todos',
                'resumen' => 'Abrir, vender, cobrar, devoluciones y cerrar la caja sin descuadres.',
                'lecciones' => [
                    ['titulo' => 'Abrir la caja', 'min' => 2, 'accion' => ['pos.index', 'Ir a la caja'], 'contenido' => [
                        'Cada vendedor abre <b>su propia caja</b> al empezar el turno, con el dinero que recibe en el cajón.',
                        'Cuente la base delante de quien se la entrega y escríbala exacta.',
                    ], 'tip' => 'Una caja por persona: así cada uno responde por sus ventas y su plata.'],
                    ['titulo' => 'Cobrar bien', 'min' => 3, 'accion' => ['metodos-pago.index', 'Ver métodos de pago'], 'contenido' => [
                        'Elija siempre el <b>método de pago real</b>: si pagó por Nequi, márquelo como Nequi, no como efectivo. Al cierre, eso es lo que cuadra.',
                        'Si el cliente paga con billete grande, LocalPro le dice cuánto devolver.',
                        'Para dejar un producto apartado use <b>Separados</b>: el cliente abona y se le entrega al completar.',
                    ], 'tip' => 'Revise la transferencia en su celular ANTES de entregar el producto.'],
                    ['titulo' => 'Devoluciones y cambios', 'min' => 2, 'accion' => ['pos.ventas', 'Ver ventas'], 'contenido' => [
                        'Busque la venta original y haga la <b>devolución</b> desde ahí: el producto vuelve al inventario y la plata sale de la caja.',
                        'Nunca borre una venta para "corregir": haga la devolución. Así queda registro de todo.',
                    ], 'tip' => null],
                    ['titulo' => 'Cerrar la caja sin descuadres', 'min' => 3, 'accion' => ['cajas.index', 'Ver mis cajas'], 'contenido' => [
                        'Al final del turno cuente el efectivo y escriba lo que hay. LocalPro compara con lo que debería haber.',
                        'Si hay <b>faltante o sobrante</b>, escriba el motivo en la nota. Es normal un error pequeño; lo importante es explicarlo.',
                        'El dueño ve el reporte de cada cierre con las ventas por método de pago.',
                    ], 'tip' => 'Cierre todos los días, aunque haya vendido poco.'],
                ],
                'prueba' => [
                    ['p' => 'El cliente pagó por Nequi. ¿Cómo se registra?', 'o' => ['Como Nequi', 'Como efectivo', 'No se registra'], 'r' => 0],
                    ['p' => 'Si me equivoqué en una venta, ¿qué hago?', 'o' => ['Hago una devolución', 'Borro la venta', 'Lo dejo así'], 'r' => 0],
                    ['p' => 'Al cerrar falta plata. ¿Qué hago?', 'o' => ['Escribo el motivo en la nota del cierre', 'No cierro la caja', 'Cambio el valor de una venta'], 'r' => 0],
                ],
            ],

            'tecnicas-de-venta' => [
                'titulo' => 'Técnicas de venta', 'icono' => 'fa-handshake', 'color' => '#f59e0b', 'para' => ['todos', 'asesor'],
                'resumen' => 'Cómo atender, ofrecer y cerrar la venta en el mostrador y por WhatsApp.',
                'lecciones' => [
                    ['titulo' => 'El saludo que vende', 'min' => 2, 'accion' => null, 'contenido' => [
                        'Salude en los primeros <b>10 segundos</b>, con una sonrisa y mirando a los ojos. El cliente que se siente ignorado se va.',
                        'Cambie "¿le ayudo en algo?" (la respuesta casi siempre es "no, gracias") por una pregunta abierta: <b>"¿Busca algo para usted o para regalar?"</b>',
                    ], 'tip' => 'Aprenda el nombre de sus clientes frecuentes. Es lo que más fideliza.'],
                    ['titulo' => 'Escuchar antes de ofrecer', 'min' => 3, 'accion' => null, 'contenido' => [
                        'Haga 2 o 3 preguntas: <b>¿para qué ocasión? ¿qué talla o tamaño? ¿qué colores le gustan?</b>',
                        'Muestre <b>máximo 3 opciones</b>. Con demasiadas, el cliente se confunde y dice "lo pienso".',
                        'Hable de lo que gana el cliente ("le dura años", "le queda perfecto para el trabajo"), no solo del producto.',
                    ], 'tip' => null],
                    ['titulo' => 'Cuando dicen "está caro"', 'min' => 3, 'accion' => null, 'contenido' => [
                        'No baje el precio de una. Primero pregunte: <b>"¿Comparado con qué?"</b> y explique la calidad o la garantía.',
                        'Ofrezca una alternativa más económica, o <b>separado con abono</b> si no le alcanza hoy.',
                        'Si da descuento, que sea por algo: llevar 2 unidades, pagar en efectivo, cliente frecuente.',
                    ], 'tip' => 'Un "no" de hoy puede ser un cliente de mañana: pida su WhatsApp para avisarle de promociones.'],
                    ['titulo' => 'Cerrar y vender más', 'min' => 3, 'accion' => null, 'contenido' => [
                        'Cierre con una pregunta de opción: <b>"¿Se lleva la negra o la azul?"</b> en vez de "¿se lo lleva?".',
                        'Antes de cobrar ofrezca un <b>complemento</b>: a la camisa, el cinturón; al celular, el protector. Así sube el valor de cada venta.',
                        'Despídase invitando a volver y a seguir su tienda en línea.',
                    ], 'tip' => null],
                ],
                'prueba' => [
                    ['p' => '¿Cuál pregunta ayuda más a vender al saludar?', 'o' => ['¿Busca algo para usted o para regalar?', '¿Le ayudo en algo?', '¿Va a comprar?'], 'r' => 0],
                    ['p' => '¿Cuántas opciones conviene mostrar a la vez?', 'o' => ['Máximo 3', 'Todas las que haya', 'Solo una'], 'r' => 0],
                    ['p' => 'El cliente dice "está caro". ¿Qué hace primero?', 'o' => ['Preguntar "¿comparado con qué?" y explicar el valor', 'Bajar el precio de una', 'Decirle que se vaya'], 'r' => 0],
                ],
            ],

            'vender-mas' => [
                'titulo' => 'Vender más con LocalPro', 'icono' => 'fa-rocket', 'color' => '#22c55e', 'para' => 'todos',
                'resumen' => 'Base de datos, WhatsApp marketing, tienda en línea y redes para conseguir más clientes.',
                'lecciones' => [
                    ['titulo' => 'Su base de datos de clientes', 'min' => 3, 'accion' => ['vender.index', 'Ir a Vender más'], 'contenido' => [
                        'El activo más valioso de su negocio son sus clientes. Guarde el <b>nombre y el WhatsApp</b> de cada uno en cada venta.',
                        'Suba su Excel o los contactos de su celular en <b>Vender más</b>: LocalPro los organiza sin repetir a nadie.',
                    ], 'tip' => 'Pida el WhatsApp con un motivo: "para avisarle cuando llegue mercancía nueva".'],
                    ['titulo' => 'WhatsApp marketing', 'min' => 3, 'accion' => ['clientes.contactar', 'Contactar clientes'], 'contenido' => [
                        'Cada día a usted y a su equipo les salen clientes para escribirles con un toque, con un mensaje listo y el enlace de su tienda.',
                        'A cada persona se le escribe <b>una sola vez</b>: nadie del equipo le repite el mensaje.',
                        'Escriba de 9 a. m. a 7 p. m. y si alguien pide no recibir más mensajes, márquelo como "No le interesa" (Ley 1581 de datos personales).',
                    ], 'tip' => 'Lo mejor es poco y todos los días: 20 mensajes diarios venden más que 500 de una vez.'],
                    ['titulo' => 'Su tienda en línea', 'min' => 2, 'accion' => ['negocio.edit', 'Configurar mi tienda'], 'contenido' => [
                        'Su catálogo en internet con botón de <b>comprar por WhatsApp</b>, que además aparece en Google.',
                        'Compártala en sus estados de WhatsApp, en la biografía de Instagram y en sus grupos. En el panel ve cuántas visitas tiene.',
                    ], 'tip' => 'Publique en sus estados un producto distinto cada día con el enlace de su tienda.'],
                    ['titulo' => 'Facebook, Instagram y Mis enlaces', 'min' => 2, 'accion' => ['redes.index', 'Ir a Mis redes'], 'contenido' => [
                        'En <b>Mis redes</b> responde los mensajes de Messenger e Instagram en un solo lugar y publica sus productos con foto y precio.',
                        'En <b>Mis enlaces</b> arma una página con su WhatsApp, redes y promociones para poner en un código QR en el mostrador.',
                    ], 'tip' => 'Responda rápido: el cliente que escribe por redes casi siempre le escribe también a la competencia.'],
                ],
                'prueba' => [
                    ['p' => '¿Qué dato es clave guardar de cada cliente?', 'o' => ['Su WhatsApp', 'Su color favorito', 'Nada'], 'r' => 0],
                    ['p' => '¿Qué funciona mejor para escribir a los clientes?', 'o' => ['Pocos mensajes todos los días', '500 mensajes de una vez', 'Escribir a medianoche'], 'r' => 0],
                    ['p' => 'Alguien pide que no le escriban más. ¿Qué hace?', 'o' => ['Lo marca como "No le interesa"', 'Le sigue escribiendo', 'Le escribe desde otro número'], 'r' => 0],
                ],
            ],

            'inventario' => [
                'titulo' => 'Inventario sin pérdidas', 'icono' => 'fa-boxes-stacked', 'color' => '#8b5cf6', 'para' => 'dueno',
                'resumen' => 'Entradas de mercancía, existencias, etiquetas y cómo evitar faltantes.',
                'lecciones' => [
                    ['titulo' => 'Registrar la mercancía que llega', 'min' => 3, 'accion' => ['inventario.index', 'Ver inventario'], 'contenido' => [
                        'Cada vez que llega mercancía, regístrela como <b>entrada</b> (o compra): suma al inventario y guarda el costo.',
                        'Si cambia el costo del proveedor, actualícelo: así la ganancia de los reportes es real.',
                    ], 'tip' => 'Imprima las etiquetas con código de barras al registrar la entrada: vender después es mucho más rápido.'],
                    ['titulo' => 'Revisar existencias', 'min' => 2, 'accion' => ['inventario.movimientos', 'Ver movimientos'], 'contenido' => [
                        'En <b>Inventario</b> ve cuánto queda de cada producto, talla y color, y en <b>Movimientos</b> quién vendió, devolvió o ajustó.',
                        'Haga un conteo físico al menos una vez al mes y compare con LocalPro. Si no cuadra, revise los movimientos.',
                    ], 'tip' => null],
                    ['titulo' => 'Qué comprar y qué no', 'min' => 2, 'accion' => ['reportes.index', 'Ver reportes'], 'contenido' => [
                        'En <b>Reportes</b> mire los productos más vendidos: de esos nunca se debe quedar sin existencias.',
                        'Los que llevan meses sin venderse son plata quieta: póngalos en <b>promoción</b> y compre menos de esos.',
                    ], 'tip' => 'Compre seguido y poco de lo que rota, en vez de mucho de todo.'],
                ],
                'prueba' => [
                    ['p' => 'Llegó mercancía nueva. ¿Qué hago en LocalPro?', 'o' => ['Registrarla como entrada', 'Nada, se suma sola', 'Crear el producto otra vez'], 'r' => 0],
                    ['p' => '¿Cada cuánto conviene hacer conteo físico?', 'o' => ['Al menos una vez al mes', 'Nunca', 'Cada 5 años'], 'r' => 0],
                    ['p' => '¿Qué hago con lo que lleva meses sin venderse?', 'o' => ['Ponerlo en promoción', 'Comprar más de eso', 'Subirle el precio'], 'r' => 0],
                ],
            ],

            'contabilidad' => [
                'titulo' => 'Contabilidad fácil para su negocio', 'icono' => 'fa-calculator', 'color' => '#ef4444', 'para' => 'dueno',
                'resumen' => 'Venta no es ganancia: gastos, ganancia real, IVA y facturación electrónica sin enredos.',
                'lecciones' => [
                    ['titulo' => 'Vender no es ganar', 'min' => 3, 'accion' => ['reportes.index', 'Ver mis reportes'], 'contenido' => [
                        '<b>Ventas</b> es todo lo que entra. <b>Ganancia</b> es lo que queda después de pagar la mercancía y los gastos.',
                        'Fórmula sencilla: <b>Ganancia = Ventas − Costo de la mercancía − Gastos</b> (arriendo, servicios, nómina, domicilios…).',
                        'LocalPro calcula la ganancia de cada venta con el costo de los productos. Por eso es clave tenerlo al día.',
                    ], 'tip' => 'Separe la plata del negocio de la plata de la casa. Páguese un sueldo fijo a usted mismo.'],
                    ['titulo' => 'Registre sus gastos', 'min' => 2, 'accion' => ['gastos', 'Registrar gastos'], 'contenido' => [
                        'Anote cada gasto en <b>Gastos</b>: arriendo, luz, internet, bolsas, transporte, publicidad.',
                        'Con los gastos registrados, el reporte del mes le dice si de verdad ganó plata y cuánto.',
                    ], 'tip' => 'Guarde la foto de cada factura de compra o gasto: le sirve a su contador.'],
                    ['titulo' => 'IVA en palabras sencillas', 'min' => 3, 'accion' => ['facturacion.index', 'Ver facturación'], 'contenido' => [
                        'Si su negocio es <b>responsable de IVA</b> (aparece en su RUT), el IVA que cobra no es suyo: es de la DIAN y se declara.',
                        'En LocalPro cada producto tiene su impuesto (IVA 19 %, 5 %, exento, excluido…) y el precio ya lo incluye. LocalPro separa la base y el impuesto en cada venta.',
                        'Si no es responsable de IVA, sus productos van "sin impuesto" y entrega tiquete. Ante la duda, pregúntele a su contador.',
                    ], 'tip' => null],
                    ['titulo' => 'Facturación electrónica y reportes para el contador', 'min' => 3, 'accion' => ['facturacion.index', 'Revisar mi preparación'], 'contenido' => [
                        'Cuando la DIAN lo obligue, LocalPro genera la <b>factura electrónica o el POS electrónico</b> desde la misma caja.',
                        'En <b>Facturación DIAN</b> está la revisión de preparación: le dice qué le falta (RUT, resoluciones, IVA de los productos).',
                        'Cada mes descargue los reportes de ventas y gastos en Excel y envíeselos a su contador.',
                    ], 'tip' => 'Un buen contador no es un gasto: le evita multas y le ayuda a pagar los impuestos justos.'],
                ],
                'prueba' => [
                    ['p' => 'Vendí 10 millones, la mercancía me costó 6 y gasté 2. ¿Cuánto gané?', 'o' => ['2 millones', '10 millones', '4 millones'], 'r' => 0],
                    ['p' => 'Si soy responsable de IVA, el IVA que cobro es…', 'o' => ['De la DIAN, se declara', 'Mío para gastar', 'Del cliente'], 'r' => 0],
                    ['p' => '¿Para qué registro los gastos en LocalPro?', 'o' => ['Para saber si de verdad estoy ganando', 'No sirve', 'Solo por cumplir'], 'r' => 0],
                ],
            ],

            'equipo' => [
                'titulo' => 'Dirigir a su equipo', 'icono' => 'fa-people-group', 'color' => '#0b0d0c', 'para' => 'dueno',
                'resumen' => 'Usuarios, permisos, metas de venta y cómo motivar a sus vendedores.',
                'lecciones' => [
                    ['titulo' => 'Usuarios y permisos', 'min' => 2, 'accion' => ['users.index', 'Ver mi equipo'], 'contenido' => [
                        'Cree un usuario para cada vendedor: nunca compartan la misma clave.',
                        'Los vendedores <b>no ven ganancias ni costos</b>, solo sus ventas y su meta. Usted decide qué más pueden hacer.',
                    ], 'tip' => 'Cuando alguien deje de trabajar con usted, desactive su usuario el mismo día.'],
                    ['titulo' => 'Metas que motivan', 'min' => 3, 'accion' => ['users.index', 'Poner metas'], 'contenido' => [
                        'Póngale a cada vendedor una <b>meta de ventas</b>: él ve en su panel cuánto lleva y cuánto le falta.',
                        'Una meta buena es alcanzable pero exigente: tome lo que vendió el mes pasado y súbale un 10 %.',
                        'Reconozca en público al que cumple: un premio pequeño motiva más que un regaño.',
                    ], 'tip' => 'Pídales a sus vendedores que hagan estos cursos: Aprende LocalPro también es para ellos.'],
                ],
                'prueba' => [
                    ['p' => '¿Los vendedores pueden compartir una misma clave?', 'o' => ['No, cada uno tiene su usuario', 'Sí, es mejor', 'Da igual'], 'r' => 0],
                    ['p' => 'Una buena meta de ventas es…', 'o' => ['Alcanzable pero exigente', 'Imposible de cumplir', 'Cero'], 'r' => 0],
                    ['p' => 'Un vendedor dejó de trabajar. ¿Qué hago?', 'o' => ['Desactivo su usuario ese día', 'Lo dejo activo', 'Le cambio el nombre'], 'r' => 0],
                ],
            ],
        ];
    }

    public static function curso(string $slug): ?array
    {
        return self::cursos()[$slug] ?? null;
    }

    public static function esJefe(): bool
    {
        $u = auth()->user();
        return (bool) ($u && ($u->es_superadmin || $u->hasAnyRole(['dueno', 'administrador'])));
    }

    // A quién va cada curso: 'todos' (negocio: dueño y vendedores), 'dueno' (dueño y administrador), 'asesor' (red comercial)
    public static function grupos(): array
    {
        $u = auth()->user();
        if (!$u) return [];
        if ($u->es_superadmin) return ['todos', 'dueno', 'asesor'];
        $g = [];
        if ($u->tienda_id) {
            $g[] = 'todos';
            if (self::esJefe()) $g[] = 'dueno';
        }
        // El asesor debe ser el más preparado: además de sus cursos ve, como opcionales, todos los del negocio
        // (caja, inventario, contabilidad…) para poder explicarle LocalPro al dueño y a su contador.
        if ($u->asesor) $g = array_merge($g, ['asesor', 'todos', 'dueno']);
        return array_values(array_unique($g));
    }

    // Para un asesor, los cursos del negocio son opcionales (los obligatorios son los suyos)
    public static function esOpcional(array $c): bool
    {
        $u = auth()->user();
        return (bool) ($u?->asesor && !$u->tienda_id && !$u->es_superadmin && !in_array('asesor', (array) $c['para'], true));
    }

    public static function paraMi(): array
    {
        $g = self::grupos();
        return array_filter(self::cursos(), fn ($c) => (bool) array_intersect((array) $c['para'], $g));
    }
}
