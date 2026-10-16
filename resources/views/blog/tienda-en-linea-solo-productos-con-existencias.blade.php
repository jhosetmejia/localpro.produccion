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

<p>Pocas cosas desaniman más a un cliente que entrar a una tienda en línea, enamorarse de un producto y descubrir que está agotado. Ahora usted decide qué ve su cliente.</p>

<h2>Dos opciones, usted elige</h2>
<div class="tw"><table class="tabla">
<tr><th>Opción</th><th>Qué pasa</th></tr>
<tr><td><b>Solo los que tienen existencias</b></td><td>Lo agotado se oculta solo y vuelve a aparecer cuando entra mercancía. Su catálogo se ve ordenado y todo lo que el cliente ve, lo puede comprar.</td></tr>
<tr><td><b>Todos, también los agotados</b></td><td>Los agotados salen al final con la etiqueta "Agotado". Sirve si quiere mostrar todo lo que maneja aunque no haya en este momento.</td></tr>
</table></div>

<h2>Cómo cambiarlo</h2>
<ol>
    <li>Entre a <b>Mi negocio → Datos del negocio</b>.</li>
    <li>Debajo de "Tienda en línea visible al público", en <b>¿Qué productos se muestran en la tienda en línea?</b>, elija la opción.</li>
    <li>Guarde. El cambio se ve de inmediato en su tienda en línea.</li>
</ol>

<div class="box">
    <b>Bueno saber:</b> los servicios y productos que no llevan control de inventario (por ejemplo un plan o una asesoría) siempre se muestran, porque nunca se agotan. Y si alguien entra al enlace de un producto agotado, lo lleva a su catálogo en vez de mostrarle una página vacía.
</div>

<h2>Fotos que cargan rápido</h2>
<p>Además, ahora LocalProo <b>achica las fotos automáticamente</b> cuando las sube, sin perder calidad: una foto de 4 MB del celular queda en unos 200 KB. Su tienda en línea abre más rápido y sus clientes gastan menos datos.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Cree su tienda en línea con LocalProo</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en <b>Mi negocio → Datos del negocio</b>.</p>
@endsection
