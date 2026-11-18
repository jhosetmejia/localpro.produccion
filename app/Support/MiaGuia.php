<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * 2026-11-18 (Jhoset): lo que Mía sabe de CÓMO SE USA el panel de LocalProo, sacado de las pantallas reales.
 * Para no gastar la IA gratis, a cada pregunta solo se le pasan los temas que tienen que ver (palabras clave),
 * más el mapa corto del menú. Si se cambia una pantalla (botones, nombres), actualizar aquí.
 */
class MiaGuia
{
    /** Mapa corto del menú de la izquierda (siempre va). */
    public static function mapa(): string
    {
        return implode("\n", [
            'MAPA DEL MENÚ DE LA IZQUIERDA DEL PANEL (nombres reales; cada negocio puede esconder lo que no usa en "Personalizar menú", así que puede que no vea alguno):',
            '- Arriba: Panel. Luego, según el negocio: Tomar pedidos, Pedidos por cobrar, Pantalla de cocina (restaurantes), Vender (POS), Tiquetera, Separados y abonos, Alquiler, Plataforma 360, Cotizaciones, Mi caja, Ventas, Egresos, Reportes, Nómina, Cierres de caja. Consultorios: Hoy en consulta, Agenda de citas, Pacientes. Funerarias: Obituarios, Pantallas. Eventos: Eventos y entradas.',
            '- Sección Inventario: Productos (o "Productos y servicios"), Existencias, Resumen y conteo, Entradas de mercancía, Categorías, Mis redes, Editar videos · App, LP Chat.',
            '- Sección Contactos: Aprende, Clientes, Vender más, Proveedores.',
            '- Sección Mi negocio: Datos del negocio, Facturación DIAN, Mis enlaces, Mi carta, Impresora, Personalizar menú, Mi plan, Comparta y gane, Usuarios.',
            'No existe un menú "Administración > ..." para estas opciones: se entra directo por su nombre en el menú. Si no ve una opción, puede estar escondida en Mi negocio → Personalizar menú (botón "Mostrar todo") o su usuario no tiene permiso (lo da el dueño en Usuarios).',
        ]);
    }

    /** Temas con sus palabras clave (sin tildes, en minúscula) y los pasos reales. */
    private static function temas(): array
    {
        $r = fn (string $n, array $p = []) => Route::has($n) ? route($n, $p) : '';

        return [
            [['egreso', 'gasto', 'gaste', 'salida de plata', 'pago del arriendo', 'pague'], implode("\n", [
                'EGRESOS (gastos del negocio) · '.$r('egresos.index'),
                '- Anotar: menú Egresos → cuadro "Anotar egreso": ¿En qué se gastó?, Valor, Fecha, Tipo de gasto, ¿Cómo se pagó?; si es en efectivo y tiene la caja abierta, la casilla "Lo saqué de mi caja" lo descuenta del efectivo esperado. Foto del recibo y nota son opcionales. Botón "Guardar egreso".',
                '- Editar: en la lista de la derecha (filtros Desde, Hasta, Tipo, Quién y botón Filtrar) toque el lápiz ✏️ al lado del valor; se abre el formulario debajo; botón "Guardar cambios". El vendedor solo edita los suyos mientras su caja siga abierta (o el mismo día si no tenía caja); el dueño o administrador, todos. Con la caja ya cerrada, el valor y la forma de pago quedan fijos.',
                '- Borrar: botón de la caneca 🗑️ al lado del egreso (mismas reglas). Si había salido de la caja, vuelve a sumarse al efectivo.',
                '- El vendedor ve los suyos y los de sus compañeros; no ve los que anota el administrador.',
            ])],
            [['producto', 'servicio', 'precio', 'foto', 'importar', 'excel', 'talla', 'color', 'codigo de barras', 'etiqueta', 'catalogo', 'agotado', 'desactivar', 'ocultar'], implode("\n", [
                'PRODUCTOS · '.$r('productos.index'),
                '- Crear: Productos → botón "Nuevo producto" (o "Nuevo servicio"). Paso 1 ¿Qué va a vender? (Producto: lleva inventario · Servicio: sin stock), nombre, precio de venta, se vende por (unidad, metro, kilo, litro...), foto (opcional; sin foto no sale en el catálogo a menos que marque "Mostrar aunque no tenga foto"), "Mostrar en el catálogo en línea". Paso 2 ¿Cuántas unidades tiene? (cantidad, o tallas/colores con "Este producto tiene tallas o colores" → escribir tallas y colores separados por coma → Aplicar → cantidad de cada una), código de barras (puede escanearlo con el botón Escanear). Paso 3 Categoría, precio de costo, código, descripción, "Controlar inventario" (apagar para comidas preparadas o servicios). Botón Guardar.',
                '- Muchos de una vez: Productos → "Importar Excel".',
                '- Editar: en la lista, botón "Editar" del producto. También: "Subir foto", "Etiquetas", "Desactivar" (se acabó: deja de ofrecerse sin borrarlo) / "Activar", "Ocultar", "Eliminar" / "Restaurar".',
                '- Filtros de la lista: Activos, Productos, Servicios, Poco stock, Agotados, Ocultos; y "Escanear" con la cámara para buscar.',
                '- Etiquetas con código de barras: botones "Ver todas las etiquetas" y "Etiquetas para mercancía nueva" arriba en Productos.',
            ])],
            [['vender', 'venta', 'pos', 'cobrar', 'caja', 'tiquete', 'ticket', 'factura', 'cliente en la venta', 'escanear', 'saldo a favor', 'nota credito', 'tasa', 'bolivar'], implode("\n", [
                'VENDER EN LA CAJA (POS) · '.$r('pos.index'),
                '- Si no tiene caja abierta, primero "Abrir caja": base de caja (efectivo inicial) y nota → "Abrir caja e ir al POS".',
                '- En el POS: busque o escanee el producto (botón de la cámara) → "Agregar a la venta". A la derecha "Venta actual" (botón Vaciar para empezar de cero). Botón "Cobrar" (tecla F2): elegir forma de pago, cliente (puede crear uno con "Nuevo" o seguir sin cliente), "Usar saldo a favor (nota crédito)" si tiene, y la casilla "El cliente pide factura electrónica" (necesita cliente con cédula o NIT y correo). "Confirmar venta" (Enter). Después: "Imprimir tiquete", "Enviar factura por WhatsApp" o "Nueva venta".',
                '- Arriba del POS: "Mi turno" (ventas de su turno), "Cerrar caja", "Panel". En Venezuela, botón "Tasa" para la tasa del día en bolívares.',
            ])],
            [['correg', 'anul', 'devol', 'cambio', 'me equivoque', 'error en la venta', 'forma de pago', 'venta olvidada', 'olvide registrar', 'ventas'], implode("\n", [
                'VENTAS HECHAS: corregir, cambiar pago, devolver, anular · '.$r('pos.ventas'),
                '- Menú Ventas: lista con N.º, fecha, vendedor, cliente, pago y total. Botones de cada venta: tiquete 🧾, "Corregir venta (error al digitar)" (ícono de lápiz con cuadro), "Cambiar forma de pago" (ícono de billete), "Cambio o devolución" (ícono de flechas), factura y WhatsApp.',
                '- "Registrar venta olvidada": botón arriba en Ventas, para una venta que no se anotó a tiempo.',
                '- Anular: en Mi caja (caja abierta), en "Ventas del turno", botón rojo 🚫 de la venta; las cosas vuelven al inventario y no suma en la caja. Solo si su usuario tiene el permiso de anular.',
            ])],
            [['abrir caja', 'cerrar caja', 'cuadre', 'cierre', 'turno', 'base', 'efectivo', 'mi caja', 'faltante', 'sobrante', 'diferencia'], implode("\n", [
                'CAJA: abrir, ver y cerrar · '.$r('cajas.actual'),
                '- Abrir: Mi caja (o al entrar al POS) → "Abrir caja": base de caja y nota → "Abrir caja e ir al POS".',
                '- Ver el turno: Mi caja → ventas del turno, resumen por método de pago y egresos pagados con la caja; botones "Ir al POS", "Cerrar caja", "Imprimir cuadre".',
                '- Cerrar: "Cerrar caja" → muestra lo vendido en el turno → "Cuente el efectivo": escriba el efectivo contado (y bolívares si aplica), nota de cierre → "Cerrar caja e imprimir cuadre". La diferencia sale sola.',
                '- Cierres anteriores: menú "Cierres de caja" ('.$r('cajas.index').'): vendedor, apertura, cierre, base, ventas, diferencia; botón "Ver" e imprimir.',
            ])],
            [['separado', 'abono', 'apartar', 'credito', 'fiado', 'plazo'], implode("\n", [
                'SEPARADOS Y ABONOS · '.$r('separados.index'),
                '- Menú "Separados y abonos" → "Nuevo separado": 1. qué separa, 2. cliente (nombre, WhatsApp, correo), 3. abono (descuento, plazo para pagar, cuánto abona hoy, cómo pagó, nota) → "Separar y registrar abono".',
                '- En la lista: buscar, filtrar por estado, ver cuánto le falta; tocar el separado para abrirlo y registrar más abonos; botón de WhatsApp para escribirle al cliente.',
            ])],
            [['cliente', 'contacto', 'base de datos', 'whatsapp a clientes', 'vender mas'], implode("\n", [
                'CLIENTES · '.$r('clientes.index'),
                '- Menú Clientes: "Añadir un cliente", "Añadir base de datos de clientes" / "Subir mi Excel", "Crear base de datos (contactos del celular)", "Vender más".',
                '- En la lista, por cliente: WhatsApp, "Ver compras", "Editar", "Eliminar" (o "Restaurar").',
                '- También se crea un cliente desde el POS al cobrar (botón "Nuevo").',
            ])],
            [['proveedor', 'entrada', 'mercancia', 'llego', 'compra', 'surtido', 'inventario', 'existencia', 'conteo', 'stock', 'cuanto tengo', 'cuanto tenemos'], implode("\n", [
                'INVENTARIO Y MERCANCÍA',
                '- Mercancía que llega: Inventario → "Entradas de mercancía" ('.$r('entradas.index').') → "Nueva entrada": proveedor (opcional), fecha, N.º de factura, productos que llegaron con cantidad y costo → "Guardar entrada y sumar al inventario".',
                '- Ver cuánto hay: "Existencias" ('.$r('inventario.index').') o "Resumen y conteo" ('.$r('inventario.resumen').', "¿Cuánto tenemos?" por categoría, talla y color, botón Imprimir).',
                '- Conteo físico con el celular: Resumen y conteo → "Hacer conteo físico" (o "Seguir contando" si dejó uno a medias).',
                '- Proveedores: Contactos → Proveedores ('.$r('proveedores.index').').',
            ])],
            [['usuario', 'empleado', 'vendedor', 'permiso', 'rol', 'acceso', 'clave del trabajador', 'cajero'], implode("\n", [
                'USUARIOS Y PERMISOS · '.$r('users.index'),
                '- Mi negocio → Usuarios → "Añadir nuevo usuario": nombres, email, WhatsApp, contraseña, confirmar y Rol ("Crear o editar roles" si necesita otro) → Guardar.',
                '- Por usuario: "Editar", "Permisos del vendedor", "Compartir accesos por WhatsApp" (le manda el usuario y una clave nueva opcional), "Eliminar".',
            ])],
            [['reporte', 'ganancia', 'cuanto vendi', 'informe', 'exportar', 'lo mas vendido', 'estadistica'], implode("\n", [
                'REPORTES · '.$r('reportes.index'),
                '- Menú Reportes ("¿Cómo va su negocio?"): elegir Desde y Hasta → "Ver". Muestra ventas por día, lo más vendido (unidades, vendido, ganancia), por vendedor y por método de pago.',
                '- "Exportar a Excel": Ventas del periodo, Productos vendidos o Inventario actual.',
            ])],
            [['cotizacion', 'cotizar', 'presupuesto'], implode("\n", [
                'COTIZACIONES · '.$r('cotizaciones.index'),
                '- Menú Cotizaciones → "Nueva cotización". En la lista: filtrar por fechas y estado, buscar, y tocar una para verla, mandarla por WhatsApp o en PDF.',
            ])],
            [['impresora', 'imprimir', 'tiquete sale', 'comanda', 'papel'], implode("\n", [
                'IMPRESORA · '.$r('impresora.edit'),
                '- Mi negocio → Impresora: elegir "Impresora de caja" (tiquetes, facturas, cuadre, recibos) o "Impresora de cocina" (comandas). Ajustar medidas, "Letra más oscura (negrita)" si sale pálido, si aplica solo a este computador o a todos → "Imprimir prueba" y "Guardar". "Valores de fábrica" para volver a como venía.',
            ])],
            [['datos del negocio', 'logo', 'nit', 'nombre del negocio', 'tienda en linea', 'catalogo en linea', 'direccion de mi tienda', 'pie del tiquete', 'modulo', 'menu', 'esconder', 'no me aparece', 'no veo', 'personalizar'], implode("\n", [
                'DATOS DEL NEGOCIO, TIENDA EN LÍNEA Y MENÚ',
                '- Mi negocio → "Datos del negocio" ('.$r('negocio.edit').'): nombre, NIT, tipo de negocio, dirección, WhatsApp, correo, dirección del catálogo en línea, mensaje al pie del tiquete, logo. Sección "Tienda en línea": copiar/ver el enlace, "Tienda en línea visible al público" y qué productos mostrar (solo con existencias o todos). Sección Caja: "Pedir siempre el cliente para cobrar". Botón "Guardar cambios".',
                '- Mi negocio → "Personalizar menú" ('.$r('menu.edit').'): marcar lo que se muestra; botones "Guardar menú", "Usar lo sugerido" y "Mostrar todo".',
            ])],
            [['plan', 'pagar', 'mensualidad', 'vence', 'vencio', 'suscripcion', 'renovar', 'wompi', 'ampliar productos'], implode("\n", [
                'MI PLAN · '.$r('plan.index'),
                '- Mi negocio → "Mi plan": ve el plan, cuándo vence, botón "Pagar en línea" (Wompi), "Ampliar productos" e historial de pagos. Si quiere pagar de otra forma: "Escribir por WhatsApp" ahí mismo.',
            ])],
            [['dian', 'factura electronica', 'facturacion', 'resolucion', 'rut', 'pos electronico', 'cufe'], implode("\n", [
                'FACTURACIÓN DIAN · '.$r('facturacion.index'),
                '- Mi negocio → "Facturación DIAN": "Lo que necesita para estar bien ante la DIAN", Datos del RUT (razón social, NIT, DV, tipo de persona, CIIU, responsabilidades, municipio, correo), "POS electrónico" (resolución, prefijo, fechas, rango) y "Factura electrónica (cuando el cliente la pide)" (clave técnica, ambiente) → Guardar. Abajo, "Documentos electrónicos" con botones Ver y "Enviar de nuevo"; "Revisar mi última venta como la vería la DIAN".',
                '- Para facturar en una venta: en el POS al cobrar, casilla "El cliente pide factura electrónica".',
            ])],
            [['nomina', 'sueldo', 'salario', 'liquidar', 'desprendible', 'prima'], implode("\n", [
                'NÓMINA · '.$r('nomina.index'),
                '- Menú Nómina: primero "Empleados" para registrarlos. "Liquidar un periodo": Desde, Hasta, Fecha de pago → "Crear y calcular"; en Periodos, botón "Abrir" para verlo. "Nómina electrónica" para enviarla a la DIAN. Configuración: cómo maneja la nómina, pago, prefijo, exoneración (Art. 114-1 E.T.).',
            ])],
            [['enlace', 'linktree', 'link en bio', 'mis enlaces'], implode("\n", [
                'MIS ENLACES (tipo Linktree) · '.$r('enlaces.index'),
                '- Mi negocio → "Mis enlaces": "Agregar" (enlace, título, destacar, fechas para aparecer/ocultarse), subir/bajar, ocultar, editar ✏️, eliminar; "Perfil y diseño" (foto, descripción, tema, botones, redes) → "Guardar perfil"; "Publicar ahora", "Copiar", "Compartir", "QR"; estadísticas.',
            ])],
        ];
    }

    /** Temas que tienen que ver con lo que pregunta (máx. 3). */
    public static function para(string $texto): string
    {
        $t = mb_strtolower(\Illuminate\Support\Str::ascii($texto));
        $hits = [];
        foreach (self::temas() as $i => [$claves, $txt]) {
            $n = 0;
            foreach ($claves as $c) {
                if (str_contains($t, $c)) {
                    $n += mb_strlen($c);
                }
            }
            if ($n) {
                $hits[$i] = [$n, $txt];
            }
        }
        uasort($hits, fn ($a, $b) => $b[0] <=> $a[0]);
        $txt = array_column(array_slice($hits, 0, 3), 1);

        return "CÓMO SE HACE (pasos reales del panel; usa SOLO estos, no inventes otros):\n".self::mapa()
            .($txt ? "\n\n".implode("\n\n", $txt) : '');
    }
}
