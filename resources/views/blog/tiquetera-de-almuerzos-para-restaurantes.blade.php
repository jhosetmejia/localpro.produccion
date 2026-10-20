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


<p>En muchos restaurantes de almuerzos el cliente compra una <b>tiquetera</b>: paga, por ejemplo, 20 almuerzos por adelantado y cada día le perforan la tarjeta. El problema: la tarjeta se pierde, se moja y nadie sabe cuántos le quedan. En LocalProo la tiquetera es digital.</p>

<h2>1. Venda la tiquetera</h2>
<ol>
    <li>Abra su caja y entre al menú <b>Tiquetera → Vender tiquetera</b>.</li>
    <li>Escriba el nombre y el celular del cliente.</li>
    <li>Escriba qué incluye (por ejemplo, <b>Almuerzo ejecutivo</b>), cuántos (<b>20</b>) y el valor total. LocalProo le muestra cuánto le sale cada uno.</li>
    <li>Si quiere, póngale fecha de vencimiento. Elija cómo pagó y toque <b>Vender tiquetera</b>.</li>
</ol>
<p>El pago entra a su caja como una venta normal: el cierre de caja y los reportes quedan cuadrados.</p>

<h2>2. Cada día, marque el almuerzo</h2>
<ol>
    <li>En <b>Tiquetera</b> busque al cliente por su nombre, celular o código.</li>
    <li>Toque <b>Marcar almuerzo</b>. Listo: le queda uno menos y no se le cobra.</li>
</ol>
<p>Si vinieron varios con la misma tiquetera, en la ficha del cliente marque la cantidad de una vez.</p>

<div class="box">
    <b>Avísele cuántos le quedan.</b> En la ficha del cliente toque <b>Enviarle su saldo</b> y le llega por WhatsApp. Cuando se le acabe, ofrézcale una nueva.
</div>

<h2>Todo queda registrado</h2>
<ul>
    <li>Cada consumo guarda la fecha, la hora y quién lo marcó.</li>
    <li>Si alguien marca un almuerzo por error, lo puede quitar en los primeros 15 minutos; el dueño o el administrador, siempre.</li>
    <li>En la Tiquetera ve cuántas hay activas, cuántos almuerzos faltan por consumir y cuánto vendió en el mes.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en el menú: <b>Tiquetera</b>.</p>
@endsection
