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

<p>Más de la mitad de las personas ve los videos <b>sin sonido</b>, sobre todo en estados y en Reels. Si su video no tiene subtítulos, se pierden su mensaje. Escribirlos a mano toma mucho tiempo; con <b>LocalProCut</b> se hacen solos y gratis.</p>

<div class="box"><b>Pruébelo: <a href="{{ route('cut') }}">localproo.com/cut</a></b> → pestaña <b>Subtítulos</b> → <b>Generar subtítulos automáticos</b>.</div>

<h2>Paso a paso</h2>
<ol>
    <li>Abra LocalProCut y <b>agregue su video</b>.</li>
    <li>Toque la pestaña <b>Subtítulos</b> y escoja el idioma (español, inglés o portugués).</li>
    <li>Toque <b>Generar subtítulos automáticos</b>. La primera vez descarga el modelo de voz; después es más rápido.</li>
    <li>Escoja el <b>estilo</b>: TikTok, palabra resaltada, karaoke, pop, caja, neón, máquina de escribir o elegante.</li>
    <li>Ajuste <b>tamaño, color, altura</b> y cuántas <b>palabras</b> salen a la vez (1, 2, 3, 5 o la frase completa).</li>
    <li>Toque <b>Editar textos</b> para corregir lo que no entendió y <b>exporte</b>.</li>
</ol>

<h2>Consejos para que se lean bien</h2>
<ul>
    <li><b>2 o 3 palabras</b> a la vez se leen mejor en el celular que una frase larga.</li>
    <li>Póngalos en la parte de abajo, pero <b>no tan abajo</b>: TikTok e Instagram tapan esa zona con sus botones.</li>
    <li>Use un color que contraste con su video (el verde y el amarillo se ven muy bien).</li>
    <li>Grabe con poco ruido de fondo: el editor entiende mejor la voz.</li>
</ul>

<h2>¿Y la privacidad?</h2>
<p>El video <b>no se sube a ningún servidor</b>: la inteligencia artificial que escucha la voz trabaja dentro de su propio celular o computador.</p>

<a class="cta" href="{{ route('cut') }}">Poner subtítulos gratis</a>
<p style="text-align:center;color:#6b7570">¿Tiene negocio? Con <a href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">LocalProo</a> también lleva sus ventas, inventario y tienda en línea.</p>
@endsection
