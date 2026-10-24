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
<p>En la página tocan <b>Comprar entrada</b> y llenan sus datos: nombre, correo, cédula o tarjeta de identidad, fecha de nacimiento, colegio, celular y una clave. Al terminar les sale el Nequi y el valor, y el botón <b>Enviar comprobante de pago</b> les abre WhatsApp con el mensaje listo y sus datos para que usted sepa quién es.</p>

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

<h2>6. ¿Cuánta gente ha visto su evento?</h2>
<p>La página cuenta las visitas (una por persona, sin contar robots ni a su equipo). En el panel ve las visitas y qué porcentaje se registró; si quiere, la página muestra al público <b>"X personas han visto este evento"</b> para animar a comprar. Se apaga en <b>Editar → Publicación</b>.</p>

<a class="cta" href="{{ route('registro', ['tipo' => 'eventos', 'desde' => 'blog:' . $slug]) }}">Cree su evento gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Actívelo en <b>Datos del negocio → Módulos → Eventos y venta de entradas</b>.</p>
@endsection
