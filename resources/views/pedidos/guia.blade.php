@extends('layouts.app')

@section('title', 'Cómo funcionan los pedidos')

@push('css')
<style>
.gd{max-width:900px}
.gd h2{font-size:1.3rem;font-weight:900;margin:1.6rem 0 .6rem}
.gd p,.gd li{font-size:1rem;line-height:1.6;color:#2b342f}
.gd-flujo{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.5rem;margin:1rem 0}
@media (max-width:800px){.gd-flujo{grid-template-columns:1fr}}
.gd-flujo div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem;text-align:center;position:relative}
.gd-flujo i{font-size:1.5rem;color:#16b34a;display:block;margin-bottom:.35rem}
.gd-flujo b{display:block;font-size:.95rem}
.gd-flujo small{color:#5b665e;font-weight:600;font-size:.82rem}
.gd-flujo span{position:absolute;top:-10px;left:-6px;background:#0b0d0c;color:#a6f34a;border-radius:999px;width:26px;height:26px;display:grid;place-items:center;font-weight:900;font-size:.8rem}
.gd-tip{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:16px;padding:.8rem 1rem;font-weight:600}
.gd-tabla{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;overflow:hidden}
.gd-tabla th,.gd-tabla td{padding:.6rem .8rem;border-bottom:1px solid #eef1ef;vertical-align:top;font-size:.93rem}
.gd-tabla th{background:#f6f8f7;font-weight:900}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-book-open me-1"></i>Manual</small><h1>Cómo funcionan los <span>pedidos</span></h1>
            <span class="text-muted fw-semibold">La dinámica de un restaurante (o granja, parque o cafetería) en LocalProo, paso a paso.</span></div>
    </div>
    @include('pedidos._nav')

    <div class="gd">
        <h2>El recorrido de un pedido</h2>
        <div class="gd-flujo">
            <div><span>1</span><i class="fa-solid fa-bell-concierge"></i><b>Mesero</b><small>Anota el pedido con el nombre del cliente o la mesa.</small></div>
            <div><span>2</span><i class="fa-solid fa-cash-register"></i><b>Caja</b><small>Le llega con el nombre y lo cobra en el POS.</small></div>
            <div><span>3</span><i class="fa-solid fa-fire-burner"></i><b>Cocina</b><small>Ve en su pantalla solo lo suyo y lo marca listo.</small></div>
            <div><span>4</span><i class="fa-solid fa-bell"></i><b>Aviso</b><small>Al mesero le suena y vibra: "¡Listo!". Entrega y marca.</small></div>
            <div><span>5</span><i class="fa-solid fa-scale-balanced"></i><b>Cierre</b><small>Ventas por mesero, por estación, tiempos y lo más pedido.</small></div>
        </div>
        <p>Si su negocio <b>cobra al final</b> (restaurante a la mesa), el paso 2 va al último: el pedido llega de una a cocina, el mesero puede agregar más y cuando el cliente pide la cuenta pasa a caja. Se cambia en <b>Configurar</b>.</p>

        <h2>1. Prepare su negocio (una sola vez)</h2>
        <ol>
            <li><b>Productos:</b> cree sus platos, bebidas, entradas o paseos en <b>Productos</b>, con su precio. Si no lleva control de existencias de un plato, apague "controla inventario".</li>
            <li><b>Estaciones:</b> en <b>Pedidos → Configurar</b> cree las estaciones que preparan o atienden: Cocina, Cafetería, Bar… En una granja: Entradas, Paseos con animales, Granja.</li>
            <li><b>Qué prepara cada estación:</b> en la misma pantalla diga a qué estación va cada producto (o una categoría completa). Lo que no tenga estación va a la primera.</li>
            <li><b>Roles:</b> en <b>Roles del negocio</b> cree los roles con las plantillas (Caja, Mesero, Cocina, Cafetería, Entradas, Paseos con animales, Granjero) y ajuste los permisos de cada uno.</li>
            <li><b>Personas:</b> en <b>Usuarios</b> cree a cada empleado con su correo y clave, y elija su rol. Al entrar, cada uno ve directo su pantalla.</li>
            <li><b>Pantallas:</b> en la cocina (y cada estación) deje abierta su pantalla en una tablet, celular o TV con <b>Pantalla completa</b>. Si quiere, active "Imprimir al llegar".</li>
        </ol>

        <h2>2. Qué hace cada rol</h2>
        <table class="gd-tabla">
            <tr><th>Rol</th><th>Qué ve y qué hace</th></tr>
            <tr><td><b>Mesero</b></td><td>Toca <b>Tomar pedido</b>, escribe el nombre del cliente o la mesa, toca los productos (con notas como "sin cebolla") y envía. En <b>Mis pedidos</b> ve cómo van; cuando uno queda listo le suena, lo entrega y toca <b>Marcar entregado</b>.</td></tr>
            <tr><td><b>Caja</b></td><td>En <b>Pedidos por cobrar</b> le llegan con el nombre del cliente y el valor. Toca <b>Cobrar</b>: se abre el POS con el pedido cargado; recibe el pago (efectivo, Nequi, tarjeta…) y confirma. El dinero queda en su caja y en los reportes.</td></tr>
            <tr><td><b>Cocina / Cafetería / Bar</b></td><td>Su pantalla muestra los pedidos en tarjetas, el más viejo primero, con un reloj que se pone amarillo a los 10 minutos y rojo a los 20. Toca cada producto cuando está listo o <b>Todo listo</b>.</td></tr>
            <tr><td><b>Entradas / Paseos / Granjero</b></td><td>Funcionan igual que la cocina: les llega quién compró la entrada o el paseo, lo atienden y lo marcan <b>listo</b>.</td></tr>
            <tr><td><b>Administrador</b></td><td>Ve todas las pantallas, configura estaciones y roles, y revisa el <b>Cierre</b> del día.</td></tr>
        </table>

        <h2>3. Un día normal (paga primero)</h2>
        <ol>
            <li>Llega Juan. El mesero toma el pedido: 2 almuerzos y 1 limonada, "Juan, mesa 4".</li>
            <li>En caja aparece <b>#12 Juan · Mesa 4 · $38.000</b>. Juan paga y el cajero confirma.</li>
            <li>En ese instante los almuerzos salen en la pantalla de <b>Cocina</b> y la limonada en la de <b>Cafetería</b>.</li>
            <li>Cada estación marca lo suyo. Cuando todo está listo, al mesero le suena el aviso.</li>
            <li>El mesero lleva el pedido y toca <b>Marcar entregado</b>. El pedido queda cerrado.</li>
        </ol>
        <div class="gd-tip"><i class="fa-solid fa-lightbulb me-1"></i>Para restaurantes de comida rápida o con turnos, abra la <b>pantalla para los clientes</b> (en Configurar) en un televisor: muestra "Su pedido está listo, Juan".</div>

        <h2>4. Un día normal (paga al final)</h2>
        <ol>
            <li>El mesero toma el pedido de la mesa 4 y lo envía: llega de una a cocina.</li>
            <li>Si piden algo más, el mesero abre el pedido y toca <b>Agregar productos</b>: solo lo nuevo llega a cocina, marcado como "agregado después".</li>
            <li>Al terminar, el mesero toca <b>Pedir la cuenta</b> y el pedido pasa a caja con el total.</li>
            <li>Caja cobra y el pedido queda cerrado.</li>
        </ol>

        <h2>5. Cierre del día</h2>
        <p>El dinero se cuadra como siempre en <b>Cerrar caja</b>. En <b>Pedidos → Cierre</b> el dueño ve cuánto se cobró en pedidos, qué quedó sin cobrar, cuántos se anularon, cuánto vendió cada mesero, cuánto se demoró cada estación y los platos más pedidos.</p>
        <div class="gd-tip"><i class="fa-solid fa-triangle-exclamation me-1"></i>Antes de cerrar la caja revise que no queden pedidos <b>sin cobrar</b>: cóbrelos o anúlelos.</div>

        <h2>Preguntas frecuentes</h2>
        <p><b>¿Se puede quitar un producto?</b> Sí, mientras el pedido no esté cobrado: ábralo y toque la ✕ del producto. Si ya se cobró, se anula la venta en <b>Ventas</b>.</p>
        <p><b>¿Y si la cocina marcó listo por error?</b> En su pantalla toque de nuevo el producto, o el botón de deshacer de la tarjeta.</p>
        <p><b>¿Funciona en celular?</b> Sí. Meseros y estaciones trabajan desde el celular o una tablet; no hay que instalar nada.</p>
        <p><b>¿Un empleado puede tener dos funciones?</b> Sí: cree un rol con los permisos de ambas (por ejemplo, Caja + Mesero).</p>
    </div>
</div>
@endsection
