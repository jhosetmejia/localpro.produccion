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

<p>LocalProo sirve para todo tipo de negocios: tiendas de ropa, restaurantes, ferreterías, funerarias, conjuntos… Por eso trae muchas funciones. Pero una funeraria no necesita "Separados y abonos", y un restaurante quizá no usa "Entradas de mercancía".</p>
<p>Con <b>Personalizar menú</b>, usted apaga lo que no usa y su menú queda limpio, con solo lo de su negocio.</p>

<h2>Cómo hacerlo</h2>
<ol>
    <li>Entre a <b>Mi negocio → Personalizar menú</b>.</li>
    <li>Apague con el interruptor lo que no usa: Separados, Existencias, Proveedores, Facturación DIAN, Mis redes…</li>
    <li>Toque <b>Guardar menú</b>. Listo: el menú cambia para todo su equipo.</li>
</ol>

<div class="box">
    <b>Tranquilo:</b> no se borra nada. Lo que apaga solo se esconde del menú y lo puede volver a encender cuando quiera con el botón <b>Mostrar todo</b>.
</div>

<h2>Sugerido según su negocio</h2>
<p>El sistema le marca como <b>"Poco usado"</b> lo que normalmente no necesita su tipo de negocio, y con el botón <b>"Usar lo sugerido"</b> lo esconde todo de una vez. Por ejemplo, a las funerarias les sugiere esconder Separados, Existencias, Entradas y Categorías.</p>

<h2>Y los íconos también cambian</h2>
<p>LocalProo muestra los productos con el ícono de su tipo de negocio: una paloma para funerarias, una taza para cafeterías, una canasta para minimercados, herramientas para ferreterías. La palabra "prenda" solo sale en tiendas de ropa; en los demás negocios dice "producto".</p>

<h2>En resumen</h2>
<ul>
    <li>Un menú con solo lo que su negocio usa.</li>
    <li>Sugerencias según el tipo de negocio.</li>
    <li>Nada se borra: se puede volver a mostrar en cualquier momento.</li>
</ul>

<a class="cta" href="{{ route('registro') }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en <b>Mi negocio → Personalizar menú</b>.</p>
@endsection
