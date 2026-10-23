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

<p>Pasa en todos los negocios: el vendedor marcó 5 en vez de 3, o se vendió algo con afán y nadie lo registró. Al cerrar, la caja sale con <b>faltante</b> o <b>sobrante</b>, el inventario no coincide y los reportes de ese día quedan mal. Y lo peor: la caja ya está cerrada.</p>
<p>En LocalProo el dueño o el administrador lo arregla en un minuto, sin borrar nada y dejando registro de todo.</p>

<h2>1. Se digitaron productos de más: Corregir venta</h2>
<ol>
    <li>Entre a <b>Ventas</b> y busque la venta (por número, cliente o teléfono).</li>
    <li>Toque el botón ✏️ <b>Corregir venta</b>.</li>
    <li>Baje la cantidad a lo que realmente se vendió. Abajo ve cuánto se quita.</li>
    <li>¿Se digitó mal el <b>precio</b>? (ej. se vendió en $150.000 y quedó en $125.000) Escriba el valor correcto en <b>Precio real</b>: la venta, la ganancia y la caja suben solas y el sobrante desaparece.</li>
    <li>¿Quedó en <b>Efectivo</b> y el cliente pagó por <b>Transferencia</b> (o al revés)? Cámbielo en <b>¿Cómo pagó realmente?</b>: la caja de ese día deja de esperar esa plata en efectivo y cuadra.</li>
    <li>Si la venta se pagó con dos métodos, elija de cuál se descuenta (por defecto, el efectivo). Escriba el motivo y confirme.</li>
</ol>
<p>Los productos vuelven al inventario, la venta recalcula su total, el IVA y la ganancia, y <b>la caja de ese día</b> recalcula el total de ventas, el efectivo esperado y la diferencia. Si el error dejó un faltante, la caja queda cuadrada.</p>

<h2>2. Se vendió algo y no se registró: Registrar venta olvidada</h2>
<ol>
    <li>En <b>Ventas</b> toque <b>Registrar venta olvidada</b>.</li>
    <li>Elija el día y la caja (por ejemplo, la de ayer) y, si quiere, la hora aproximada.</li>
    <li>Busque los productos, ponga la cantidad y el precio, elija cómo pagó el cliente y escriba el motivo.</li>
</ol>
<p>La venta queda con la fecha de ese día, a nombre del vendedor de esa caja, descuenta el inventario y sale en los reportes del día correcto. Si esa plata quedó como sobrante en el cierre, la caja queda cuadrada.</p>

<div class="box">
    <b>Nada de esto mueve la plata de la caja de hoy.</b> Cada arreglo se aplica a la caja del día en que pasó. Lo que se contó al cerrar no cambia: lo que cambia es lo que debía haber.
</div>

<h2>Todo queda registrado</h2>
<ul>
    <li>Cada corrección guarda quién la hizo, cuándo, qué cambió y por qué.</li>
    <li>En Ventas la venta sale con la etiqueta <b>Corregida</b> o <b>Registrada después</b>.</li>
    <li>El informe de cierre muestra las ventas corregidas y las registradas después.</li>
    <li>En Movimientos de inventario aparece como "Corrección de venta" o "Venta".</li>
</ul>

<h2>Cuándo no se puede</h2>
<p>Solo el dueño o el administrador ven estos botones. No sirven para ventas con <b>factura electrónica o POS electrónico</b>: la DIAN exige nota crédito para corregir (use la devolución) y no permite documentos con fecha pasada. Tampoco para ventas que ya tienen devoluciones o que vienen de un separado o un alquiler.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Ventas → ✏️ Corregir venta</b> y <b>Ventas → Registrar venta olvidada</b>.</p>
@endsection
