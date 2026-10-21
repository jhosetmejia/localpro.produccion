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

<p>Muchos programas de ventas están pensados solo para mercancía: le piden cantidad, le avisan que está "agotado" y lo obligan a inventar existencias para poder cobrar un corte de pelo o una revisión. Un <b>servicio no se acaba</b>. Por eso en <b>LocalProo</b> usted crea sus servicios una sola vez y quedan siempre disponibles para vender.</p>

<h2>¿Qué es un servicio en LocalProo?</h2>
<p>Es todo lo que usted vende sin que se gaste mercancía: un corte, un manicure, una consulta, una revisión de carro, una clase, un lavado, una asesoría o una instalación. Al crearlo elige <b>Servicio</b> en vez de Producto y listo:</p>
<ul>
    <li><b>Sin cantidad ni inventario:</b> no tiene que contar nada ni hacer entradas de mercancía.</li>
    <li><b>Nunca sale "Agotado":</b> ni en la caja, ni en pedidos, ni en su tienda en línea.</li>
    <li><b>Se administran aparte:</b> en Productos tiene la pestaña <b>Servicios</b> para ver, cambiar precios u ocultar sus servicios sin mezclarse con la mercancía.</li>
    <li><b>Ganancia real:</b> si le pone costo (por ejemplo, lo que le paga al estilista), LocalProo le calcula cuánto le queda.</li>
</ul>

<h2>Productos y servicios en la misma venta</h2>
<div class="tw"><table class="tabla">
    <tr><th>Negocio</th><th>Servicios</th><th>Productos</th></tr>
    <tr><td>Barbería y peluquería</td><td>Corte, barba, tinte, cepillado</td><td>Ceras, shampoo, tratamientos</td></tr>
    <tr><td>Taller y servicio técnico</td><td>Revisión, mano de obra, instalación</td><td>Repuestos, accesorios</td></tr>
    <tr><td>Consultorio y estética</td><td>Consulta, control, sesión</td><td>Cremas, suplementos</td></tr>
    <tr><td>Academia y entrenador</td><td>Clase, mensualidad, asesoría</td><td>Uniformes, materiales</td></tr>
</table></div>
<p>En la caja agrega los dos y cobra una sola vez: solo el producto descuenta inventario.</p>

<h2>¿Cómo se activa?</h2>
<ol>
    <li><b>Si se registra nuevo:</b> en el paso "Su negocio" marque <b>¿Ofrece servicios?</b>. En sus primeros productos podrá elegir cuáles son servicios.</li>
    <li><b>Si ya tiene LocalProo:</b> en <b>Productos</b> toque <b>Activar servicios</b> (o en Datos del negocio → Módulos encienda <b>Servicios</b>). No pierde nada de lo que ya tiene.</li>
    <li>Toque <b>Nuevo servicio</b>, escriba el nombre y el precio, y ya puede venderlo.</li>
</ol>

<a class="cta" href="{{ route('registro', array_filter(['desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Active sus servicios en <b>Productos → Activar servicios</b>.</p>
@endsection
