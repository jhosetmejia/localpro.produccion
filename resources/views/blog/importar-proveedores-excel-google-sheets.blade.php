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

<p>Crear proveedores o clientes uno por uno quita horas. Si ya los tiene en un Excel, en el sistema anterior, en los contactos del celular o en una hoja de Google, súbalos de una vez.</p>

<h2>Cómo se hace</h2>
<ol>
    <li>Entre a <b>Proveedores</b> y toque <b>Importar Excel de proveedores</b> (para clientes: <b>Clientes → Añadir base de datos de clientes</b>).</li>
    <li>Suba su archivo: Excel (.xlsx), CSV o contactos del celular (.vcf). O <b>pegue el enlace de su hoja de Google</b>.</li>
    <li>LocalProo le muestra qué columna es el nombre o la empresa, el NIT o la cédula, el celular, el correo y la dirección. Si algo quedó mal, lo cambia ahí mismo.</li>
    <li>Toque <b>Crear proveedores</b> y listo.</li>
</ol>

<div class="box">
    <b>¿Hoja de Google?</b> En Google Sheets toque <b>Compartir</b> y elija <b>"Cualquier persona con el enlace"</b>. Luego copie el enlace y péguelo en LocalProo. No hace falta descargarla.
</div>

<h2>No importa cómo esté su archivo</h2>
<ul>
    <li>Las columnas pueden estar en cualquier orden y con cualquier nombre ("Empresa", "Razón social", "NIT", "Cel", "WhatsApp"…).</li>
    <li>Si no tiene títulos, LocalProo adivina por lo que hay escrito: correos con @, celulares de 10 dígitos, NIT con guion.</li>
    <li>Si una celda trae dos teléfonos o una extensión, toma el celular.</li>
    <li>Archivos grandes se importan por partes de 5.000.</li>
</ul>

<h2>Sin repetidos</h2>
<p>Si ya existe el NIT, el celular o el correo, no se duplica. Y si alguien ya era su cliente y ahora lo sube como proveedor, queda como las dos cosas, sin crear otra persona.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en: <b>Proveedores → Importar Excel de proveedores</b>.</p>
@endsection
