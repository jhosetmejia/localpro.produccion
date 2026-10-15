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

<p>Las fotos del celular de hoy pesan 3, 4 o hasta 8 MB. Si las sube así a su tienda en línea, tardan en cargar, sus clientes gastan datos y muchos se van antes de ver el producto.</p>
<p>Desde ahora <b>LocalProo optimiza las fotos solo</b>: usted sube la foto como la tomó y el sistema la deja liviana, sin que se note la diferencia.</p>

<h2>Qué hace el sistema</h2>
<ul>
    <li><b>Achica la foto antes de subirla:</b> sube mucho más rápido, también con datos del celular. Le muestra un aviso, por ejemplo <i>"Imagen optimizada: 3,7 MB → 184 KB"</i>.</li>
    <li><b>Le deja el tamaño justo:</b> grande para verse nítida en el catálogo, el celular y hasta en un televisor 4K, pero sin peso de más.</li>
    <li><b>Endereza las fotos del celular</b> que a veces quedaban acostadas.</li>
    <li><b>Respeta los logos sin fondo:</b> los PNG transparentes siguen transparentes.</li>
</ul>

<h2>¿Por qué importa?</h2>
<div class="tw"><table class="tabla">
<tr><th>Antes</th><th>Ahora</th></tr>
<tr><td>Foto de 4 MB</td><td>Foto de unos 200 KB</td></tr>
<tr><td>El catálogo tarda en abrir</td><td>Abre rápido, incluso con mala señal</td></tr>
<tr><td>El cliente gasta sus datos</td><td>Gasta 20 veces menos</td></tr>
<tr><td>Google la posiciona peor</td><td>Las páginas rápidas salen mejor en Google</td></tr>
</table></div>

<div class="box">
    <b>No tiene que hacer nada:</b> funciona en productos, logo, Mis enlaces, obituarios y en todo lugar donde suba una imagen.
</div>

<a class="cta" href="{{ route('registro') }}">Pruebe LocalProo gratis</a>
@endsection
