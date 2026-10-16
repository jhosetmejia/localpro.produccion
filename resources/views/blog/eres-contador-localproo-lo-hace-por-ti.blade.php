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

<p>Si eres contador o analista contable, conoces bien el cierre de cada mes: descargar las facturas de la DIAN, sacar el auxiliar de Contapyme, pegar todo en Excel, buscar NIT por NIT, revisar si el IVA cuadra, si a cada proveedor le retuvieron lo que era y, al final, armar la declaración de retenciones. Y repetirlo con cada cliente.</p>
<p><b>Ese trabajo repetitivo ya lo puede hacer LocalProo por ti.</b> Tú te quedas con lo que de verdad necesita tu criterio: revisar, corregir y firmar.</p>

<h2>¿Te pasa alguna de estas?</h2>
<ul>
    <li>Te llega una factura a la DIAN que nadie contabilizó y la descubres meses después.</li>
    <li>El IVA descontable de la contabilidad no coincide con el de las facturas y no sabes cuál es.</li>
    <li>A un proveedor no le practicaron retención, o le retuvieron con la tarifa que no era.</li>
    <li>Cambió la norma (como el Decreto 572 y su suspensión) y no sabes con qué tabla calcular cada mes.</li>
    <li>Tienes diez clientes y el cierre te consume la semana entera.</li>
</ul>
<p>Si dijiste que sí a una, esta herramienta es para ti.</p>
<a class="cta" href="{{ route('registro', ['tipo' => 'contable', 'desde' => 'blog:' . $slug]) }}">Crear mi cuenta de contador gratis</a>

<h2>Qué hace LocalProo por ti</h2>
<div class="tw"><table class="tabla">
<tr><th>Antes, a mano</th><th>Ahora, con LocalProo</th></tr>
<tr><td>Comparar factura por factura en Excel</td><td>Subes dos archivos y el cruce sale solo, por NIT y número de factura.</td></tr>
<tr><td>Buscar qué facturas de la DIAN faltan en la contabilidad</td><td>Te las lista: <b>sin contabilizar</b> y <b>contabilizadas sin factura</b>.</td></tr>
<tr><td>Revisar si el IVA cuadra</td><td>Te marca cada factura donde el IVA o la base son distintos.</td></tr>
<tr><td>Calcular la retención que debía practicarse</td><td>La calcula con la tabla vigente en la fecha de la factura (concepto, base en UVT, declarante o no) y te dice si faltó o sobró.</td></tr>
<tr><td>Armar el 350 sumando a mano</td><td>Te deja el <b>borrador del Formulario 350</b> por concepto, con autorretenciones y reteIVA.</td></tr>
<tr><td>Calcular el IVA del periodo</td><td>Generado menos descontable, por mes y por bimestre o cuatrimestre.</td></tr>
<tr><td>Hacer los certificados de retención uno por uno</td><td>Salen listos para imprimir o guardar en PDF.</td></tr>
</table></div>

<h2>Cómo se usa, paso a paso</h2>
<ol>
    <li><b>Crea tu cuenta</b> y elige el tipo de negocio <b>Contador / analista contable</b>.</li>
    <li><b>Registra a tu cliente:</b> nombre, NIT, si es persona natural o jurídica, si es agente de retención o autorretenedor, y si declara IVA bimestral o cuatrimestral.</li>
    <li><b>Abre el mes</b> que vas a revisar.</li>
    <li><b>Sube el listado de la DIAN:</b> el Excel que descargas del portal de facturación electrónica con las facturas emitidas y recibidas.</li>
    <li><b>Sube tu contabilidad:</b> el Excel o CSV de Contapyme, Siigo, World Office o el programa que uses, en columnas o como auxiliar por cuenta. La primera vez eliges qué columna es cada dato y desde ahí lo recuerda.</li>
    <li><b>Revisa el cruce:</b> filtra por compras, ventas o "solo lo que hay que revisar", corrige el concepto de un proveedor con un clic y el cálculo se actualiza.</li>
    <li><b>Descarga:</b> el borrador del 350, el resumen del IVA, el Excel de papel de trabajo y los certificados.</li>
</ol>
<p>Dentro del sistema tienes una <b>guía de uso</b> con este paso a paso, qué significa cada alerta y las normas con sus fuentes oficiales.</p>

<div class="box">
    <b>Con la norma al día:</b> trae la UVT de 2024, 2025 y 2026 (52.374) y las bases y tarifas vigentes desde el 8 de mayo de 2026, cuando el Consejo de Estado suspendió el Decreto 572 de 2025. Cada tarifa tiene fecha de vigencia y la puedes ajustar tú mismo, así cada factura se calcula con la regla que aplicaba en su fecha.
</div>

<h2>Todos tus clientes en un solo lugar</h2>
<p>Cada cliente guarda sus meses, sus proveedores y el concepto habitual de cada uno. El mes siguiente ya no empiezas de cero: LocalProo recuerda quién es declarante, quién se autorretiene y qué concepto le aplica a cada proveedor. Marcas cada mes como <b>abierto, revisado o presentado</b> y sabes en qué va cada cliente.</p>

<h2>Lo que debes saber</h2>
<ul>
    <li>LocalProo <b>no presenta la declaración</b> por ti. Te deja el borrador listo y la presentas en el portal MUISCA de la DIAN con tu firma electrónica.</li>
    <li>Es una herramienta de apoyo: la revisión final y la responsabilidad profesional siguen siendo del contador.</li>
    <li>Tus archivos y los de tus clientes quedan en tu cuenta y nadie más los ve.</li>
</ul>

<h2>¿Eres asesor de la red LocalPro?</h2>
<p>También la tienes en tu panel, en <b>Red comercial → Contabilidad</b>, para llevar los clientes de un analista contable con tu propio espacio, sin crear un negocio aparte.</p>

<h2>Regístrate aquí mismo</h2>
<p>Con este botón tu cuenta queda creada como <b>contador</b> y la herramienta de Contabilidad sale activa desde el primer momento. Solo pones tus datos, el nombre de tu oficina y empiezas a registrar clientes.</p>
<a class="cta" href="{{ route('registro', ['tipo' => 'contable', 'desde' => 'blog:' . $slug]) }}">Crear mi cuenta de contador gratis</a>
<p style="text-align:center;color:#6b7570">Enlace directo para compartir: <b>{{ url('/contadores') }}</b><br>¿Ya tienes LocalProo? Actívala en <b>Datos del negocio → Módulos</b>.</p>
@endsection
