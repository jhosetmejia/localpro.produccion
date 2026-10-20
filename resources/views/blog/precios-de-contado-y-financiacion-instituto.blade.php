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


<p>La mayoría de institutos técnicos manejan tres precios por programa: el <b>precio pleno</b>, un <b>precio de contado</b> con descuento y un <b>precio financiado</b> que se paga con una cuota inicial y varias cuotas mensuales. En LocalProo los carga una sola vez y al matricular se llenan solos.</p>

<h2>1. Cargue los precios de cada programa</h2>
<ol>
    <li>Entre a <b>Instituto → Programas</b> y abra el programa (o toque <b>Agregar un programa</b>).</li>
    <li>Escriba el <b>precio pleno</b> del semestre. Por ejemplo, $1.240.000.</li>
    <li>En el recuadro verde <b>Precio de contado y financiación</b> escriba:
        <ul>
            <li><b>Precio de contado</b>: por ejemplo, $1.050.000.</li>
            <li><b>Precio financiado</b>: por ejemplo, $1.140.000.</li>
            <li><b>Cuota inicial</b>: por ejemplo, $260.000.</li>
            <li><b>No. de cuotas</b>: por ejemplo, 4.</li>
        </ul>
    </li>
    <li>Debajo, LocalProo le muestra el descuento de cada opción frente al precio pleno (en el ejemplo, 15,32 % de contado) y el valor de cada cuota ($220.000). Toque <b>Guardar</b>.</li>
</ol>

<div class="box">
    <b>¿Recién empieza?</b> Toque <b>Cargar programas técnicos de ejemplo</b>: se crean los programas técnicos laborales más comunes con precios de ejemplo. Después solo los ajusta a su lista.
</div>

<h2>2. Matricule con el plan que elija el estudiante</h2>
<ol>
    <li>En la ficha del estudiante toque <b>Matricular</b> y elija el programa.</li>
    <li>Arriba de la matrícula financiera ve los tres precios del programa.</li>
    <li>Toque <b>Contado</b> y el valor se llena con el precio de contado. Toque <b>Crédito (cuotas)</b> y se llenan el precio financiado, la cuota inicial y el número de cuotas.</li>
    <li>Revise el <b>plan de pagos</b> con la fecha de cada cuota y toque <b>Guardar matrícula</b>.</li>
</ol>
<p>Si el estudiante tiene una beca o un descuento especial, cambie el valor en ese momento: solo aplica a esa matrícula.</p>

<h2>3. Cobre y lleve la cartera</h2>
<ul>
    <li>Las cuotas vencen el día que usted elija, por defecto el 5 de cada mes.</li>
    <li>Cada pago sale con su <b>recibo numerado</b> para imprimir o enviar por WhatsApp.</li>
    <li>En <b>Cartera y morosos</b> ve quién está atrasado y le escribe con un toque.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Instituto → Programas</b>.</p>
@endsection
