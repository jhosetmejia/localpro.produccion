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

<p>Hoy un negocio vende con videos: el producto en uso, la llegada de mercancía, la promoción del día en los estados de WhatsApp, en TikTok y en los Reels de Instagram. Para eso no tiene que pagar un editor ni aguantar marcas de agua y anuncios. En LocalProo creamos <b>LocalProCut</b>, un <b>editor de videos gratis</b> que funciona en el celular o el computador y se maneja como CapCut.</p>

<div class="box">
    <b>Ábralo aquí: <a href="{{ route('cut') }}">localproo.com/cut</a></b>. Es gratis, <b>sin marca de agua y sin publicidad</b>. Sus videos <b>no se suben a ningún servidor</b>: todo se edita en su propio equipo.
</div>

<h2>Lo que puede hacer con LocalProCut</h2>
<ul>
    <li><b>Subtítulos automáticos:</b> el editor escucha el video y escribe el texto (español, inglés o portugués). Elija un estilo: TikTok, palabra resaltada, karaoke, una palabra a la vez, caja negra, neón, máquina de escribir o elegante.</li>
    <li><b>Transiciones clásicas:</b> fundido, disolver, deslizar, zoom, barrido, desenfoque, giro, círculo y destello.</li>
    <li><b>Velocidad normal y en curva:</b> más rápido o cámara lenta, y curvas como Montaje, Héroe, Bala, Salto o Pulso.</li>
    <li><b>Zoom y encuadre:</b> pellizque el video con dos dedos para acercarlo desde un momento y arrástrelo para escoger qué parte se ve.</li>
    <li><b>Audio:</b> agregue música, <b>extraiga el audio</b> de otro video, cambie el volumen o silencie el sonido original.</li>
    <li><b>Filtro de belleza natural:</b> toma su tono de piel y lo empareja sin dejarlo pálido; ojos, cejas y gafas quedan nítidos.</li>
    <li><b>Formatos:</b> vertical 9:16 (TikTok, Reels, estados), 16:9 (YouTube), cuadrado y 4:5.</li>
    <li><b>Exportar en alta calidad:</b> 1080p, 2K a 60 fps en el celular y 4K en el computador. Al terminar lo descarga o lo comparte directo a WhatsApp, TikTok o Instagram.</li>
    <li><b>Deshacer y rehacer</b>, y su trabajo queda guardado aunque cierre la página.</li>
</ul>

<h2>Cómo editar su primer video en 5 pasos</h2>
<ol>
    <li>Entre a <b>localproo.com/cut</b> y toque <b>Agregar videos o fotos</b>.</li>
    <li>Toque un clip y use <b>Editar → Dividir</b> para cortar lo que sobra.</li>
    <li>En <b>Subtítulos</b>, toque <b>Generar subtítulos automáticos</b> y elija un estilo.</li>
    <li>Ponga <b>música</b> en Audio y una <b>transición</b> entre clips con el botón que hay entre ellos.</li>
    <li>Toque <b>Exportar</b>, elija la calidad y compártalo.</li>
</ol>

<h2>Instálelo como app en su celular</h2>
<p>En el editor toque <b>Descargar app</b>. En Android se instala de una; en iPhone abra la página en Safari, toque <b>Compartir → Agregar a inicio</b>. Le queda el ícono <b>LocalProCut</b>, se abre a pantalla completa y guarda mejor su trabajo.</p>

<h2>¿Por qué es gratis?</h2>
<p>LocalProCut es parte de <b>LocalProo</b>, el sistema para que los negocios vendan, controlen su inventario y su caja y tengan su tienda en línea. Si tiene negocio, encuentra el editor en el menú <b>Editar videos · App</b> y en <b>Mis redes</b>, junto a la publicación en Facebook e Instagram.</p>

<div class="tw"><table class="tabla">
    <tr><th>Necesidad</th><th>Con LocalProCut</th></tr>
    <tr><td>Editar sin pagar</td><td>Gratis, sin marca de agua ni publicidad</td></tr>
    <tr><td>Subtítulos</td><td>Automáticos y con estilos tipo TikTok</td></tr>
    <tr><td>Videos para estados y Reels</td><td>Formato vertical 9:16 listo</td></tr>
    <tr><td>Calidad</td><td>Hasta 2K a 60 fps en el celular, 4K en el computador</td></tr>
    <tr><td>Privacidad</td><td>El video nunca sale de su equipo</td></tr>
</table></div>

<a class="cta" href="{{ route('cut') }}">Abrir LocalProCut gratis</a>
<p style="text-align:center;color:#6b7570">¿Tiene negocio? <a href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">Cree su cuenta de LocalProo</a> y lleve también sus ventas, inventario y tienda en línea.</p>
@endsection
