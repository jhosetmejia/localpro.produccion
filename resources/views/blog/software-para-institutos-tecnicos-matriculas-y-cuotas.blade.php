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

<p>En muchos institutos técnicos la inscripción se llena a mano, la matrícula se archiva en una carpeta y los pagos de las cuotas se anotan en un cuaderno. Cuando hay que saber quién debe, toca revisar papel por papel. Con LocalProo todo eso queda en un solo lugar, desde el celular o el computador.</p>

<h2>Lo que maneja el instituto</h2>
<ul>
    <li><b>Programas</b>: los técnicos laborales que ofrece, con la duración, el valor del semestre y de la inscripción.</li>
    <li><b>Inscripciones</b>: el mismo formulario de papel (datos personales, laborales, académicos y del acudiente), con su consecutivo.</li>
    <li><b>Matrícula académica y financiera</b>: programa, periodo, semestre, jornada y módulos pendientes; de contado o a crédito con cuota inicial y cuotas mensuales.</li>
    <li><b>Pagos y recibos</b>: cada pago se aplica a la cuota más antigua y sale un recibo numerado para imprimir o enviar por WhatsApp.</li>
    <li><b>Cartera y morosos</b>: quién debe, cuánto y desde hace cuántos días, con recordatorio por WhatsApp.</li>
    <li><b>Formatos impresos</b>: el formulario de inscripción y el formato de matrícula salen llenos, con el logo del instituto, las autorizaciones de imagen y de datos personales (Ley 1581 de 2012) y los espacios para firmar.</li>
</ul>

<div class="box">
    <b>Las cuotas se arman solas.</b> Si el semestre vale $1.200.000, con una cuota inicial de $200.000 y 5 cuotas, LocalProo crea las 5 cuotas de $200.000 con su fecha de pago (por ejemplo, del 1 al 5 de cada mes) y avisa cuando alguna se vence.
</div>

<a class="cta" href="{{ route('registro', ['tipo' => 'instituto', 'desde' => 'blog:' . $slug]) }}">Pruebe LocalProo gratis para su instituto</a>
@endsection
