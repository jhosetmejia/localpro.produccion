<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="description" content="LocalPRO: sistema para gestionar ventas, compras, inventario, clientes y proveedores de su negocio desde cualquier lugar." />
    <title>LocalPRO — Te ayudamos a gestionar tu negocio</title>
</head>
<body>

<header class="nav">
    <div class="wrap">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <nav class="nav-links">
            <a class="link" href="#funciones">Funciones</a>
            <a class="link" href="#como-funciona">Cómo funciona</a>
            <div class="nav-cta">
                <a class="btn btn-ghost" href="{{ route('login') }}">Ingresar</a>
                <a class="btn btn-ink" href="{{ route('registro') }}">Crear cuenta</a>
            </div>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap">
            <div>
                <span class="eyebrow rise"><i></i> POS e inventario para cualquier negocio</span>
                <h1 class="serif rise d1">Te ayudamos a <em>gestionar</em> tu negocio.</h1>
                <p class="lead rise d2">Caja, inventario con stock actualizado, clientes y su <b>propia tienda en línea que aparece en Google</b>. Para tiendas de ropa, calzado, ferreterías, restaurantes, minimercados y más. Desde el celular o el computador.</p>
                <div class="hero-actions rise d3">
                    <a class="btn btn-amber" href="{{ route('registro') }}"><i class="fa-solid fa-user-plus"></i> Crear cuenta</a>
                    <a class="btn btn-ghost" href="{{ route('login') }}"><i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar</a>
                </div>
                <p class="hero-note"><span>🎁 3 meses gratis</span><span>🌐 Página web en Google</span><span>📱 App en su celular</span></p>
            </div>

            <div class="mock rise d2" aria-hidden="true">
                <div class="chip chip-1"><span><i class="fa-solid fa-check"></i></span> Venta registrada</div>
                <div class="chip chip-2"><span><i class="fa-solid fa-boxes-stacked"></i></span> Stock al día</div>
                <div class="mock-top"><span></span><span></span><span></span></div>
                <div class="mock-grid">
                    <div class="kpi hl"><small>Ventas hoy</small><b>$1.2M</b></div>
                    <div class="kpi"><small>Productos</small><b>348</b></div>
                    <div class="kpi"><small>Clientes</small><b>126</b></div>
                </div>
                <div class="chart">
                    <div style="height:38%"></div><div style="height:55%"></div><div style="height:46%"></div>
                    <div style="height:70%"></div><div style="height:92%"></div><div style="height:64%"></div><div style="height:78%"></div>
                </div>
                <div class="mock-row"><span>Venta #0482 · Factura</span><em>+ $86.500</em></div>
                <div class="mock-row"><span>Compra a proveedor</span><em>+ 40 und.</em></div>
            </div>
        </div>
    </section>

    <div class="marquee" aria-hidden="true"><div class="marquee-track">
        <span>Caja POS</span><span>Stock actualizado</span><span>Tienda en línea</span><span>Aparece en Google</span><span>Comprar por WhatsApp</span><span>Importar desde Excel</span><span>Metas de vendedores</span><span>Reportes y ganancias</span>
        <span>Caja POS</span><span>Stock actualizado</span><span>Tienda en línea</span><span>Aparece en Google</span><span>Comprar por WhatsApp</span><span>Importar desde Excel</span><span>Metas de vendedores</span><span>Reportes y ganancias</span>
    </div></div>

    <section class="section" id="funciones">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">Todo lo que incluye</span>
                <h2 class="serif">Todo su negocio, <span class="grad-text">en un solo lugar.</span></h2>
                <p>Cada módulo está pensado para el día a día de un negocio local: rápido de usar y fácil de entender.</p>
            </div>
            <div class="features">
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-cash-register"></i></div>
                    <h3>Caja (POS) rápida</h3>
                    <p>Venda en segundos con o sin lector de código, varios medios de pago, tiquete o factura por WhatsApp y cuadre de caja exacto.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <h3>Stock actualizado al instante</h3>
                    <p>Cada venta descuenta el inventario. Tallas y colores, alertas de lo que se acaba y entradas de mercancía con su costo.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-globe"></i></div>
                    <h3>Tienda en línea en Google</h3>
                    <p>Su página web con productos por categorías, botón Comprar por WhatsApp y cada producto con su propia página para que lo encuentren en Google.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-file-excel"></i></div>
                    <h3>Importe sus productos</h3>
                    <p>¿Ya tiene 1.000 productos en Excel? Súbalo y quedan listos. O envíenos el archivo y nuestro equipo se los carga.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-ruler-combined"></i></div>
                    <h3>Vende por metro, kilo o litro</h3>
                    <p>Vidrio, telas, cables, carnes o granos: venda 1,5 m o 0,250 kg y el inventario queda exacto.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-bullseye"></i></div>
                    <h3>Vendedores con metas</h3>
                    <p>Cree vendedores para su caja: ven sus ventas y cuánto les falta para su meta, sin ver sus ganancias.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>Reportes y ganancia del día</h3>
                    <p>Sepa cuánto vendió, cuánto ganó, su hora pico y sus productos estrella. Exporte todo a Excel.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-people-group"></i></div>
                    <h3>Clientes y WhatsApp</h3>
                    <p>Guarde sus clientes con su historial y envíeles mensajes y promociones por WhatsApp.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-mobile-screen"></i></div>
                    <h3>App en su celular</h3>
                    <p>Instálela como app en el celular o úsela en el computador. Todo en la nube, sin instalar programas.</p>
                </article>
            </div>
            @php $vp = (float) \App\Models\Ajuste::valor('red_valor_plan', 220000); @endphp
            <div class="plus">
                <div class="plus-t">
                    <span class="eyebrow">El plus de LocalProo</span>
                    <h3 class="serif">Por solo ${{ number_format($vp, 0, ',', '.') }} al año: sistema completo + su página web en Google.</h3>
                    <p>Empiece con <b>3 meses gratis con todo</b> (usuarios y productos ilimitados). Si le sirve, sigue con el plan anual: menos de ${{ number_format(ceil($vp / 12 / 100) * 100, 0, ',', '.') }} al mes. <b>Páguelo en línea</b> con tarjeta, PSE o Nequi y se activa al instante.</p>
                </div>
                <a class="btn btn-amber" href="{{ route('registro') }}"><i class="fa-solid fa-gift"></i> Empezar gratis</a>
            </div>
        </div>
    </section>

    <section class="section" id="como-funciona" style="padding-top:0">
        <div class="wrap">
            <div class="steps">
                <div class="section-head">
                    <span class="eyebrow">Cómo funciona</span>
                    <h2 class="serif">Empiece en tres pasos.</h2>
                    <p>Sin instalaciones ni configuraciones difíciles.</p>
                </div>
                <div class="steps-grid">
                    <div class="step"><b>01</b><h3>Cree su cuenta</h3><p>Sus datos como dueño en menos de un minuto.</p></div>
                    <div class="step"><b>02</b><h3>Configure su tienda</h3><p>Tipo de negocio, sus primeros productos y formas de pago, en 3 minutos.</p></div>
                    <div class="step"><b>03</b><h3>Venda y comparta</h3><p>Abra su caja, venda en el POS y comparta su tienda en línea con sus clientes. Funciona en cualquier país y moneda.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="wrap"><div class="box">
            <h2 class="serif">Su negocio, <span class="grad-text">más ordenado</span> desde hoy.</h2>
            <p>Cree su cuenta gratis o ingrese si ya tiene una.</p>
            <div class="hero-actions">
                <a class="btn btn-amber" href="{{ route('registro') }}">Crear cuenta</a>
                <a class="btn btn-ink" href="{{ route('login') }}">Ingresar</a>
            </div>
        </div></div>
    </section>
</main>

<footer class="footer">
    <div class="wrap">
        <span>© {{ date('Y') }} LocalProo · Todos los derechos reservados</span>
    </div>
</footer>

</body>
</html>
