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

<p>Cuando el negocio crece, no todos deben ver todo: el vendedor no necesita ver las ganancias, el bodeguero no necesita la caja y la recepción no debe tocar los datos del negocio. En <b>LocalProo</b> cada persona tiene su propio usuario y un <b>rol</b> que define qué puede hacer. Y lo mejor: según su tipo de negocio, <b>los roles ya vienen creados</b>.</p>

<h2>1. Los roles que ya trae su negocio</h2>
<table style="width:100%;border-collapse:collapse;font-size:.95rem">
    <tr><th style="text-align:left;padding:.5rem;border-bottom:2px solid #e5e7eb">Tipo de negocio</th><th style="text-align:left;padding:.5rem;border-bottom:2px solid #e5e7eb">Roles listos</th></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Ropa, calzado, accesorios, tecnología</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Vendedor · Bodega e inventario · Redes y ventas en línea</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Minimercado, ferretería, papelería</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Vendedor · Bodega e inventario · Contador</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Restaurante</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Caja · Mesero · Cocina · Bar y bebidas</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Cafetería y panadería</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Caja · Mesero · Cafetería</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Belleza, peluquería, barbería</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Caja · Estilista / barbero</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Funeraria</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Recepción · Salas y obituarios · Contador</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Consultorio</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador · Recepción del consultorio · Profesional de salud</td></tr>
    <tr><td style="padding:.5rem;border-bottom:1px solid #eee">Eventos, fiestas y conciertos</td><td style="padding:.5rem;border-bottom:1px solid #eee">Administrador del evento · Contabilidad · Logística · Diseño · Puerta</td></tr>
    <tr><td style="padding:.5rem">Contador / firma contable</td><td style="padding:.5rem">Administrador · Auxiliar contable</td></tr>
</table>

<h2>2. Revise y ajuste los roles</h2>
<p>Entre a <b>Roles del negocio</b>. Cada rol muestra cuántas personas lo tienen y qué puede hacer. Toque <b>Permisos</b> para marcar o quitar lo que puede ver y hacer, y en qué pantalla arranca (por ejemplo, el vendedor directo en la caja).</p>
<x-guia-ruta tipo="moda" :ruta="['roles', 'Permisos', 'Guardar']" />
<p>¿Necesita otro rol? Use <b>Nuevo rol</b> o <b>Crear rápido desde una plantilla</b>.</p>

<h2>3. Cree el usuario de cada persona</h2>
<x-guia-ruta tipo="moda" :ruta="['usuarios', 'Nuevo usuario', 'Elegir su rol', 'Guardar']" />
<ol>
    <li>Escriba el nombre, el correo, el celular y una clave.</li>
    <li>En <b>Rol</b> elija el que le corresponde.</li>
    <li>Mándele por WhatsApp: "Entra a <b>localproo.com/login</b> con tu correo y esta clave".</li>
</ol>
<div class="box"><b>Su equipo no se registra solo.</b> Si alguien se registra por su cuenta en LocalProo, se le crea otro negocio aparte. A su equipo lo crea usted en <b>Usuarios</b>.</div>

<h2>4. Lo que solo maneja el dueño</h2>
<p>Los <b>datos del negocio</b>, los <b>usuarios</b>, los <b>roles</b>, <b>personalizar el menú</b> y <b>Mi plan</b> son siempre del dueño (o de quien tenga el rol <b>Dueño / administrador</b>). Así nadie del equipo cambia la configuración por error.</p>

<h2>5. Preguntas que nos hacen</h2>
<ul>
    <li><b>¿Si cambio un rol, se cambia para todos?</b> Sí, para todas las personas de su negocio que tengan ese rol. Nunca afecta a otro negocio.</li>
    <li><b>¿Puedo borrar un rol que no uso?</b> Sí, con la papelera. Si alguien lo tiene, primero cámbiele el rol.</li>
    <li><b>¿Y el menú?</b> También viene limpio según su tipo de negocio. Lo que no ve lo activa en <b>Personalizar menú</b>.</li>
</ul>

<div class="box"><b>¿Ya tiene cuenta?</b> En LocalPro entre a <b>Aprende</b>: arriba sale el curso de su tipo de negocio, con la lección <b>"Su equipo y sus roles"</b>.</div>

<a class="cta" href="{{ route('registro', ['desde' => 'blog:' . $slug]) }}">Cree su cuenta gratis</a>
@endsection
