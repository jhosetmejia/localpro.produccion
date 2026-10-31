@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $a['titulo'], 'description' => $a['resumen'], 'datePublished' => $a['publicado'], 'dateModified' => $a['publicado'], 'inLanguage' => 'es-CO', 'mainEntityOfPage' => url()->current(), 'author' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')], 'publisher' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'LocalProo · App para plataforma 360', 'applicationCategory' => 'MultimediaApplication', 'operatingSystem' => 'Android, iOS (navegador)', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'COP'], 'description' => 'App para plataformas 360: graba y edita el video en el celular con cámara lenta, logo del evento y música, y guarda el WhatsApp de los invitados.', 'url' => route('registro.360')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
@section('cuerpo')
<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>La <b>plataforma 360</b> (también llamada cabina 360 o "photo booth 360") se volvió infaltable en bodas, quinces, grados y fiestas de empresa: los invitados se suben a la base, el brazo gira con el celular y salen con un video en cámara lenta para sus redes. Si usted tiene una o está pensando en montar su <b>negocio de alquiler de plataforma 360</b>, en LocalProo tiene dos cosas en un solo lugar: <b>la app que edita los videos</b> y <b>el sistema para llevar sus alquileres</b>.</p>

<h2>La app para su plataforma 360</h2>
<p>Funciona desde el navegador del celular que va en el brazo de la plataforma: no hay que instalar nada raro ni pagar por video.</p>
<ul>
    <li><b>Graba y edita al instante:</b> el video sale listo en el mismo celular, sin pasarlo a un computador.</li>
    <li><b>Efectos de velocidad:</b> 360 clásico (rápido y luego lento), Héroe, Bala, Montaje, Todo lento, Sin efecto y <b>Al ritmo de la canción</b>, que acelera donde la música sube.</li>
    <li><b>Logo del evento:</b> "Boda Ana y Luis", "Quince de Valentina" o la marca de la empresa, encima del video.</li>
    <li><b>Música:</b> elija una canción de la lista o suba la que pidió el cliente, y escoja desde qué segundo arranca.</li>
    <li><b>Texto abajo del video:</b> el nombre del evento o la fecha.</li>
    <li><b>WhatsApp de cada invitado:</b> antes de llevarse su video, el invitado deja su nombre y WhatsApp (y acepta el tratamiento de datos). Usted ve la lista por evento y la baja a Excel: es su base de clientes para el próximo evento.</li>
    <li><b>El video no sale del celular:</b> no se sube a ningún servidor, así que no gasta datos ni espera que cargue.</li>
</ul>
<div class="box">
    <b>Sus logos y canciones quedan en su cuenta.</b> Lo que suba sale en todos los celulares donde entre con su usuario de LocalProo: si tiene dos o tres plataformas, todas usan los mismos logos sin volver a subirlos.
</div>

<h2>Cómo empezar en 4 pasos</h2>
<ol>
    <li>Entre a <b>localproo.com/alquiler-360</b> y cree su cuenta gratis (nombre, celular, correo y una clave).</li>
    <li>Escriba el nombre de su negocio. En <b>¿Qué hace su negocio?</b> ya viene marcado <b>Solo alquila</b> con <b>Alquilo plataforma 360</b>.</li>
    <li>En el menú toque <b>Plataforma 360 → Abrir la app 360</b> desde el celular de la plataforma y suba el logo del evento.</li>
    <li>Grabe. Al final de la noche, en <b>Plataforma 360</b> ve quiénes grabaron, con su WhatsApp, para escribirles.</li>
</ol>
<p>¿Ya tiene LocalProo? Actívelo en <b>Datos del negocio → ¿Qué hace su negocio?</b>: elija "Solo alquila" o "Vende y alquila" y prenda <b>Alquilo plataforma 360</b>.</p>

<h2>Y además: lleve sus alquileres en orden</h2>
<p>La app es la mitad. La otra mitad es no cruzar fechas ni perder abonos:</p>
<ul>
    <li><b>Cotizaciones en PDF con su logo</b> por WhatsApp, con horas de servicio, transporte y montaje como líneas aparte.</li>
    <li><b>Disponibilidad por fecha:</b> si tiene dos plataformas y ya aceptó dos eventos el sábado, el sistema le avisa que no le queda libre ninguna.</li>
    <li><b>Abonos y saldo:</b> cada abono entra a su caja y sabe cuánto le deben de cada evento.</li>
    <li><b>Ganancia real:</b> registre lo que le paga al operador del evento y vea cuánto le quedó.</li>
</ul>

<h2>¿Cuánto cobrar por el alquiler de una plataforma 360?</h2>
<p>Depende de la ciudad, las horas y lo que incluya. Para armar su precio sume: <b>horas de servicio</b>, <b>operador</b> (lo que le paga por evento), <b>transporte y montaje</b>, <b>extras</b> (luces, fondo, props, logo personalizado) y el <b>desgaste del equipo</b>. Una forma práctica es tener paquetes de 2, 3 y 4 horas y cobrar la hora adicional aparte. Con las cotizaciones de LocalProo arma cada paquete una vez y lo reutiliza.</p>

<div class="tw"><table class="tabla">
    <tr><th>Necesidad</th><th>Con LocalProo</th></tr>
    <tr><td>Editar el video 360 al instante</td><td>App en el celular con efectos, logo y música</td></tr>
    <tr><td>Logo de cada evento en todas sus plataformas</td><td>Guardado en su cuenta</td></tr>
    <tr><td>Clientes para el próximo evento</td><td>WhatsApp de cada invitado, por evento y en Excel</td></tr>
    <tr><td>No cruzar fechas</td><td>Cotizaciones con disponibilidad por fecha</td></tr>
    <tr><td>Abonos y saldos</td><td>Alquileres con abonos que cuadran con la caja</td></tr>
</table></div>

<a class="cta" href="{{ route('registro.360', ['desde' => 'blog:' . $slug]) }}">Crear mi negocio de plataforma 360 gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Datos del negocio → ¿Qué hace su negocio? → Alquilo plataforma 360</b></p>
@endsection
