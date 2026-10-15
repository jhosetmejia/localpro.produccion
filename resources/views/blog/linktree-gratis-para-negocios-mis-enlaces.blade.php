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

<p>Instagram y TikTok solo dejan poner <b>un enlace</b> en la biografía. Pero su negocio tiene muchos: el WhatsApp para pedidos, la ubicación, la promoción del mes, el menú, la tienda en línea… ¿Cuál pone?</p>
<p>La respuesta es <b>Mis enlaces</b>: una página con todos sus enlaces en un solo lugar, como Linktree, pero hecha para negocios y <b>incluida en LocalProo sin costo adicional</b>.</p>

<h2>Así se ve su página</h2>
<p>Su negocio queda con una dirección fácil de recordar: <b>localproo.com/@su-negocio</b>. Al abrirla, sus clientes ven:</p>
<ul>
    <li>Su <b>logo o foto</b>, el nombre y una descripción corta.</li>
    <li>Los <b>íconos de sus redes</b>: WhatsApp, Instagram, Facebook, TikTok, YouTube y más.</li>
    <li>Sus <b>botones</b>: "Pida por WhatsApp", "Cómo llegar", "Promo de la semana", "Nuestro menú"…</li>
    <li>El botón a su <b>tienda en línea</b> con todos sus productos.</li>
</ul>

<h2>Lo que la hace diferente</h2>
<div class="tw"><table class="tabla">
<tr><th>Función</th><th>Para qué le sirve</th></tr>
<tr><td><b>Botón destacado</b></td><td>Se mueve para que nadie se pierda su promoción.</td></tr>
<tr><td><b>Enlaces programados</b></td><td>Ponga fecha de inicio y fin: la promo aparece y desaparece sola.</td></tr>
<tr><td><b>8 temas de color</b></td><td>Que la página combine con su marca.</td></tr>
<tr><td><b>Código QR</b></td><td>Descárguelo e imprímalo para el mostrador, el empaque o el menú.</td></tr>
<tr><td><b>Estadísticas</b></td><td>Vea cuántas personas entraron y en qué botón hicieron clic.</td></tr>
<tr><td><b>WhatsApp fácil</b></td><td>Escriba solo su número de celular: el sistema arma el enlace de WhatsApp.</td></tr>
</table></div>

<h2>Cómo crearla en 3 minutos</h2>
<ol>
    <li>Entre a LocalProo y vaya a <b>Mi negocio → Mis enlaces</b>.</li>
    <li>Agregue sus enlaces (WhatsApp, ubicación, promociones) y sus redes.</li>
    <li>Escoja el tema y la forma de los botones, y active <b>"Página publicada"</b>.</li>
    <li>Copie su enlace y péguelo en la biografía de Instagram y TikTok, en su estado de WhatsApp o imprima el QR.</li>
</ol>

<div class="box">
    <b>Consejo:</b> ponga de primero lo que más quiere que hagan sus clientes (normalmente "Pida por WhatsApp") y destáquelo. Revise las estadísticas cada semana: el botón con menos clics quizá necesita un mejor nombre.
</div>

<h2>En resumen</h2>
<ul>
    <li>Un solo enlace para todas sus redes y canales de venta.</li>
    <li>Con QR, estadísticas y promociones que se vencen solas.</li>
    <li>Incluido en LocalProo, junto con su punto de venta, inventario y tienda en línea.</li>
</ul>

<a class="cta" href="{{ route('registro') }}">Cree su página gratis con LocalProo</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquela en <b>Mi negocio → Mis enlaces</b>.</p>
@endsection
