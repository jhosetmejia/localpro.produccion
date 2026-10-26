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

<p>Si usted alquila sonido, trajes, vestidos, salones, carpas, sillas, inflables o equipos, no necesita una caja de supermercado ni contar mercancía. Necesita saber <b>qué tiene alquilado, qué le queda libre para cada fecha, cuánto le abonaron, cuánto le deben y si ya se lo devolvieron</b>, y mandar cotizaciones bonitas. En LocalProo su negocio puede quedar como <b>"Solo alquila"</b> y el sistema le muestra solo eso.</p>

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

<h2>Paso 4: cree lo que alquila (con cuántos tiene)</h2>
<p>En <b>Productos → Nuevo producto</b> cree cada cosa que alquila con su precio y <b>la cantidad que tiene</b>: por ejemplo "Silla Tiffany" con 200, "Carpa 6x6" con 4 o "Cabina de sonido" con 2. Con esa cantidad LocalProo le dice cuántos le quedan libres para cada fecha.</p>

<h2>Paso 5: haga cotizaciones con su logo</h2>
<ol>
    <li>Entre a <b>Cotizaciones → Nueva cotización</b>.</li>
    <li>Escriba el cliente (si ya es cliente, le salen sus datos) y la <b>fecha del alquiler</b> (o desde y hasta, si es de varios días).</li>
    <li>Busque sus productos: el precio sale solo y lo puede cambiar. También puede agregar <b>líneas libres</b> (transporte, montaje, decoración…).</li>
    <li>En cada producto ve si le alcanza: <b>"Disponibles 150 de 200"</b> o, en rojo, <b>"Solo quedan 20"</b>.</li>
    <li>Guarde y toque <b>Descargar PDF</b> o <b>Enviar por WhatsApp</b>: al cliente le llega un enlace con la cotización con su logo, lista para descargar.</li>
</ol>
<p>Cuando el cliente diga que sí, marque <b>Aceptó</b>: lo cotizado queda <b>ocupado</b> para esas fechas y ya no le sale libre a otro cliente. Con <b>Pasar a alquiler</b> se crea el alquiler con los datos de la cotización, para recibir abonos y llevar la entrega y la devolución. Y en <b>¿Qué tengo libre?</b> elige una fecha y ve, de todo lo que tiene, cuánto está ocupado y cuánto libre.</p>

<h2>Paso 6: registre el alquiler</h2>
<ol>
    <li>Toque <b>Crear servicio en alquiler</b> (o, desde una cotización aceptada, <b>Pasar a alquiler</b>).</li>
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
<p>No tiene que crear otra cuenta ni empezar de cero. Entre a <b>Datos del negocio</b> y en <b>¿Qué hace su negocio?</b> cambie a <b>Vende y alquila</b>. Al guardar vuelven a aparecer la caja de venta, los separados y las entradas de mercancía, y sus alquileres siguen ahí. Nada se borra: con "Solo alquila" esas opciones solo quedan escondidas.</p>

<div class="tw"><table class="tabla">
    <tr><th>Opción</th><th>Qué ve en el menú</th></tr>
    <tr><td>Solo alquila</td><td>Alquileres, Cotizaciones, Productos (lo que alquila), Mi caja, Ventas, Reportes, Clientes, Egresos</td></tr>
    <tr><td>Vende y alquila</td><td>Todo lo anterior + caja de venta (POS), separados y entradas de mercancía</td></tr>
    <tr><td>Vende</td><td>Caja de venta, productos, inventario y cotizaciones (sin Alquileres)</td></tr>
</table></div>

<a class="cta" href="{{ route('registro.alquiler', ['desde' => 'blog:' . $slug]) }}">Crear mi negocio de alquiler gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Datos del negocio → ¿Qué hace su negocio?</b></p>
@endsection
