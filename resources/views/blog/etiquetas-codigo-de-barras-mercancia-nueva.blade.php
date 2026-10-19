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

<p>Llega el pedido del proveedor: cajas y cajas de prendas. Lo normal es crear cada producto en el sistema, imprimir sus etiquetas y solo ahí pegarlas. Mientras tanto la mercancía queda "suelta", sin código, y es fácil que algo se quede sin contar.</p>
<p>Con LocalProo puede hacerlo al revés: <b>primero sella toda la mercancía y después la crea</b>, a su ritmo.</p>

<h2>Cómo funciona</h2>
<ol>
    <li>Entre a <b>Productos → Etiquetas para mercancía nueva</b>, elija cuántos códigos necesita y toque <b>Generar e imprimir</b>. Salen etiquetas <b>en blanco</b>: solo el código de barras, sin nombre ni precio.</li>
    <li>Pegue las etiquetas a la mercancía mientras desempaca. En un momento queda todo sellado.</li>
    <li>Cuando cree cada producto, en cada <b>talla y color</b> toque <b>Escanear</b> y pase la etiqueta con la cámara del celular (o con su lector). El producto queda con <b>ese código</b>: el sistema no le inventa otro y no hay que volver a imprimir.</li>
    <li>Listo: la caja la vende y el conteo físico la cuenta al escanearla.</li>
</ol>

<div class="box">
    <b>¿Una etiqueta por prenda o por referencia?</b> Lo más fácil es <b>un código por cada talla y color</b> con varias copias. Pero si ya pegó códigos distintos a prendas iguales, no pasa nada: escanéelos todos en la misma talla/color y quedan como <b>etiquetas extra</b> que sirven igual.
</div>

<h2>Si algo se quedó sin crear</h2>
<p>Si en el <b>conteo físico</b> escanea una etiqueta que todavía no tiene producto, LocalProo le avisa que es mercancía nueva y le da el botón <b>Crear el producto con esta etiqueta</b>. En la <b>caja</b> también le avisa, para que no se venda algo que el sistema no conoce.</p>
<p>Y si una prenda ya creada no tenía etiqueta, en sus <b>tallas y colores</b> escanee la etiqueta que le pegó y queda enlazada.</p>

<h2>En resumen</h2>
<ul>
    <li>Etiquetas en blanco por lotes, en rollo de 50 × 25 mm o en hoja carta.</li>
    <li>Selle toda la mercancía de una vez; créela después escaneando la etiqueta.</li>
    <li>Sirven en la caja y en el conteo, también los códigos de fábrica.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Productos → Etiquetas para mercancía nueva</b>.</p>
@endsection
