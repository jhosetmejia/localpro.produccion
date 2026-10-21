@extends('layouts.app')

@section('title', 'Capacitación de asesores')

@push('css')
<style>
.lpx{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--k:#0b0d0c;--ink:#151a17;--mut:#5b665e;--line:#e3efe5;--bg:#f4f8f5;--lime:#a6f34a;--gold:#ffcc33;--pink:#ff5d8f}
.lpx, .lpx *{box-sizing:border-box}
.lpx{margin:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;line-height:1.5}
.lpx .w{max-width:980px;margin:0 auto;padding:0 16px 40px}
.lpx .top{display:flex;align-items:center;justify-content:space-between;padding:16px 0;gap:10px}
.lpx .brand{display:flex;align-items:center;gap:.5rem;font-weight:800;text-decoration:none;color:inherit;font-size:1.1rem}
.lpx .brand b{width:32px;height:32px;border-radius:10px;background:var(--k);color:var(--lime);display:grid;place-items:center}
.lpx .btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;border:0;border-radius:999px;padding:.7rem 1.1rem;font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:.95rem}
.lpx .btn-k{background:var(--k);color:#fff}
.lpx .btn-g{background:var(--g);color:var(--k)}
.lpx .btn-w{background:#25d366;color:#fff}
.lpx .btn-l{background:#fff;border:1.5px solid var(--line);color:var(--ink)}
.lpx .hero{background:var(--k);color:#fff;border-radius:32px;padding:34px 24px;position:relative;overflow:hidden;margin-bottom:18px}
.lpx .hero::after{content:"";position:absolute;right:-70px;top:-70px;width:260px;height:260px;border-radius:50%;background:var(--g);opacity:.35;filter:blur(8px)}
.lpx .hero small{color:var(--lime);font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.75rem}
.lpx .hero h1{font-size:clamp(1.9rem,6vw,3rem);line-height:1.05;margin:.4rem 0 .7rem;letter-spacing:-.03em;position:relative;z-index:1}
.lpx .hero h1 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
.lpx .hero p{color:#cfd8d2;margin:0 0 14px;position:relative;z-index:1;max-width:640px}
.lpx .indice{display:flex;flex-wrap:wrap;gap:8px;position:relative;z-index:1}
.lpx .indice a{background:#1a201c;color:#fff;border-radius:999px;padding:.4rem .8rem;font-weight:700;font-size:.82rem;text-decoration:none}
.lpx .card{background:#fff;border:1.5px solid var(--line);border-radius:26px;padding:22px;margin-bottom:16px}
.lpx .card h2{margin:0 0 6px;font-size:1.4rem;letter-spacing:-.02em;display:flex;align-items:center;gap:.6rem}
.lpx .card h2 .n{width:34px;height:34px;border-radius:12px;background:var(--g);color:var(--k);display:grid;place-items:center;font-size:1rem;flex:none}
.lpx .card .sub{color:var(--mut);font-weight:600;margin:0 0 16px}
.lpx .meta{background:var(--g);border-radius:26px;padding:22px;color:var(--k);margin-bottom:16px}
.lpx .meta h2{margin:0 0 4px;font-size:clamp(1.4rem,4.5vw,2rem);letter-spacing:-.03em;line-height:1.1}
.lpx .meta p{margin:0 0 14px;font-weight:600}
.lpx .calc{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}
.lpx .calc div{background:rgba(255,255,255,.55);border-radius:18px;padding:12px}
.lpx .calc small{font-weight:800;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;opacity:.75}
.lpx .calc b{display:block;font-size:1.5rem;letter-spacing:-.03em}
.lpx .sl label{display:flex;justify-content:space-between;font-weight:800;margin-bottom:4px}
.lpx .sl input{width:100%;accent-color:#0b0d0c;height:28px}
.lpx .ej{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.lpx .ej div{background:#fff;border-radius:18px;padding:14px;text-align:center}
.lpx .ej b{display:block;font-size:1.25rem}
.lpx .ej small{color:var(--mut);font-weight:700}
.lpx .pasos{counter-reset:p;list-style:none;margin:0;padding:0}
.lpx .pasos li{position:relative;padding:12px 0 12px 54px;border-bottom:1px dashed var(--line)}
.lpx .pasos li::before{counter-increment:p;content:counter(p);position:absolute;left:0;top:12px;width:38px;height:38px;border-radius:13px;background:var(--k);color:var(--lime);display:grid;place-items:center;font-weight:800}
.lpx .pasos b{display:block}
.lpx .pasos span{color:var(--mut);font-weight:500}
.lpx .guion{background:var(--bg);border-radius:18px;padding:14px 16px;margin-bottom:10px;white-space:pre-line;font-weight:500;position:relative}
.lpx .guion .t{font-weight:800;display:block;margin-bottom:4px;color:#11702f}
.lpx .guion button{margin-top:10px}
.lpx details.obj{border:1.5px solid var(--line);border-radius:16px;padding:12px 14px;margin-bottom:8px}
.lpx details.obj summary{font-weight:800;cursor:pointer}
.lpx details.obj p{margin:.5rem 0 0;color:var(--mut);font-weight:500}
.lpx .check label{display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px dashed var(--line);font-weight:600;cursor:pointer}
.lpx .check input{width:20px;height:20px;accent-color:#16b34a;flex:none;margin-top:2px}
.lpx .tabla{width:100%;border-collapse:collapse}
.lpx .tabla td,.lpx .tabla th{padding:9px 6px;border-bottom:1px solid var(--line);text-align:left}
.lpx .tabla td:last-child,.lpx .tabla th:last-child{text-align:right}
.lpx .tabla th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--mut)}
.lpx .regla{background:#fff5d6;color:#8a6400;border-radius:16px;padding:12px 14px;font-weight:700;margin-top:10px}
.lpx .dos{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.lpx .mente{background:var(--k);color:#fff;border-radius:26px;padding:26px 22px;margin-bottom:16px;position:relative;overflow:hidden}
.lpx .mente::after{content:"";position:absolute;left:-80px;bottom:-80px;width:240px;height:240px;border-radius:50%;background:var(--g);opacity:.22;filter:blur(10px)}
.lpx .mente>*{position:relative;z-index:1}
.lpx .mente small.k{color:var(--lime);font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.75rem}
.lpx .mente h2{font-size:clamp(1.6rem,5vw,2.3rem);line-height:1.08;letter-spacing:-.03em;margin:.35rem 0 .6rem}
.lpx .mente h2 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
.lpx .mente p{color:#cfd8d2;font-weight:500;margin:0 0 14px;max-width:700px}
.lpx .mente p b{color:#fff}
.lpx .red3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:16px 0}
.lpx .red3 div{background:#161c18;border:1px solid #26302a;border-radius:18px;padding:14px}
.lpx .red3 .num{font-size:1.9rem;font-weight:800;color:var(--lime);line-height:1}
.lpx .red3 b{display:block;margin:.3rem 0 .15rem}
.lpx .red3 span{color:#aab5ad;font-size:.85rem;font-weight:500}
.lpx .lnk{background:#161c18;border:1.5px dashed #3c5a46;border-radius:18px;padding:14px;margin-bottom:10px}
.lpx .lnk small{display:block;color:var(--lime);font-weight:800;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px}
.lpx .lnk code{display:block;color:#fff;font-size:.95rem;word-break:break-all;margin-bottom:10px;font-family:inherit;font-weight:700}
.lpx .lnk .bt{display:flex;gap:8px;flex-wrap:wrap}
.lpx .donde{background:#fff5d6;color:#5c4300;border-radius:16px;padding:12px 14px;font-weight:600;margin-top:12px}
.lpx .ingreso{background:var(--g);color:var(--k);border-radius:18px;padding:14px 16px;font-weight:700;margin-top:12px}
.lpx .ingreso b{font-size:1.15rem}
@media (max-width:640px){.lpx .red3{grid-template-columns:1fr}}
@media (max-width:640px){.lpx .calc,.lpx .ej{grid-template-columns:1fr 1fr}
.lpx .calc div:last-child,.lpx .ej div:last-child{grid-column:1/-1}
.lpx .dos{grid-template-columns:1fr}}

    
.lpx{font-family:'Plus Jakarta Sans',system-ui,sans-serif;-webkit-font-smoothing:antialiased;margin:-.5rem -.25rem 0}
.lpx .card{display:block;flex-direction:initial;min-width:0;word-wrap:normal}
.lpx .btn:hover,.lpx .btn:focus,.lpx .btn:active{filter:brightness(.96);box-shadow:none}
.lpx .w{padding-top:0}

</style>
@endpush

@section('content')
@php
        $mon = $valores['moneda'] ?? 'COP'; // dólares si el asesor es de Venezuela
        $f = fn ($v) => \App\Support\Moneda::formato($v, $mon);
        $com = (float) $valores['nueva']['venta'];
        $plan = (float) $valores['valor_plan'];
        $planesSalario = $com > 0 ? (int) ceil($salario / $com) : 0;
        $enlace = $yo?->enlace ?? url('/registro');
    @endphp
<div class="lpx">
<div class="w">
    

    <section class="hero">
        <small>Capacitación del equipo</small>
        <h1>Cómo ganar un <em>salario mínimo</em> en una semana… o en 3 días.</h1>
        <p>Todo lo que necesita un asesor de LocalPro para vender desde el primer día: cuánto se gana, qué decir, cómo responder dudas y la rutina que funciona.</p>
        <div class="indice">
            <a href="#meta">💰 La meta</a><a href="#producto">📱 Qué vendemos</a><a href="{{ route('asesor.ejemplos') }}">👀 Ejemplos para mostrar</a><a href="#conjuntos">🏢 Conjuntos</a><a href="#pasos">🪜 Paso a paso</a><a href="#guiones">💬 Guiones</a><a href="#dudas">🙋 Dudas</a><a href="#rutina">✅ Rutina</a><a href="#mentalidad">🧠 Mentalidad</a><a href="#crecer">🚀 Crecer</a>
        </div>
    </section>

    <section class="meta" id="meta">
        <h2>Vendes {{ $planesSalario }} planes y ya tienes un salario mínimo.</h2>
        <p>Cada plan anual vale {{ $f($plan) }} y tú ganas <b>{{ $f($com) }}</b> por cada negocio. El salario mínimo es de aprox. {{ $f($salario) }}.</p>
        <div class="calc">
            <div><small>Por cada plan ganas</small><b>{{ $f($com) }}</b></div>
            <div><small>Planes para 1 salario</small><b>{{ $planesSalario }}</b></div>
            <div><small>En 3 días son</small><b id="porDia3">{{ (int) ceil($planesSalario / 3) }} al día</b></div>
        </div>
        <div class="sl">
            <label>Si vendes <span id="vPlanes">25</span> planes…</label>
            <input type="range" id="planes" min="1" max="100" value="25">
        </div>
        <div class="ej">
            <div><small>Ganas</small><b id="gana">{{ $f(25 * $com) }}</b></div>
            <div><small>Eso es</small><b id="salarios">{{ number_format(25 * $com / max(1, $salario), 1, ',', '.') }} salarios</b></div>
            <div><small>En una semana son</small><b id="semana">{{ (int) ceil(25 / 6) }} al día</b></div>
        </div>
    </section>

    <section class="card" id="producto">
        <h2><span class="n">📱</span>Qué vendemos (en 20 segundos)</h2>
        <p class="sub">LocalPro es el sistema para que cualquier negocio venda, controle su inventario con <b>stock actualizado</b> en tiempo real y su caja desde el celular.</p>
        <div class="dos">
            <ul style="margin:0;padding-left:1.1rem;font-weight:600">
                <li>Caja (POS) para vender rápido, con o sin lector de código, varios medios de pago y cuadre de caja exacto.</li>
                <li><b>Stock actualizado</b> al instante con cada venta: tallas, colores y alertas de lo que se acaba.</li>
                <li><b>Vende por metro, kilo o litro</b> (vidrio, telas, cables, carnes, granos) con decimales: 1,5 m · 0,250 kg.</li>
                <li><b>Importa sus productos desde Excel</b>: si ya tiene 1.000 productos no los crea uno por uno. O nos manda el archivo y el equipo se los carga.</li>
                <li>Factura o tiquete por WhatsApp o impreso.</li>
                <li><b>💬 Mis redes</b>: conecta su Facebook e Instagram, responde todos los chats en un solo lugar, envía productos con foto y precio, y publica en las dos redes al tiempo (o programado).</li>
                <li><b>🏆 Niveles con certificado</b>: el panel le pone tareas al empresario (productos, compartir su tienda, clientes, primera venta y conectar sus redes). Al completar el nivel 1 le sale una celebración y descarga su <b>Certificado en competencias básicas para el manejo de LocalPro</b> para presumirlo en redes.</li>
                <li><b>🧾 Preparado para la DIAN</b>: separa el IVA de cada venta, maneja las resoluciones de numeración (avisa cuando se acaban o vencen), el POS electrónico y la factura electrónica cuando el cliente la pide, y la anulación con nota crédito. El empresario ve en <b>Facturación DIAN</b> qué le falta, con un porcentaje de avance.</li>
                <li><b>🎓 Institutos y academias (nuevo cliente)</b>: institutos técnicos laborales, academias y cursos. Pasan a LocalPro el <b>formulario de inscripción</b> y el <b>formato de matrícula</b> (se imprimen llenos con su logo), matriculan de contado o a crédito y el sistema arma las <b>cuotas mensuales</b>, da recibo por WhatsApp y muestra quién está en mora para recordarle con un toque. ¡Se acabó la carpeta de papeles y el cuaderno de pagos!</li>
                <li><b>🩺 Consultorios (nuevo cliente)</b>: psicólogos, odontólogos, estética, fisioterapia, nutrición y médicos particulares. Llevan pacientes, agenda con recordatorio por WhatsApp e <b>historia clínica</b> por especialidad, con diagnósticos CIE-10, fórmulas e incapacidades con su firma. Lo clínico va cifrado y una atención cerrada no se puede cambiar (como pide la ley). Y cobran la consulta en la misma caja. Es para consulta particular: todavía no factura a EPS.</li>
                <li><b>📋 Inventario y conteo físico</b>: el negocio ve cuántas prendas tiene por talla, categoría y color (y cuánta plata hay en mercancía). Para el inventario, escanean las etiquetas con el celular prenda por prenda, tocan <b>Terminé</b> y el sistema dice cuáles están, cuáles faltan y cuáles sobran; con un botón queda todo cuadrado. ¡Se acabó el cuaderno y la noche contando!</li>
                <li><b>📉 Egresos</b>: el negocio anota cada gasto (arriendo, servicios, domicilios…) con foto del recibo y ve en qué se le va la plata. Si el vendedor paga con la caja, se descuenta solo al cerrar. El vendedor ve los suyos y los de sus compañeros; el dueño ve todos.</li>
                <li><b>📦 Separados y abonos</b>: el cliente aparta la prenda, abona por partes (recibo de cada abono por WhatsApp con lo que le falta) y se la lleva al completar. ¡Las tiendas de ropa lo piden mucho!</li>
                <li><b>Sube sus clientes y ventas anteriores desde Excel</b> para no empezar de cero.</li>
            </ul>
            <ul style="margin:0;padding-left:1.1rem;font-weight:600">
                <li><b>🛍️ Marketplace LocalProo</b> (<b>localproo.com/marketplace</b>): todas las tiendas en línea de LocalPro en un solo lugar, como Mercado Libre. El cliente busca un producto, compara precios entre tiendas y compra directo al negocio por WhatsApp. Cada negocio nuevo que inscriba sale ahí solo, con todos sus productos: ¡clientes gratis!</li>
                <li><b>🌐 Página web / tienda en línea</b> por categorías, botón <b>Comprar</b> por WhatsApp (le llega el producto exacto) y que <b>aparece en Google</b>.</li>
                <li><b>💬 Contactar clientes nuevos</b>: cada día el sistema le reparte a cada persona del equipo 20 contactos de la base de datos con un mensaje listo y el enlace del catálogo; se envía por WhatsApp con un toque y el dueño ve el avance.</li>
                <li><b>📱 Base de datos con los contactos del celular</b>: el negocio exporta los contactos de su celular (Android, Samsung, iPhone o Google) y los sube; LocalPro los vuelve clientes sin repetidos, listos para ofrecerles su tienda y promociones.</li>
                <li><b>📷 Escanear con la cámara del celular</b>: el código de barras de cada prenda se lee con la cámara, sin comprar lector. Sirve para vender en el POS, buscar un producto y revisar existencias.</li>
                <li><b>🎟️ Tiquetera para restaurantes y cafeterías</b>: el cliente paga por adelantado, por ejemplo, 20 almuerzos; cada día el restaurante busca su nombre y toca "Marcar almuerzo". La venta entra a la caja y el cliente recibe por WhatsApp cuántos le quedan. Ideal para restaurantes de almuerzos ejecutivos.</li>
                <li><b>🎁 Comparta y gane</b>: cada negocio tiene su código (LCT-…). Si invita a otro empresario y este paga su plan anual, al que invitó se le abona un valor de por vida para su plan. El invitado queda en <b>su misma red</b> (con usted como asesor), así que sus clientes también le traen ventas.</li>
                <li><b>⚖️ La caja siempre cuadra</b>: si se digitaron productos de más o se vendió algo que no se registró, el dueño usa <b>Corregir venta</b> o <b>Registrar venta olvidada</b> y la caja de ese día, el inventario y los reportes se recalculan solos, aunque la caja ya esté cerrada.</li>
                <li><b>👥 WhatsApp con todo el equipo</b>: el dueño comparte su base de datos con sus vendedores y cada uno escribe a sus contactos del día, sin repetir a nadie.</li>
                <li><b>📥 Importar proveedores y clientes</b> desde Excel, CSV, contactos del celular o una hoja de Google.</li>
                <li><b>🏷️ Etiquetas para mercancía nueva</b>: el negocio imprime códigos de barras en blanco, sella toda la mercancía apenas llega y, al crear cada producto, escanea la etiqueta y queda con ese código. No tiene que crear todo primero para poder etiquetar.</li>
                <li><b>🔥 Promociones y liquidaciones</b>: el negocio pone "precio antes" y "precio ahora" y la tienda en línea muestra solo el precio tachado, el % de descuento y una sección de ofertas.</li>
                <li><b>Vendedores con metas</b>: el dueño les pone meta (en dinero o en número de ventas) y ellos ven cuánto les falta, sin ver las ganancias.</li>
                <li>Reportes: cuánto vendió, cuánto ganó, hora pico, todo a Excel.</li>
                <li>Se instala como <b>app en el celular</b>; sin instalar programas.</li>
                <li><b>2 meses gratis con todo</b> (usuarios y productos ilimitados) y luego solo {{ $f($plan) }} al año.</li>
            </ul>
        </div>
        <div class="regla" style="background:#fff8e6;color:#7a4b00;margin-bottom:10px">🔜 <b>Muy pronto:</b> recetas e insumos (restaurantes y panaderías) y fechas de vencimiento por lote (droguerías y alimentos). Si su cliente los necesita, inscríbalo ya con la prueba gratis.</div>
        <p class="regla" style="background:#e8f8ec;color:#11702f">💡 Frase clave: <i>"Por solo {{ $f($plan) }} al año tiene su sistema de ventas con stock actualizado y además una página web de su tienda que aparece en Google. Por menos de {{ $f(\App\Support\Moneda::esExtranjera($mon) ? ceil($plan / 12 * 100) / 100 : ceil($plan / 12 / 100) * 100) }} al mes tienes tu negocio organizado y sabes cuánto ganas cada día."</i></p>
    </section>

    <section class="card" id="conjuntos">
        <h2><span class="n">🏢</span>Otra cancha: conjuntos residenciales y edificios</h2>
        <p class="sub">LocalPro también administra conjuntos, torres y edificios. Cada conjunto es un cliente más para ti, y suele tener cientos de familias.</p>
        <div class="dos">
            <ul style="margin:0;padding-left:1.1rem;font-weight:600">
                <li>Cobro de administración mensual automático a todas las unidades.</li>
                <li>Registro de pagos con recibo por WhatsApp o impreso.</li>
                <li>Cartera y morosos con recordatorio por WhatsApp en un toque.</li>
                <li>Cuotas extraordinarias (iguales o por coeficiente), multas e intereses de mora.</li>
            </ul>
            <ul style="margin:0;padding-left:1.1rem;font-weight:600">
                <li>Gastos del conjunto e informe de ingresos vs. gastos.</li>
                <li>Comunicados para los residentes.</li>
                <li>PQRS y reportes de daños.</li>
                <li>Reservas de zonas comunes sin cruces de horario.</li>
            </ul>
        </div>
        <p class="regla" style="background:#e8f8ec;color:#11702f">🎯 A quién visitar: administradores de propiedad horizontal, consejos de administración y contadores de conjuntos. Al registrarse eligen el tipo <b>"Conjunto residencial / edificio"</b>.</p>
    </section>

    <section class="card" id="pasos">
        <h2><span class="n">🪜</span>Paso a paso de una venta</h2>
        <ol class="pasos">
            <li><b>Elige tu zona y haz tu lista</b><span>Tiendas de ropa, calzado, minimercados, cafeterías, misceláneas, restaurantes de tu comuna. Anota 20 negocios por día.</span></li>
            <li><b>Saluda y pregunta (no vendas todavía)</b><span>"¿Cómo llevas hoy el control de lo que vendes y lo que tienes en bodega?". Escucha el problema.</span></li>
            <li><b>Muéstralo en tu celular</b><span>Abre una venta de ejemplo: busca un producto, cóbralo y manda la factura por WhatsApp. En 1 minuto lo entienden.</span></li>
            <li><b>Inscríbelo ahí mismo con tu enlace</b><span>Que se registre con <b>tu enlace</b> (o escriba tu código) para que quede a tu nombre. Arranca con 2 meses gratis.</span></li>
            <li><b>Ayúdale a subir sus primeros productos</b><span>Acompáñalo 10 minutos: crear 3 productos y hacer su primera venta. El que vende el primer día, se queda.</span></li>
            <li><b>Haz seguimiento</b><span>Escríbele a los 3 días, al mes y antes de que termine la prueba. Cuando paga su plan anual, <b>ganas {{ $f($com) }}</b>.</span></li>
            <li><b>Cóbrale con el enlace de pago</b><span>En tu panel, en <b>Mis negocios</b>, toca <b>Cobrar</b>: le llega por WhatsApp un enlace para pagar en línea (tarjeta, PSE o Nequi). Apenas paga, su plan queda activo solo. Si el cliente te da el efectivo, con el botón 💳 pagas tú mismo desde tu celular. La comisión queda a tu nombre igual.</span></li>
        </ol>
        <div class="regla">📌 Tu enlace para inscribir negocios: <span style="word-break:break-all">{{ $enlace }}</span></div>
    </section>

    <section class="card" id="guiones">
        <h2><span class="n">💬</span>Guiones listos para usar</h2>
        <p class="sub">Cópialos, ajústalos a tu estilo y envíalos.</p>
        @php
            $guiones = [
                ['Primer mensaje por WhatsApp', "¡Hola! 👋 Soy {nombre}, asesor de LocalPro. Ayudamos a negocios como el tuyo a vender más rápido y saber exactamente cuánto ganan cada día, desde el celular.\n\nTe regalo 2 meses gratis para que lo pruebes. ¿Te muestro en 5 minutos cómo funciona? 😊"],
                ['En persona (en el local)', "Hola, ¿cómo está? Soy {nombre}, de LocalPro. Una pregunta rápida: ¿cómo lleva el control de lo que vende y de lo que tiene en inventario?\n\n(Escuchar)\n\nMire, con esto lo hace desde el celular: vende, ve lo que le queda y al final del día sabe cuánto ganó. ¿Le muestro una venta de ejemplo? Son 2 minutos."],
                ['Cuando se termina la prueba', "¡Hola {negocio}! 🙌 Ya llevas 2 meses usando LocalPro. Tu prueba gratis termina pronto.\n\nPara seguir con todo (ventas, inventario, caja y tu página web en Google) el plan anual es de {plan}, menos de {mes} al mes. Te mando el enlace para pagarlo en línea (tarjeta, PSE o Nequi) y queda activo de una vez. ¿Te lo envío?"],
                ['Para sumar asesores a tu equipo', "¡Hola! Estoy trabajando con LocalPro y me está yendo muy bien. Ganas {comision} por cada negocio que inscribes y no necesitas invertir.\n\n¿Te gustaría trabajar conmigo? Te enseño todo. Inscríbete aquí: {enlace_equipo}"],
            ];
            $rep = ['{nombre}' => $yo ? strtok($yo->nombre, ' ') : '(tu nombre)', '{plan}' => $f($plan), '{mes}' => $f(\App\Support\Moneda::esExtranjera($mon) ? ceil($plan / 12 * 100) / 100 : ceil($plan / 12 / 100) * 100), '{comision}' => $f($com), '{enlace_equipo}' => $yo?->enlace_reclutar ?? '(tu enlace)'];
        @endphp
        @foreach ($guiones as $i => [$titulo, $texto])
        <div class="guion"><span class="t">{{ $titulo }}</span><span id="g{{ $i }}">{{ strtr($texto, $rep) }}</span>
            <div><button type="button" class="btn btn-l" onclick="copiar('g{{ $i }}')"><i class="fa-regular fa-copy"></i> Copiar</button></div>
        </div>
        @endforeach
    </section>

    <section class="card" id="dudas">
        <h2><span class="n">🙋</span>Dudas frecuentes y qué responder</h2>
        @php $dudas = [
            ['"Es muy caro."', 'Son ' . $f($plan) . ' al año: menos de ' . $f(\App\Support\Moneda::esExtranjera($mon) ? ceil($plan / 12 * 100) / 100 : ceil($plan / 12 / 100) * 100) . ' al mes. Con evitar una sola pérdida de mercancía o un error de caja ya se paga. Y los primeros 2 meses son gratis.'],
            ['"Yo lo anoto en un cuaderno."', 'El cuaderno no te dice qué se está acabando, cuánto ganaste hoy ni cuál es tu producto más vendido. LocalPro sí, y sin hacer cuentas.'],
            ['"No sé de tecnología."', 'Si sabes usar WhatsApp, sabes usar LocalPro. Yo te ayudo a subir tus primeros productos ahora mismo.'],
            ['"No tengo computador ni impresora."', 'Funciona en el celular. La factura se manda por WhatsApp; la impresora es opcional.'],
            ['"Lo voy a pensar."', 'Perfecto, por eso son 2 meses gratis: lo usas sin pagar nada y decides con tu negocio funcionando. Inscribámoslo ya y lo pruebas hoy.'],
            ['"¿Y si se me va el internet?"', 'Tus datos quedan guardados en la nube; apenas vuelve la señal sigues donde ibas. No se pierde nada.'],
            ['"Tengo muchísimos productos, me da pereza crearlos."', 'No los creas uno por uno: si los tienes en Excel se importan en 2 minutos, y si no, nos mandas el archivo (o fotos) y el equipo de LocalPro te los carga.'],
            ['"Ya tengo página en Facebook / Instagram."', 'Perfecto, y con LocalPro además tienes tu propia tienda en línea que aparece en Google, con botón Comprar por WhatsApp. La compartes en tus redes y te llegan pedidos con el producto exacto.'],
            ['"Yo vendo por metros / por kilos."', 'LocalPro vende por metro, kilo o litro con decimales (1,5 m · 0,250 kg) y el inventario queda exacto.'],
            ['"Tengo empleados y no quiero que vean lo que gano."', 'Los vendedores solo ven sus ventas del día y su meta. Las ganancias solo las ves tú.'],
        ]; @endphp
        @foreach ($dudas as [$p, $r])
        <details class="obj"><summary>{{ $p }}</summary><p>{{ $r }}</p></details>
        @endforeach
    </section>

    <section class="card" id="rutina">
        <h2><span class="n">✅</span>Rutina diaria que funciona</h2>
        <p class="sub">Marca cada punto. Con esta rutina, {{ (int) ceil($planesSalario / 5) }} negocios al día te dan un salario mínimo en una semana laboral.</p>
        <div class="check">
            @php $rutina = ['Revisa tu panel: aspirantes por aprobar y negocios por renovar.', 'Visita o escribe a 20 negocios nuevos de tu zona.', 'Haz al menos 8 demostraciones en tu celular.', 'Inscribe con tu enlace a todos los que quieran la prueba.', 'Ayuda a los nuevos a subir sus primeros 3 productos.', 'Escríbele a los que están por terminar su prueba.', 'Invita a 1 persona a tu equipo con tu enlace de asesores.']; @endphp
            @foreach ($rutina as $i => $t)
            <label><input type="checkbox" data-k="r{{ $i }}"> {{ $t }}</label>
            @endforeach
        </div>
    </section>

    @php
        $renAsesor = (float) $valores['renovacion']['venta'];
        $comArriba = (float) $valores['nueva']['arriba'];
        $renZona = (float) $valores['renovacion']['lider_zona'];
        $enlaceRed = $yo?->enlace_reclutar;
    @endphp
    <section class="mente" id="mentalidad">
        <small class="k">🧠 Mentalidad de líder</small>
        <h2>Si tienes la mentalidad grande de <em>crecer, liderar y cumplir tus proyectos</em>, esto es para ti.</h2>
        <p>Vender tú solo te da un ingreso. <b>Armar tu red te da un negocio.</b> Cuando invitas a otras personas a ser asesores con tu enlace, quedan en tu equipo y <b>tú también ganas por el trabajo de toda tu red de trabajo</b>. Así se sube a <b>Líder de zona</b> y después a líder de ciudad, departamento y país.</p>
        <p>No se trata de trabajar más horas: se trata de <b>multiplicarte</b>. Tú enseñas, tu equipo vende, y todos ganan.</p>

        <div class="red3">
            <div><span class="num">10</span><b>Negocios o emprendedores</b><span>Inscríbelos con tu enlace de negocios. Cada uno que pague su plan anual te deja {{ $f($com) }} y luego {{ $f($renAsesor) }} cada año que renueve.</span></div>
            <div><span class="num">10</span><b>Asesores en tu equipo</b><span>Invítalos con tu enlace de asesores: solo llenan datos básicos, pasan una prueba corta y quedan en tu red automáticamente.</span></div>
            <div><span class="num">🏆</span><b>Subes de rango</b><span>Por cada negocio que venda o renueve alguien que tú sumaste ganas {{ $f($comArriba) }}. Con 10 negocios pagos + 10 asesores activos, LocalPro te sube a Líder de zona y ganas también por cada venta y renovación de tu equipo.</span></div>
        </div>

        @if ($enlaceRed)
        <div class="lnk">
            <small>1 · Tu enlace para sumar asesores a tu red</small>
            <code id="lnkRed">{{ $enlaceRed }}</code>
            <div class="bt">
                <button type="button" class="btn btn-g" onclick="copiarTxt('lnkRed','Enlace de asesores copiado')"><i class="fa-regular fa-copy"></i> Copiar mi enlace</button>
                <a class="btn btn-w" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! 🙌 Estoy creciendo con LocalPro y quiero que trabajes conmigo. Ganas ' . $f($com) . ' por cada negocio que inscribas, sin invertir, y te enseño todo. Inscríbete aquí: ' . $enlaceRed) }}"><i class="fa-brands fa-whatsapp"></i> Invitar por WhatsApp</a>
            </div>
        </div>
        @endif
        <div class="lnk">
            <small>2 · Tu enlace para inscribir negocios y emprendedores</small>
            <code id="lnkNeg">{{ $enlace }}</code>
            <div class="bt">
                <button type="button" class="btn btn-g" onclick="copiarTxt('lnkNeg','Enlace de negocios copiado')"><i class="fa-regular fa-copy"></i> Copiar mi enlace</button>
                <a class="btn btn-w" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! 👋 Te recomiendo LocalPro para manejar tu negocio desde el celular: ventas, caja, inventario y tu tienda en línea. Pruébalo 2 meses gratis aquí: ' . $enlace) }}"><i class="fa-brands fa-whatsapp"></i> Enviar a un negocio</a>
            </div>
        </div>

        <div class="donde">📍 <b>¿Dónde encuentro mis enlaces después?</b> Entra a <b>Mi panel</b> de asesor: ahí están siempre en los cuadros <b>"Su enlace para inscribir negocios"</b> y <b>"Suma asesores a tu equipo"</b>, con botones para copiar y compartir por WhatsApp. También los encuentras aquí, en esta capacitación.</div>

        <div class="ingreso">💚 <b>Un ingreso continuo de por vida.</b> Mientras tus negocios sigan vinculados a LocalPro y tú estés activo, cada año que renuevan te dejan {{ $f($renAsesor) }} por negocio; por cada negocio que venda o renueve alguien que tú sumaste ganas {{ $f($comArriba) }}, y como líder de zona {{ $f($renZona) }} por cada venta y cada renovación de tu equipo. Ejemplo: si tus 10 asesores inscriben 10 negocios cada uno, son 100 negocios en tu red trabajando para ti.</div>
    </section>

    <section class="card" id="crecer">
        <h2><span class="n">🚀</span>Cómo crecer y ganar más</h2>
        <p class="sub">Cuando cumples la meta subes de rango y también ganas por tu equipo.</p>
        <table class="tabla">
            <thead><tr><th>Quién gana</th><th>Negocio nuevo</th><th>Cada renovación</th></tr></thead>
            <tbody>
            @foreach (['venta' => 'Quien inscribe el negocio', 'arriba' => 'Quien sumó a ese asesor', 'lider_zona' => 'Líder de zona', 'lider_ciudad' => 'Líder de ciudad', 'lider_departamento' => 'Líder de departamento', 'lider_pais' => 'Líder de país'] as $k => $n)
                <tr><td><b>{{ $n }}</b></td><td>{{ (float) $valores['nueva'][$k] > 0 ? $f($valores['nueva'][$k]) : '—' }}</td><td>{{ (float) $valores['renovacion'][$k] > 0 ? $f($valores['renovacion'][$k]) : '—' }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <ul style="font-weight:600;margin:12px 0 0;padding-left:1.1rem">
            <li><b>Meta para subir de rango:</b> 10 negocios activos que pagaron su plan anual + 10 asesores sumados a tu equipo.</li>
            <li>Las pruebas gratis cuentan cuando el negocio <b>paga</b> su plan anual: ahí ganas tu comisión completa.</li>
            <li>Un líder de zona maneja hasta 10 asesores de su comuna.</li>
        </ul>
        <div class="regla">⚠️ Para recibir comisiones debes estar activo: mínimo <b>una venta nueva en los últimos {{ \App\Support\Comisiones::DIAS_ACTIVO }} días</b>. Si ese mes no vendiste, tu parte queda para LocalPro. Vale para todos: asesores y líderes.</div>
    </section>

    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin:24px 0 32px">
        @if ($yo)
        <a class="btn btn-w" target="_blank" href="https://wa.me/?text={{ rawurlencode('Equipo 🙌 esta es la capacitación de LocalPro: cuánto ganamos, qué decir y la rutina diaria. Entren desde su panel → Capacitación.') }}"><i class="fa-brands fa-whatsapp"></i> Compartir con mi equipo</a>
        @endif
        <a class="btn btn-g" href="{{ route('asesor.simulador') }}"><i class="fa-solid fa-calculator"></i> Abrir simulador</a>
    </div>
</div>


@include('layouts.partials.lp-ui')
</div>
@endsection

@push('js')
<script>
(function () {
    var COM = {{ (float) $com }}, SAL = {{ (float) $salario }};
    var fmt = {!! \App\Support\Moneda::js($valores['moneda'] ?? 'COP') !!};
    var r = document.getElementById('planes');
    function calc() {
        var p = +r.value;
        document.getElementById('vPlanes').textContent = p;
        document.getElementById('gana').textContent = fmt(p * COM);
        document.getElementById('salarios').textContent = (p * COM / SAL).toFixed(1).replace('.', ',') + ' salarios';
        document.getElementById('semana').textContent = Math.ceil(p / 6) + ' al día';
    }
    r.addEventListener('input', calc); calc();
    window.copiarTxt = function (id, msg) {
        var t = document.getElementById(id).innerText.trim();
        (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { if (window.LP) LP.toast(msg); }, function () { if (window.LP) LP.toast('Mantenga presionado el enlace para copiarlo'); });
    };
    window.copiar = function (id) {
        var t = document.getElementById(id).innerText;
        (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { if (window.LP) LP.toast('Guion copiado'); }, function () {});
    };
    // La rutina se recuerda en este celular durante el día
    var hoy = new Date().toISOString().slice(0, 10);
    document.querySelectorAll('.check input').forEach(function (c) {
        var k = 'lpRutina-' + hoy + '-' + c.dataset.k;
        try { c.checked = localStorage.getItem(k) === '1'; } catch (e) {}
        c.addEventListener('change', function () { try { localStorage.setItem(k, c.checked ? '1' : '0'); } catch (e) {} });
    });
})();
</script>
@endpush
