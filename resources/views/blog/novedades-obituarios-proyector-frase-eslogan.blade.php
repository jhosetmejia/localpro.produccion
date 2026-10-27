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

<p>Escuchamos a las funerarias que usan <b>Obituarios de LocalProo</b> y hoy dejamos listos varios cambios para que cada obituario salga como la familia y la funeraria lo quieren. No tiene que instalar nada: ya están en su cuenta.</p>

<h2>Lo nuevo en resumen</h2>
<div class="tw"><table class="tabla">
<tr><th>Cambio</th><th>Qué significa para usted</th></tr>
<tr><td><b>Foto sin cortar la cabeza</b></td><td>Las fotos altas o grandes ahora se recortan por abajo, no por arriba.</td></tr>
<tr><td><b>Fechas opcionales</b></td><td>"Nació" y "Falleció" salen solo si elige <b>Sí, mostrar</b>.</td></tr>
<tr><td><b>Frase opcional</b></td><td>Ya no sale una frase sola. Usted decide si lleva frase y cuál.</td></tr>
<tr><td><b>Pie con su eslogan</b></td><td>Debajo del nombre de la funeraria pone una frase, su eslogan o los dos.</td></tr>
<tr><td><b>Proyector y lugar de velación</b></td><td>El proyector es la pantalla donde sale; el lugar de velación es lo que lee la gente.</td></tr>
</table></div>

<h2>1. Configure una sola vez el pie con su eslogan</h2>
<ol>
    <li>Entre a <b>Obituarios</b> y toque <b>Plantillas</b>.</li>
    <li>Abra la plantilla que usa (por ejemplo "Amanecer en la montaña") con <b>Editar</b>.</li>
    <li>Baje hasta <b>Textos y colores</b> y busque <b>Pie de la tarjeta</b>. Elija una opción:
        <ul>
            <li><b>Solo la frase</b>: por ejemplo "Un homenaje de amor".</li>
            <li><b>Solo el eslogan</b>: por ejemplo "Excelencia en servicios... Más humana".</li>
            <li><b>Frase y eslogan</b>: salen las dos, una debajo de la otra.</li>
            <li><b>Quitar el pie</b>: no sale nada abajo.</li>
        </ul>
    </li>
    <li>En <b>Nombre en el pie</b> escriba cómo quiere que aparezca su funeraria (por ejemplo "Monte de los Olivos"). Si lo deja vacío, sale el nombre de su negocio.</li>
    <li>Mire la vista previa a la derecha: cambia mientras escribe. Toque <b>Guardar</b>.</li>
</ol>
<div class="box"><b>Consejo:</b> el pie se guarda en la plantilla. Hágalo una vez y todos los obituarios que usen esa plantilla salen con su eslogan.</div>

<h2>2. Cree el obituario paso a paso</h2>
<ol>
    <li>En <b>Obituarios</b> toque <b>Nuevo obituario</b>.</li>
    <li><b>Plantilla y proyector:</b> elija la plantilla y el <b>proyector</b> (la pantalla o televisor donde se va a ver: Proyector 1, Proyector 2…).</li>
    <li><b>El fallecido:</b> escriba el nombre completo y suba la foto. Si quiere, muévala, acérquela o gírela en la vista previa.</li>
    <li><b>Fechas de nacimiento y fallecimiento:</b> viene en <b>No mostrar</b>. Si la familia las quiere, elija <b>Sí, mostrar</b> y escriba las dos fechas.</li>
    <li><b>Agregar frase:</b> viene en <b>No</b>. Si la familia quiere una frase, elija <b>Sí</b> y escríbala (por ejemplo "Siempre vivirás en nuestros corazones"). Si elige Sí, la frase es obligatoria.</li>
    <li><b>Velación y exequias:</b> escriba el <b>lugar de velación</b> (por ejemplo "Sala San José"), la fecha, la hora, el lugar de las exequias y el destino final.</li>
    <li><b>Invitan:</b> escriba un grupo por renglón, por ejemplo "Sus hijos: Ana y Pedro".</li>
    <li>Toque <b>Guardar</b>. En menos de un minuto aparece en el proyector elegido.</li>
</ol>

<h2>3. La foto</h2>
<p>Use una foto donde se vea bien la cara. Si es una foto grande o tomada de cuerpo entero, el sistema ahora deja la cabeza completa y recorta por abajo. Si quiere afinar, en el obituario tiene los controles para <b>mover</b>, <b>acercar</b> y <b>girar</b>, o puede arrastrar la foto con el dedo o el mouse en la vista previa.</p>

<h2>Preguntas rápidas</h2>
<ul>
    <li><b>Mis obituarios de antes, ¿cambiaron?</b> Las fechas ya no salen hasta que elija "Sí, mostrar". Si había escrito una frase a mano, se sigue viendo.</li>
    <li><b>¿Puedo tener una plantilla con eslogan y otra sin?</b> Sí, el pie se configura en cada plantilla.</li>
    <li><b>¿La imagen para WhatsApp y el video también cambian?</b> Sí, salen igual que en el televisor.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo en su funeraria</a>
<p style="text-align:center;color:#6b7570">¿Ya es cliente? Entre a su cuenta → <b>Obituarios</b> y pruébelo hoy mismo.</p>
@endsection
