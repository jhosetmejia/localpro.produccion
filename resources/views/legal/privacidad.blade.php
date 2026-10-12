@extends('legal._base')
@section('titulo', 'Política de privacidad')
@section('cuerpo')
<p>En LocalProo (localproo.com) tratamos los datos personales conforme a la Ley 1581 de 2012 de Colombia y sus decretos reglamentarios. Esta política explica qué datos recogemos, para qué los usamos y cómo puede ejercer sus derechos.</p>
<h2>1. Qué datos tratamos</h2>
<ul>
    <li><b>De los negocios y sus usuarios:</b> nombre, correo, celular, datos del negocio, productos, ventas, cajas e inventario que registran en el sistema.</li>
    <li><b>De los clientes de cada negocio:</b> los datos que el negocio registra (nombre, cédula, celular, correo, dirección e historial de compras).</li>
    <li><b>De Facebook e Instagram ("Mis redes"):</b> cuando un negocio conecta su página de Facebook y su cuenta profesional de Instagram, recibimos el nombre y la foto de la página y de la cuenta, un token de acceso, y los mensajes que las personas envían a esa página o cuenta (texto, imágenes, nombre de usuario y foto de perfil pública), para mostrarlos en la bandeja del negocio y permitirle responder. También publicamos en su nombre las fotos y textos que el negocio decide publicar.</li>
    <li><b>De los asesores:</b> nombre, cédula, celular, correo, ubicación y datos para el pago de comisiones.</li>
</ul>
<h2>2. Para qué los usamos</h2>
<ul>
    <li>Prestar el servicio: ventas, inventario, clientes, facturas, separados, tienda en línea y "Mis redes".</li>
    <li>Responder mensajes y publicar en Facebook e Instagram <b>solo cuando el negocio lo decide</b>. No vendemos datos, no los usamos para publicidad de terceros y no leemos los mensajes con otro fin.</li>
    <li>Cobrar los planes, pagar comisiones y dar soporte.</li>
</ul>
<h2>3. Con quién los compartimos</h2>
<p>Solo con proveedores necesarios para operar: servidor (Hostinger), pagos (Wompi) y Meta Platforms (Facebook e Instagram) para enviar mensajes y publicaciones que el negocio ordena. Cada negocio es responsable de los datos de sus propios clientes.</p>
<h2>4. Seguridad y conservación</h2>
<p>Los tokens de acceso a Facebook e Instagram se guardan cifrados. Los datos se conservan mientras la cuenta esté activa. Al desconectar una página en "Mis redes → Cuentas" dejamos de recibir mensajes y se invalida su token.</p>
<h2>5. Sus derechos</h2>
<p>Puede conocer, actualizar, rectificar y pedir la eliminación de sus datos, y revocar la autorización, escribiendo a <a href="mailto:{{ $correo }}">{{ $correo }}</a>. Vea cómo eliminar sus datos en <a href="{{ route('legal.eliminar') }}">{{ route('legal.eliminar') }}</a>.</p>
@endsection
