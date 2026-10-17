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

<p>Muchos dueños de negocio saben cuánto venden, pero no cuánto gastan. Un domicilio por aquí, unas bolsas por allá, el internet, un arreglo de la vitrina… Al final del mes la plata "no alcanza" y nadie sabe bien por qué.</p>
<p>Hay otro problema más común: el vendedor saca $10.000 de la caja para pagar algo y no lo anota. Al cerrar, la caja aparece con un <b>faltante</b> que no es robo, sino un gasto que nadie registró.</p>
<p>Con <b>Egresos</b> de LocalProo cada salida de dinero queda anotada, con su recibo, y la caja cuadra sola.</p>

<h2>Cómo funciona</h2>
<ol>
    <li>Entre a <b>Egresos</b> en el menú.</li>
    <li>Escriba en qué se gastó, el valor y la fecha.</li>
    <li>Elija el tipo de gasto: arriendo, servicios, transporte y domicilios, insumos, aseo, mantenimiento, publicidad, mercancía…</li>
    <li>Diga cómo se pagó (efectivo, Nequi, transferencia) y, si quiere, tómele una foto al recibo.</li>
</ol>

<div class="box">
    <b>La caja cuadra sola:</b> si el gasto se paga en efectivo con la plata de la caja abierta, LocalProo lo descuenta del efectivo que debe haber. Al cerrar, aparece en los <b>movimientos del turno</b> y no hay faltantes misteriosos.
</div>

<h2>Quién ve qué</h2>
<ul>
    <li><b>El vendedor</b> anota los gastos que paga y ve los suyos y los de sus compañeros.</li>
    <li><b>El dueño o administrador</b> ve todos los egresos del negocio. Los que anota él quedan marcados como "Administración" y <b>los vendedores no los ven</b>: el arriendo, los pagos a empleados o lo que usted prefiera guardar.</li>
    <li>El vendedor puede borrar un egreso suyo mientras su caja siga abierta. Después, solo el administrador lo corrige.</li>
</ul>

<h2>Sepa en qué se va la plata</h2>
<p>En la misma pantalla ve el total del mes y una gráfica de <b>"En qué se va la plata"</b> por tipo de gasto. Puede filtrar por fechas, por tipo y por quién lo anotó. Con eso decide mejor: si el transporte se disparó, si conviene otro proveedor o cuánto le queda de verdad después de pagar todo.</p>

<h2>Ejemplos reales</h2>
<ul>
    <li><b>Tienda de ropa:</b> la vendedora paga el domicilio de un pedido con la caja; lo anota en un minuto y al cerrar todo cuadra.</li>
    <li><b>Minimercado:</b> el dueño registra el arriendo y los servicios como Administración; sus empleados solo ven los gastos del día a día.</li>
    <li><b>Restaurante:</b> cada compra de insumos en la plaza queda con foto del recibo para revisar a fin de mes.</li>
</ul>

<h2>En resumen</h2>
<ul>
    <li>Cada gasto anotado, con tipo, forma de pago y foto del recibo.</li>
    <li>Los pagos en efectivo con la caja se descuentan solos al cerrar.</li>
    <li>Los vendedores ven lo del equipo; usted ve todo.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en el menú: <b>Egresos</b>.</p>
@endsection
