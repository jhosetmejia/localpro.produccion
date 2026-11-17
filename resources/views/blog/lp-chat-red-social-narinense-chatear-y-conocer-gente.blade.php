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

<p><b>LP Chat</b> (LocalProChat) es la <b>red social nariñense</b>: una app para chatear como en WhatsApp, crear grupos, subir estados, conocer gente nueva y escribirle directo a las empresas de la región. Es gratis y se instala en el celular desde <b>localproo.com/chat</b>.</p>

<h2>Así se ve: igualita a WhatsApp</h2>
<p>Abajo tiene tres botones: <b>Mensajes</b>, <b>Estados</b> y <b>Tienda</b> (el Marketplace de LocalProo). Arriba, en Mensajes, cuatro pestañas:</p>
<div class="tw"><table class="tabla">
<tr><th>Pestaña</th><th>Qué hay</th></tr>
<tr><td><b>Mis chats</b></td><td>Sus amigos: los que abrieron su link, los que le escribieron con su correo o celular y sus grupos.</td></tr>
<tr><td><b>General</b></td><td>La gente que conoció en la app y las empresas a las que les escribió. Aquí también están las <b>solicitudes</b> ocultas.</td></tr>
<tr><td><b>Conocer gente</b></td><td>Fotos de personas de LocalProo, Pasto Fight Night y Nariño ExpoShow para saludar. Hasta 5 personas nuevas al día.</td></tr>
<tr><td><b>Empresas</b></td><td>Logo, nombre y qué hace cada negocio, con botón para escribirle y ver su tienda.</td></tr>
</table></div>

<h2>Solicitudes, como en Instagram</h2>
<p>Si alguien que no conoce le escribe desde Conocer gente, el mensaje llega a <b>Solicitudes</b>: no le suena ni le llena la bandeja. Usted decide: <b>Aceptar</b>, <b>Descartar</b> (ya no le llegan sus mensajes) o <b>Bloquear</b>. La otra persona no sabe que usted lo vio hasta que acepte.</p>

<h2>Comparta su link e inicie a chatear</h2>
<p>Cada persona tiene su link (por ejemplo <b>localproo.com/chat/u/abc123</b>). Póngalo en su estado de WhatsApp o en su Instagram: quien lo abra queda en sus <b>Mis chats</b> de una vez.</p>

<h2>Fotos, videos, audios y "ver una vez"</h2>
<ul>
    <li>Envíe fotos, videos (hasta 30 MB) y <b>notas de voz</b>.</li>
    <li>Toque el <b>1</b> antes de enviar y la foto, el video o el audio se podrá ver <b>una sola vez</b>.</li>
    <li>Para que la app sea rápida, cada chat guarda los <b>últimos 7 días</b>. Los estados duran 24 horas.</li>
</ul>

<h2>Para negocios: sus clientes le escriben por LP Chat</h2>
<p>Si su negocio tiene LocalProo y su tienda en línea activa, ya aparece en <b>Empresas</b>. Entre a LP Chat con su usuario de LocalProo, toque <b>⋮ → Usar como empresa</b> y conteste como su negocio, como en WhatsApp Business. Cuando le escriban, le sale el aviso en la campanita de LocalProo.</p>

<div class="box">
    <b>Su seguridad:</b> en Conocer gente nunca se ven correos ni celulares, cada persona ve solo un grupo de perfiles al día y puede saludar a máximo 5 personas nuevas. Si alguien le molesta, toque <b>⋮ → Denunciar</b>.
</div>

<h2>Cómo instalarla</h2>
<ol>
    <li>Abra <b>localproo.com/chat</b> en el celular.</li>
    <li>Cree su cuenta con su <b>correo o su celular</b> (si ya tiene LocalProo, entra solo).</li>
    <li>En Android toque <b>⋮ → Instalar la app</b>; en iPhone, <b>Compartir → Agregar a inicio</b>.</li>
</ol>

<a class="cta" href="{{ route('lpchat.index') }}">Entrar a LP Chat gratis</a>
<p style="text-align:center;color:#6b7570">¿Tiene negocio? <a href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Créelo en LocalProo</a> y aparezca en Empresas.</p>
@endsection
