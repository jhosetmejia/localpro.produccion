<?php

namespace App\Http\Controllers;

use App\Models\AsesorContacto;
use App\Models\RedContacto;
use App\Models\Tienda;
use Illuminate\Http\Request;

/**
 * "Empiece a vender LocalPro": plan paso a paso para el asesor — importar su base de datos, escribir 20 mensajes
 * al día, compartir la propuesta en grupos de Facebook y WhatsApp y en sus estados, con mensajes listos y su enlace.
 */
class asesorVenderController extends Controller
{
    public const DIARIOS = 20;

    // Mensajes listos. {nombre} se cambia por el nombre del contacto; {enlace} por el enlace del asesor (con su código)
    public static function mensajes(): array
    {
        return [
            'directo' => ['titulo' => 'Mensaje por WhatsApp (uno a uno)', 'icono' => 'fa-whatsapp', 'texto' =>
                "¡Hola {nombre}! 👋\n\n¿No sabes cómo organizar tu negocio? 🤔 Con *LocalPro* lo manejas todo desde el celular:\n\n✅ Ventas y caja (cada vendedor con su caja)\n✅ Inventario por talla y color\n✅ Cuánto ganas cada día\n✅ Tu propia *tienda en línea* que sale en Google\n✅ Facturas por WhatsApp\n\n🎁 Regístrate y pruébalo *2 meses gratis* aquí 👉 {enlace}\n\nSi quieres te lo muestro en 5 minutos. ¡Feliz día! 💚"],
            'grupo' => ['titulo' => 'Publicación para grupos de Facebook y WhatsApp', 'icono' => 'fa-users', 'texto' =>
                "📢 ¿Tienes un negocio y todavía anotas en cuaderno? 📒\n\nTe presento *LocalPro*, el sistema para organizar tu negocio desde el celular:\n\n🛒 Vende con caja y tiquete por WhatsApp\n📦 Controla tu inventario (tallas, colores, existencias)\n💰 Mira cuánto vendes y cuánto ganas cada día\n👥 Cada vendedor con su usuario y su meta\n🌐 Tu tienda en línea con botón de comprar por WhatsApp\n\nSirve para tiendas de ropa, calzado, misceláneas, tiendas de barrio, restaurantes y más.\n\n🎁 *2 meses GRATIS* para probarlo. Regístrate aquí 👉 {enlace}\n\n¿Dudas? Escríbeme y te ayudo a dejarlo listo 🙌"],
            'estado' => ['titulo' => 'Para sus estados de WhatsApp', 'icono' => 'fa-circle-notch', 'texto' =>
                "¿Tienes negocio? 🏪 Organízalo desde tu celular con LocalPro: ventas, inventario, ganancias y tienda en línea 🚀\n🎁 2 meses gratis 👉 {enlace}"],
        ];
    }

    /**
     * "Vende más": una imagen tipo historia (1080×1920) por cada sistema, para que el asesor la suba a sus estados
     * de WhatsApp e historias de Instagram. Cada imagen lleva el QR y el enlace del asesor (con el tipo de negocio,
     * para que el registro nazca con ese sistema), su nombre y su WhatsApp. Se dibujan en el celular (canvas).
     * pantalla = lo que se ve en el celular de la imagen: titulo, dato grande y filas [texto, valor, alerta?].
     */
    public static function estados(\App\Models\Asesor $yo): array
    {
        $p = fn ($v) => $yo->plata($v);
        $l = [
            'general' => ['Todo negocio', '🏪', '¿Tienes un negocio y todavía', 'anotas en cuaderno?', ['Ventas y caja desde el celular', 'Inventario sin enredos', 'Cuánto ganas cada día'],
                ['Mi negocio', 'Ganancia de hoy', $p(486000), [['Ventas del día', '23'], ['Productos', '312'], ['Caja abierta', 'Laura'], ['Por vencer', '4 productos', 1]]], [],
                '¿Tienes negocio? 🏪 Organízalo desde tu celular con LocalPro: ventas, caja, inventario y cuánto ganas cada día 🚀'],
            'moda' => ['Tiendas de ropa', '👗', '¿Tienes tienda de ropa?', 'Controla cada talla y color', ['Inventario por talla y color', 'Cada vendedor con su caja', 'Tu tienda en línea en Google'],
                ['Inventario', 'Vendido hoy', $p(1280000), [['Jean slim · 32 · Azul', '8 und'], ['Blusa lino · M · Blanca', '5 und'], ['Chaqueta · L · Negra', '¡Quedan 2!', 1], ['Vestido · S · Rojo', '6 und']]], ['tipo' => 'moda'],
                '¿Tienes tienda de ropa? 👗 Con LocalPro controlas tallas y colores, cada vendedor con su caja y tu tienda en línea que sale en Google 🛍️'],
            'calzado' => ['Calzado', '👟', '¿Vendes zapatos o tenis?', 'Sabe qué talla te queda', ['Inventario por talla y color', 'Etiquetas con código de barras', 'Separados y abonos'],
                ['Inventario', 'Vendido hoy', $p(960000), [['Tenis urbano · 38', '4 pares'], ['Tenis urbano · 40', '¡Queda 1!', 1], ['Bota cuero · 37', '3 pares'], ['Sandalia · 36', '7 pares']]], ['tipo' => 'calzado'],
                '¿Vendes calzado? 👟 Con LocalPro sabes qué talla te queda, vendes con código de barras y manejas separados desde el celular'],
            'restaurante' => ['Restaurantes', '🍔', '¿Tienes restaurante o', 'comidas rápidas?', ['Carta virtual con QR', 'Pedidos y mesas desde el celular', 'Tiquetera de almuerzos'],
                ['Pedidos', 'Ventas de hoy', $p(735000), [['Mesa 4 · 2 hamburguesas', 'Listo'], ['Domicilio · Bandeja paisa', 'En cocina', 1], ['Mesa 2 · Almuerzo x3', 'Pagado'], ['Para llevar · Salchipapa', 'Nuevo']]], ['tipo' => 'restaurante'],
                '¿Tienes restaurante? 🍔 Con LocalPro tienes carta virtual con QR, pedidos, caja y tiquetera de almuerzos desde el celular'],
            'cafeteria' => ['Cafeterías y panaderías', '☕', '¿Tienes cafetería', 'o panadería?', ['Caja rápida y cuadrada', 'Carta virtual para pedidos', 'Lo que más se vende cada día'],
                ['Caja', 'Ventas de hoy', $p(412000), [['Capuchino', '38 vendidos'], ['Pan de bono', '64 vendidos'], ['Torta de chocolate', '¡Quedan 3!', 1], ['Jugo natural', '21 vendidos']]], ['tipo' => 'cafeteria'],
                '¿Tienes cafetería o panadería? ☕ Con LocalPro vendes rápido, la caja cuadra sola y tienes carta virtual para pedidos'],
            'minimercado' => ['Tiendas de barrio', '🛒', '¿Tienes tienda', 'o minimercado?', ['Vende con lector de código', 'Fiados y clientes al día', 'Qué se te está acabando'],
                ['Caja', 'Ventas de hoy', $p(1150000), [['Arroz x 500 g', '26 und'], ['Aceite 1 L', '¡Quedan 4!', 1], ['Gaseosa 1,5 L', '18 und'], ['Huevos x 30', '9 und']]], ['tipo' => 'minimercado'],
                '¿Tienes tienda o minimercado? 🛒 Con LocalPro vendes con lector de código, controlas fiados y sabes qué se te está acabando'],
            'belleza' => ['Peluquerías y barberías', '💇', '¿Tienes peluquería,', 'barbería o spa?', ['Servicios y productos en la caja', 'Comisión de cada estilista', 'Clientes y su historial'],
                ['Servicios de hoy', 'Ventas de hoy', $p(540000), [['Corte + barba · Andrés', 'Pagado'], ['Tinte · Camila', 'En curso', 1], ['Manicure · Sofía', 'Pagado'], ['Keratina · Valeria', '3:00 p. m.']]], ['tipo' => 'belleza'],
                '¿Tienes peluquería o barbería? 💇 Con LocalPro cobras servicios y productos, ves la comisión de cada estilista y tus clientes'],
            'odontologia' => ['Odontólogos', '🦷', '¿Eres odontólogo?', 'Tu consultorio en el celular', ['Odontograma y planes de tratamiento', 'Citas en línea con recordatorio', 'Abonos que entran a la caja'],
                ['Hoy en consulta', 'Citas de hoy', '9', [['8:00 · Valoración · Ana R.', 'Confirmó'], ['9:30 · Ortodoncia · Juan P.', 'Control'], ['11:00 · Resina · Marta G.', 'Por confirmar', 1], ['3:00 · Limpieza · Luis T.', 'Confirmó']]], ['tipo' => 'odontologia'],
                '¿Eres odontólogo? 🦷 Con LocalPro tienes odontograma, planes de tratamiento con abonos, citas en línea y recordatorios por WhatsApp'],
            'consultorio' => ['Consultorios de salud', '🩺', '¿Tienes consultorio?', 'Historia clínica segura', ['Agenda de citas sin cruces', 'Historia clínica cifrada y firmada', 'Fórmulas e incapacidades en PDF'],
                ['Hoy en consulta', 'Pacientes de hoy', '7', [['8:30 · Psicología · Diana M.', 'Atendido'], ['10:00 · Fisioterapia · Carlos', 'En sala', 1], ['11:30 · Nutrición · Paola', 'Confirmó'], ['4:00 · Control · Jorge', 'Programada']]], ['tipo' => 'consultorio'],
                '¿Tienes consultorio? 🩺 Con LocalPro manejas agenda, historia clínica cifrada y firmada, fórmulas e incapacidades en PDF'],
            'conjunto' => ['Conjuntos y edificios', '🏢', '¿Administras un conjunto', 'o edificio?', ['Cobro del mes con un botón', 'Recibos y cartera de morosos', 'Reservas y PQRS'],
                ['Administración', 'Recaudo del mes', $p(18450000), [['Torre 1 · Apto 302', 'Al día'], ['Torre 2 · Apto 104', '45 días', 1], ['Torre 3 · Apto 501', 'Al día'], ['Salón social · Sábado', 'Reservado']]], ['tipo' => 'conjunto'],
                '¿Administras un conjunto o edificio? 🏢 Con LocalPro generas el cobro del mes, recibos, cartera de morosos, reservas y PQRS'],
            'instituto' => ['Institutos y academias', '🎓', '¿Tienes instituto', 'o academia?', ['Inscripciones y matrículas', 'Cuotas de contado o financiadas', 'Cartera y recordatorios'],
                ['Instituto', 'Recaudo del mes', $p(9600000), [['Laura G. · Enfermería', 'Al día'], ['Mateo R. · Sistemas', 'Cuota 3 vencida', 1], ['Sara P. · Contabilidad', 'Al día'], ['Inscripciones nuevas', '12']]], ['tipo' => 'instituto'],
                '¿Tienes instituto o academia? 🎓 Con LocalPro manejas inscripciones, matrículas, cuotas de contado o financiadas y cartera'],
            'contable' => ['Contadores', '🧮', '¿Eres contador?', 'Tus clientes al día', ['Retenciones e IVA', 'Cruce de facturas con la DIAN', 'Todos tus clientes en un panel'],
                ['Mis clientes', 'Clientes activos', '24', [['Ferretería El Tornillo', 'IVA listo'], ['Panadería La Espiga', 'Retención', 1], ['Moda Valentina', 'Al día'], ['Distribuciones JR', 'Por cruzar']]], ['tipo' => 'contable'],
                '¿Eres contador? 🧮 Con LocalPro llevas retenciones, IVA y el cruce de facturas con la DIAN de todos tus clientes en un panel'],
            'alquiler' => ['Alquiler para eventos', '🎉', '¿Alquilas sillas, carpas', 'o decoración?', ['Cotizaciones en PDF con tu logo', '¿Qué tengo libre? por fechas', 'Abonos y entregas al día'],
                ['¿Qué tengo libre?', 'Sábado 14', 'Disponible', [['Sillas Tiffany', '80 de 120'], ['Carpa 6×12', '¡Solo 1!', 1], ['Mesas redondas', '15 de 20'], ['Mantel blanco', '40 de 60']]], ['actividad' => 'alquila'],
                '¿Alquilas para eventos? 🎉 Con LocalPro haces cotizaciones en PDF con tu logo y sabes qué tienes libre en cada fecha'],
            '360' => ['Plataformas 360', '📸', '¿Alquilas plataforma 360?', 'QR para tus invitados', ['Tu logo y canción en cada video', 'QR por evento para invitados', 'Lista de invitados con WhatsApp'],
                ['Evento 360', 'Videos grabados', '146', [['Boda Ana & Luis', 'QR listo'], ['Quince Valentina', 'Sábado', 1], ['Empresa Tecno SAS', 'Grabando'], ['Invitados con WhatsApp', '118']]], ['actividad' => 'alquila', 'plataforma' => '360'],
                '¿Alquilas plataforma 360? 📸 Con LocalPro tienes la app de videos 360 con tu logo y canción y un QR por evento para los invitados'],
            'eventos' => ['Eventos y fiestas', '🎟️', '¿Organizas fiestas', 'o conciertos?', ['Venta de entradas en línea', 'QR único por entrada', 'Control en la puerta'],
                ['Mi evento', 'Entradas vendidas', '320', [['General', '240 vendidas'], ['VIP', '¡Quedan 8!', 1], ['Pagos por aprobar', '5'], ['Ingresaron', '0 / 320']]], ['tipo' => 'eventos'],
                '¿Organizas eventos? 🎟️ Con LocalPro vendes entradas en línea, cada una con QR único, y controlas la puerta desde el celular'],
            'funeraria' => ['Funerarias', '🕊️', '¿Tienes funeraria?', 'Todo en un solo lugar', ['Obituarios para pantallas', 'Planes y afiliados', 'Servicios y caja'],
                ['Obituarios', 'Servicios del mes', '18', [['Sala 1 · Velación', 'En pantalla'], ['Sala 2 · Velación', 'Desde 4 p. m.', 1], ['Planes exequiales', '312 afiliados'], ['Pagos del mes', 'Al día']]], ['tipo' => 'funeraria'],
                '¿Tienes funeraria? 🕊️ Con LocalPro publicas obituarios en pantallas, manejas planes, afiliados y la caja en un solo lugar'],
        ];

        $out = [];
        foreach ($l as $k => [$titulo, $emoji, $l1, $l2, $puntos, $pant, $query, $texto]) {
            $enlace = $yo->enlace . ($query ? '?' . http_build_query($query + ['desde' => 'estado-' . $k]) : '?desde=estado-general');
            $out[] = [
                'clave' => $k, 'titulo' => $titulo, 'emoji' => $emoji, 'linea1' => $l1, 'linea2' => $l2, 'puntos' => $puntos,
                'pantalla' => ['titulo' => $pant[0], 'etiqueta' => $pant[1], 'dato' => $pant[2], 'filas' => $pant[3]],
                'enlace' => $enlace,
                'texto' => $texto . "\n🎁 2 meses gratis 👉 " . $enlace,
            ];
        }
        return $out;
    }

    public function imagenes(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        $lista = self::estados($yo);
        return view('asesor.estados', [
            'yo' => $yo,
            'lista' => $lista,
            'sugerida' => $lista[now()->dayOfYear % count($lista)]['clave'],
            'nombre' => trim((string) $yo->user?->name),
            'whatsapp' => preg_replace('/\D+/', '', (string) $yo->user?->telefono),
        ]);
    }

    public function index(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        $hoy = now()->toDateString();
        $base = AsesorContacto::where('asesor_id', $yo->id)->count();
        $hoyPropios = AsesorContacto::where('asesor_id', $yo->id)->whereDate('contactado_at', $hoy)->count();
        $hoyRed = class_exists(RedContacto::class) && \Illuminate\Support\Facades\Schema::hasTable('red_contactos')
            ? RedContacto::where('contactado_por', $yo->id)->whereDate('contactado_at', $hoy)->count() : 0;
        $semana = AsesorContacto::where('asesor_id', $yo->id)->where('contactado_at', '>=', now()->startOfWeek())->count();

        return view('asesor.vender', [
            'yo' => $yo,
            'enlace' => $yo->enlace,
            'mensajes' => self::mensajes(),
            'diarios' => self::DIARIOS,
            'base' => $base,
            'hoy' => $hoyPropios + $hoyRed,
            'semana' => $semana,
            'negocios' => Tienda::where('asesor_id', $yo->id)->count(),
            'negociosMes' => Tienda::where('asesor_id', $yo->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }
}
