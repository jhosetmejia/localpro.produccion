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

<p>Tener una tienda en línea es el primer paso. El segundo es que la gente la encuentre. Por eso creamos el <b>Marketplace LocalProo</b>: un solo lugar donde están todas las tiendas en línea de los negocios que usan LocalProo, con todos sus productos.</p>

<h2>Cómo funciona para el cliente</h2>
<ol>
    <li>Entra a <a href="{{ route('marketplace') }}"><b>localproo.com/marketplace</b></a> y escribe lo que busca: "tenis", "chaqueta", "audífonos"…</li>
    <li>Ve los productos de todas las tiendas, del más barato al más caro, con el nombre del negocio y la ciudad.</li>
    <li>Toca el producto, entra a la tienda del negocio y le compra directo por <b>WhatsApp</b>.</li>
</ol>

<h2>Qué gana su negocio</h2>
<ul>
    <li><b>Más clientes sin pagar publicidad</b>: su tienda aparece junto a las demás tiendas locales.</li>
    <li><b>Sin comisión por venta</b>: el cliente le compra a usted directamente.</li>
    <li>Filtros por tipo de negocio y por ciudad, para que lo encuentren los clientes de su zona.</li>
</ul>

<div class="box">
    <b>No tiene que hacer nada extra.</b> Si su tienda en línea está activa y sus productos tienen foto, su negocio ya sale en el Marketplace. Buenas fotos y precios claros hacen la diferencia, porque su producto queda al lado del de otras tiendas.
</div>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Cree su tienda gratis</a>
<p style="text-align:center;color:#6b7570"><a href="{{ route('marketplace') }}">Ver el Marketplace LocalProo →</a></p>
@endsection
