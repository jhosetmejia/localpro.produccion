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

<p>En una tienda de ropa, calzado o accesorios, el <b>separado con abonos</b> vende mucho: el cliente aparta la prenda, paga por cuotas y se la lleva al terminar. El problema es que muchos se olvidan, el plazo se vence y la prenda queda guardada en bodega sin venderse.</p>
<p>Ahora LocalProo <b>le avisa a usted y le avisa al cliente</b> antes de que el separado venza.</p>

<h2>1. La campanita de notificaciones</h2>
<p>Arriba, al lado de su nombre, está la <b>campanita de notificaciones 🔔</b> (como la de Facebook). Ahí le salen los separados que vencen en los próximos 5 días o que ya vencieron. Al tocarla ve:</p>
<ul>
    <li>El cliente, el número del separado y <b>cuánto le falta</b>.</li>
    <li>Cuándo vence: <b>"Vence en 3 días"</b>, <b>"Vence hoy"</b> o <b>"Venció hace 2 días"</b> (en rojo).</li>
    <li>Un botón de <b>WhatsApp</b> con el mensaje ya escrito para recordarle al cliente.</li>
</ul>
<p>En <b>Separados</b> también tiene la pestaña <b>"Por vencer"</b> para verlos todos.</p>

<h2>2. Correo automático al cliente</h2>
<p>Si al crear el separado escribe el <b>correo del cliente</b> (o ya lo tenía en Clientes), el sistema le envía solo:</p>
<ol>
    <li><b>3 días antes:</b> "Su separado está por vencer".</li>
    <li><b>El día que vence:</b> "Hoy vence su separado".</li>
    <li><b>Al día siguiente:</b> "Su separado venció: acérquese a pagar".</li>
</ol>
<p>El correo lleva la prenda, lo que ha abonado, <b>lo que le falta</b>, la dirección de la tienda, un botón para ver sus abonos y otro para escribirle a la tienda por WhatsApp. Si ya pagó todo, no se le envía nada.</p>

<div class="box"><b>Consejo:</b> pídale el correo al cliente cuando separe. Es el que más ayuda a que vuelva a pagar a tiempo.</div>

<h2>3. Su asesor también se entera</h2>
<p>Si su negocio tiene asesor de LocalProo, a él también le sale la campanita con los separados por vencer de sus negocios, para ayudarle a que no se le quede plata por cobrar.</p>

<h2>En resumen</h2>
<ul>
    <li>Campanita con los separados por vencer y vencidos.</li>
    <li>Correo automático al cliente 3 días antes, el día y al día siguiente.</li>
    <li>Recordatorio por WhatsApp con un toque.</li>
    <li>Menos prendas quietas en bodega y más ventas cerradas.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Toque la campanita 🔔 arriba o entre a <b>Separados → Por vencer</b>.</p>
@endsection
