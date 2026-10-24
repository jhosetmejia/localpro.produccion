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

<p>Este diciembre, por <b>primera vez</b>, en <b>LocalProo</b> vamos a salir a los <b>barrios más necesitados de Pasto, Nariño</b>, a entregar ayudas a los niños que más lo necesitan. Y queremos hacerlo junto a los negocios que confían en nosotros.</p>

<h2>¿Cómo puedo ayudar?</h2>
<p>Cuando pague su plan de LocalProo verá la opción <b>"Dona $5.000 para recolectar ayudas para los niños más necesitados en diciembre"</b>. Si quiere ayudar, marque la casilla: los $5.000 se suman al valor de su plan en el mismo pago. Si no la marca, no se cobra nada adicional.</p>
<div class="box"><b>Su donación va aparte del plan.</b> En LocalProo la registramos por separado: todo lo que se recoge en esta campaña se destina a las ayudas de diciembre.</div>

<h2>¿Qué vamos a hacer?</h2>
<ul>
    <li>Recogemos las donaciones durante noviembre y diciembre de 2026.</li>
    <li>Con lo recogido compramos las ayudas para los niños.</li>
    <li>En diciembre vamos a los barrios más necesitados de Pasto a entregarlas en un evento de donación.</li>
    <li>Publicamos fotos y videos de la entrega en nuestras redes y en este blog.</li>
</ul>

<h2>Si su marca dona, lo invitamos</h2>
<p>A los negocios que donen los <b>avisaremos con tiempo</b> para que nos acompañen al evento de entrega, y su marca aparecerá en los <b>agradecimientos</b> cuando publiquemos el contenido de la donación. Es nuestra manera de decirle gracias y de mostrar que su negocio también le aporta a la ciudad.</p>

<h2>Preguntas frecuentes</h2>
<ul>
    <li><b>¿Es obligatorio?</b> No. Es voluntario: solo se cobra si usted marca la casilla al pagar.</li>
    <li><b>¿Cuánto es?</b> $5.000 por pago, una sola vez.</li>
    <li><b>¿Me cobran cada mes?</b> No. Se cobra solo en el pago en el que usted marca la casilla.</li>
    <li><b>¿Cómo sé que mi donación llegó?</b> Al aprobarse el pago le aparece el mensaje de agradecimiento, y su negocio queda en la lista de donantes para invitarlo a la entrega.</li>
    <li><b>¿Puedo donar más?</b> ¡Claro! Escríbanos por WhatsApp y le contamos cómo.</li>
</ul>

<p style="text-align:center;font-size:1.1rem;margin-top:24px"><b>Gracias por ayudarnos a llevar una Navidad diferente a los niños de Pasto. 💚</b></p>

<a class="cta" href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">Conozca LocalProo</a>
@endsection
