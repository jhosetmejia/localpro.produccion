@extends('legal._base')
@section('titulo', 'Política de privacidad')
@section('fecha', '24 de octubre de 2026')
@section('cuerpo')
<p>En LocalProo (localproo.com) tratamos los datos personales conforme a la Ley 1581 de 2012 de Colombia y sus decretos reglamentarios. Esta política explica qué datos recogemos, para qué los usamos y cómo puede ejercer sus derechos.</p>
<h2>1. Qué datos tratamos</h2>
<ul>
    <li><b>De los negocios y sus usuarios:</b> nombre, correo, celular, datos del negocio, productos, ventas, cajas e inventario que registran en el sistema.</li>
    <li><b>De los clientes de cada negocio:</b> los datos que el negocio registra (nombre, cédula, celular, correo, dirección e historial de compras).</li>
    <li><b>De Facebook e Instagram ("Mis redes"):</b> cuando un negocio conecta su página de Facebook y su cuenta profesional de Instagram, recibimos el nombre y la foto de la página y de la cuenta, un token de acceso, y los mensajes que las personas envían a esa página o cuenta (texto, imágenes, nombre de usuario y foto de perfil pública), para mostrarlos en la bandeja del negocio y permitirle responder. También publicamos en su nombre las fotos y textos que el negocio decide publicar.</li>
    <li><b>De los consultorios:</b> datos de pacientes, agenda e historia clínica que registra el profesional de salud. Son <b>datos sensibles</b> (Ley 1581, art. 5).</li>
    <li><b>De los asistentes a eventos:</b> nombre, documento, correo, celular, fecha de nacimiento, las respuestas al formulario que el organizador arme y los datos de sus acompañantes.</li>
    <li><b>De firmas y negocios a los que ofrecemos LocalProo:</b> nombre del negocio y datos de contacto publicados en su página web o en directorios públicos.</li>
    <li><b>De los asesores:</b> nombre, cédula, celular, correo, ubicación y datos para el pago de comisiones.</li>
</ul>
<h2>2. Para qué los usamos</h2>
<ul>
    <li>Prestar el servicio: ventas, inventario, clientes, facturas, separados, tienda en línea y "Mis redes".</li>
    <li>Responder mensajes y publicar en Facebook e Instagram <b>solo cuando el negocio lo decide</b>. No vendemos datos, no los usamos para publicidad de terceros y no leemos los mensajes con otro fin.</li>
    <li>Cobrar los planes, pagar comisiones y dar soporte.</li>
</ul>
<h2>3. Quién es responsable de cada dato</h2>
<ul>
    <li><b>LocalProo es responsable</b> de los datos de los negocios, sus usuarios, los asesores y las personas a las que ofrecemos el servicio.</li>
    <li><b>Cada negocio es responsable</b> de los datos de sus clientes, pacientes, empleados y asistentes a sus eventos, y debe tener su autorización para tratarlos. Frente a esos datos, <b>LocalProo actúa como encargado</b>: los guarda y procesa solo para prestarle el servicio al negocio, siguiendo sus instrucciones, con reserva y medidas de seguridad, y no los usa para nada más.</li>
    <li><b>Datos de salud:</b> el profesional recoge la autorización expresa del paciente. En LocalProo solo los ven las personas del consultorio con permiso de historia clínica, y se conservan por el tiempo que exige la ley (Resolución 839 de 2017), aunque el consultorio deje de pagar.</li>
    <li><b>Niñas, niños y adolescentes:</b> en eventos se registran con autorización de su padre, madre o acudiente. Sus datos se tratan respetando su interés superior y sus derechos fundamentales (Ley 1581, art. 7).</li>
</ul>
<h2>4. Con quién los compartimos</h2>
<p>Solo con proveedores necesarios para operar, que nos prestan servicios como encargados: servidor (Hostinger), protección y correo del dominio (Cloudflare), envío de correos (Brevo), copias de seguridad (Backblaze), pagos (Wompi) y Meta Platforms (Facebook e Instagram) para los mensajes y publicaciones que el negocio ordena. Algunos de ellos guardan la información fuera de Colombia (por ejemplo, en Estados Unidos o la Unión Europea), con niveles adecuados de protección. No vendemos ni alquilamos datos.</p>
<h2>5. Seguridad y conservación</h2>
<p>Usamos conexión cifrada (HTTPS), claves guardadas de forma irreversible, permisos por rol y copias de seguridad diarias. Los tokens de acceso a Facebook e Instagram y las credenciales de facturación se guardan cifrados. Los datos se conservan mientras la cuenta esté activa; al terminar una prueba sin pago se aplica lo que dicen las <a href="{{ route('legal.terminos') }}">condiciones de uso</a>. Al desconectar una página en "Mis redes → Cuentas" dejamos de recibir mensajes y se invalida su token.</p>
<h2>6. Sus derechos y cómo ejercerlos</h2>
<p>Como titular puede conocer, actualizar y rectificar sus datos, pedir prueba de la autorización, saber cómo se han usado, revocar la autorización o pedir que se eliminen (cuando no exista un deber legal de conservarlos) y presentar quejas ante la Superintendencia de Industria y Comercio. Escriba a <a href="mailto:{{ $correo }}">{{ $correo }}</a> con su nombre, documento y lo que pide.</p>
<ul>
    <li><b>Consultas:</b> respondemos en máximo 10 días hábiles (prorrogables 5 más avisándole).</li>
    <li><b>Reclamos</b> (corregir, actualizar, suprimir o revocar): respondemos en máximo 15 días hábiles (prorrogables 8 más avisándole).</li>
    <li>Si sus datos los registró un negocio (por ejemplo, como cliente, paciente o asistente a un evento), puede dirigirse a ese negocio o a nosotros, y le trasladaremos la solicitud.</li>
</ul>
<p>Si recibe correos de LocalProo y no quiere más, use el enlace "Darme de baja" del correo. Vea cómo eliminar sus datos en <a href="{{ route('legal.eliminar') }}">{{ route('legal.eliminar') }}</a>.</p>
@endsection
