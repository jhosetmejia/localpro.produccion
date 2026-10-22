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

<p>En un restaurante los problemas casi siempre aparecen entre el mesero, la caja y la cocina: el papelito que se pierde, el plato que sale sin cobrar, la limonada que nadie preparó o el cliente que espera sin saber si su pedido está listo. Con <b>Pedidos de LocalProo</b> cada persona ve en su celular o tablet solo lo que le toca, y el pedido pasa solo de una a otra.</p>

<h2>Así funciona un pedido</h2>
<ol>
    <li><b>El mesero toma el pedido</b> desde el celular: nombre del cliente o número de mesa, los productos y notas como "sin cebolla" o "término medio".</li>
    <li><b>Caja lo cobra:</b> le llega con el nombre del cliente y el valor. Toca <b>Cobrar</b> y se abre la caja con el pedido cargado; recibe efectivo, Nequi, tarjeta o transferencia.</li>
    <li><b>La cocina lo prepara:</b> apenas se paga, el pedido aparece en la pantalla de la cocina. La limonada va a la pantalla de la cafetería; las entradas, a la taquilla. Cada estación ve solo lo suyo.</li>
    <li><b>Aviso de "listo":</b> cuando todo está listo, al mesero le suena y vibra el celular. Entrega y marca <b>entregado</b>.</li>
    <li><b>Cierre del día:</b> el dueño ve cuánto vendió cada mesero, cuánto se demoró cada estación, qué quedó sin cobrar y los platos más pedidos.</li>
</ol>

<h2>¿Cobra antes o al final?</h2>
<div class="tw"><table class="tabla">
    <tr><th>Paga primero</th><th>Paga al final</th></tr>
    <tr><td>Comidas rápidas, cafeterías, granjas, parques y eventos. Nada se prepara sin pagar.</td><td>Restaurante a la mesa. El pedido va de una a cocina, el mesero agrega lo que pidan y al final pide la cuenta.</td></tr>
</table></div>
<p>Se cambia con un toque en <b>Pedidos → Configurar</b>.</p>

<h2>Roles a la medida de su negocio</h2>
<p><b>Al crear su restaurante, los roles ya vienen listos:</b> Administrador, Caja, Mesero, Cocina y Bar, cada uno con sus permisos y su pantalla de inicio. No tiene que inventarlos: solo asigna a cada persona de su equipo.</p>
<p>El dueño o el administrador crea los roles de su equipo y marca qué puede hacer cada uno. Vienen plantillas listas: <b>Caja, Mesero, Cocina, Cafetería, Bar, Entradas, Paseos con animales, Granjero y Administrador</b>. Si cambia los permisos de un rol, se actualizan todas las personas que lo tienen. Cada empleado, al entrar, ve directo su pantalla.</p>

<h2>Pantallas para la cocina y para los clientes</h2>
<ul>
    <li><b>Pantalla de estación:</b> tarjetas con cada pedido, el más viejo primero, con reloj que se pone amarillo a los 10 minutos y rojo a los 20. Suena cuando llega uno nuevo y puede imprimir la comanda.</li>
    <li><b>Pantalla para los clientes:</b> en un televisor muestra "Su pedido está listo, Juan". Ideal para comidas rápidas y plazoletas.</li>
</ul>

<h2>Ejemplo: una granja con restaurante</h2>
<p>En la taquilla venden las <b>entradas</b> y los <b>paseos con animales</b>; en el restaurante, el almuerzo y los jugos. El mesero o el cajero registra todo en un solo pedido a nombre de la familia. Al pagar, a la estación <b>Entradas</b> le aparece "Familia Pérez · 4 entradas", al <b>granjero</b> "2 paseos a caballo" y a la <b>cocina</b> los almuerzos. Cada uno marca listo cuando atiende.</p>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => 'restaurante', 'desde' => 'blog:' . $slug])) }}">Pruebe LocalProo gratis</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Actívelo en <b>Datos del negocio → Módulos → Pedidos</b> y lea el manual en <b>Pedidos → Cómo funciona</b>.</p>
@endsection
