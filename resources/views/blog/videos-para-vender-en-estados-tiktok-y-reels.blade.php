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

<p>No necesita una cámara profesional ni un estudio: con el celular, buena luz y un editor gratis puede hacer videos que venden. Estas son 10 ideas que funcionan para casi cualquier negocio.</p>

<h2>Las 10 ideas</h2>
<ol>
    <li><b>El producto en uso:</b> la prenda puesta, el plato servido, la herramienta funcionando.</li>
    <li><b>"Llegó mercancía nueva":</b> abra la caja frente a la cámara.</li>
    <li><b>Antes y después:</b> un corte de cabello, una reparación, una limpieza.</li>
    <li><b>La promoción del día</b> con el precio en texto grande.</li>
    <li><b>Detrás de cámaras:</b> cómo prepara un pedido o cómo empaca.</li>
    <li><b>Responda una pregunta</b> que le hacen mucho ("¿hacen envíos?").</li>
    <li><b>Un cliente feliz</b> recibiendo su pedido (con su permiso).</li>
    <li><b>3 formas de usar</b> un mismo producto.</li>
    <li><b>Su equipo</b> presentándose: la gente le compra a personas.</li>
    <li><b>"Lo más vendido de la semana"</b> en un video rápido con transiciones.</li>
</ol>

<h2>Cómo editarlos gratis en el celular</h2>
<p>Con <b><a href="{{ route('cut') }}">LocalProCut</a></b> los arma en minutos: corte lo que sobra, ordene los clips manteniéndolos presionados, ponga <b>subtítulos automáticos</b>, música y una transición, agregue su <b>logo</b> y exporte en 9:16 para estados, TikTok y Reels. Sin marca de agua y sin publicidad.</p>

<div class="box"><b>Fórmula rápida:</b> producto en los primeros 2 segundos + texto con el precio o la promoción + subtítulos + llamado a la acción ("escríbanos por WhatsApp").</div>

<h2>Y después del video, la venta</h2>
<p>Si los videos le traen clientes, que no se le pierda ninguno: con <b>LocalProo</b> tiene su tienda en línea con botón de WhatsApp, caja, inventario y clientes, todo desde el celular.</p>

<a class="cta" href="{{ route('cut') }}">Editar mi video gratis</a>
<p style="text-align:center;color:#6b7570"><a href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">Cree su cuenta de LocalProo</a> y organice las ventas que le traen sus videos.</p>
@endsection
