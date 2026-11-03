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

<p>En muchos consultorios odontológicos el odontograma todavía se pinta a mano, el presupuesto se hace en una hoja aparte y los abonos de cada paciente se anotan en un cuaderno. Al final del mes nadie sabe con certeza quién debe cuánto.</p>
<p>Al registrarse en LocalProo como <b>Consultorio odontológico</b>, su sistema queda armado para una clínica dental: agenda, pacientes, historia clínica dental, <b>odontograma</b>, <b>planes de tratamiento con abonos</b>, consentimientos firmados en la pantalla y radiografías. Lo que una clínica dental no usa (alquileres, recetas, eventos…) no le aparece.</p>

<h2>Odontograma gráfico, por caras y con versiones</h2>
<ul>
    <li>Las 32 piezas permanentes, las 20 temporales o la dentición mixta, con la numeración FDI.</li>
    <li>Toque la herramienta y luego la cara: <b>vestibular, lingual o palatina, mesial, distal u oclusal</b>.</li>
    <li>Colores de siempre: <b>rojo</b> para lo que hay que tratar (caries, fractura, extracción, endodoncia o corona indicada) y <b>azul</b> para lo que ya está hecho (resinas, amalgamas, coronas, endodoncias, ausentes, implantes).</li>
    <li>Cada vez que guarda queda una <b>versión nueva</b> con fecha y profesional: puede ver cómo estaba la boca en cada control.</li>
    <li>Notas por pieza ("sensibilidad al frío") y observaciones generales.</li>
</ul>

<h2>El presupuesto sale solo del odontograma</h2>
<p>Con un toque en <b>Pasar al plan de tratamiento</b>, LocalProo arma el plan con lo que quedó en rojo: resina en la 16 por dos superficies, exodoncia de la 38, endodoncia de la 26… con los <b>precios de sus servicios</b>. Usted revisa, ajusta valores o descuentos e imprime el presupuesto con su logo y su firma, o se lo manda al paciente por WhatsApp.</p>

<div class="box">
    <b>Saldos claros:</b> cada plan muestra el total, lo abonado y lo que falta. La recepción recibe los abonos en la caja (efectivo, Nequi, tarjeta…), salen en el cierre de caja y el paciente recibe su recibo impreso o por WhatsApp. En <b>Tratamientos y saldos</b> ve cuánto le deben en total.
</div>

<h2>Consentimiento informado firmado en la pantalla</h2>
<p>Trae plantillas listas: atención general, <b>exodoncia</b>, <b>endodoncia</b>, cirugía e <b>implantes</b>, <b>blanqueamiento</b>, <b>ortodoncia</b>, anestesia local y la del acudiente para menores de edad. El texto sale con el nombre y el documento del paciente; usted lo puede ajustar y el paciente firma con el dedo en el celular o la tableta. Queda guardado con fecha, hora y una huella que muestra si alguien lo alteró.</p>

<h2>Radiografías y fotos en la historia</h2>
<p>Suba periapicales, panorámicas y fotos de antes y después desde el celular o el computador. Se guardan en privado: no quedan públicas en internet y solo las ve el odontólogo.</p>

<h2>Su equipo, cada uno con lo suyo</h2>
<ul>
    <li><b>Odontólogo(a):</b> agenda, historia clínica, odontograma, planes y consentimientos.</li>
    <li><b>Recepción y auxiliar de odontología:</b> agenda, pacientes, abonos y caja; no ven la parte clínica.</li>
    <li>El dueño ve quién abrió, escribió o imprimió cada historia.</li>
</ul>

<h2>Y lo de siempre de LocalProo</h2>
<p>Caja para cobrar la valoración o la limpieza, gastos, nómina, reportes, facturación electrónica, <b>Mis enlaces</b> (con el diseño "Sonrisa") y su página de <b>servicios en línea</b> para que los pacientes lo encuentren y le escriban por WhatsApp.</p>

<h2>En resumen</h2>
<ul>
    <li>Odontograma digital por caras, en rojo y azul, con versiones por fecha.</li>
    <li>Plan de tratamiento que se arma solo, con presupuesto imprimible y por WhatsApp.</li>
    <li>Abonos a la caja, saldo por paciente y recibos.</li>
    <li>Consentimientos firmados en la pantalla y radiografías en la historia.</li>
</ul>
<p style="color:#6b7570;font-size:.9rem">Pensado para consulta particular. La facturación a EPS (RIPS) viene en una siguiente fase.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis en su consultorio odontológico</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo como consultorio? En <b>Datos del negocio</b> cambie el tipo a <b>Consultorio odontológico</b>.</p>
@endsection
