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
                <span class="eyebrow rise"><i></i> Sistema de gestión para negocios</span>
                <h1 class="serif rise d1">Te ayudamos a <em>gestionar</em> tu negocio.</h1>
                <p class="lead rise d2">Ventas, compras, inventario, clientes y proveedores en un solo lugar. Sepa qué vende, qué le falta y cuánto gana, desde el computador o el celular.</p>
                <div class="hero-actions rise d3">
                    <a class="btn btn-amber" href="{{ route('registro') }}"><i class="fa-solid fa-user-plus"></i> Crear cuenta</a>
                    <a class="btn btn-ghost" href="{{ route('login') }}"><i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar</a>
                </div>
                <p class="hero-note"><span>⚡ Sin instalar nada</span><span>📱 Desde el celular</span><span>☁️ En la nube</span></p>
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
        <span>Ventas</span><span>Compras</span><span>Inventario</span><span>Clientes</span><span>Proveedores</span><span>Comprobantes</span><span>Usuarios y roles</span>
        <span>Ventas</span><span>Compras</span><span>Inventario</span><span>Clientes</span><span>Proveedores</span><span>Comprobantes</span><span>Usuarios y roles</span>
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
                    <h3>Ventas</h3>
                    <p>Registre cada venta en segundos, con el detalle de productos, cliente y forma de comprobante.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    <h3>Compras</h3>
                    <p>Lleve el control de lo que le compra a sus proveedores y actualice el inventario automáticamente.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <h3>Inventario y productos</h3>
                    <p>Stock al día, organizado por categorías, marcas y presentaciones. Sepa qué le está faltando.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-people-group"></i></div>
                    <h3>Clientes</h3>
                    <p>Tenga a la mano los datos de sus clientes y su historial de compras.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-handshake"></i></div>
                    <h3>Proveedores</h3>
                    <p>Directorio de proveedores con sus documentos y todo lo que les ha comprado.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <h3>Comprobantes</h3>
                    <p>Facturas y boletas numeradas para cada venta, listas para entregar a su cliente.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>Panel de resumen</h3>
                    <p>Vea de un vistazo sus clientes, productos, compras y ventas en un solo tablero.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-user-shield"></i></div>
                    <h3>Usuarios y roles</h3>
                    <p>Cree cuentas para sus empleados y decida qué puede ver y hacer cada uno.</p>
                </article>
                <article class="feature">
                    <div class="ico"><i class="fa-solid fa-mobile-screen"></i></div>
                    <h3>Desde cualquier lugar</h3>
                    <p>Todo en la nube: consulte su negocio desde el celular, la tablet o el computador.</p>
                </article>
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
                    <div class="step"><b>01</b><h3>Cree su cuenta</h3><p>Regístrese con su correo en menos de un minuto.</p></div>
                    <div class="step"><b>02</b><h3>Activamos su acceso</h3><p>Nuestro equipo habilita su cuenta y los módulos para su negocio.</p></div>
                    <div class="step"><b>03</b><h3>Gestione y crezca</h3><p>Cargue sus productos y empiece a registrar ventas y compras.</p></div>
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
        <span>© {{ date('Y') }} LocalPRO</span>
        <span>Desarrollado por <b>Jhoset Mejía - DiCreWeb</b></span>
    </div>
</footer>

</body>
</html>
