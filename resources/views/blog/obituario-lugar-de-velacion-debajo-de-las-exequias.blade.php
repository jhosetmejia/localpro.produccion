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

<p>Una funeraria cliente nos dijo: <i>"el lugar de velación debajo de la foto estorba, y el nombre sale dos veces"</i>. Tenía razón. Desde hoy los <b>obituarios de LocalProo</b> salen más limpios, y cada funeraria decide cómo los quiere. Ya está en su cuenta, no tiene que instalar nada.</p>

<h2>Lo nuevo en resumen</h2>
<div class="tw"><table class="tabla">
<tr><th>Antes</th><th>Ahora</th></tr>
<tr><td>El lugar de velación salía en una etiqueta sobre la foto.</td><td>Sale como un dato más, <b>debajo de las exequias</b>: fecha, lugar y hora, y luego el lugar de velación.</td></tr>
<tr><td>Debajo de la foto salía "En memoria de" y otra vez el nombre.</td><td>Ya no sale: el nombre va una sola vez, grande, arriba. Si lo quiere, lo activa.</td></tr>
<tr><td>No se podía quitar el lugar de velación de la tarjeta.</td><td>En la plantilla puede elegir <b>No mostrarlo</b>.</td></tr>
</table></div>

<h2>Cómo elegir dónde sale el lugar de velación</h2>
<ol>
    <li>Entre a <b>Obituarios → Plantillas</b> y toque <b>Editar</b> en la plantilla que usa.</li>
    <li>Busque <b>Dónde sale el lugar de velación</b> y elija:
        <ul>
            <li><b>Debajo de las exequias</b> (recomendado): fecha, lugar, hora y luego el lugar de velación.</li>
            <li><b>Sobre la foto (etiqueta)</b> o <b>Debajo de la foto</b>: como antes.</li>
            <li><b>No mostrarlo</b>: no sale en la tarjeta ni en el televisor.</li>
        </ul>
    </li>
    <li>Si quiere moverlo un poco, use las barras <b>← →</b> y <b>↑ ↓</b> o arrástrelo en la vista previa. Nunca se sale del borde.</li>
    <li>Toque <b>Guardar plantilla</b>.</li>
</ol>

<h2>Cómo mostrar u ocultar "En memoria de"</h2>
<ol>
    <li>En la misma plantilla, busque el interruptor <b>Mostrar "En memoria de" + nombre debajo de la foto</b>.</li>
    <li>Apagado (como viene): el nombre sale solo arriba. Encendido: sale también debajo de la foto.</li>
</ol>
<div class="box"><b>Consejo:</b> con la casilla <b>Usar el mismo logo, título y pie en todas mis plantillas</b>, estos dos ajustes se copian a todas sus plantillas de una vez.</div>

<h2>Y en el obituario</h2>
<p>Todo sigue igual: en <b>Velación y exequias</b> escriba el <b>Nombre del campo</b> (viene "Lugar de velación") y <b>Qué dice</b>, por ejemplo "Sala 1 · Monte de los Olivos". Si lo deja vacío, no sale. Recuerde guardar: el televisor de la sala solo muestra lo guardado.</p>

<h2>Preguntas rápidas</h2>
<ul>
    <li><b>¿Mis obituarios de antes cambiaron?</b> Sí, quedaron con el lugar de velación debajo de las exequias y sin "En memoria de".</li>
    <li><b>¿La imagen para WhatsApp y el video también?</b> Sí, salen igual que en el televisor.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo en su funeraria</a>
<p style="text-align:center;color:#6b7570">¿Ya es cliente? Entre a su cuenta → <b>Obituarios → Plantillas</b> y ajústelo hoy mismo.</p>
@endsection
