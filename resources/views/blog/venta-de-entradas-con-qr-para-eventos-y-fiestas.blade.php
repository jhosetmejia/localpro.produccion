@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $a['titulo'], 'description' => $a['resumen'], 'datePublished' => $a['publicado'], 'dateModified' => $a['publicado'], 'inLanguage' => 'es-CO', 'mainEntityOfPage' => url()->current(), 'author' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')], 'publisher' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
@section('cuerpo')
<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>Vender boletas por WhatsApp es un caos: capturas de pantalla perdidas, listas en Excel, gente que dice "yo ya pagué" y boletas fotocopiadas en la puerta. Con <b>LocalProo Eventos</b> todo queda en un solo lugar y cada entrada tiene un <b>QR único</b> que solo sirve una vez.</p>

<h2>1. Cree la página de su evento</h2>
<ol>
    <li>Entre a <b>Eventos y entradas → Nuevo evento</b>.</li>
    <li>Suba el afiche, escriba la fecha, el lugar y la publicidad del evento.</li>
    <li>Ponga la etapa y el precio (por ejemplo, <b>Primera etapa</b> hasta el 26 de octubre), su número de <b>Nequi</b> y el <b>WhatsApp</b> donde recibe los comprobantes.</li>
    <li>Publíquelo y comparta el enlace <b>localproo.com/evento/su-evento</b> en Instagram, TikTok y los estados de WhatsApp.</li>
</ol>

<h2>2. La gente se registra y paga</h2>
<p>En la página tocan <b>Comprar entrada</b> y llenan sus datos: nombre, correo, cédula o tarjeta de identidad, celular, fecha de nacimiento y una clave, más las preguntas que usted agregue al formulario (talla, empresa, ciudad, acompañante…). Al terminar les sale el Nequi y el valor, y el botón <b>Enviar comprobante de pago</b> les abre WhatsApp con el mensaje listo y sus datos para que usted sepa quién es.</p>

<h2>3. Usted aprueba el pago</h2>
<p>En el panel ve primero a quienes ya mandaron el comprobante. Revisa la captura, toca <b>Aprobar pago</b> y el sistema le genera a esa persona su entrada con QR, le llega un correo y usted le puede avisar por WhatsApp con un toque.</p>

<div class="box">
    <b>La entrada es personal e intransferible.</b> La persona la ve en <b>Mi entrada</b> con su correo y su clave, con el aviso de no compartir el QR, y la puede descargar en PDF para no depender del internet.
</div>

<h2>4. En la puerta, escanee con el celular</h2>
<ul>
    <li><b>Verde:</b> puede entrar (sale el nombre y el documento para revisarlo).</li>
    <li><b>Rojo:</b> ese QR ya se usó (y a qué hora) o no existe.</li>
    <li><b>Naranja:</b> se registró pero el pago no está aprobado.</li>
</ul>
<p>Aunque dos porteros escaneen el mismo QR al mismo tiempo, solo uno lo deja entrar.</p>

<h2>5. Su equipo del evento, cada uno con su parte</h2>
<p>Al activar Eventos, LocalProo ya deja creados los roles del equipo de organización. Usted solo crea el usuario de cada persona y le asigna su rol en <b>Usuarios</b>:</p>
<ul>
    <li><b>Administrador del evento:</b> todo, incluido el precio, los datos de pago y publicar.</li>
    <li><b>Contabilidad:</b> ve a los compradores, aprueba o rechaza los pagos y descarga el Excel.</li>
    <li><b>Logística:</b> edita la información del evento (fecha, lugar, descripción, condiciones, redes), ve a los asistentes y usa la puerta.</li>
    <li><b>Diseño:</b> cambia el afiche o banner, el logo, el estilo y el color de la página.</li>
    <li><b>Puerta:</b> solo el escáner del QR.</li>
</ul>
<p>Nadie del equipo ve sus ventas, inventario ni caja: cada uno entra a LocalProo y llega directo a Eventos. Puede cambiar los permisos de cada rol en <b>Roles del negocio</b>.</p>

<h2>6. Arme su propio formulario de registro</h2>
<p>En <b>Editar → Formulario de registro</b> decide si pide fecha de nacimiento y celular, y agrega sus propias preguntas: texto, número, fecha, lista de opciones o Sí/No, obligatorias u opcionales. Las respuestas salen en Compradores y en el Excel. Sirve para fiestas, conciertos, congresos, ferias, carreras o cualquier evento.</p>

<h2>7. ¿Cuánta gente ha visto su evento?</h2>
<p>La página cuenta las visitas (una por persona, sin contar robots ni a su equipo). En el panel ve las visitas y qué porcentaje se registró; si quiere, la página muestra al público <b>"X personas han visto este evento"</b> para animar a comprar. Se apaga en <b>Editar → Publicación</b>.</p>

<h2>8. Guía rápida: ¿dónde está cada cosa?</h2>
<table style="width:100%;border-collapse:collapse;font-size:.95rem">
    <tr><th style="text-align:left;padding:.5rem;border-bottom:2px solid #e5e7eb">Quiero…</th><th style="text-align:left;padding:.5rem;border-bottom:2px solid #e5e7eb">Vaya a…</th></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Crear un evento</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Eventos y entradas → Nuevo evento</b></td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Cambiar fecha, lugar, precio o Nequi</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Eventos y entradas → su evento → Editar</b></td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Cambiar afiche, logo, estilo o color</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Editar</b> → columna derecha (Estilo de la página, Logo, Afiches)</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Agregar, quitar o editar preguntas del registro</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Editar → Formulario de registro → Agregar campo</b> (la 🗑 lo quita)</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Ver quién compró y aprobar pagos</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Eventos y entradas → Compradores</b> (pestaña <b>Por aprobar</b>)</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Descargar la lista</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Compradores → Excel</b></td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Ver o cambiar lo que puede hacer cada rol</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Roles del negocio</b> (menú lateral)</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Darle acceso a alguien del equipo</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Usuarios → Nuevo usuario</b> → elija su rol</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Escanear en la entrada</td><td style="padding:.5rem;border-bottom:1px solid #eee"><b>Eventos y entradas → Puerta</b> (desde el celular)</td></tr>
    <tr><td style="padding:.5rem">Mostrar u ocultar el contador de visitas</td><td style="padding:.5rem"><b>Editar → Publicación</b></td></tr>
</table>

<h2>9. Antes de publicar: haga una prueba completa</h2>
<p>Dedique 15 minutos a probar todo usted mismo antes de compartir el enlace. Es la mejor forma de evitar sorpresas el día de la venta:</p>
<ol>
    <li><b>Déjelo en Borrador.</b> Así solo usted ve la página. Ábrala en su celular y revise el afiche, la fecha, el precio y que el Nequi esté bien escrito.</li>
    <li><b>Regístrese como si fuera un comprador.</b> Abra el enlace en una ventana de incógnito (o en otro celular) y llene el formulario con un correo suyo. Revise que las preguntas sean las que quiere.</li>
    <li><b>Toque "Enviar comprobante de pago".</b> Confirme que se abre el WhatsApp correcto con el mensaje listo.</li>
    <li><b>Apruebe ese registro de prueba</b> en Compradores y revise que le llegue el correo y que en <b>Mi entrada</b> salga el QR y se descargue el PDF.</li>
    <li><b>Escanéelo en Puerta</b> con otro celular: debe salir en verde. Escanéelo otra vez: debe salir en rojo, "ya se usó".</li>
    <li><b>Anule la entrada de prueba</b> para que no le cuente en el cupo ni en el recaudo.</li>
    <li>Cambie el evento a <b>Publicado</b> y comparta el enlace.</li>
</ol>

<h2>10. Arme su equipo paso a paso</h2>
<ol>
    <li>Entre a <b>Roles del negocio</b> y revise los roles que ya vienen creados: <b>Administrador del evento, Contabilidad, Logística, Diseño y Puerta</b>. Si quiere, cambie sus permisos o cree otro rol.</li>
    <li>En <b>Usuarios → Nuevo usuario</b> cree a cada persona con su nombre, correo, celular y una clave, y elija su rol.</li>
    <li>Mándele por WhatsApp: "Entra a <b>localproo.com/login</b> con tu correo y esta clave". Al entrar llega directo a Eventos y solo ve lo de su rol.</li>
    <li>El equipo <b>no se registra solo</b>: si alguien se registra por su cuenta en LocalProo, se le crea otro negocio aparte, sin su evento.</li>
</ol>

<h2>11. Recomendaciones para que todo salga bien</h2>
<ul>
    <li><b>Apruebe los pagos varias veces al día.</b> La gente se tranquiliza cuando recibe su QR rápido, y así evita mensajes de "¿ya me aprobaron?".</li>
    <li><b>Compare siempre la captura con el nombre y el valor</b> antes de aprobar. Si tiene dudas, revise el movimiento en su Nequi y anote la referencia.</li>
    <li><b>Cambie de etapa a tiempo.</b> Edite el nombre de la etapa, el precio y la fecha "hasta"; los ya registrados conservan su precio.</li>
    <li><b>Ponga cupo</b> si el lugar tiene capacidad limitada: al llenarse, la página muestra "Agotado" sola.</li>
    <li><b>Pida solo lo necesario</b> en el formulario. Cada pregunta de más hace que menos gente termine el registro.</li>
    <li><b>Prepare la puerta:</b> celulares con batería y datos, brillo alto, un portero por fila, y que revisen el documento contra el nombre que sale en pantalla.</li>
    <li><b>Pida a los asistentes que descarguen el PDF</b> de su entrada antes de llegar, por si no tienen señal.</li>
    <li><b>Descargue el Excel</b> al cierre de cada día para cuadrar con su Nequi.</li>
    <li><b>Si alguien olvidó su clave</b>, en Compradores toque <b>Restablecer clave</b>: queda su número de documento.</li>
</ul>

<a class="cta" href="{{ route('registro', ['tipo' => 'eventos', 'desde' => 'blog:' . $slug]) }}">Cree su evento gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Actívelo en <b>Datos del negocio → Módulos → Eventos y venta de entradas</b>.</p>
@endsection
