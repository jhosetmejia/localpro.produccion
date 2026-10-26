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
        return Moneda::formato($v, Moneda::actual());
    }

    // Cursos para los asesores de LocalPro (la red comercial)
    public static function cursosAsesor(): array
    {
        $v = Comisiones::valores(Moneda::actual());
        $plan = self::plata($v['valor_plan']);
        $com = self::plata($v['nueva']['venta']);
        $renov = self::plata($v['renovacion']['venta']);
        $usd = Moneda::esExtranjera($v['moneda']); // lleva centavos (US$ o Bs)
        $otra = fn ($cop) => self::plata(Moneda::convertir($cop, Moneda::COP, $v['moneda']));
        $mes = self::plata($usd ? ceil($v['valor_plan'] / 12 * 100) / 100 : ceil($v['valor_plan'] / 12 / 100) * 100);
        $dia = self::plata($usd ? ceil($v['valor_plan'] / 365 * 100) / 100 : ceil($v['valor_plan'] / 365 / 100) * 100);

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
                        'Si usted tiene asesores en su red, marque <b>"Compartir mi base de datos con mis asesores"</b>: ellos también escriben a sus contactos (cada uno con su enlace) y a nadie se le escribe dos veces.',
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
                        'Si vende ropa, calzado o mercancía, muestre el <b>código de barras</b>: en <b>Ejemplos</b> hay etiquetas de muestra y una caja de prueba. Escanee con la cámara de su celular y el dueño ve cómo cae la prenda con su precio, sin comprar lector.',
                    ], 'tip' => 'Practique la demostración con un familiar hasta que la haga en menos de 5 minutos.'],
                    ['titulo' => 'Cierre con la prueba gratis', 'min' => 2, 'accion' => ['asesor.index', 'Ver mi enlace'], 'contenido' => [
                        'No pida la venta de una: ofrezca la <b>prueba gratis de 2 meses</b> con su enlace. Es fácil decir que sí a algo gratis.',
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
                        'La prueba dura 2 meses. Llame o visite al negocio en la <b>semana 1</b> (que esté usando), en la <b>semana 4</b> (que vea sus reportes) y <b>2 semanas antes</b> de que termine.',
                        "Cuando el negocio paga el plan anual cuenta como <b>venta nueva</b>: usted gana <b>$com</b>, y cada año que renueve gana <b>$renov</b> de por vida. Para cobrar debe hacer mínimo una venta nueva cada mes.",
                        'Cobre con el botón <b>Cobrar</b> de Mis negocios: le llega al negocio un enlace para pagar con tarjeta, PSE o Nequi.',
                    ], 'tip' => 'El negocio que ve su ganancia en el panel se renueva solo. Ayúdelo a poner los costos de sus productos.'],
                ],
                'prueba' => [
                    ['p' => "El plan anual de $plan equivale aproximadamente a…", 'o' => ["$mes al mes", $otra(500000) . ' al mes', $otra(1000) . ' al año'], 'r' => 0],
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
                        'Por cada negocio que venda o renueve alguien que usted sumó gana una comisión, y al subir gana también por las ventas y renovaciones de su equipo. Recuerde: para cobrar debe hacer mínimo una venta nueva cada mes.',
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
                'no_tipos' => ['eventos'], 'titulo' => 'Primeros pasos en LocalPro', 'icono' => 'fa-seedling', 'color' => '#16b34a', 'para' => 'todos',
                'resumen' => 'Conozca el panel, sus productos y cómo hacer su primera venta en 10 minutos.',
                'lecciones' => [
                    ['titulo' => 'Su panel en un vistazo', 'min' => 2, 'accion' => ['panel', 'Ir al panel'], 'contenido' => [
                        'El <b>panel</b> es la pantalla de inicio: ahí ve las ventas del día, lo que lleva del mes y los avisos importantes.',
                        'El <b>menú</b> de la izquierda (en el celular, el botón ☰ de arriba) lleva a todo: caja, productos, clientes, reportes y Vender más.',
                        'Si algo no le aparece en el menú, es porque su usuario no tiene permiso. El dueño lo activa en <b>Usuarios</b>.',
                    ], 'tip' => 'Agregue LocalPro a la pantalla de inicio de su celular: en Chrome toque ⋮ → "Agregar a la pantalla principal". Queda como una app.'],
                    ['titulo' => 'Sus productos', 'rutas' => [['productos', 'Nuevo producto']], 'min' => 3, 'accion' => ['productos.index', 'Ver mis productos'], 'contenido' => [
                        'Cada producto tiene nombre, precio de venta, costo y cantidad en inventario. Si vende ropa o calzado, puede manejar <b>tallas y colores</b>.',
                        'El <b>costo</b> es importante: con él LocalPro le calcula la ganancia real de cada venta.',
                        '¿Ya tiene sus productos en Excel? Súbalos en <b>Productos → Importar</b> y no tiene que crearlos uno por uno.',
                        '¿Ofrece <b>servicios</b> (cortes, consultas, reparaciones, asesorías, clases)? Actívelos en <b>Productos → Activar servicios</b> o en <b>Datos del negocio → Módulos</b>. Al crear elige <b>Producto</b> o <b>Servicio</b>: el servicio no lleva cantidad ni inventario, nunca sale "Agotado" y siempre está disponible para vender. Los ve aparte en la pestaña <b>Servicios</b>.',
                    ], 'tip' => 'Póngale foto a cada producto: se ve en la caja y en su tienda en línea, y vende mucho más.'],
                    ['titulo' => 'Su primera venta', 'rutas' => [['pos', 'Abrir la caja', 'Agregar productos', 'Cobrar']], 'min' => 3, 'accion' => ['pos.index', 'Abrir la caja'], 'contenido' => [
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
                'no_tipos' => ['eventos'], 'titulo' => 'La caja paso a paso', 'icono' => 'fa-cash-register', 'color' => '#0ea5e9', 'para' => 'todos',
                'resumen' => 'Abrir, vender escaneando el código de barras, cobrar, devoluciones, anotar los gastos y cerrar la caja sin descuadres.',
                'lecciones' => [
                    ['titulo' => 'Abrir la caja', 'min' => 2, 'accion' => ['pos.index', 'Ir a la caja'], 'contenido' => [
                        'Cada vendedor abre <b>su propia caja</b> al empezar el turno, con el dinero que recibe en el cajón.',
                        'Cuente la base delante de quien se la entrega y escríbala exacta.',
                    ], 'tip' => 'Una caja por persona: así cada uno responde por sus ventas y su plata.'],
                    ['titulo' => 'Cobrar bien', 'min' => 3, 'accion' => ['metodos-pago.index', 'Ver métodos de pago'], 'contenido' => [
                        'Elija siempre el <b>método de pago real</b>: si pagó por Nequi, márquelo como Nequi, no como efectivo. Al cierre, eso es lo que cuadra.',
                        'Si el cliente paga con billete grande, LocalPro le dice cuánto devolver.',
                        'Para dejar un producto apartado use <b>Separados</b>: el cliente abona y se le entrega al completar.',
                        '¿Restaurante o cafetería? Venda <b>tiqueteras</b> (por ejemplo, 20 almuerzos pagados por adelantado) en el menú <b>Tiquetera</b>: cada día busca al cliente y toca <b>Marcar almuerzo</b>, sin volver a cobrarle.',
                        '¿Restaurante, cafetería o granja? Use <b>Pedidos</b>: el mesero toma el pedido en el celular, caja lo cobra, la cocina (o la cafetería, las entradas…) lo ve en su pantalla y al mesero le suena cuando está listo. El manual completo está en <b>Pedidos → Cómo funciona</b>.',
                    ], 'tip' => 'Revise la transferencia en su celular ANTES de entregar el producto.'],
                    ['titulo' => 'Devoluciones y cambios', 'min' => 2, 'accion' => ['pos.ventas', 'Ver ventas'], 'contenido' => [
                        'Busque la venta original y haga la <b>devolución</b> desde ahí: el producto vuelve al inventario y la plata sale de la caja.',
                        'Nunca borre una venta para "corregir": haga la devolución. Así queda registro de todo.',
                        'Si en la caja se digitaron productos <b>de más</b> (aunque la caja ya esté cerrada), el dueño o el administrador usa <b>Corregir venta</b> en Ventas: baja la cantidad o corrige el precio mal digitado (ej. se vendió en $150.000 y se registró en $125.000) o el método de pago (Efectivo por Transferencia), el producto vuelve al inventario y la caja y los reportes se cuadran solos.',
                        'Si se vendió algo y <b>no se registró</b>, el dueño o el administrador usa <b>Registrar venta olvidada</b> en Ventas: queda con la fecha y en la caja de ese día, descuenta el inventario y el cierre se vuelve a cuadrar.',
                    ], 'tip' => 'Si al cerrar le sobra o le falta plata por un error al digitar, avísele al dueño: con Corregir venta o Registrar venta olvidada la caja queda cuadrada.'],
                    ['titulo' => 'Cerrar la caja sin descuadres', 'min' => 3, 'accion' => ['cajas.index', 'Ver mis cajas'], 'contenido' => [
                        'Al final del turno cuente el efectivo y escriba lo que hay. LocalPro compara con lo que debería haber.',
                        'Si hay <b>faltante o sobrante</b>, escriba el motivo en la nota. Es normal un error pequeño; lo importante es explicarlo.',
                        'El dueño ve el reporte de cada cierre con las ventas por método de pago.',
                    ], 'tip' => 'Cierre todos los días, aunque haya vendido poco: una caja abierta varios días mezcla las ventas y es más difícil encontrar un descuadre.'],
                    ['titulo' => 'Anotar los egresos (gastos)', 'min' => 3, 'accion' => ['egresos.index', 'Ir a Egresos'], 'contenido' => [
                        'Cada vez que salga plata del negocio (un domicilio, bolsas, el internet, un arreglo) anótela en <b>Egresos</b>: qué fue, cuánto, el tipo de gasto y cómo se pagó. Si tiene el recibo, tómele una foto.',
                        'Si lo paga <b>en efectivo con la plata de su caja abierta</b>, LocalPro lo descuenta solo del efectivo que debe haber. Así la caja cuadra al cerrar y no aparece un "faltante".',
                        'Usted ve <b>sus egresos y los de sus compañeros</b>. Los que anota el dueño o el administrador solo los ve él. El dueño ve todo y sabe en qué se va la plata del negocio.',
                    ], 'tip' => 'Anote el gasto en el momento, no al final del día: así no se le olvida y la caja cuadra.'],
                    ['titulo' => 'Vender escaneando el código de barras', 'min' => 2, 'accion' => ['pos.index', 'Probar en la caja'], 'contenido' => [
                        'Cada producto (y cada talla y color) tiene su <b>código de barras</b> en la etiqueta. En la caja toque el botón de la <b>cámara</b> 📷, apunte a la etiqueta y el producto cae a la venta con su precio. Suena un pitido en cada lectura.',
                        'La cámara queda abierta: escanee una prenda tras otra y al final cobre. Si el negocio tiene <b>pistola lectora</b>, también sirve: el código entra solo.',
                        'Si una etiqueta no se lee (arrugada, rota o con poca luz), busque el producto por nombre y pídale al dueño que la vuelva a imprimir.',
                    ], 'tip' => 'Escanear evita equivocarse de talla, color o precio: lo que se vende es exactamente lo que se descuenta del inventario.'],
                ],
                'prueba' => [
                    ['p' => 'Pagó un domicilio con $8.000 de la caja. ¿Qué hace?', 'o' => ['Lo anoto en Egresos', 'No anoto nada', 'Lo saco y lo repongo otro día'], 'r' => 0],
                    ['p' => 'El cliente pagó por Nequi. ¿Cómo se registra?', 'o' => ['Como Nequi', 'Como efectivo', 'No se registra'], 'r' => 0],
                    ['p' => 'Si me equivoqué en una venta, ¿qué hago?', 'o' => ['Hago una devolución', 'Borro la venta', 'Lo dejo así'], 'r' => 0],
                    ['p' => 'Ayer se vendió algo y no se registró. ¿Qué se hace?', 'o' => ['El dueño o el administrador usa "Registrar venta olvidada" con la caja de ayer', 'Se vende hoy como si fuera de hoy', 'No se hace nada'], 'r' => 0],
                    ['p' => 'Al cerrar falta plata. ¿Qué hago?', 'o' => ['Escribo el motivo en la nota del cierre', 'No cierro la caja', 'Cambio el valor de una venta'], 'r' => 0],
                    ['p' => '¿Cómo agrego rápido una prenda a la venta?', 'o' => ['Escaneo su código de barras con la cámara', 'Escribo el precio a mano', 'Le tomo una foto y la envío'], 'r' => 0],
                ],
            ],

            'tecnicas-de-venta' => [
                'no_tipos' => ['eventos'], 'titulo' => 'Técnicas de venta', 'icono' => 'fa-handshake', 'color' => '#f59e0b', 'para' => ['todos', 'asesor'],
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
                'no_tipos' => ['eventos'], 'titulo' => 'Vender más con LocalPro', 'icono' => 'fa-rocket', 'color' => '#22c55e', 'para' => 'todos',
                'resumen' => 'Base de datos, WhatsApp marketing, tienda en línea y redes para conseguir más clientes.',
                'lecciones' => [
                    ['titulo' => 'Su base de datos de clientes', 'min' => 3, 'accion' => ['vender.index', 'Ir a Vender más'], 'contenido' => [
                        'El activo más valioso de su negocio son sus clientes. Guarde el <b>nombre y el WhatsApp</b> de cada uno en cada venta.',
                        'Suba su Excel o los contactos de su celular en <b>Vender más</b>: LocalPro los organiza sin repetir a nadie.',
                    ], 'tip' => 'Pida el WhatsApp con un motivo: "para avisarle cuando llegue mercancía nueva".'],
                    ['titulo' => 'WhatsApp marketing', 'min' => 3, 'accion' => ['clientes.contactar', 'Contactar clientes'], 'contenido' => [
                        'Cada día a usted y a su equipo les salen clientes para escribirles con un toque, con un mensaje listo y el enlace de su tienda.',
                        'A cada persona se le escribe <b>una sola vez</b>: nadie del equipo le repite el mensaje.',
                        'Marque <b>"Compartir mi base de datos con mis asesores"</b> para que sus vendedores también reciban contactos; ellos eligen si mandan su mensaje o el mensaje normal.',
                        'Escriba de 9 a. m. a 7 p. m. y si alguien pide no recibir más mensajes, márquelo como "No le interesa" (Ley 1581 de datos personales).',
                    ], 'tip' => 'Lo mejor es poco y todos los días: 20 mensajes diarios venden más que 500 de una vez.'],
                    ['titulo' => 'Su tienda en línea', 'min' => 2, 'accion' => ['negocio.edit', 'Configurar mi tienda'], 'contenido' => [
                        'Su catálogo en internet con botón de <b>comprar por WhatsApp</b>, que además aparece en Google.',
                        'Compártala en sus estados de WhatsApp, en la biografía de Instagram y en sus grupos. En el panel ve cuántas visitas tiene.',
                        'Los productos salen siempre <b>del más barato al más caro</b>: los precios bajos enganchan al cliente y lo hacen seguir mirando.',
                    ], 'tip' => 'Publique en sus estados un producto distinto cada día con el enlace de su tienda.'],
                    ['titulo' => 'Su tienda en el Marketplace LocalProo', 'min' => 2, 'accion' => ['negocio.edit', 'Configurar mi tienda'], 'contenido' => [
                        'En <b>localproo.com/marketplace</b> están todas las tiendas en línea de LocalPro, como un Mercado Libre de negocios locales.',
                        'Su tienda y sus productos aparecen solos: basta con tener la tienda en línea activa y productos con foto.',
                        'El cliente busca un producto, compara precios y entra a su tienda a comprarle por WhatsApp. Usted no paga comisión por esas ventas.',
                    ], 'tip' => 'Ponga buenas fotos y precios claros: en el Marketplace su producto queda al lado del de otras tiendas.'],
                    ['titulo' => 'Facebook, Instagram y Mis enlaces', 'min' => 2, 'accion' => ['redes.index', 'Ir a Mis redes'], 'contenido' => [
                        'En <b>Mis redes</b> responde los mensajes de Messenger e Instagram en un solo lugar y publica sus productos con foto y precio.',
                        'En <b>Mis enlaces</b> arma una página con su WhatsApp, redes y promociones para poner en un código QR en el mostrador.',
                    ], 'tip' => 'Responda rápido: el cliente que escribe por redes casi siempre le escribe también a la competencia.'],
                    ['titulo' => 'Comparta LocalProo y gane', 'min' => 2, 'accion' => ['beneficios.index', 'Ver mi código'], 'contenido' => [
                        'Su negocio tiene un <b>código (LCT-…)</b> y un enlace. Compártalo con otros dueños de negocio: su colega recibe <b>2 meses gratis</b>.',
                        'Cada vez que ese negocio pague su <b>plan anual</b>, a usted se le abona un valor <b>de por vida</b>, que se descuenta solo cuando paga su propio plan. Si alcanza, su plan le sale gratis.',
                        'Además, los negocios que recomiendan LocalProo reciben primero las herramientas nuevas y recursos como cursos y plantillas.',
                    ], 'tip' => 'Compártalo con el vecino, su proveedor o un amigo emprendedor: la recomendación de un colega vale más que cualquier anuncio.'],
                ],
                'prueba' => [
                    ['p' => '¿Qué dato es clave guardar de cada cliente?', 'o' => ['Su WhatsApp', 'Su color favorito', 'Nada'], 'r' => 0],
                    ['p' => '¿Qué funciona mejor para escribir a los clientes?', 'o' => ['Pocos mensajes todos los días', '500 mensajes de una vez', 'Escribir a medianoche'], 'r' => 0],
                    ['p' => '¿Qué necesita para que sus productos salgan en el Marketplace LocalProo?', 'o' => ['Tener la tienda en línea activa y productos con foto', 'Pagar un anuncio aparte', 'Llamar a cada cliente'], 'r' => 0],
                    ['p' => 'Alguien pide que no le escriban más. ¿Qué hace?', 'o' => ['Lo marca como "No le interesa"', 'Le sigue escribiendo', 'Le escribe desde otro número'], 'r' => 0],
                ],
            ],

            'inventario' => [
                'no_tipos' => ['eventos'], 'titulo' => 'Inventario sin pérdidas', 'icono' => 'fa-boxes-stacked', 'color' => '#8b5cf6', 'para' => 'dueno',
                'resumen' => 'Entradas de mercancía, etiquetas con código de barras, existencias, conteo físico con el celular y cómo evitar faltantes.',
                'lecciones' => [
                    ['titulo' => 'Registrar la mercancía que llega', 'min' => 3, 'accion' => ['inventario.index', 'Ver inventario'], 'contenido' => [
                        'Cada vez que llega mercancía, regístrela como <b>entrada</b> (o compra): suma al inventario y guarda el costo.',
                        'Si cambia el costo del proveedor, actualícelo: así la ganancia de los reportes es real.',
                        '¿Tiene la lista de proveedores en Excel o en una hoja de Google? En <b>Proveedores → Importar Excel de proveedores</b> la sube y LocalPro reconoce solo las columnas (nombre, NIT, celular, correo, dirección).',
                    ], 'tip' => 'Imprima las etiquetas con código de barras al registrar la entrada: vender después es mucho más rápido.'],
                    ['titulo' => 'Revisar existencias', 'min' => 2, 'accion' => ['inventario.movimientos', 'Ver movimientos'], 'contenido' => [
                        'En <b>Inventario</b> ve cuánto queda de cada producto, talla y color, y en <b>Movimientos</b> quién vendió, devolvió o ajustó.',
                        'Haga un conteo físico al menos una vez al mes y compare con LocalPro. Si no cuadra, revise los movimientos.',
                    ], 'tip' => null],
                    ['titulo' => 'Qué comprar y qué no', 'min' => 2, 'accion' => ['reportes.index', 'Ver reportes'], 'contenido' => [
                        'En <b>Reportes</b> mire los productos más vendidos: de esos nunca se debe quedar sin existencias.',
                        'Los que llevan meses sin venderse son plata quieta: póngalos en <b>promoción</b> y compre menos de esos.',
                    ], 'tip' => 'Compre seguido y poco de lo que rota, en vez de mucho de todo.'],
                    ['titulo' => 'Conteo físico con el celular', 'min' => 3, 'accion' => ['inventario.conteos.index', 'Hacer un conteo'], 'contenido' => [
                        'En <b>Inventario → Resumen</b> ve cuántas prendas tiene por <b>talla</b>, por <b>categoría</b> y por <b>color</b>, y cuánta plata hay en mercancía.',
                        'Para contar, abra un <b>Conteo físico</b> (de toda la tienda o de una categoría), toque <b>Escanear prendas</b> y pase la cámara por cada etiqueta: cada lectura suma una. También puede escribir la cantidad.',
                        'Al tocar <b>Terminé</b> el sistema le dice cuáles <b>están</b>, cuáles <b>faltan</b>, cuáles <b>no aparecieron</b> y cuáles <b>sobran</b>. El dueño revisa y con <b>Ajustar inventario</b> el sistema queda igual a lo físico.',
                    ], 'tip' => 'Active "Contar a ciegas" para que quien cuenta no vea lo que dice el sistema: así el conteo sale honesto.'],
                    ['titulo' => 'Etiquetas con código de barras', 'min' => 3, 'accion' => ['etiquetas.todas', 'Ver mis etiquetas'], 'contenido' => [
                        'LocalPro le crea un <b>código de barras a cada producto</b>, y uno distinto a <b>cada talla y color</b> (por ejemplo P011302 = camiseta, talla M, negra). Así el inventario se descuenta de la prenda exacta.',
                        'Si el producto ya trae <b>código de fábrica</b> (EAN o UPC), escríbalo o escanéelo en la talla y color del producto y LocalPro lo reconoce.',
                        'Para imprimir: <b>Productos → Etiquetas</b> (de un producto o de todos) o, cuando llega mercancía, <b>Entrada → Etiquetas de lo que llegó</b>. Elija cuántas, qué mostrar (nombre, precio, talla y color) y el papel: <b>rollo de 50 × 25 mm</b> (impresora de etiquetas) u <b>hoja carta</b> con 40 o 24 por hoja en cualquier impresora.',
                        'No necesita comprar lector: en la caja y en el conteo se escanea con la <b>cámara del celular</b>.',
                    ], 'tip' => 'Pegue la etiqueta en una parte plana de la prenda y en negro sobre blanco: así se lee al primer intento.'],
                    ['titulo' => 'Selle la mercancía antes de crearla', 'min' => 3, 'accion' => ['etiquetas.nuevas', 'Imprimir etiquetas en blanco'], 'contenido' => [
                        '¿Llegó mucha mercancía o quedó algo sin código? No tiene que crear todo primero: en <b>Productos → Etiquetas para mercancía nueva</b> imprima códigos de barras <b>en blanco</b> (sin nombre ni precio).',
                        'Péguelos a toda la mercancía de una vez. Después, cuando cree cada producto, en cada <b>talla y color</b> toque <b>Escanear</b> y pase la etiqueta: el producto queda con <b>ese código</b> y no hay que volver a imprimir.',
                        'Lo más fácil es <b>un código por talla/color</b> con varias copias. Si prendas iguales quedaron con códigos distintos, escanéelos todos en la misma talla/color: todos sirven en la caja y en el conteo.',
                        'Si en el conteo o en la caja escanea una etiqueta que todavía no tiene producto, LocalPro le avisa y le deja crearlo con ese código.',
                    ], 'tip' => 'Imprima un lote al recibir el pedido del proveedor y selle mientras desempaca: el inventario queda listo el mismo día.'],
                ],
                'prueba' => [
                    ['p' => 'Llegó mercancía nueva. ¿Qué hago en LocalPro?', 'o' => ['Registrarla como entrada', 'Nada, se suma sola', 'Crear el producto otra vez'], 'r' => 0],
                    ['p' => '¿Cada cuánto conviene hacer conteo físico?', 'o' => ['Al menos una vez al mes', 'Nunca', 'Cada 5 años'], 'r' => 0],
                    ['p' => '¿Qué hago con lo que lleva meses sin venderse?', 'o' => ['Ponerlo en promoción', 'Comprar más de eso', 'Subirle el precio'], 'r' => 0],
                    ['p' => 'En el conteo físico escaneé todas las prendas y toqué Terminé. ¿Qué me muestra?', 'o' => ['Cuáles están, cuáles faltan y cuáles sobran', 'Nada, hay que contar en un cuaderno', 'Solo el total de ventas'], 'r' => 0],
                    ['p' => 'Llegaron 20 camisetas nuevas. ¿Cómo saco sus etiquetas con código de barras?', 'o' => ['Desde la entrada: Etiquetas de lo que llegó', 'Las dibujo a mano', 'No se puede, hay que comprar un lector'], 'r' => 0],
                    ['p' => 'Llegó mercancía que todavía no está creada. ¿Cómo la sello con código de barras ya?', 'o' => ['Imprimo etiquetas en blanco y al crear el producto escaneo la etiqueta', 'Espero a crear todo para poder etiquetar', 'Le pego el precio a mano'], 'r' => 0],
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

            // Solo aparece en institutos y academias (y para los asesores, que lo venden)
            'instituto' => [
                'titulo' => 'Su instituto en LocalPro', 'icono' => 'fa-graduation-cap', 'color' => '#6366f1', 'para' => ['todos', 'asesor'], 'modulo' => 'es_instituto', 'tipos' => ['instituto'],
                'resumen' => 'Programas, inscripciones, matrículas de contado o a crédito, cuotas, recibos y cartera de estudiantes.',
                'lecciones' => [
                    ['titulo' => 'Programas e inscripciones', 'min' => 3, 'accion' => ['instituto.programas', 'Ver programas'], 'contenido' => [
                        'En <b>Programas</b> cree los técnicos o cursos que ofrece, con la duración y el <b>valor del semestre y de la inscripción</b>. Puede cargar los de ejemplo y editarlos.',
                        'En cada programa ponga su lista de precios: <b>precio pleno</b>, <b>precio de contado</b> (con descuento) y <b>financiado</b> (cuota inicial + número de cuotas). LocalPro calcula el valor de cada cuota y el % de descuento.',
                        'En <b>Nueva inscripción</b> llena el mismo formulario de papel: datos personales, trabajo, estudios y acudiente. El consecutivo sale solo.',
                        'Marque las <b>autorizaciones</b> de datos personales (Ley 1581 de 2012) y de uso de imagen. Con un botón imprime el formulario lleno para que lo firme.',
                    ], 'tip' => 'Si el programa tiene valor de inscripción, el cobro queda cargado al aspirante desde el primer día.'],
                    ['titulo' => 'La matrícula y las cuotas', 'min' => 3, 'accion' => ['instituto.estudiantes', 'Ver estudiantes'], 'contenido' => [
                        'En la ficha del estudiante toque <b>Matricular</b>: programa, periodo, semestre, jornada y módulos pendientes.',
                        'Elija <b>contado</b> o <b>crédito</b>: si el programa tiene sus precios, el valor, la cuota inicial y el número de cuotas se llenan solos (y los puede cambiar). El sistema arma las cuotas con la fecha de pago de cada mes.',
                        'Imprima el <b>formato de matrícula</b> lleno, con las autorizaciones y las firmas.',
                    ], 'tip' => 'Las cuotas vencen el día que usted elija (por defecto el 5: se pagan del 1 al 5 de cada mes).'],
                    ['titulo' => 'Pagos, recibos y cartera', 'min' => 2, 'accion' => ['instituto.cartera', 'Ver cartera'], 'contenido' => [
                        'En la ficha del estudiante registre cada pago: se aplica a la cuota más antigua y sale un <b>recibo numerado</b> para imprimir o enviar por WhatsApp.',
                        'En <b>Cartera y morosos</b> ve quién debe, cuánto y desde hace cuántos días, y le recuerda por WhatsApp con un toque.',
                        'Solo el dueño o el administrador pueden anular recibos, cobros o matrículas.',
                    ], 'tip' => 'Recuerde los pagos los primeros días del mes: así casi nadie se atrasa.'],
                ],
                'prueba' => [
                    ['p' => 'Un estudiante paga el semestre en cuotas. ¿Qué elijo al matricularlo?', 'o' => ['Crédito, con la cuota inicial y el número de cuotas', 'Contado', 'No se puede'], 'r' => 0],
                    ['p' => '¿Dónde veo quién está atrasado con las cuotas?', 'o' => ['En Cartera y morosos', 'En Programas', 'En ningún lado'], 'r' => 0],
                    ['p' => 'Al registrar un pago, ¿a qué cuota se aplica?', 'o' => ['A la más antigua primero', 'A la última', 'A ninguna'], 'r' => 0],
                ],
            ],

            // Solo aparece en negocios con el módulo Eventos (y para los asesores, que lo venden)
            'eventos' => [
                'titulo' => 'Venda entradas con LocalProo Eventos', 'icono' => 'fa-ticket', 'color' => '#a855f7', 'para' => ['todos', 'asesor'], 'modulo' => 'mod_eventos', 'tipos' => ['eventos'],
                'resumen' => 'Página del evento, registro de compradores, pago por Nequi con comprobante por WhatsApp, aprobar pagos y escanear el QR en la puerta.',
                'lecciones' => [
                    ['titulo' => 'Cree el evento y comparta la página', 'rutas' => [['eventos', 'Nuevo evento', 'Guardar evento']], 'min' => 3, 'accion' => ['eventos.create', 'Crear evento'], 'contenido' => [
                        'En <b>Eventos y entradas → Nuevo evento</b> escriba el nombre, la fecha y hora, el lugar y la publicidad. Suba el <b>afiche</b> (vertical se ve mejor en celular) y, si quiere, un segundo afiche con la información.',
                        'Ponga la <b>etapa</b> (ej. "Primera etapa"), el <b>precio</b> y hasta cuándo va esa etapa. Para la segunda etapa solo cambie nombre, precio y fecha: los que ya se registraron conservan su precio.',
                        'Escriba su <b>Nequi</b> (o la cuenta donde recibe) y el <b>WhatsApp</b> donde le mandan el comprobante. Cambie el estado a <b>Publicado</b> y copie el enlace <b>localproo.com/evento/…</b> para Instagram, TikTok y WhatsApp.',
                    ], 'tip' => 'Mientras está en Borrador solo usted ve la página: úsela para revisar cómo queda antes de publicar.'],
                    ['titulo' => 'Cómo compra la gente', 'min' => 2, 'accion' => null, 'contenido' => [
                        'La persona abre la página, toca <b>Comprar entrada</b> y llena sus datos: nombre, correo, cédula o tarjeta de identidad, celular, fecha de nacimiento, una <b>clave</b> y las preguntas que el organizador agregue en <b>Formulario de registro</b>. Toca <b>Terminar registro</b>.',
                        'Le sale: <b>"Envía tu pago a este Nequi"</b> con el número y el valor, y el botón <b>Enviar comprobante de pago</b>, que abre su WhatsApp con el mensaje listo ("Hola, ya pagué aquí está mi comprobante" + su nombre, documento y número de registro). Solo adjunta la captura.',
                        'Si es menor de edad, debe marcar que su padre, madre o acudiente autoriza el registro. Todos autorizan el tratamiento de datos (Ley 1581 de 2012).',
                    ], 'tip' => null],
                    ['titulo' => 'Aprobar pagos', 'rutas' => [['eventos', 'Compradores', 'Por aprobar', 'Aprobar pago']], 'min' => 2, 'accion' => ['eventos.index', 'Ver mis eventos'], 'contenido' => [
                        'En el evento, la pestaña <b>Por aprobar</b> muestra primero (con borde verde) a los que ya tocaron "Enviar comprobante". Compare la captura que le llegó por WhatsApp con el nombre y el valor.',
                        'Toque <b>Aprobar pago</b> (puede anotar la referencia de Nequi). El sistema le genera la entrada con <b>QR único</b>, le manda un correo y usted le puede avisar por WhatsApp con un toque.',
                        'Si se equivocó: <b>Deshacer</b> la devuelve a Por aprobar y <b>Anular</b> deja el QR sin servir. Si alguien olvidó la clave, <b>Restablecer clave</b> le pone como clave su número de documento.',
                    ], 'tip' => 'Descargue la lista en Excel para cuadrar con los movimientos de su Nequi.'],
                    ['titulo' => 'La puerta: escanear el QR', 'rutas' => [['eventos', 'Puerta', 'Escanear el QR']], 'min' => 2, 'accion' => null, 'contenido' => [
                        'La persona entra a <b>Mi entrada</b> con su correo y su clave y muestra el QR (se recomienda descargarlo en <b>PDF</b> por si no hay internet).',
                        'En la puerta abra <b>Puerta</b> en el celular y escanee: <b>verde</b> = puede entrar; <b>rojo</b> = ya se usó o no existe; <b>naranja</b> = el pago no está aprobado. Cada QR sirve <b>una sola vez</b>, aunque dos porteros escaneen a la vez.',
                        'Si no lee el QR, busque por número de documento y toque <b>Dejar entrar</b>. Para que alguien solo maneje la puerta, créele el rol <b>Puerta (eventos)</b> en Roles del negocio.',
                    ], 'tip' => 'Revise el documento contra el nombre que sale en pantalla: así nadie entra con el QR de otro.'],
                    ['titulo' => 'Su formulario de registro y dónde está cada cosa', 'rutas' => [['eventos', 'Compradores', 'Editar', 'Formulario de registro', 'Agregar campo'], ['eventos', 'Compradores', 'Editar', 'Estilo, logo y afiches (columna derecha)']], 'min' => 3, 'accion' => ['eventos.index', 'Ver mis eventos'], 'contenido' => [
                        'En <b>Editar → Formulario de registro</b> decide si pide fecha de nacimiento y celular. Con <b>Agregar campo</b> crea sus propias preguntas (texto, número, fecha, lista de opciones o Sí/No) y las marca como obligatorias u opcionales; la papelera las quita. Las respuestas salen en Compradores y en el Excel.',
                        'Fecha, lugar, precio, etapa y Nequi: <b>Editar</b>. Afiche, logo, estilo (ej. Terror) y color: columna derecha de <b>Editar</b>. Pagos: <b>Compradores → Por aprobar</b>. Lista: <b>Excel</b>. Entrada: <b>Puerta</b>.',
                        'En <b>Editar → Publicación</b> elige si la página muestra "X personas han visto este evento". En Compradores ve las visitas y qué porcentaje se registró.',
                    ], 'tip' => 'Pida solo lo necesario: cada pregunta de más hace que menos gente termine el registro.'],
                    ['titulo' => 'Promociones y compras en grupo', 'rutas' => [['eventos', 'Compradores', 'Editar', 'Promociones', 'Agregar promoción']], 'min' => 3, 'accion' => ['eventos.index', 'Ver mis eventos'], 'contenido' => [
                        'En <b>Editar → Entrada y venta</b> ponga el <b>máximo de entradas por compra</b> (ej. 10). Así una persona puede comprar para sus amigos: llena el nombre y el documento de cada uno y <b>cada uno recibe su propio QR</b>.',
                        'En <b>Editar → Promociones</b> toque <b>Agregar promoción</b>: escriba cuántas compra (ej. 2) y qué pasa con la siguiente (<b>Gratis</b> o un % de descuento). Ejemplos: "Compra 2 y la 3.ª es gratis", "Compra 5 y la 6.ª con 50 % de descuento". Puede ponerle fecha de fin y apagarla cuando quiera.',
                        'La promo sale en la página del evento y el comprador ve el total con el ahorro al elegir la cantidad. El sistema calcula el precio de cada entrada (no se puede cambiar desde el navegador) y, si hay varias promos, aplica la que más le ahorra.',
                        'Toda la compra se paga con un solo comprobante: en <b>Compradores</b> aprueba en la fila del comprador y se aprueban todas sus entradas juntas.',
                        '¿Alguien ya compró y quiere otra entrada para un amigo? Como cada entrada es personal y el ingreso es único, en la página del evento toca <b>Comprar otra entrada</b>: puede <b>enviar el enlace por WhatsApp</b> para que el amigo se registre con sus datos, o <b>crear una cuenta nueva en su celular</b> (se cierra su sesión y su entrada sigue guardada). El amigo paga y recibe su propio QR.',
                    ], 'tip' => 'Las promos se repiten: con "compra 2 y la 3.ª gratis", quien compra 6 lleva 2 gratis. Úselas en preventa o los últimos días para mover la venta.'],
                    ['titulo' => 'Antes de publicar: haga una prueba completa', 'rutas' => [['eventos', 'Compradores', 'Editar', 'Publicación: Borrador'], ['eventos', 'Puerta', 'Escanear el QR de prueba']], 'min' => 3, 'accion' => null, 'contenido' => [
                        'Deje el evento en <b>Borrador</b> (solo usted ve la página) y ábrala en su celular: revise afiche, fecha, precio y que el Nequi esté bien escrito.',
                        'Regístrese como comprador en una <b>ventana de incógnito</b> o en otro celular, con un correo suyo. Toque <b>Enviar comprobante</b> y mire que abra el WhatsApp correcto.',
                        'Apruebe ese registro, revise el correo y el QR en <b>Mi entrada</b> (y el PDF). Escanéelo en <b>Puerta</b>: verde la primera vez, rojo la segunda. Luego <b>anule</b> la entrada de prueba y cambie el evento a <b>Publicado</b>.',
                    ], 'tip' => 'El día del evento: celulares con batería y datos, brillo alto, un portero por fila y pídale a la gente que descargue el PDF de su entrada.'],
                    ['titulo' => 'Su equipo: Contabilidad, Logística, Diseño y Puerta', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Al activar Eventos ya quedan creados los roles <b>Administrador del evento</b>, <b>Contabilidad</b>, <b>Logística</b>, <b>Diseño</b> y <b>Puerta</b>. No tiene que inventarlos.',
                        '<b>Contabilidad</b> aprueba o rechaza pagos y baja el Excel. <b>Logística</b> edita fecha, lugar, descripción, condiciones y redes, y usa la puerta. <b>Diseño</b> cambia afiches, logo, estilo y color. El precio, el Nequi y publicar solo los toca el <b>Administrador del evento</b>.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona y elija su rol. Al entrar a LocalProo llega directo a Eventos y no ve ventas, caja ni inventario. Los permisos de cada rol se cambian en <b>Roles del negocio</b>.',
                    ], 'tip' => 'Dele a cada persona solo lo que necesita: así nadie cambia el precio o el Nequi por error.'],
                ],
                'prueba' => [
                    ['p' => '¿Cuándo recibe la persona su entrada con QR?', 'o' => ['Cuando usted aprueba el pago', 'Apenas se registra', 'El día del evento'], 'r' => 0],
                    ['p' => 'Un QR ya se usó y alguien lo vuelve a mostrar en la puerta. ¿Qué pasa?', 'o' => ['Sale en rojo: "ya se usó" y no deja entrar', 'Deja entrar otra vez', 'Se borra el evento'], 'r' => 0],
                    ['p' => 'Pasó la primera etapa y ahora la entrada vale más. ¿Qué hace?', 'o' => ['Edita el evento: cambia la etapa, el precio y la fecha', 'Crea un evento nuevo', 'No se puede'], 'r' => 0],
                    ['p' => 'Quiere que quien compre 2 entradas lleve la 3.ª gratis. ¿Qué hace?', 'o' => ['Editar → Promociones: Compra 2, la siguiente Gratis', 'Bajar el precio a todos', 'No se puede'], 'r' => 0],
                    ['p' => 'Alguien ya tiene su entrada y quiere comprar otra para un amigo. ¿Qué le dice?', 'o' => ['Que toque Comprar otra entrada y le envíe el enlace por WhatsApp o le cree una cuenta nueva', 'Que vuelva a comprar con su misma cuenta', 'No se puede'], 'r' => 0],
                    ['p' => 'Su diseñadora debe cambiar el afiche, pero no el precio. ¿Qué rol le da?', 'o' => ['Diseño', 'Administrador del evento', 'Contabilidad'], 'r' => 0],
                    ['p' => 'Una persona olvidó su clave. ¿Qué hace?', 'o' => ['Restablecer clave: queda su número de documento', 'Borra su registro', 'Le crea otra cuenta con el mismo correo'], 'r' => 0],
                ],
            ],

            // Solo aparece en negocios con el módulo Consultorio (y para los asesores, que lo venden)
            'consultorio' => [
                'titulo' => 'Su consultorio en LocalPro', 'icono' => 'fa-user-doctor', 'color' => '#0ea5e9', 'para' => ['todos', 'asesor'], 'modulo' => 'mod_consultorio', 'tipos' => ['consultorio'],
                'resumen' => 'Pacientes, agenda de citas, historia clínica firmada, fórmulas e incapacidades, y cómo se cuida la información.',
                'lecciones' => [
                    ['titulo' => 'Pacientes y agenda', 'min' => 3, 'accion' => ['consultorio.agenda', 'Ir a la agenda'], 'contenido' => [
                        'Cree cada paciente con su documento, celular y fecha de nacimiento. Marque la <b>autorización de tratamiento de datos</b> (Ley 1581 de 2012).',
                        'En <b>Agenda</b> elige el día, busca el paciente y le pone hora, duración y motivo. El sistema no deja cruzar dos citas del mismo profesional.',
                        'Con el botón <b>Recordar</b> le manda el recordatorio por WhatsApp en un toque. Cuando confirme, márquela como <b>Confirmó</b>.',
                    ], 'tip' => 'Recuerde las citas el día anterior: baja mucho la cantidad de pacientes que no llegan.'],
                    ['titulo' => 'La historia clínica', 'min' => 4, 'accion' => ['consultorio.pacientes', 'Ver pacientes'], 'contenido' => [
                        'Desde la cita o la ficha del paciente toque <b>Atender</b>. Elija el tipo de historia: medicina general, odontología, psicología, estética, fisioterapia o nutrición.',
                        'Escriba motivo, enfermedad actual, signos vitales (el IMC sale solo), examen, <b>diagnósticos CIE-10</b> (busque por palabra, ej. "lumbago") y plan.',
                        'Mientras escribe se guarda solo. Al terminar toque <b>Cerrar y firmar</b>: queda con su nombre y registro y <b>ya no se puede cambiar</b>. Si se equivocó, agregue una <b>nota aclaratoria</b>.',
                    ], 'tip' => 'Suba su firma y su registro profesional en "Mi perfil y firma": salen en todo lo que imprima.'],
                    ['titulo' => 'Fórmulas, incapacidades y cobro', 'min' => 2, 'accion' => ['consultorio.inicio', 'Ir al consultorio'], 'contenido' => [
                        'Escriba la fórmula (un medicamento por línea) y los días de incapacidad en la misma atención. Al cerrarla, imprímalas o guárdelas en PDF para el paciente.',
                        'Cobre la consulta en la <b>caja</b> como cualquier venta: consulta de primera vez, control, valoración o paquete de sesiones.',
                    ], 'tip' => null],
                    ['titulo' => 'La información está protegida', 'min' => 2, 'accion' => null, 'contenido' => [
                        'La historia clínica es <b>reservada</b>: solo la ven los usuarios con permiso de Historia clínica. Recepción maneja la agenda y los datos de contacto.',
                        'Lo clínico se guarda <b>cifrado</b>, y el dueño ve quién abrió, escribió o imprimió cada historia.',
                        'Un paciente con historia clínica <b>no se puede borrar</b>: la ley obliga a conservarla.',
                    ], 'tip' => 'Nunca preste su usuario: lo que se firma con él queda a su nombre.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b>, <b>Recepción del consultorio</b> (agenda, caja y pacientes) y <b>Profesional de salud</b> (agenda e historia clínica). Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => 'Cerré una atención y me equivoqué en una dosis. ¿Qué hago?', 'o' => ['Agrego una nota aclaratoria', 'Borro la atención', 'Le pido a LocalPro que la cambie'], 'r' => 0],
                    ['p' => '¿Quién puede ver la historia clínica?', 'o' => ['Solo quien tiene permiso de Historia clínica', 'Cualquier usuario del negocio', 'Cualquier persona con el enlace'], 'r' => 0],
                    ['p' => '¿Cómo le recuerdo la cita al paciente?', 'o' => ['Con el botón Recordar por WhatsApp', 'No se puede', 'Llamando a LocalPro'], 'r' => 0],
                    ['p' => '¿Qué hay que pedirle al paciente al registrarlo?', 'o' => ['La autorización de tratamiento de datos', 'La clave de su correo', 'Nada'], 'r' => 0],
                ],
            ],

            // ===== Cursos por tipo de negocio: "Cómo manejar su sistema" =====
            'restaurante' => [
                'titulo' => 'Su restaurante o cafetería en LocalPro', 'icono' => 'fa-burger', 'color' => '#f97316', 'para' => ['todos', 'asesor'], 'modulo' => 'mod_pedidos', 'tipos' => ['restaurante', 'cafeteria'],
                'resumen' => 'Pedidos de mesero a caja y cocina, roles del equipo, cobrar antes o al final, avisos de "listo", cierre del día y su carta virtual.',
                'lecciones' => [
                    ['titulo' => 'Configure sus pedidos', 'rutas' => [['pedidos', 'Configurar', 'Estaciones y forma de cobro']], 'min' => 3, 'accion' => ['pedidos.configurar', 'Configurar pedidos'], 'contenido' => [
                        'En <b>Pedidos → Configurar</b> elija cómo cobra: <b>paga primero</b> (el pedido llega a cocina cuando la caja cobra) o <b>paga al final</b> (llega de una y el mesero pide la cuenta al terminar).',
                        'Cree sus <b>estaciones</b> (Cocina, Bar, Cafetería…) y diga qué estación prepara cada producto o categoría.',
                        'Si quiere, active la <b>pantalla para clientes</b>: un televisor que muestra "Su pedido está listo, Juan".',
                    ], 'tip' => 'Empiece con una sola estación (Cocina) y agregue Bar cuando el equipo ya esté cómodo.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['roles-negocio.index', 'Ver roles'], 'contenido' => [
                        'Los roles ya vienen creados: <b>Administrador, Caja, Mesero, Cocina, Bar</b> (o Cafetería). Revíselos en <b>Roles del negocio</b> y ajuste permisos si quiere.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona y elija su rol. Al entrar, cada uno llega directo a su pantalla: el mesero a sus pedidos, la cocina a su estación.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién tomó, cobró o preparó cada pedido.'],
                    ['titulo' => 'Mesero, caja y cocina en acción', 'rutas' => [['pedidos', 'Tomar pedido', 'Enviar']], 'min' => 3, 'accion' => ['pedidos.mesero', 'Tomar un pedido'], 'contenido' => [
                        'El <b>mesero</b> toca "Tomar pedido", pone mesa o nombre del cliente y notas por producto ("sin cebolla").',
                        'La <b>caja</b> ve los pedidos por cobrar, toca <b>Cobrar</b> y se abre la caja (POS) con el pedido cargado.',
                        'La <b>cocina</b> ve las tarjetas con reloj (amarillo a los 10 min, rojo a los 20), marca <b>Todo listo</b> y al mesero le suena el aviso para entregar.',
                    ], 'tip' => 'Haga una ronda de prueba con el equipo antes de abrir: un pedido completo de mesero a entregado.'],
                    ['titulo' => 'Cierre del día', 'rutas' => [['pedidos', 'Cierre']], 'min' => 2, 'accion' => ['pedidos.cierre', 'Ver el cierre'], 'contenido' => [
                        'En <b>Pedidos → Cierre</b> ve los pedidos del día, lo cobrado y lo que quedó pendiente.',
                        'Cierre la caja con el conteo del efectivo para que cuadre con las ventas.',
                    ], 'tip' => null],
                    ['titulo' => 'Carta virtual y platos agotados', 'min' => 3, 'accion' => ['productos.index', 'Ver productos'], 'contenido' => [
                        'En el panel toque <b>Ver mi carta virtual</b>: su menú por categorías con pedido por WhatsApp (domicilio, recoger o en la mesa). Compártala o póngala en un QR en las mesas.',
                        'En <b>Mi negocio</b> elija el diseño de la carta: <b>Bistró</b>, <b>Fresca</b>, <b>Parrilla</b> o <b>Huerta</b>. Con "Ver cómo queda" los compara antes de guardar.',
                        'Para almuerzos no tiene que poner cantidades: apague <b>Controlar inventario</b>. Cuando se acaben, en <b>Productos</b> toque <b>Desactivar</b>: sale "Agotado por hoy" y nadie lo puede pedir (ni en la carta, ni en la caja, ni con los meseros). Al día siguiente toque <b>Activar</b>.',
                    ], 'tip' => 'Desactivar no borra el plato ni sus ventas: solo lo deja de ofrecer hasta que lo active.'],
                ],
                'prueba' => [
                    ['p' => 'En modo "paga primero", ¿cuándo llega el pedido a la cocina?', 'o' => ['Cuando la caja lo cobra', 'Apenas el mesero lo toma', 'Al día siguiente'], 'r' => 0],
                    ['p' => '¿Dónde crea el usuario de un mesero nuevo?', 'o' => ['Usuarios → Nuevo usuario, con el rol Mesero', 'En Productos', 'No se puede'], 'r' => 0],
                    ['p' => 'La tarjeta de un pedido se puso roja en la cocina. ¿Qué significa?', 'o' => ['Lleva más de 20 minutos', 'Ya se pagó', 'Se anuló'], 'r' => 0],
                    ['p' => 'Se acabaron los almuerzos de hoy. ¿Qué hace para que no los pidan en la carta?', 'o' => ['En Productos toca Desactivar en el almuerzo', 'Borra el producto', 'Cambia el precio a cero'], 'r' => 0],
                ],
            ],

            'funeraria' => [
                'titulo' => 'Su funeraria en LocalPro', 'icono' => 'fa-dove', 'color' => '#64748b', 'para' => ['todos', 'asesor'], 'modulo' => 'mod_obituarios', 'tipos' => ['funeraria'],
                'resumen' => 'Salas de velación, plantillas, obituarios en el televisor de cada sala y cómo compartirlos por WhatsApp.',
                'lecciones' => [
                    ['titulo' => 'Salas y televisores', 'rutas' => [['obituarios', 'Salas', 'Código del TV']], 'min' => 3, 'accion' => ['obituarios.index', 'Ver obituarios'], 'contenido' => [
                        'Cree cada <b>sala de velación</b>. Cada una tiene un <b>código de 4 números</b> para su televisor.',
                        'En el navegador del TV escriba <b>localproo.com/tv</b> y digite el código con el control. Queda en pantalla completa y se actualiza sola.',
                        'Para recepción está la <b>pantalla general</b>, que rota todos los obituarios activos.',
                    ], 'tip' => 'Si el TV no es Smart, un Fire TV Stick o Google TV con navegador funciona muy bien.'],
                    ['titulo' => 'Plantillas', 'rutas' => [['obituarios', 'Plantillas', 'Nueva plantilla']], 'min' => 2, 'accion' => ['obituarios.plantillas', 'Ver plantillas'], 'contenido' => [
                        'Elija un diseño (mármol, negro y dorado, cielo, floral, vela, sencillo), la medida de la pantalla y el fondo: color, imagen o video.',
                        'Agregue animaciones suaves, su frase y su logo. Las plantillas se reutilizan y se pueden copiar.',
                    ], 'tip' => null],
                    ['titulo' => 'Crear y mostrar un obituario', 'rutas' => [['obituarios', 'Nuevo obituario', 'Guardar', 'Ver ya en la pantalla']], 'min' => 3, 'accion' => ['obituarios.create', 'Nuevo obituario'], 'contenido' => [
                        'Llene nombre, foto, fechas, exequias y quiénes invitan (una línea por grupo). La vista previa se ve en vivo.',
                        '<b>Guarde</b>: la pantalla solo muestra lo guardado. Con <b>Ver ya en la pantalla</b> sale de inmediato en la sala.',
                        'Descargue la imagen o el video y envíela por <b>WhatsApp</b> a la familia.',
                    ], 'tip' => 'Si el TV muestra la pantalla de espera, revise que el obituario esté guardado, asignado a esa sala y con fecha "hasta" futura.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b>, <b>Recepción</b> (obituarios, caja y clientes), <b>Salas y obituarios</b> (solo obituarios) y <b>Contador</b>. Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => '¿Cómo abre el TV de una sala?', 'o' => ['localproo.com/tv y el código de 4 números', 'Con su clave de LocalPro', 'Por Bluetooth'], 'r' => 0],
                    ['p' => 'El obituario no sale en el TV. ¿Qué hace primero?', 'o' => ['Tocar "Ver ya en la pantalla"', 'Borrar la sala', 'Apagar el TV un día'], 'r' => 0],
                    ['p' => '¿La vista previa en vivo ya sale en el TV?', 'o' => ['No, hay que guardar', 'Sí, siempre', 'Solo los lunes'], 'r' => 0],
                ],
            ],

            'moda' => [
                'titulo' => 'Su tienda de ropa, calzado o accesorios', 'icono' => 'fa-shirt', 'color' => '#ec4899', 'para' => ['todos', 'asesor'], 'tipos' => ['moda', 'calzado', 'accesorios'],
                'resumen' => 'Tallas y colores, etiquetas con código, separados con abonos y su catálogo en línea.',
                'lecciones' => [
                    ['titulo' => 'Tallas y colores', 'rutas' => [['productos', 'Editar producto', 'Tallas y colores']], 'min' => 3, 'accion' => ['productos.index', 'Ver productos'], 'contenido' => [
                        'Cree el producto una vez y agregue sus <b>variantes</b>: cada talla y color con su propia cantidad.',
                        'En la caja elige la talla y el color, y el inventario baja solo de esa combinación.',
                    ], 'tip' => 'Revise cada semana qué tallas se agotan primero para pedir mejor.'],
                    ['titulo' => 'Etiquetas con código', 'min' => 2, 'accion' => ['etiquetas.todas', 'Imprimir etiquetas'], 'contenido' => [
                        'Imprima etiquetas con código para cada prenda. En la caja la busca rápido sin escribir.',
                        'Cuando le llega mercancía nueva, imprima solo las etiquetas de esa entrada.',
                    ], 'tip' => null],
                    ['titulo' => 'Separados y catálogo', 'rutas' => [['separados', 'Nuevo separado'], ['catalogo', 'Compartir enlace']], 'min' => 3, 'accion' => ['separados.index', 'Ver separados'], 'contenido' => [
                        'Con <b>Separados</b> el cliente aparta una prenda con un abono, paga en cuotas y se la lleva al terminar. El producto queda reservado.',
                        'Active su <b>catálogo en línea</b> y compártalo por WhatsApp e Instagram: los clientes ven fotos, tallas y precios.',
                    ], 'tip' => 'Fotos claras y con buena luz venden más en el catálogo.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b>, <b>Vendedor</b> (caja, clientes y separados), <b>Bodega e inventario</b> (productos, entradas y proveedores) y <b>Redes y ventas en línea</b> (Mis redes, Mis enlaces y catálogo). Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => '¿Cómo maneja la misma camisa en talla S, M y L?', 'o' => ['Un producto con variantes de talla', 'Tres productos sin relación', 'No se puede'], 'r' => 0],
                    ['p' => 'Un cliente quiere apartar un vestido y pagarlo en cuotas. ¿Qué usa?', 'o' => ['Separados', 'Un egreso', 'Borrar el producto'], 'r' => 0],
                    ['p' => '¿Para qué sirven las etiquetas con código?', 'o' => ['Para buscar rápido la prenda en la caja', 'Para cambiar el precio', 'Para nada'], 'r' => 0],
                ],
            ],

            'tienda' => [
                'titulo' => 'Su tienda, minimercado o ferretería', 'icono' => 'fa-basket-shopping', 'color' => '#16a34a', 'para' => ['todos', 'asesor'], 'tipos' => ['minimercado', 'ferreteria', 'papeleria', 'tecnologia', 'otro'],
                'resumen' => 'Cargar muchos productos rápido, recibir mercancía de proveedores, vencimientos y ventas por metro o kilo.',
                'lecciones' => [
                    ['titulo' => 'Cargue sus productos rápido', 'rutas' => [['productos', 'Importar', 'Subir el Excel']], 'min' => 3, 'accion' => ['productos.importar', 'Importar productos'], 'contenido' => [
                        'Si ya los tiene en Excel, súbalos en <b>Productos → Importar</b>: el sistema revisa y le muestra antes de guardar.',
                        'Ponga a cada producto su <b>costo</b> y su <b>precio</b>: así LocalPro le calcula la ganancia real.',
                    ], 'tip' => 'Use el código de barras del empaque como código del producto para encontrarlo rápido en la caja.'],
                    ['titulo' => 'Llegó mercancía', 'rutas' => [['entradas', 'Nueva entrada', 'Elegir proveedor']], 'min' => 2, 'accion' => ['entradas.index', 'Ver entradas'], 'contenido' => [
                        'Registre lo que le llega en <b>Entradas</b>, con su <b>proveedor</b>. El inventario sube solo.',
                        'Así sabe a quién le compró, cuánto y a qué precio.',
                    ], 'tip' => null],
                    ['titulo' => 'Vencimientos, metros y kilos', 'rutas' => [['negocio', 'Módulos', 'Fechas de vencimiento']], 'min' => 2, 'accion' => ['negocio.edit', 'Datos del negocio'], 'contenido' => [
                        'En <b>Datos del negocio → Módulos</b> active <b>Fechas de vencimiento</b> para manejar lotes y recibir alertas de lo que está por vencer.',
                        'Si vende por metro, kilo o litro, active <b>Vender por metro, kilo o litro</b> y en la caja venda con decimales.',
                    ], 'tip' => 'Ponga adelante en el estante lo que vence primero.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b>, <b>Vendedor</b> (caja y clientes), <b>Bodega e inventario</b> (productos, entradas de mercancía y proveedores) y <b>Contador</b> (reportes, cierres, egresos y facturación). Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => 'Tengo 500 productos en Excel. ¿Qué hago?', 'o' => ['Productos → Importar', 'Crearlos uno por uno', 'No usarlos'], 'r' => 0],
                    ['p' => 'Me llegó un pedido del proveedor. ¿Dónde lo registro?', 'o' => ['En Entradas', 'En Egresos', 'En Clientes'], 'r' => 0],
                    ['p' => 'Vendo cable por metros. ¿Qué activo?', 'o' => ['Vender por metro, kilo o litro', 'Separados', 'Eventos'], 'r' => 0],
                ],
            ],

            'servicios' => [
                'titulo' => 'Su negocio de belleza y servicios', 'icono' => 'fa-spa', 'color' => '#a855f7', 'para' => ['todos', 'asesor'], 'tipos' => ['belleza'],
                'resumen' => 'Servicios sin inventario, productos para la venta y clientes que vuelven.',
                'lecciones' => [
                    ['titulo' => 'Servicios y productos', 'rutas' => [['productos', 'Activar servicios']], 'min' => 3, 'accion' => ['productos.index', 'Ver productos'], 'contenido' => [
                        'Cree sus <b>servicios</b> (cortes, tintes, manicure, barba…): no manejan inventario y siempre están disponibles.',
                        'Los <b>productos</b> que vende (shampoo, cremas) sí llevan inventario. En la caja cobra los dos juntos.',
                    ], 'tip' => 'Si no le aparece la opción de servicios, actívela en Datos del negocio → Módulos.'],
                    ['titulo' => 'Clientes que vuelven', 'rutas' => [['clientes', 'Escribir por WhatsApp']], 'min' => 2, 'accion' => ['clientes.index', 'Ver clientes'], 'contenido' => [
                        'Ponga el cliente en cada venta: así sabe qué se hizo y cuándo volvió.',
                        'Desde <b>Clientes</b> escríbales por WhatsApp a los que no han vuelto, con una promoción.',
                    ], 'tip' => 'Un mensaje al mes a sus clientes frecuentes trae de vuelta a muchos.'],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b>, <b>Caja</b> y <b>Estilista / barbero</b> (cobra sus servicios y ve sus clientes). Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => '¿Un corte de cabello lleva inventario?', 'o' => ['No, es un servicio', 'Sí, siempre', 'Depende del día'], 'r' => 0],
                    ['p' => '¿Para qué pongo el cliente en cada venta?', 'o' => ['Para saber cuándo volvió y escribirle', 'No sirve', 'Para subir el precio'], 'r' => 0],
                    ['p' => '¿Puedo cobrar un servicio y un producto en la misma venta?', 'o' => ['Sí', 'No', 'Solo con tarjeta'], 'r' => 0],
                ],
            ],

            'contador' => [
                'titulo' => 'Contabilidad para contadores: retenciones e IVA', 'icono' => 'fa-calculator', 'color' => '#0f766e', 'para' => ['todos', 'asesor'], 'modulo' => 'mod_contable', 'tipos' => ['contable'],
                'resumen' => 'Sus clientes, cruce del listado DIAN con la contabilidad, borrador del 350, IVA y calendario tributario.',
                'lecciones' => [
                    ['titulo' => 'Pruebe con datos de ejemplo', 'rutas' => [['contabilidad', 'Probar con datos de ejemplo']], 'min' => 2, 'accion' => ['contabilidad.index', 'Ir a Contabilidad'], 'contenido' => [
                        'Toque <b>Probar con datos de ejemplo</b>: se crea una empresa de ejemplo con un mes cargado para ver todo funcionando.',
                        'Descargue los archivos de muestra para ver qué columnas espera el sistema.',
                    ], 'tip' => null],
                    ['titulo' => 'Sus clientes y el mes', 'rutas' => [['contabilidad', 'Nuevo cliente', 'Abrir el mes', 'Subir archivos', 'Cruce']], 'min' => 3, 'accion' => ['contabilidad.index', 'Ver mis clientes'], 'contenido' => [
                        'Cree cada cliente con su NIT y si es agente de retención, autorretenedor, si practica reteIVA y su periodicidad de IVA.',
                        'Abra el mes y suba el <b>listado de la DIAN</b> y la <b>base contable</b> (por columnas o auxiliar por cuenta).',
                        'Revise el <b>cruce</b>: lo que no está contabilizado, lo que no tiene factura DIAN y las retenciones que faltaron o sobraron.',
                    ], 'tip' => 'La revisión final siempre es suya: el sistema no presenta en la DIAN.'],
                    ['titulo' => 'Calendario y recordatorios', 'rutas' => [['contabilidad', 'Calendario']], 'min' => 2, 'accion' => ['contabilidad.calendario', 'Ver calendario'], 'contenido' => [
                        'El calendario muestra los vencimientos de retención e IVA de cada cliente según el último dígito del NIT.',
                        'Le llega un correo 3 días antes y el mismo día. Marque "Presentado" para que no le vuelva a avisar.',
                    ], 'tip' => null],
                    ['titulo' => 'Su equipo y sus roles', 'rutas' => [['roles', 'Revisar los roles'], ['usuarios', 'Nuevo usuario', 'Elegir su rol']], 'min' => 2, 'accion' => ['users.index', 'Crear usuarios'], 'contenido' => [
                        'Su negocio ya trae roles listos: <b>Administrador</b> y <b>Auxiliar contable</b> (solo el módulo de Contabilidad). Revíselos en <b>Roles del negocio</b> y cambie lo que cada uno puede hacer.',
                        'En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su correo y una clave, y elija su rol. Cada uno ve solo lo de su rol; los datos del negocio, los usuarios y los roles solo los maneja el dueño.',
                        'Si cambia los permisos de un rol, se actualizan para todas las personas que lo tienen. Lo que haga en su negocio no afecta a ningún otro negocio.',
                    ], 'tip' => 'Un usuario por persona: así sabe quién vendió, cobró o cambió cada cosa.'],
                ],
                'prueba' => [
                    ['p' => '¿Qué dos archivos se cruzan cada mes?', 'o' => ['El listado DIAN y la base contable', 'Dos facturas', 'Ninguno'], 'r' => 0],
                    ['p' => '¿LocalPro presenta la declaración en la DIAN?', 'o' => ['No, la revisión y presentación son del contador', 'Sí, sola', 'Solo en diciembre'], 'r' => 0],
                    ['p' => '¿Cómo deja de recibir recordatorios de un vencimiento?', 'o' => ['Marcándolo como "Presentado"', 'Borrando el cliente', 'No se puede'], 'r' => 0],
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
        $u = auth()->user();
        $tienda = TiendaActual::tienda();
        return array_filter(self::cursos(), function ($c) use ($g, $u, $tienda) {
            if (!array_intersect((array) $c['para'], $g)) return false;
            // Cursos que no aplican a ese tipo de negocio (ej. caja e inventario para un organizador de eventos)
            if (!empty($c['no_tipos']) && in_array($tienda?->tipo_negocio, (array) $c['no_tipos'], true) && !$u?->es_superadmin && !$u?->asesor) return false;
            // Cursos de un tipo de negocio o de un módulo: solo si el negocio es de ese tipo o tiene el módulo (asesores y LocalPro ven todos)
            if ((!empty($c['modulo']) || !empty($c['tipos'])) && !$u?->es_superadmin && !$u?->asesor) {
                $porTipo = !empty($c['tipos']) && in_array($tienda?->tipo_negocio, (array) $c['tipos'], true);
                $porModulo = !empty($c['modulo']) && $tienda?->{$c['modulo']};
                if (!$porTipo && !$porModulo) return false;
            }
            return true;
        });
    }

    // El curso "Cómo manejar su sistema" del tipo de negocio actual (ej. restaurante, funeraria, eventos)
    public static function suCurso(): ?string
    {
        $tipo = TiendaActual::tienda()?->tipo_negocio;
        if (!$tipo) return null;
        foreach (self::cursosNegocio() as $slug => $c) {
            if (in_array($tipo, (array) ($c['tipos'] ?? []), true)) return $slug;
        }
        return null;
    }
}
