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

<p>Cuando el negocio crece, el dueño no puede hacerlo todo. Pero tampoco quiere entregarle su clave a un empleado: con ella vería las ganancias, podría cambiar precios a su antojo o borrar información.</p>
<p>En LocalProo cada vendedor entra con su propia clave y, por defecto, solo puede <b>vender en caja, ver productos y existencias y registrar clientes</b>. Con <b>Permisos del vendedor</b> usted le da más funciones, una por una, solo a quien usted quiera.</p>

<h2>Qué le puede activar a un vendedor</h2>
<ul>
    <li><b>Productos:</b> crear, editar (precios, fotos, datos) o eliminar productos.</li>
    <li><b>Inventario:</b> registrar entradas de mercancía y ajustar existencias.</li>
    <li><b>Categorías y proveedores:</b> crearlos y mantenerlos al día.</li>
    <li><b>Ventas y caja:</b> anular ventas, hacer devoluciones, ver los cierres de caja.</li>
    <li><b>Reportes:</b> ver ventas y ganancias (solo si usted quiere que las vea).</li>
    <li><b>Otros:</b> métodos de pago, publicar en redes sociales, facturación DIAN, nómina y contabilidad.</li>
</ul>

<h2>Cómo hacerlo</h2>
<ol>
    <li>Entre a <b>Mi negocio → Usuarios</b>.</li>
    <li>En el vendedor, toque el menú de opciones y elija <b>Permisos</b>.</li>
    <li>Encienda lo que quiera que pueda hacer (por ejemplo, <b>Crear productos</b>) y toque <b>Guardar permisos</b>.</li>
</ol>
<p>En la lista de usuarios verá cuántos permisos extra tiene cada vendedor. Si se equivoca, lo apaga y listo: el cambio aplica de inmediato.</p>

<div class="box">
    <b>Su negocio sigue siendo suyo:</b> manejar usuarios, los datos del negocio y el plan es solo del dueño. Esos permisos no se pueden dar a un vendedor.
</div>

<h2>Ejemplos reales</h2>
<ul>
    <li><b>Tienda de ropa:</b> la vendedora que recibe la mercancía registra las entradas y crea las prendas nuevas, pero no ve las ganancias.</li>
    <li><b>Ferretería:</b> el encargado de bodega ajusta existencias y maneja proveedores; los demás solo venden.</li>
    <li><b>Restaurante o cafetería:</b> el administrador de turno puede anular ventas y ver los cierres de caja.</li>
</ul>

<h2>En resumen</h2>
<ul>
    <li>Cada empleado con su clave y solo lo que usted le permita.</li>
    <li>Delegue crear productos o registrar mercancía sin mostrar sus ganancias.</li>
    <li>Quite o ponga permisos cuando quiera.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.' . ($a['rol'] ?? 'negocios') . '.tipo'), 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Búsquelo en <b>Mi negocio → Usuarios → Permisos</b>.</p>
@endsection
