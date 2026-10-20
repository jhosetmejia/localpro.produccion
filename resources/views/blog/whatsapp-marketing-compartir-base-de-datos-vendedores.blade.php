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

<p>Su base de datos de clientes es lo más valioso de su negocio. Pero si solo usted les escribe, a la semana alcanza a pocos. Con LocalProo sus vendedores también pueden escribirles por WhatsApp, cada uno con sus contactos del día y <b>sin repetir a nadie</b>.</p>

<h2>Cómo se activa</h2>
<ol>
    <li>Entre a <b>Vender más → Contactar clientes nuevos</b>.</li>
    <li>Arriba marque la casilla <b>Compartir mi base de datos con mis asesores</b>. Se guarda sola.</li>
    <li>Listo: cada vendedor entra a la misma pantalla y le salen sus contactos del día (la cantidad la define usted en "Contactos al día por persona").</li>
</ol>

<h2>Cada vendedor elige qué mensaje enviar</h2>
<p>Si usted escribió un mensaje propio, el vendedor ve dos botones: <b>Mensaje del negocio</b> (el suyo) o <b>Mensaje normal</b> (el de LocalProo, con el enlace de su tienda en línea). El sistema recuerda lo que eligió.</p>

<div class="box">
    <b>A cada número se le escribe una sola vez.</b> Si usted ya le escribió a 20 personas, esos números no le salen a ningún vendedor. Lo que escribe un vendedor ya no le sale a nadie más, aunque el número esté cargado dos veces en la base.
</div>

<h2>Su base es suya</h2>
<p>La base de datos de su negocio solo la reciben <b>sus propios vendedores</b>. Nunca se comparte con personas de otros negocios. Si desmarca la casilla, lo que los vendedores no alcanzaron a escribir ese día vuelve a su base.</p>

<h2>Consejos</h2>
<ul>
    <li>Escriban de 9 a. m. a 7 p. m. y poco cada día: 20 mensajes diarios venden más que 500 de una vez.</li>
    <li>Si alguien pide no recibir más mensajes, márquenlo como "No le interesa" (Ley 1581 de datos personales).</li>
    <li>En "Avance del equipo hoy" usted ve cuánto lleva cada vendedor.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Vender más → Contactar clientes nuevos</b>.</p>
@endsection
