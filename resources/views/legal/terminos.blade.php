@extends('legal._base')
@section('titulo', 'Condiciones de uso')
@section('fecha', '27 de octubre de 2026')
@section('cuerpo')
@php
    $planAnual = \App\Models\Plan::where('activo', true)->where('precio', '>', 0)->where('dias', '>=', 365)->orderBy('precio')->first();
    $valorAnual = '$' . number_format((float) ($planAnual->precio ?? 220000), 0, ',', '.') . ' COP'
        . ($planAnual ? ' o ' . \App\Support\Moneda::formato($planAnual->precioEn(\App\Support\Moneda::deVenezuela()), \App\Support\Moneda::deVenezuela()) . ' para negocios de Venezuela' : '');
    $maxProductos = $planAnual->max_productos ?? 400;
@endphp
<p>Al registrarse y usar LocalProo usted acepta estas condiciones. Al crear la cuenta se marca la casilla "Acepto las condiciones de uso".</p>
<h2>1. El servicio</h2>
<p>LocalProo es un sistema en línea para negocios: caja (POS), inventario, clientes, separados, tienda en línea y conexión con Facebook e Instagram ("Mis redes"). Se ofrece con una prueba gratis y luego con el plan elegido.</p>
<h2>2. Responsabilidad del negocio</h2>
<ul>
    <li>Es responsable de la información que registra, de los datos de sus clientes y de tener su autorización para tratarlos.</li>
    <li>En "Mis redes" es responsable de lo que responde y publica, y debe cumplir las políticas de Meta (Facebook e Instagram). Meta solo permite responder dentro de las 24 horas siguientes al último mensaje del cliente.</li>
    <li>Debe cuidar su usuario y clave.</li>
</ul>
<h2>3. Prueba gratis</h2>
<ul>
    <li>Cada negocio nuevo tiene un tiempo de prueba gratis. Su duración depende de la promoción vigente al momento del registro (por ejemplo, unos días, un mes o tres meses) y se muestra dentro del sistema.</li>
    <li>Al terminar la prueba, el sistema se bloquea y muestra la opción de pagar el plan (mensual o anual). LocalProo le avisa por <strong>correo electrónico</strong> a los correos registrados del negocio y de sus dueños o administradores, y con un aviso dentro del sistema. Antes de que termine la prueba también se envía un recordatorio.</li>
    <li>Desde ese aviso, el negocio tiene <strong>15 días calendario</strong> para pagar y conservar toda su información. Unos días antes de vencer ese plazo se envía un último aviso. LocalProo guarda constancia de la fecha, hora y destinatario de cada aviso enviado y de cuándo se vio el aviso en el sistema.</li>
    <li>Pasado ese plazo sin pago, LocalProo <strong>elimina de forma definitiva el contenido del negocio</strong> para no ocupar espacio en el servidor con información que no se va a usar: productos, imágenes, videos, inventario, ventas, cajas, clientes, proveedores, separados, alquileres, publicaciones y demás registros. Esta información no se puede recuperar. Al eliminarla se envía un correo informándolo.</li>
    <li>Es responsabilidad del negocio mantener actualizado su correo electrónico para recibir estos avisos.</li>
    <li>Se <strong>conservan los datos de contacto</strong>: nombre y datos del negocio (NIT, teléfono, correo, ciudad, tipo de negocio) y los datos de sus usuarios (nombre, correo y celular). LocalProo puede usarlos para contactarle con ofrecimientos del servicio, promociones o para reactivar su cuenta, y puede pedir que se borren en "Eliminar mis datos".</li>
    <li>Si después paga un plan, podrá seguir usando su cuenta, pero empezará con el negocio vacío.</li>
    <li><strong>Excepción, historias clínicas:</strong> si el negocio usa el módulo Consultorio, las historias clínicas de sus pacientes no se eliminan por falta de pago. Se conservan durante el tiempo que exige la ley y se le entregan al negocio cuando las solicite.</li>
</ul>
<h2>4. Pagos</h2>
<p>Los planes se pagan en línea (Wompi) o por los medios que LocalProo indique. Si un plan pago vence, el acceso se bloquea hasta renovarlo; en planes pagos la información no se borra por el vencimiento.</p>
<h2>5. Qué incluye el plan y cobros adicionales</h2>
<ul>
    <li><strong>El plan es el uso del software.</strong> El valor del plan (el anual hoy cuesta {{ $valorAnual }}) corresponde a la licencia de uso de LocalProo para un negocio, durante el periodo pagado. No incluye servicios de terceros ni consumos que dependen del volumen de cada negocio.</li>
    <li><strong>Límites del plan.</strong> Cada plan incluye una cantidad de productos, de usuarios y de espacio de almacenamiento (fotos, videos y documentos), que se muestra en <em>Mi plan</em>. El plan anual incluye hasta {{ number_format($maxProductos, 0, ',', '.') }} productos; para tener más, el negocio paga {{ \App\Support\AmpliarProductos::oferta() }}.</li>
    <li><strong>Uso adicional.</strong> Si un negocio supera los límites de su plan en productos, usuarios o espacio de almacenamiento, debe pagar el uso adicional según la tarifa vigente. LocalProo se lo informa antes por correo y con un aviso dentro del sistema. Mientras no se pague, LocalProo puede no permitir crear más productos o usuarios ni subir más archivos, pero <strong>no borra</strong> la información que el negocio ya tiene.</li>
    <li><strong>Facturación electrónica.</strong> No está incluida en el valor del plan. Los documentos electrónicos ante la DIAN (factura electrónica, documento equivalente POS, notas crédito y débito, documento soporte y nómina electrónica) se emiten a través de un proveedor tecnológico autorizado que cobra por documento. El negocio que facture electrónicamente paga aparte su paquete de documentos y, si aplica, el certificado digital, según la tarifa que LocalProo le informe antes de activarla. La habilitación ante la DIAN y la resolución de numeración son responsabilidad del negocio; LocalProo lo acompaña en el proceso.</li>
    <li><strong>Otros servicios.</strong> Desarrollos a la medida, migración de información, dominio propio, capacitaciones presenciales y otros servicios especiales se cotizan aparte.</li>
    <li><strong>Cambios de tarifas.</strong> Cualquier cambio en los precios del plan o de los cobros adicionales se avisa con al menos 30 días de anticipación y no afecta el periodo que ya está pagado.</li>
</ul>
@php
    $cv = \App\Support\Comisiones::valores();
    $cf = fn ($v) => \App\Support\Moneda::formato($v, 'COP');
@endphp
<h2>6. Asesores comerciales: comisiones y pagos</h2>
<ul>
    <li><strong>Solo el plan anual paga comisión.</strong> Mensualidades, pruebas gratis y servicios adicionales no generan comisión. Los pagos de prueba tampoco.</li>
    <li><strong>Venta nueva</strong> (el negocio paga su plan anual por primera vez): el asesor que lo inscribió gana {{ $cf($cv['nueva']['venta']) }}; quien sumó a ese asesor a la red gana {{ $cf($cv['nueva']['arriba']) }}, y cada líder de zona, ciudad, departamento y país que tenga arriba gana {{ $cf($cv['nueva']['lider_zona']) }}.</li>
    <li><strong>Renovación anual:</strong> cada año que el negocio renueva, el asesor, quien lo sumó y cada líder ganan {{ $cf($cv['renovacion']['venta']) }}, de por vida.</li>
    <li><strong>Para cobrar hay que estar activo:</strong> haber hecho mínimo <strong>una venta nueva en los últimos {{ \App\Support\Comisiones::DIAS_ACTIVO }} días</strong>. Quien no cumpla no recibe esa comisión y su parte queda para LocalProo. Si un cargo está vacío o la persona está inactiva, esa parte también queda para LocalProo.</li>
    <li><strong>Cuándo se paga:</strong> la comisión de una <strong>venta nueva</strong> se puede retirar apenas el pago del negocio queda aprobado. Las comisiones de <strong>renovación</strong> se pagan <strong>una vez al mes</strong>, en el corte mensual de LocalProo.</li>
    <li>Las comisiones se consignan en la cuenta o billetera que el asesor registró en su perfil. Es responsabilidad del asesor mantener esos datos actualizados.</li>
    <li>Los valores son fijos: si cambia el precio del plan, las comisiones no cambian, salvo que LocalProo las actualice y lo avise. Si un pago se devuelve o se anula, la comisión de ese pago también se anula.</li>
    <li>Los negocios y asesores de Venezuela reciben sus comisiones en dólares, según los valores vigentes en dólares.</li>
</ul>
<h3 id="localpro-gratis">LocalPro gratis (1 mes por cada emprendedor invitado)</h3>
<ul>
    <li>Por cada negocio que se registre con el link o el código (LCT-…) de otro negocio y cumpla las reglas, el negocio que lo invitó gana <strong>1 mes (30 días)</strong> del plan que tenga, con todas sus funciones. Si su plan ya venció, el mes cuenta desde el día en que se entrega.</li>
    <li>Reglas: el invitado (1) completa los primeros pasos de LocalProo (logo y WhatsApp, 3 productos con foto, tienda en línea activa, 10 clientes, 5 clientes contactados y su primera venta); (2) usa un WhatsApp propio, distinto al del negocio que invita y que no tenga otro negocio en LocalProo; (3) lleva al menos {{ \App\Support\ReferidosMes::DIAS_MINIMOS }} días registrado y ha usado LocalProo en {{ \App\Support\ReferidosMes::DIAS_DE_USO }} días distintos; y (4) su cuenta no está suspendida.</li>
    <li>Se entrega un solo mes por cada invitado y máximo {{ \App\Support\ReferidosMes::maximoAnual() }} meses por año a cada negocio. Los meses no se cambian por dinero ni se transfieren.</li>
    <li>Las cuentas falsas, repetidas o creadas solo para ganar meses no cuentan. LocalProo puede revisarlas y anular los meses obtenidos con ellas, y suspender a quien abuse del beneficio.</li>
    <li>Mientras los invitados de un negocio completan los pasos (30 días desde que se registran), la información de ese negocio no se elimina aunque su prueba haya terminado.</li>
</ul>
<h3>Comparta y gane (negocios que recomiendan LocalProo)</h3>
<ul>
    <li>Cada negocio tiene un código (LCT-…). El negocio que se registra con ese código recibe la prueba gratis de 2 meses.</li>
    <li>Cada vez que el negocio invitado paga su <strong>plan anual</strong> (sin contar pagos de prueba), el negocio que lo invitó recibe un abono de {{ \App\Support\Moneda::formato(\App\Support\Referidos::valor('COP'), 'COP') }} ({{ \App\Support\Moneda::formato(\App\Support\Referidos::valor('USD'), 'USD') }} en Venezuela), mientras el invitado siga pagando.</li>
    <li>El abono <strong>no se consigna en dinero</strong>: es saldo que se descuenta automáticamente cuando el negocio paga su propio plan anual. No se transfiere a otros negocios ni se cambia por dinero.</li>
    <li>LocalProo puede cambiar el valor del abono avisándolo con anticipación; los abonos ya ganados se respetan. Si un pago se devuelve o se anula, el abono de ese pago también se anula. Los negocios suspendidos no reciben abonos.</li>
</ul>
<h2>7. LocalProo Eventos (venta de entradas)</h2>
<ul>
    <li>LocalProo presta la herramienta tecnológica. <strong>El organizador es quien vende las entradas y responde por el evento</strong>: su realización, permisos y registros que exija la ley (por ejemplo, el PULEP y la contribución parafiscal de la Ley 1493 de 2011 cuando se trate de espectáculos públicos de las artes escénicas), el control de edad, la seguridad y la información que publica.</li>
    <li>El comprador le paga directamente al organizador (Nequi u otro medio que este indique). LocalProo no recibe ese dinero. Las promociones, precios y condiciones las define el organizador.</li>
    <li><strong>Si el evento se cancela o cambia de fecha</strong>, el organizador debe devolver el dinero a quien lo pida, conforme a la Ley 1480 de 2011 (Estatuto del Consumidor).</li>
    <li>Cada entrada tiene un QR único que sirve una sola vez. El organizador puede pedir el documento en la puerta.</li>
    <li>Al publicar un evento, el organizador se compromete a atender directamente las quejas, reclamos y reembolsos de sus compradores, a cumplir la política de reembolsos de abajo y a <strong>mantener libre de responsabilidad a LocalProo</strong> frente a reclamos, sanciones o demandas que surjan del evento o de su venta.</li>
</ul>
<p>Los compradores aceptan estos términos al registrarse (también se ven en <a href="{{ route('eventos.terminos-compra') }}">localproo.com/eventos/terminos</a>):</p>
@include('legal._entradas', ['correo' => $correo])
<h2>8. Donaciones de Navidad</h2>
<ul>
    <li>Al pagar el plan, el negocio puede marcar voluntariamente una donación. Solo se cobra si marca la casilla, va aparte del valor del plan y no genera comisión.</li>
    <li>Lo recogido se destina a ayudas para niños de barrios vulnerables de Pasto. LocalProo publicará cuánto se recogió y cómo se entregó. Las fotos o videos de niños solo se publican con autorización de sus padres o acudientes.</li>
    <li>LocalProo no es una entidad sin ánimo de lucro: la donación no da derecho a certificado de donación para descontar en la declaración de renta.</li>
</ul>
<h2>9. Derecho de retracto y reversión del pago</h2>
<ul>
    <li>Como el plan es un servicio que empieza a prestarse apenas el negocio lo paga y lo activa, aplica la excepción al derecho de retracto del artículo 47 de la Ley 1480 de 2011. Si pagó por error y no ha usado el periodo pagado, escríbanos y lo revisamos.</li>
    <li>Si su pago con tarjeta, PSE u otro medio electrónico fue fraudulento, no autorizado o el servicio no se prestó, puede pedir la reversión del pago (artículo 51 de la Ley 1480) escribiéndonos dentro de los 5 días hábiles siguientes a que se enteró, y avisando también a su banco.</li>
</ul>
<h2>10. Datos de clientes, pacientes y asistentes</h2>
<p>Frente a los datos personales que el negocio registra, el negocio es el responsable y LocalProo actúa como encargado: los trata solo para prestar el servicio, con reserva y seguridad, según la <a href="{{ route('legal.privacidad') }}">política de privacidad</a>. Al aceptar estas condiciones, el negocio le encarga a LocalProo ese tratamiento.</p>
<h2>11. Limitaciones</h2>
<p>Trabajamos para que el servicio esté siempre disponible, pero puede haber interrupciones por mantenimiento o por servicios de terceros (por ejemplo, Facebook o Instagram).</p>
<h2>12. Ley aplicable y peticiones</h2>
<p>Estas condiciones se rigen por las leyes de Colombia. Peticiones, quejas o reclamos: <a href="mailto:{{ $correo }}">{{ $correo }}</a>. Respondemos en máximo 15 días hábiles.</p>
@endsection
