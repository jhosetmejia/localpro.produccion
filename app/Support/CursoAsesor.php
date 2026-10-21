<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Curso básico para aspirantes a asesor: corto y sencillo.
 * Lecciones cortas (se pasan con "Entendido") + examen fácil de 10 preguntas (intentos ilimitados) + certificado.
 * Quien aprueba queda como asesor activo automáticamente.
 * Cada función nueva del sistema se agrega aquí (lección y preguntas) con 'nuevo' => 'AAAA-MM' para que la red la conozca.
 */
class CursoAsesor
{
    // Lección con tarea práctica: subir su base de datos y escribir a sus primeros contactos
    public const LECCION_CONTACTOS = 7;
    public const TAREA_MINIMO = 3; // contactos escritos para dar la tarea por hecha

    public static function notaMinima(): int
    {
        return (int) Ajuste::valor('curso_nota_minima', 70);
    }

    public static function cantidadPreguntas(): int
    {
        return max(5, (int) Ajuste::valor('curso_preguntas', 10));
    }

    private static function plata(float $v): string
    {
        return Moneda::formato($v, Moneda::actual());
    }

    /** Lecciones: cada una con título, ícono, minutos y bloques de contenido (párrafos y viñetas) */
    public static function lecciones(): array
    {
        $v = Comisiones::valores(Moneda::actual());
        $plan = self::plata($v['valor_plan']);
        $com = self::plata($v['nueva']['venta']);
        $renov = self::plata($v['renovacion']['venta']);
        $mes = self::plata(ceil($v['valor_plan'] / 12 / 100) * 100);

        return [
            1 => [
                'titulo' => 'Qué es LocalPro',
                'icono' => 'fa-store',
                'minutos' => 2,
                'intro' => 'LocalPro es el sistema para que cualquier negocio venda, controle su inventario y su caja desde el celular o el computador.',
                'puntos' => [
                    'Sirve para tiendas de ropa, minimercados, misceláneas, restaurantes, cafeterías, ferreterías y muchos negocios más.',
                    'También sirve para administrar conjuntos residenciales y edificios.',
                    'No hay que instalar nada: funciona en el navegador del celular o del computador.',
                    'El dueño del negocio sabe al instante cuánto vendió, qué se le está acabando y cuánto ganó.',
                ],
                'clave' => 'LocalPro ayuda a los negocios a organizarse y a saber exactamente cuánto ganan cada día.',
            ],
            2 => [
                'titulo' => 'Conozca la herramienta',
                'icono' => 'fa-cash-register',
                'minutos' => 3,
                'intro' => 'Esto es lo que usted le va a mostrar a los negocios. No tiene que saberlo todo: con estas funciones es suficiente.',
                'puntos' => [
                    '<b>Caja (POS):</b> para vender rápido, buscando el producto o escaneando su código de barras.',
                    '<b>Código de barras:</b> LocalPro crea el código de cada producto (y de cada talla y color), imprime las etiquetas y se escanea con la <b>cámara del celular</b>: no hay que comprar lector. Sirve para vender y para contar el inventario.',
                    '<b>Inventario:</b> el stock se descuenta solo con cada venta y avisa lo que se está acabando.',
                    '<b>Abrir y cerrar caja:</b> cada vendedor cuadra su caja al final del turno.',
                    '<b>Factura:</b> se imprime o se envía por WhatsApp si el negocio no tiene impresora.',
                    '<b>Página web del negocio:</b> sus productos por categorías con botón "Comprar por WhatsApp" y que aparece en Google.',
                    '<b>Reportes:</b> ventas del día, productos más vendidos, clientes y metas para los vendedores.',
                    '<b>Mis enlaces:</b> una página tipo Linktree (localproo.com/@negocio) con su WhatsApp, redes, promociones y tienda en línea, para ponerla en la biografía de Instagram o TikTok o en un código QR del mostrador. Muestra cuántas visitas y clics tiene.',
                    '<b>Nómina (opcional):</b> si el negocio tiene empleados, liquida el mes o la quincena y saca los desprendibles. El negocio escoge: <b>sin DIAN</b> (solo interna) o <b>nómina electrónica DIAN</b>. También sirve si paga más o menos del mínimo.',
                ],
                'clave' => 'Si una persona sabe usar WhatsApp, sabe usar LocalPro.',
            ],
            3 => [
                'titulo' => 'Cómo se inscribe un negocio',
                'icono' => 'fa-handshake',
                'minutos' => 2,
                'intro' => 'Vender LocalPro es fácil porque el negocio lo prueba gratis antes de pagar.',
                'puntos' => [
                    'Usted tendrá un <b>código propio</b> (por ejemplo LCP-00012) y un <b>enlace</b> para inscribir negocios.',
                    'El negocio se registra con su enlace o escribiendo su código: así queda a su nombre.',
                    'Arranca con <b>3 meses de prueba gratis</b>.',
                    'Cuando termina la prueba, el negocio paga su plan anual de <b>' . $plan . '</b> (menos de ' . $mes . ' al mes).',
                    'Ayúdele a subir sus primeros productos y a hacer su primera venta: el que vende el primer día, se queda.',
                ],
                'clave' => 'Siempre inscriba el negocio con su enlace o su código, para que la venta quede a su nombre.',
            ],
            4 => [
                'titulo' => 'Cómo gana usted',
                'icono' => 'fa-sack-dollar',
                'minutos' => 2,
                'intro' => 'Usted gana cada vez que un negocio que inscribió paga su plan anual.',
                'puntos' => [
                    'Por cada negocio nuevo que paga su plan anual gana <b>' . $com . '</b>.',
                    'Cada año que ese negocio renueva, usted gana <b>' . $renov . '</b>, de por vida.',
                    'Si usted suma a otro asesor, gana <b>' . self::plata($v['nueva']['arriba']) . '</b> por cada negocio que él venda o renueve.',
                    'La comisión de una venta nueva la puede retirar apenas el negocio paga; las de renovación se pagan una vez al mes.',
                    'Para recibir sus comisiones debe estar activo: hacer <b>mínimo una venta nueva cada mes</b>. Si ese mes no vende, su parte queda para LocalPro.',
                    'También puede sumar asesores a su equipo y crecer a líder de zona, de ciudad y más.',
                    'En su panel verá sus negocios, sus comisiones y su enlace para compartir.',
                ],
                'clave' => 'Entre más negocios inscriba y acompañe, más gana, y las renovaciones le siguen pagando cada año.',
            ],
            5 => [
                'titulo' => 'Funciones nuevas para cada tipo de negocio',
                'icono' => 'fa-wand-magic-sparkles',
                'minutos' => 3,
                'nuevo' => '2026-10',
                'intro' => 'LocalPro se adapta al tipo de negocio. Estas funciones le ayudan a convencer a negocios que antes pensaban que el sistema no era para ellos.',
                'puntos' => [
                    '<b>Funerarias – Obituarios:</b> escriben el nombre, suben la foto y eligen la sala de velación; el obituario sale solo en el televisor de cada sala. Trae plantillas listas (con flores, palomas, cielo), animaciones, video de fondo, logo sin fondo y descarga en imagen o video para redes (el video dura lo que elijan: de 10 segundos a 2 minutos). Las fechas de nacimiento y fallecimiento salen completas (07-12-1980 / 27-03-2026) y el botón "Enviar por WhatsApp" manda la imagen del obituario con los datos de las exequias. Se activa solo al elegir el tipo "Funeraria".',
                    '<b>Personalizar menú:</b> el dueño apaga las secciones que no usa y su menú queda limpio. Por ejemplo, una funeraria no necesita tallas ni separados. No se borra nada: se puede volver a prender.',
                    '<b>Palabras e íconos según el negocio:</b> una tienda de ropa ve "prendas", una ferretería "productos", una funeraria "planes y servicios". Los servicios no salen como "Agotado".',
                    '<b>Tienda en línea solo con existencias:</b> el dueño escoge si su página muestra todos los productos o solo los que tiene en inventario, para no recibir pedidos de lo que no hay.',
                    '<b>Permisos del vendedor:</b> el dueño decide qué más puede hacer cada vendedor además de vender: crear o editar productos, registrar entradas de mercancía, hacer devoluciones, ver reportes, etc. Se hace en Usuarios → menú del vendedor → Permisos. Nunca puede manejar usuarios, datos del negocio ni el plan.',
                    '<b>Fotos livianas automáticas:</b> las fotos que suben se achican solas sin perder calidad, para que la página cargue rápido en el celular.',
                ],
                'clave' => 'Antes de mostrar LocalPro, pregunte qué tipo de negocio es: así le enseña justo la función que le resuelve su problema.',
            ],
            6 => [
                'titulo' => 'Contabilidad para contadores',
                'icono' => 'fa-calculator',
                'minutos' => 4,
                'nuevo' => '2026-10',
                'intro' => 'Los contadores son un gran cliente: cada uno lleva muchos negocios. LocalPro tiene una herramienta hecha para ellos.',
                'puntos' => [
                    '<b>Qué hace:</b> el contador sube el listado de facturas de la DIAN y la contabilidad de su cliente (Contapyme, Siigo, World Office, en Excel). LocalPro cruza factura por factura y le muestra lo que no está contabilizado, las diferencias de IVA y las retenciones que faltaron o sobraron.',
                    '<b>Qué le deja listo:</b> el borrador de la declaración de retenciones (Formulario 350), el resumen del IVA, un Excel de papel de trabajo y los certificados de retención.',
                    '<b>Calendario tributario:</b> le muestra cuándo vence la retención y el IVA de cada cliente según su NIT y le avisa al correo 3 días antes y el mismo día.',
                    '<b>Datos de ejemplo:</b> con el botón "Probar con datos de ejemplo" el contador ve todo funcionando en un minuto, sin subir nada suyo. Úselo para hacer la demostración.',
                    '<b>Cómo inscribirlo:</b> mándele su enlace para contadores <b>localproo.com/contadores/SU-CÓDIGO</b> (está en su panel). La cuenta queda como contador, con la herramienta lista y a su nombre.',
                    '<b>Usted también la tiene:</b> en su panel, en Red comercial → Contabilidad, con su propio espacio, para mostrarla o para llevar clientes de un analista contable.',
                    '<b>Lo que no hace:</b> no presenta la declaración en la DIAN. Deja el borrador listo; la presenta el contador con su firma electrónica.',
                ],
                'clave' => 'Un contador que usa LocalPro le puede recomendar el sistema a todos los negocios que atiende.',
            ],
            self::LECCION_CONTACTOS => [
                'titulo' => 'Su base de datos: 20 contactos al día',
                'icono' => 'fa-address-book',
                'minutos' => 5,
                'nuevo' => '2026-10',
                'tarea' => 'contactos',
                'intro' => 'El asesor que más gana es el que más negocios contacta. Sus contactos del celular son su primera base de datos, y LocalPro le ayuda a escribirles todos los días.',
                'puntos' => [
                    '<b>Su herramienta "Mis contactos":</b> exporta los contactos de su celular (Android, Samsung, iPhone o Google) y los sube. No se repiten.',
                    '<b>20 al día:</b> cada día le salen 20 contactos con un botón verde. Al tocarlo se abre WhatsApp con un mensaje listo, con el nombre de la persona y <b>su enlace de prueba gratis</b>. Si se inscriben, el negocio queda a su nombre.',
                    '<b>Lo mismo para los negocios:</b> el dueño que usa LocalPro también puede subir los contactos de su celular en Clientes → "Crear base de datos" y escribirles con "Contactar clientes": a cada vendedor le salen 20 al día con el enlace de su tienda en línea. Muéstreselo: es una razón fuerte para que compren.',
                    '<b>Respeto:</b> escriba de 9 a. m. a 7 p. m. y si alguien no quiere más mensajes, márquelo y no le vuelva a escribir.',
                ],
                'clave' => 'Tarea de esta lección: suba sus contactos y escríbale a ' . self::TAREA_MINIMO . ' personas. Cuando lo haga, se activa el botón para seguir.',
            ],
        ];
    }

    /** Banco de preguntas fáciles: [id => [pregunta, [opciones], índice correcto]] */
    public static function preguntas(): array
    {
        $v = Comisiones::valores(Moneda::actual());
        $plan = self::plata($v['valor_plan']);
        $com = self::plata($v['nueva']['venta']);

        // Preguntas desde el punto de vista del asesor: siempre sobre el negocio que él inscribe y acompaña
        return [
            1 => ['Un dueño de negocio le pregunta qué es LocalPro. ¿Qué le responde?', ['Un sistema para que su negocio venda y controle su inventario y su caja desde el celular', 'Una red social para negocios', 'Un banco para emprendedores'], 0],
            2 => ['El negocio que usted va a inscribir no tiene computador. ¿Puede usar LocalPro?', ['No, necesita un computador especial', 'Sí, funciona en el navegador del celular, sin instalar nada', 'Solo si compra una impresora'], 1],
            3 => ['¿Qué pasa con el inventario del negocio que usted inscribió cuando hace una venta?', ['El dueño tiene que descontarlo a mano', 'No cambia', 'Se descuenta solo con cada venta'], 2],
            4 => ['El negocio que usted inscribió no tiene impresora. ¿Cómo le entrega la factura a su cliente?', ['No puede facturar', 'Se la envía por WhatsApp', 'Tiene que comprar una impresora primero'], 1],
            5 => ['Cuando usted inscribe un negocio con su enlace, ¿cuánto tiempo lo puede usar gratis?', ['3 meses', '1 semana', 'No tiene prueba gratis'], 0],
            6 => ['Al terminar la prueba gratis, ¿cuánto paga al año el negocio que usted inscribió?', [$plan, self::plata(Moneda::convertir(20000, Moneda::COP, Moneda::actual())), self::plata(Moneda::convertir(1000000, Moneda::COP, Moneda::actual()))], 0],
            7 => ['¿Qué debe hacer para que el negocio que usted consiguió quede a su nombre?', ['Llamar a LocalPro después', 'Que se registre con su enlace o escriba su código de asesor', 'Nada, todos los negocios quedan a su nombre'], 1],
            8 => ['¿Cuándo gana usted la comisión de ' . $com . ' por el negocio que inscribió?', ['Apenas el negocio se registra', 'Cuando el negocio paga su plan anual', 'Cada vez que el negocio vende algo'], 1],
            9 => ['¿Qué necesita usted para seguir activo y recibir sus comisiones?', ['Hacer mínimo una venta nueva cada mes', 'Pagar una mensualidad a LocalPro', 'Nada'], 0],
            10 => ['¿Qué le ofrece la página web al negocio que usted inscribió?', ['Solo mostrar su logo', 'Sus productos por categorías con botón para comprar por WhatsApp y aparecer en Google', 'Juegos para sus clientes'], 1],
            11 => ['Un administrador de un conjunto residencial le pregunta si LocalPro le sirve. ¿Qué le responde?', ['Sí, para cobrar la administración, registrar pagos, ver morosos y más', 'No, solo sirve para tiendas de ropa', 'Solo sirve para restaurantes'], 0],
            12 => ['Acaba de inscribir un negocio. ¿Qué es lo mejor que puede hacer en ese momento?', ['Irse y volver cuando termine la prueba', 'Ayudarle a subir sus primeros productos y hacer su primera venta', 'Cobrarle de una vez'], 1],
            13 => ['El negocio que usted inscribió renueva su plan al año siguiente. ¿Qué pasa con usted?', ['Gana una comisión de renovación', 'Pierde su comisión', 'No pasa nada'], 0],
            14 => ['El dueño del negocio que usted inscribió tiene 2 vendedores. ¿Cómo controla la plata de cada uno?', ['No se puede', 'Cada vendedor abre y cierra su caja con cuadre exacto', 'Anotándolo en un cuaderno'], 1],
            15 => ['Un negocio quiere llevar la nómina de sus empleados en LocalPro, pero no quiere enviarla a la DIAN. ¿Se puede?', ['No, toda nómina se envía obligatoriamente a la DIAN', 'Sí, escoge "Nómina sin DIAN" y la maneja solo de forma interna', 'Solo si paga un plan adicional'], 1],
            17 => ['Un negocio quiere un solo enlace para poner en la biografía de Instagram con su WhatsApp, su ubicación y sus promociones. ¿Qué le muestra?', ['Mis enlaces: su página localproo.com/@negocio', 'Que cree una página web aparte', 'No se puede'], 0],
            18 => ['En "Mis enlaces", el negocio quiere que una promoción desaparezca sola cuando se acabe. ¿Qué hace?', ['La borra a mano ese día', 'Programa el enlace con fecha "hasta"', 'Cambia de tema'], 1],
            19 => ['Una funeraria le pregunta si LocalPro le sirve para mostrar los obituarios en los televisores de sus salas. ¿Qué le responde?', ['No, solo sirve para tiendas', 'Sí: con Obituarios elige la sala, sube la foto y el obituario sale solo en el televisor de esa sala', 'Solo si compra un televisor especial'], 1],
            20 => ['El dueño de un negocio dice que el menú tiene muchas opciones que no usa. ¿Qué le muestra?', ['Personalizar menú: apaga lo que no usa sin borrar nada', 'Que cree otra cuenta', 'Que no se puede cambiar'], 0],
            21 => ['Un negocio no quiere que en su página aparezcan productos que ya no tiene. ¿Qué hace?', ['Borra los productos', 'En los datos del negocio elige mostrar solo los productos con existencias', 'Desactiva la página'], 1],
            22 => ['Un contador le pregunta qué hace la herramienta de Contabilidad de LocalPro. ¿Qué le responde?', ['Cruza las facturas de la DIAN con su contabilidad, revisa IVA y retenciones y le deja el borrador del 350', 'Le presenta las declaraciones en la DIAN sin revisar', 'Solo sirve para imprimir facturas'], 0],
            23 => ['¿Cuál es la mejor forma de mostrarle la herramienta de Contabilidad a un contador en pocos minutos?', ['Pedirle todos los archivos de sus clientes primero', 'Usar el botón "Probar con datos de ejemplo"', 'Mandarle un PDF'], 1],
            24 => ['¿Con qué enlace inscribe a un contador para que su cuenta quede como contador y a su nombre?', ['localproo.com/contadores/SU-CÓDIGO', 'Cualquier enlace de internet', 'No se puede inscribir contadores'], 0],
            25 => ['Un contador le pregunta si LocalPro presenta la declaración en la DIAN por él. ¿Qué le responde?', ['Sí, la envía sola', 'No: deja el borrador listo y él la presenta en la DIAN con su firma electrónica', 'Sí, pero solo los lunes'], 1],
            26 => ['¿Cómo le ayuda el calendario tributario al contador?', ['Le muestra cuándo vence la retención y el IVA de cada cliente y le avisa al correo antes del vencimiento', 'Le cambia las fechas de la DIAN', 'No sirve para contadores'], 0],
            27 => ['El dueño quiere que su vendedor de confianza también pueda crear productos, pero no ver las ganancias. ¿Qué hace?', ['Le da su propia clave de dueño', 'En Usuarios → Permisos le activa "Crear productos" y deja apagado "Ver reportes"', 'No se puede: solo el dueño crea productos'], 1],
            28 => ['¿Cuántos contactos le salen cada día en "Mis contactos" para ofrecerles LocalPro?', ['20, con el mensaje y su enlace de prueba gratis listos', 'Ninguno, hay que escribir todo a mano', 'Solo 1 a la semana'], 0],
            29 => ['Un dueño de negocio quiere conseguir clientes nuevos. ¿Qué le muestra de LocalPro?', ['Que suba los contactos de su celular en Clientes y use "Contactar clientes" para escribirles con el enlace de su tienda', 'Que compre una base de datos por internet', 'Nada, LocalPro no ayuda con eso'], 0],
            16 => ['El negocio que usted inscribió le paga a un empleado menos del salario mínimo por medio tiempo. ¿Puede liquidarlo en LocalPro?', ['No, el sistema solo acepta el salario mínimo', 'Sí, con la liquidación personalizada pone el salario que pagan', 'Solo si lo envía a la DIAN'], 1],
        ];
    }

    /** Escoge las preguntas del examen al azar */
    public static function armarExamen(): array
    {
        $ids = array_keys(self::preguntas());
        shuffle($ids);
        return array_slice($ids, 0, min(self::cantidadPreguntas(), count($ids)));
    }
}
