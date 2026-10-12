<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Curso básico para aspirantes a asesor: corto y sencillo.
 * 4 lecciones (se pasan con "Entendido") + examen fácil de 10 preguntas (intentos ilimitados) + certificado.
 * Quien aprueba queda como asesor activo automáticamente.
 */
class CursoAsesor
{
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
        return '$' . number_format($v, 0, ',', '.');
    }

    /** Lecciones: cada una con título, ícono, minutos y bloques de contenido (párrafos y viñetas) */
    public static function lecciones(): array
    {
        $v = Comisiones::valores();
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
                    '<b>Caja (POS):</b> para vender rápido, buscando el producto o con lector de código de barras.',
                    '<b>Inventario:</b> el stock se descuenta solo con cada venta y avisa lo que se está acabando.',
                    '<b>Abrir y cerrar caja:</b> cada vendedor cuadra su caja al final del turno.',
                    '<b>Factura:</b> se imprime o se envía por WhatsApp si el negocio no tiene impresora.',
                    '<b>Página web del negocio:</b> sus productos por categorías con botón "Comprar por WhatsApp" y que aparece en Google.',
                    '<b>Reportes:</b> ventas del día, productos más vendidos, clientes y metas para los vendedores.',
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
                    'Cada año que ese negocio renueva, usted gana <b>' . $renov . '</b>, de por vida mientras el negocio y usted sigan activos.',
                    'Para estar activo y recibir comisiones debe tener <b>al menos un negocio con pago</b>.',
                    'También puede sumar asesores a su equipo y crecer a líder de zona, de ciudad y más.',
                    'En su panel verá sus negocios, sus comisiones y su enlace para compartir.',
                ],
                'clave' => 'Entre más negocios inscriba y acompañe, más gana, y las renovaciones le siguen pagando cada año.',
            ],
        ];
    }

    /** Banco de preguntas fáciles: [id => [pregunta, [opciones], índice correcto]] */
    public static function preguntas(): array
    {
        $v = Comisiones::valores();
        $plan = self::plata($v['valor_plan']);
        $com = self::plata($v['nueva']['venta']);

        return [
            1 => ['¿Qué es LocalPro?', ['Un sistema para que los negocios vendan y controlen su inventario y su caja', 'Una red social para negocios', 'Un banco para emprendedores'], 0],
            2 => ['¿Hay que instalar algún programa para usar LocalPro?', ['Sí, en un computador especial', 'No, funciona en el navegador del celular o del computador', 'Solo funciona con impresora'], 1],
            3 => ['¿Qué pasa con el inventario cuando el negocio hace una venta?', ['Hay que descontarlo a mano', 'No cambia', 'Se descuenta solo'], 2],
            4 => ['Si el negocio no tiene impresora, ¿cómo entrega la factura?', ['No puede facturar', 'La envía por WhatsApp', 'Tiene que comprar una impresora'], 1],
            5 => ['¿Cuánto dura la prueba gratis para un negocio inscrito por un asesor?', ['3 meses', '1 semana', 'No hay prueba gratis'], 0],
            6 => ['¿Cuánto vale el plan anual?', [$plan, '$20.000', '$1.000.000'], 0],
            7 => ['¿Cómo queda un negocio a su nombre?', ['Llamando a LocalPro', 'Registrándose con su enlace o escribiendo su código de asesor', 'No importa, todos quedan a su nombre'], 1],
            8 => ['¿Cuándo gana usted la comisión de ' . $com . '?', ['Apenas el negocio se registra', 'Cuando el negocio paga su plan anual', 'Cada vez que el negocio vende algo'], 1],
            9 => ['¿Qué necesita para estar activo y recibir comisiones?', ['Tener al menos un negocio con pago', 'Pagar una mensualidad', 'Nada'], 0],
            10 => ['¿Qué incluye la página web del negocio?', ['Solo el logo', 'Sus productos por categorías con botón para comprar por WhatsApp', 'Juegos para clientes'], 1],
            11 => ['¿LocalPro también sirve para conjuntos residenciales?', ['Sí, para cobrar la administración, registrar pagos y más', 'No, solo para tiendas de ropa', 'Solo para restaurantes'], 0],
            12 => ['¿Qué es lo mejor que puede hacer después de inscribir un negocio?', ['Olvidarse de él', 'Ayudarle a subir sus primeros productos y hacer su primera venta', 'Cobrarle de una vez'], 1],
            13 => ['¿Qué pasa cuando un negocio renueva su plan cada año?', ['Usted también gana una comisión de renovación', 'Usted pierde su comisión', 'El negocio pierde sus datos'], 0],
            14 => ['¿Qué puede hacer el dueño con la caja?', ['Nada', 'Abrirla y cerrarla con cuadre exacto por cada vendedor', 'Solo verla en papel'], 1],
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
