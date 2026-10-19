@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('cuerpo')
<style>
.pasos{counter-reset:p;list-style:none;padding:0}
.pasos li{counter-increment:p;position:relative;padding-left:44px;margin-bottom:12px}
.pasos li::before{content:counter(p);position:absolute;left:0;top:0;width:30px;height:30px;border-radius:50%;background:#a6f34a;color:#0b0d0c;font-weight:900;display:grid;place-items:center}
</style>

<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>Buscar el producto en una lista, escribir el precio a mano y equivocarse de talla: así se pierden minutos en cada venta y unidades en el inventario. Con el <b>código de barras</b> de LocalProo, cada prenda lleva su etiqueta y en la caja solo hay que <b>escanearla</b>.</p>
<p>Y lo mejor: <b>no necesita comprar un lector</b>. La cámara del celular o del computador hace el trabajo.</p>

@include('partials.demo-codigo-barras')

<h2>Lo que hace LocalProo con el código de barras</h2>
<ul>
    <li><b>Crea el código solo:</b> cada producto recibe un código, y cada talla y color el suyo (por ejemplo, la camiseta P0113 en talla M negra es <b>P011302</b>). Así el inventario se descuenta de la talla y el color exactos.</li>
    <li><b>Usa el código de fábrica:</b> si sus productos ya traen código de barras (EAN o UPC), escríbalo o escanéelo en la talla y color del producto y LocalProo lo reconoce.</li>
    <li><b>Imprime las etiquetas:</b> con nombre, talla, color, precio y código. Desde un producto, desde una entrada de mercancía (las etiquetas de lo que acaba de llegar) o de todo el inventario.</li>
    <li><b>Vende escaneando:</b> en la caja toca el botón de la cámara, escanea prenda tras prenda y cada una cae a la venta con su precio. Si tiene pistola lectora, también sirve.</li>
    <li><b>Cuenta el inventario escaneando:</b> en el conteo físico escanea todo lo que hay en la tienda y LocalProo le dice qué falta y qué sobra.</li>
</ul>

<h2>Cómo se imprimen las etiquetas</h2>
<ol class="pasos">
    <li>Entre a <b>Productos</b> (o a una <b>Entrada</b> de mercancía) y toque <b>Etiquetas</b>.</li>
    <li>Ponga cuántas quiere de cada talla y color. Elija qué mostrar: nombre, precio, talla y color, nombre de la tienda.</li>
    <li>Elija el papel e imprima.</li>
</ol>
<div class="tw"><table class="tabla">
    <tr><th>Papel</th><th>Para quién</th></tr>
    <tr><td><b>Rollo 50 × 25 mm</b></td><td>Si tiene impresora de etiquetas: sale una por una, lista para pegar.</td></tr>
    <tr><td><b>Hoja carta · 40 por hoja</b></td><td>Con cualquier impresora normal, en papel adhesivo o para recortar.</td></tr>
    <tr><td><b>Hoja carta · 24 por hoja</b></td><td>Etiquetas más grandes, más fáciles de leer.</td></tr>
</table></div>

<div class="box">
    <b>Consejo:</b> cuando llegue mercancía, regístrela como <b>Entrada</b> e imprima las etiquetas desde ahí. Sale exactamente una etiqueta por cada prenda que llegó, y ya quedan sumadas al inventario.
</div>

<h2>Vender con la cámara, paso a paso</h2>
<ol class="pasos">
    <li>Abra la <b>Caja</b> en el celular o el computador.</li>
    <li>Toque el botón de la <b>cámara</b> (o use su pistola lectora).</li>
    <li>Escanee las prendas una tras otra: suena un pitido y cada una aparece con su precio.</li>
    <li>Cobre en efectivo, Nequi, tarjeta o como el cliente pague, y envíe la factura por WhatsApp.</li>
</ol>

<h2>Consejos para que la etiqueta siempre se lea</h2>
<ul>
    <li>Pegue la etiqueta en una parte plana, sin arrugas ni costuras encima.</li>
    <li>Use buena luz al escanear con la cámara; si está oscuro, encienda la linterna del escáner.</li>
    <li>Imprima en negro sobre papel blanco, y no reduzca la etiqueta más de lo que trae el sistema.</li>
    <li>Si una etiqueta se daña, se vuelve a imprimir: el código de esa talla y color no cambia.</li>
</ul>

<h2>En resumen</h2>
<ul>
    <li>Código propio para cada talla y color, o el de fábrica si ya lo trae.</li>
    <li>Etiquetas en rollo o en hoja carta, con nombre, precio y código.</li>
    <li>Venta y conteo de inventario escaneando con la cámara del celular, sin comprar lector.</li>
    <li>Incluido en LocalProo, sin costo adicional.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.negocios.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis y venda escaneando</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Las etiquetas están en <b>Productos → Etiquetas</b> y el escáner en el botón de la cámara de la <b>Caja</b>.</p>
<p style="color:#6b7570">Vea también: <a href="{{ route('blog.show', 'conteo-de-inventario-con-el-celular') }}">Conteo de inventario con el celular</a>.</p>
@endsection
