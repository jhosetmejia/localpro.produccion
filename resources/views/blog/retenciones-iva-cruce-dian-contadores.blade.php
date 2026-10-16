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

<p>Cada mes el analista contable hace lo mismo para cada cliente: descarga de la DIAN las facturas del mes, saca la base de su programa contable, compara factura por factura el IVA y las retenciones, y con eso arma la declaración de retenciones. Con varios clientes, son días de trabajo en Excel.</p>
<p>Con <b>Contabilidad de LocalProo</b> ese cruce se hace en minutos.</p>

<h2>Cómo funciona</h2>
<ol>
    <li><b>Registre sus clientes:</b> personas naturales y empresas, con su NIT, si son agentes de retención, si se autorretienen y su periodicidad de IVA.</li>
    <li><b>Abra el mes</b> y suba dos archivos: el <b>listado de facturas de la DIAN</b> (emitidas y recibidas) y la <b>base de su programa contable</b> (Contapyme, Siigo, World Office…) en Excel o CSV.</li>
    <li>La primera vez le pregunta qué columna es cada dato. Después lo recuerda.</li>
    <li>LocalProo <b>cruza</b> factura por factura y le muestra lo que hay que revisar.</li>
</ol>

<h2>Lo que encuentra por usted</h2>
<div class="tw"><table class="tabla">
<tr><th>Revisión</th><th>Para qué sirve</th></tr>
<tr><td><b>Facturas en la DIAN sin contabilizar</b></td><td>Que no se le quede ningún costo ni IVA descontable por fuera.</td></tr>
<tr><td><b>Contabilizadas sin factura electrónica</b></td><td>Detectar gastos sin soporte antes de que los rechacen.</td></tr>
<tr><td><b>IVA que no coincide</b></td><td>Corregir diferencias entre la factura y la contabilidad.</td></tr>
<tr><td><b>Retenciones que faltaron o sobraron</b></td><td>Compara lo retenido con la tabla vigente según el concepto, la base en UVT y si el proveedor es declarante.</td></tr>
</table></div>

<h2>Lo que le deja listo</h2>
<ul>
    <li><b>Borrador del Formulario 350:</b> base y retención por concepto (compras, servicios, honorarios, arrendamientos…), separado en personas jurídicas y naturales, con autorretenciones y retención de IVA.</li>
    <li><b>Borrador del IVA:</b> generado menos descontable, por mes y sumado por bimestre o cuatrimestre.</li>
    <li><b>Excel de papel de trabajo</b> con todas las facturas y las diferencias.</li>
    <li><b>Certificados de retención</b> para los proveedores, listos para imprimir o guardar en PDF.</li>
</ul>

<div class="box">
    <b>Tabla de retenciones al día:</b> las bases y tarifas tienen fecha de vigencia y usted las puede ajustar. Así se maneja, por ejemplo, la suspensión del Decreto 572 de 2025 desde el 8 de mayo de 2026: cada factura se calcula con la tabla que regía en su fecha. Trae la UVT de 2026 (2.374).
</div>

<h2>Importante</h2>
<p>LocalProo es una herramienta de apoyo para el contador: cruza, calcula y deja el borrador. La declaración se presenta en el portal de la DIAN con la firma electrónica del declarante, y la revisión final es del contador público.</p>

<a class="cta" href="{{ route('registro') }}">Pruébelo gratis con sus clientes</a>
<p style="text-align:center;color:#6b7570">Al registrarse elija el tipo de negocio <b>Contador / analista contable</b>. Si ya tiene LocalProo, actívelo en <b>Datos del negocio → Módulos</b>. ¿Es asesor de la red LocalPro? Ya la tiene en su panel, en <b>Red comercial → Contabilidad</b>, para llevar los clientes de un analista contable.</p>
@endsection
