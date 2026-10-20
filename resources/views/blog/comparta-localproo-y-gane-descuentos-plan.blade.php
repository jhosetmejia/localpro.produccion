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


<p>La mejor publicidad de un negocio es la recomendación de otro empresario. Por eso en LocalProo cada negocio tiene su propio <b>código</b> para recomendarnos, y gana por cada colega que se quede.</p>

<h2>Cómo funciona</h2>
<ol>
    <li>Entre a <b>Comparta y gane</b> en el menú (o a <b>Mi plan</b>). Ahí está el código de su negocio, por ejemplo <b>LCT-00012</b>, con su enlace.</li>
    <li>Compártalo por WhatsApp con un botón. Su colega se registra con su enlace o escribe su código al crear la cuenta.</li>
    <li>Su colega empieza con <b>3 meses gratis</b>.</li>
    <li>Cada vez que su colega paga su <b>plan anual</b>, a usted se le abona un valor para su propio plan. <b>De por vida</b>: todos los años que siga pagando.</li>
</ol>

<div class="box">
    <b>El abono se descuenta solo.</b> Cuando usted paga su plan anual en Mi plan, LocalProo descuenta lo que tenga acumulado. Si su saldo alcanza a cubrir todo el plan, se activa sin pagar nada.
</div>

<h2>Más beneficios</h2>
<ul>
    <li>Los negocios que recomiendan LocalProo reciben primero las herramientas nuevas.</li>
    <li>Acceso a recursos como cursos y plantillas.</li>
    <li>En Comparta y gane ve a quién invitó, quién ya pagó y cuánto ha ganado.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en el menú: <b>Comparta y gane</b>.</p>
@endsection
