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

<p>En una funeraria, el obituario que se ve en el televisor de la sala es lo primero que mira la familia. Muchas funerarias todavía lo hacen a mano en un programa de diseño cada vez que llega un servicio: se demora, sale distinto cada vez y a veces queda con errores.</p>
<p>Con <b>Obituarios de LocalProo</b> lo arma en un par de minutos: escribe el nombre, sube la foto, elige la sala y sale solo en la pantalla.</p>

<h2>Cómo funciona</h2>
<ol>
    <li><b>Cree sus salas de velación</b> (Sala 1, Sala San José…). Cada sala tiene su propia dirección para abrir en el televisor (Smart TV, TV Box o un computador). Se deja abierta y se actualiza sola.</li>
    <li><b>Elija una plantilla.</b> Vienen listas cinco tarjetas prediseñadas y seis diseños clásicos.</li>
    <li><b>Ponga los datos del fallecido:</b> nombre, foto, fechas completas de nacimiento y fallecimiento (salen así: 07-12-1980 / 27-03-2026), fecha y hora de las exequias, lugar, destino final y quiénes invitan.</li>
    <li>Guarde: en menos de un minuto aparece en el televisor de la sala.</li>
</ol>

<h2>Plantillas listas para usar</h2>
<div class="tw"><table class="tabla">
<tr><th>Plantilla</th><th>Cómo se ve</th></tr>
<tr><td><b>Cielo y cruz</b></td><td>Cielo con nubes, cruz dorada y lirios blancos.</td></tr>
<tr><td><b>Noche y vela</b></td><td>Fondo negro con dorado y una vela encendida.</td></tr>
<tr><td><b>Acuarela azul</b></td><td>Blanco con manchas de acuarela y hojas azules.</td></tr>
<tr><td><b>Amanecer en la montaña</b></td><td>Montañas al atardecer con una cruz y rayos de sol.</td></tr>
<tr><td><b>Botánico</b></td><td>Crema con eucalipto y la foto tipo polaroid.</td></tr>
</table></div>
<p>Además hay seis diseños clásicos: mármol, negro y dorado, cielo, floral, luz de vela y sencillo.</p>

<h2>Todo se puede ajustar</h2>
<ul>
    <li><b>Medidas de pantalla:</b> Full HD, 4K, HD, vertical (tótem) y cuadrado para WhatsApp y Facebook.</li>
    <li><b>Fondo propio:</b> un color, una imagen o hasta un <b>video</b> que se repite.</li>
    <li><b>Animaciones:</b> pétalos cayendo, destellos dorados, estrellas, rayos de luz, nubes o palomas, con la cantidad, el tamaño y la velocidad que quiera.</li>
    <li><b>Flores:</b> elija dónde van (junto a la foto o en las esquinas), muévalas y cambie su tamaño.</li>
    <li><b>Foto sin fondo:</b> si sube un PNG sin fondo, le pregunta qué poner detrás: la tarjeta, un fondo suave o un color.</li>
    <li><b>Su logo:</b> si su logo tiene fondo blanco, el sistema se lo quita con un clic.</li>
</ul>

<h2>Para la recepción y las redes</h2>
<ul>
    <li><b>Pantalla general:</b> en la entrada muestra todos los obituarios del día, uno tras otro.</li>
    <li><b>Descargue la imagen</b> en PNG o JPG (hasta 4K) o un <b>video</b> en MP4 con la animación, para WhatsApp, Facebook o una memoria USB. Usted elige cuánto dura el video: de 10 segundos a 2 minutos (30 segundos por defecto).</li>
    <li><b>Envíe el obituario por WhatsApp con la imagen:</b> en el celular se abre el menú de compartir con la imagen y los datos de las exequias; elija WhatsApp y el contacto o grupo de la familia. En el computador la imagen se descarga y WhatsApp se abre con el texto listo para adjuntarla.</li>
</ul>

<div class="box">
    <b>Consejo:</b> arme su plantilla una sola vez con su logo, sus colores y su frase. Para cada servicio solo cambia la foto y los datos: todos los obituarios salen iguales de bonitos y en minutos.
</div>

<h2>En resumen</h2>
<ul>
    <li>Obituarios para los televisores de cada sala, listos en minutos.</li>
    <li>Plantillas prediseñadas, fondos con video y animaciones.</li>
    <li>Imagen y video para compartir con la familia.</li>
    <li>Incluido en LocalProo, junto con su punto de venta, inventario y planes exequiales.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo en su funeraria</a>
<p style="text-align:center;color:#6b7570">Al registrarse elija el tipo de negocio <b>Funeraria</b> y el módulo de Obituarios se activa solo.</p>
@endsection
