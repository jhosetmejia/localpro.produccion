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

<p>Hacer inventario en una tienda suele ser una noche entera con cuaderno y lapicero: contar prenda por prenda, sumar a mano y al final comparar con el sistema… para descubrir que no cuadra y no saber dónde está el error.</p>
<p>Con LocalProo el inventario se hace con el <b>celular</b>: se escanean las etiquetas, se toca <b>Terminé</b> y el sistema le dice qué está, qué falta y qué sobra.</p>

<h2>Primero: ¿cuánto tengo?</h2>
<p>En <b>Inventario → Resumen</b> ve de un vistazo cuántas prendas tiene:</p>
<ul>
    <li><b>Por talla:</b> cuántas S, M, L, XL… o cuántos pares del 37, 38, 39.</li>
    <li><b>Por categoría:</b> chaquetas, jeans, zapatos, accesorios. Toque una y ve sus tallas.</li>
    <li><b>Por color.</b></li>
    <li>Y cuánta plata hay en mercancía, a precio de venta y a costo.</li>
</ul>

<h2>El conteo físico, paso a paso</h2>
<ol>
    <li>Entre a <b>Inventario → Conteo físico</b> y abra un conteo de toda la tienda o solo de una categoría.</li>
    <li>Toque <b>Escanear prendas</b> y pase la cámara por cada etiqueta. Cada lectura suma una prenda: "Chaqueta Columbia · M Negro → 3". Si prefiere, escriba la cantidad.</li>
    <li>Cuando termine, toque <b>Terminé</b>.</li>
</ol>

<div class="box">
    <b>El resultado en cuatro grupos:</b> las que <b>están completas</b>, las que <b>faltan</b> (hay menos de lo que dice el sistema), las que <b>no aparecieron</b> y las que <b>sobran</b>. Toque cada grupo y ve exactamente cuáles son.
</div>

<h2>Cuadrar el sistema con un botón</h2>
<p>El dueño o administrador revisa las diferencias, ve cuánta plata representan los faltantes y toca <b>Ajustar inventario</b>. Cada corrección queda anotada en <b>Movimientos</b> con fecha y quién la hizo.</p>
<p>Si contó toda la tienda, puede indicar que lo que no apareció quede en cero. Si solo contó una parte, lo demás se deja como está.</p>

<h2>Trucos para un conteo honesto</h2>
<ul>
    <li><b>Contar a ciegas:</b> active esa opción y quien cuenta no ve lo que dice el sistema. Así no "acomoda" los números.</li>
    <li><b>Varias personas al tiempo:</b> cada una abre el mismo conteo en su celular y todo se suma en el mismo lugar.</li>
    <li><b>Planilla impresa:</b> si alguien prefiere papel, imprima la planilla con todas las tallas y colores.</li>
    <li><b>Se puede vender mientras tanto:</b> lo vendido durante el conteo se respeta al ajustar.</li>
</ul>

<h2>En resumen</h2>
<ul>
    <li>Prendas por talla, categoría y color, y el valor de su mercancía.</li>
    <li>Inventario escaneando con la cámara del celular, sin cuaderno.</li>
    <li>Qué está, qué falta y qué sobra, y el sistema cuadrado en un clic.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en el menú: <b>Inventario → Resumen y conteo</b>.</p>
@endsection
