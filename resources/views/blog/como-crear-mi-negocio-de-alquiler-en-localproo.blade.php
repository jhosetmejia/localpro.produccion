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

<p>Si usted alquila sonido, trajes, vestidos, salones, carpas, sillas, inflables o equipos, no necesita una caja de supermercado ni contar mercancía. Necesita saber <b>qué tiene alquilado, para qué fecha, cuánto le abonaron, cuánto le deben y si ya se lo devolvieron</b>. En LocalProo su negocio puede quedar como <b>"Solo alquila"</b> y el sistema le muestra solo eso.</p>

<h2>Paso 1: cree su cuenta</h2>
<ol>
    <li>Entre a <b>localproo.com/alquiler</b> (o en localproo.com toque <b>Crear cuenta</b>).</li>
    <li>Escriba sus datos: nombre, celular, correo y una clave. Si un asesor lo invitó, escriba su código.</li>
</ol>

<h2>Paso 2: cuéntenos de su negocio</h2>
<ol>
    <li>Escriba el nombre de su negocio.</li>
    <li>Elija el tipo de negocio que más se parece: por ejemplo <b>Moda y ropa</b> si alquila trajes y vestidos, o <b>Otro negocio</b> si alquila sonido, carpas, sillas o equipos.</li>
    <li>En <b>¿Qué hace su negocio?</b> elija <b>Solo alquila</b>.</li>
</ol>
<div class="box">
    <b>Si llegó por localproo.com/alquiler, "Solo alquila" ya viene marcado.</b> Igual lo puede cambiar ahí mismo.
</div>

<h2>Paso 3: elija cómo le pagan</h2>
<p>Como solo alquila, el sistema se salta los productos de ejemplo y pasa directo a <b>Cobro y caja</b>: marque cómo le pagan sus clientes (efectivo, Nequi, transferencia, tarjeta…) y con cuánta plata abre la caja. Listo: entra directo a <b>Alquileres</b>.</p>

<h2>Paso 4: registre su primer alquiler</h2>
<ol>
    <li>Toque <b>Nuevo alquiler</b>.</li>
    <li>Escriba <b>qué alquila</b> (ej. "Sonido para 100 personas" o "Vestido de gala talla M"), la <b>fecha y la hora</b>, el lugar y el teléfono del cliente.</li>
    <li>Ponga el <b>valor</b>. Si le dejan un abono, regístrelo ahí mismo; si paga después, elija "Paga después".</li>
    <li>Si alguien de su negocio va a montar o entregar, escriba quién es y cuánto se le paga: LocalProo le descuenta eso para mostrarle su ganancia real.</li>
</ol>

<h2>Lo que lleva LocalProo por usted</h2>
<ul>
    <li><b>Abonos:</b> cada abono entra a su caja y le queda claro cuánto falta. Cuando completa el pago, el alquiler queda como venta con su tiquete o factura.</li>
    <li><b>Entrega y devolución:</b> marque cuando sale (queda "alquilado") y cuando vuelve: en orden o con novedad (daño, faltante), con una nota.</li>
    <li><b>Documento de respaldo:</b> si lo alquilado sale sin nadie de su negocio, el sistema le pide registrar el documento que dejó el cliente antes de entregarlo.</li>
    <li><b>Filtros:</b> próximos, hoy, pendientes, abonando, alquilados (los que tiene el cliente), realizados y cancelados.</li>
    <li><b>Cuentas claras:</b> cuánto tiene por cobrar, cuánto alquiló en el mes y cuánto le quedó de ganancia.</li>
</ul>

<h2>¿Y si mañana también quiero vender?</h2>
<p>No tiene que crear otra cuenta ni empezar de cero. Entre a <b>Datos del negocio</b> y en <b>¿Qué hace su negocio?</b> cambie a <b>Vende y alquila</b>. Al guardar vuelven a aparecer la caja de venta, los productos y el inventario, y sus alquileres siguen ahí. Nada se borra: con "Solo alquila" esas opciones solo quedan escondidas.</p>

<div class="tw"><table class="tabla">
    <tr><th>Opción</th><th>Qué ve en el menú</th></tr>
    <tr><td>Solo alquila</td><td>Alquileres, Mi caja, Ventas, Reportes, Clientes, Egresos</td></tr>
    <tr><td>Vende y alquila</td><td>Todo lo anterior + caja de venta (POS), productos e inventario</td></tr>
    <tr><td>Vende</td><td>Caja de venta, productos e inventario (sin Alquileres)</td></tr>
</table></div>

<a class="cta" href="{{ route('registro.alquiler', ['desde' => 'blog:' . $slug]) }}">Crear mi negocio de alquiler gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Datos del negocio → ¿Qué hace su negocio?</b></p>
@endsection
