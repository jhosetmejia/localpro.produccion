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

<p>Muchos clientes preguntan por WhatsApp "¿qué tienen hoy?". Con la <b>carta virtual</b> de LocalProo les manda un enlace con su menú bonito, por categorías, con precios y fotos, y ellos mismos arman el pedido.</p>

<h2>1. Abra su carta</h2>
<ol>
    <li>En el panel toque <b>Ver mi carta virtual</b>.</li>
    <li>Ahí salen sus productos por categoría: entradas, almuerzos, bebidas… Los platos sin foto también salen.</li>
    <li>Copie el enlace en <b>Mi negocio</b> y compártalo en WhatsApp e Instagram, o póngalo en un código QR en las mesas.</li>
</ol>

<h2>2. Elija el diseño</h2>
<p>En <b>Mi negocio</b>, en la parte de la tienda en línea, elija el diseño de su carta:</p>
<ul>
    <li><b>Bistró</b>: negro y dorado, elegante (viene por defecto).</li>
    <li><b>Fresca</b>: crema y terracota, casera.</li>
    <li><b>Parrilla</b>: carbón y rojo, para asados y comidas rápidas.</li>
    <li><b>Huerta</b>: verde y blanco, saludable.</li>
</ul>
<p>Toque <b>Ver cómo queda</b> en cada uno antes de guardar.</p>

<h2>3. Sus clientes piden por WhatsApp</h2>
<p>El cliente agrega los platos, elige <b>domicilio</b>, <b>para recoger</b> o <b>en la mesa</b>, y le llega a usted el pedido completo por WhatsApp con el total.</p>

<div class="box">
    <b>¿Se acabaron los almuerzos?</b> No tiene que poner cantidades. En <b>Productos</b> toque <b>Desactivar</b> en ese plato y en la carta sale "Agotado por hoy": nadie lo puede pedir, ni en la carta ni en la caja ni con los meseros. Mañana toque <b>Activar</b> y vuelve a salir.
</div>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => 'restaurante', 'desde' => 'blog:' . $slug])) }}">Cree la carta de su restaurante gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? En el panel toque <b>Ver mi carta virtual</b>.</p>
@endsection
