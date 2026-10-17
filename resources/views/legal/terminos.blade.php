@extends('legal._base')
@section('titulo', 'Condiciones de uso')
@section('fecha', '17 de octubre de 2026')
@section('cuerpo')
@php
    $planAnual = \App\Models\Plan::where('activo', true)->where('precio', '>', 0)->where('dias', '>=', 365)->orderBy('precio')->first();
    $valorAnual = '$' . number_format((float) ($planAnual->precio ?? 220000), 0, ',', '.');
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
<h2>6. Limitaciones</h2>
<p>Trabajamos para que el servicio esté siempre disponible, pero puede haber interrupciones por mantenimiento o por servicios de terceros (por ejemplo, Facebook o Instagram).</p>
@endsection
