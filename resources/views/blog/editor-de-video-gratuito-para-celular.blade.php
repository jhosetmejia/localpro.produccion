@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $a['titulo'], 'description' => $a['resumen'], 'datePublished' => $a['publicado'], 'dateModified' => $a['publicado'], 'inLanguage' => 'es-CO', 'mainEntityOfPage' => url()->current(), 'author' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')], 'publisher' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'LocalProCut · Editor de videos', 'applicationCategory' => 'MultimediaApplication', 'operatingSystem' => 'Android, iOS, Windows, macOS (navegador)', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'COP'], 'description' => 'Editor de videos gratis y sin marca de agua: subtítulos automáticos, transiciones, velocidad en curva, música, zoom y filtro de belleza. Funciona en el navegador y se instala como app.', 'url' => route('cut')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
@section('cuerpo')
<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>Si vende por redes, los videos son su vitrina: el producto en uso, la mercancía nueva, la promoción del día. Pero muchos editores gratis ponen marca de agua, llenan la pantalla de anuncios o cobran por exportar. <b>LocalProCut</b> es un <b>editor de video gratuito</b> para el celular que no hace nada de eso.</p>

<div class="box"><b>Ábralo gratis: <a href="{{ route('cut') }}">localproo.com/cut</a></b> · Sin marca de agua · Sin publicidad · Sus videos no se suben a ningún servidor.</div>

<h2>Qué debe tener un buen editor de video gratis</h2>
<ul>
    <li><b>Sin marca de agua:</b> que su video se vea profesional y no le haga publicidad a otra app.</li>
    <li><b>Subtítulos automáticos:</b> la mayoría ve los videos sin sonido.</li>
    <li><b>Formato vertical 9:16</b> para estados, TikTok y Reels.</li>
    <li><b>Buena calidad al exportar</b> (1080p o más) para que las redes no lo pongan borroso.</li>
    <li><b>Fácil de usar</b> desde el celular, con los dedos.</li>
</ul>
<p>LocalProCut cumple con todo eso y se maneja como CapCut: la barra de abajo trae <b>Editar, Audio, Texto, Efectos, Superposición, Subtítulos, Filtros, Ajustar, Stickers, Relación de aspecto y Fondo</b>.</p>

<h2>Edite su video en 6 pasos</h2>
<ol>
    <li><b>Agregue sus videos o fotos</b> con el botón +.</li>
    <li><b>Corte lo que sobra:</b> toque el clip, ponga la línea blanca donde quiere cortar y toque <b>Dividir</b>; o arrastre los bordes blancos del clip.</li>
    <li><b>Ordene los clips:</b> mantenga presionado uno, arrástrelo a su nuevo lugar y suéltelo.</li>
    <li><b>Subtítulos automáticos:</b> en Subtítulos toque <b>Generar</b> y escoja un estilo (TikTok, karaoke, neón…).</li>
    <li><b>Música y transiciones:</b> agregue una canción en Audio y toque el rombo ◇ entre dos clips para la transición.</li>
    <li><b>Exporte</b> en 1080p y compártalo directo a WhatsApp, TikTok o Instagram.</li>
</ol>

<h2>Trucos para que su video venda más</h2>
<ul>
    <li>Muestre el producto en los <b>primeros 2 segundos</b>: corte las entradas lentas.</li>
    <li>Ponga el <b>precio o la promoción en texto</b> grande ("¡Hoy 2x1!").</li>
    <li>Use la <b>velocidad en curva</b> para darle ritmo a los videos largos.</li>
    <li>Ponga su <b>logo</b> con Superposición y escoja una buena <b>portada</b>.</li>
    <li>Publique en sus <b>estados todos los días</b>: lo que no se ve, no se vende.</li>
</ul>

<h2>Instálelo como app</h2>
<p>En el editor toque <b>Descargar la app LocalProCut</b>. En Android se instala de una; en iPhone abra la página en Safari y toque <b>Compartir → Agregar a inicio</b>. Le queda el ícono y se abre directo al editor.</p>

<a class="cta" href="{{ route('cut') }}">Editar mi video gratis</a>
<p style="text-align:center;color:#6b7570">LocalProCut es parte de LocalProo. ¿Tiene negocio? <a href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">Cree su cuenta gratis</a> y lleve también ventas, inventario y tienda en línea.</p>
@endsection
