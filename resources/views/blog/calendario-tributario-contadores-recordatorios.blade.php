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

<p>Con diez o veinte clientes, cada uno con un NIT distinto, saber qué vence esta semana es un trabajo en sí mismo. Una fecha que se pasa es una sanción por extemporaneidad y un cliente molesto.</p>
<p>Por eso la herramienta de Contabilidad de LocalProo ahora trae el <b>calendario tributario de todos tus clientes en una sola pantalla</b>, y te avisa al correo antes de que venza.</p>

<h2>Qué te muestra el calendario</h2>
<ul>
    <li><b>Retención en la fuente (Formulario 350)</b> de cada mes, para los clientes que son agentes de retención o autorretenedores.</li>
    <li><b>IVA (Formulario 300)</b> bimestral o cuatrimestral, según la periodicidad de cada cliente.</li>
    <li>La fecha exacta según el <b>último dígito del NIT</b>, con los días que faltan: "vence hoy", "vence mañana", "faltan 4 días".</li>
    <li>Los <b>vencidos sin presentar</b> de los últimos días, para que nada se quede atrás.</li>
    <li>Cuando marcas el mes como <b>Presentado en la DIAN</b>, el vencimiento queda en verde.</li>
</ul>

<h2>Te avisa al correo</h2>
<p>Recibes un correo <b>3 días antes</b> y otro <b>el mismo día</b> de cada vencimiento que no hayas marcado como presentado, con el cliente, la declaración y el periodo. Si no quieres avisos de algún cliente, los apagas en sus datos.</p>

<div class="box">
    <b>Fechas oficiales:</b> el calendario 2026 está cargado según el Decreto 1625 de 2016, modificado por el Decreto 2229 de 2023. El IVA vence el mismo día que la retención de ese mes. Cuando la DIAN publique el calendario del año siguiente, LocalProo lo agrega. Verifica siempre en el calendario oficial.
</div>

<h2>Pruébala con datos de ejemplo</h2>
<p>¿Quieres ver cómo funciona antes de subir los archivos de tus clientes? En Contabilidad toca <b>Probar con datos de ejemplo</b>. Se crea un cliente de ejemplo con el listado de la DIAN y el auxiliar contable ya cargados, y en un minuto ves:</p>
<ul>
    <li>Facturas de la DIAN sin contabilizar y una contabilizada sin factura.</li>
    <li>Una diferencia de IVA entre la factura y la contabilidad.</li>
    <li>Una retención de honorarios que faltó practicar.</li>
    <li>El borrador del 350 y el resumen del IVA del bimestre.</li>
</ul>
<p>También puedes descargar los archivos de muestra para ver el formato que espera el sistema.</p>

<h2>Retención de IVA bien calculada</h2>
<p>Ahora marcas en cada cliente si <b>practica retención de IVA</b> (gran contribuyente, entidad pública o designado por la DIAN, art. 437-2 del Estatuto Tributario) y en cada proveedor si es <b>gran contribuyente</b>. Con eso LocalProo calcula el 15 % del IVA donde corresponde y te avisa si faltó o se practicó sin que aplicara.</p>

<h2>Lo que debes saber</h2>
<ul>
    <li>LocalProo no presenta las declaraciones: te deja el borrador y las fechas. Se presentan en MUISCA con la firma electrónica del declarante.</li>
    <li>Revisa que cada cliente tenga bien su NIT (sin el dígito de verificación), si es agente de retención y su periodicidad de IVA: de eso dependen las fechas.</li>
</ul>

<a class="cta" href="{{ route('registro.contadores') }}">Crear mi cuenta de contador gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tienes LocalProo? Entra a <b>Contabilidad → Calendario</b>. ¿Eres asesor de la red? Lo tienes en <b>Red comercial → Contabilidad</b>.</p>
@endsection
