<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="robots" content="noindex">
    <title>Capacitación de asesores · LocalPro</title>
    @php
        $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $com = (float) $valores['nueva']['venta'];
        $plan = (float) $valores['valor_plan'];
        $planesSalario = $com > 0 ? (int) ceil($salario / $com) : 0;
        $enlace = $yo?->enlace ?? url('/registro');
    @endphp
    <style>
        :root{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--k:#0b0d0c;--ink:#151a17;--mut:#5b665e;--line:#e3efe5;--bg:#f4f8f5;--lime:#a6f34a;--gold:#ffcc33;--pink:#ff5d8f}
        *{box-sizing:border-box}
        body{margin:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;line-height:1.5}
        .w{max-width:980px;margin:0 auto;padding:0 16px 40px}
        .top{display:flex;align-items:center;justify-content:space-between;padding:16px 0;gap:10px}
        .brand{display:flex;align-items:center;gap:.5rem;font-weight:800;text-decoration:none;color:inherit;font-size:1.1rem}
        .brand b{width:32px;height:32px;border-radius:10px;background:var(--k);color:var(--lime);display:grid;place-items:center}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;border:0;border-radius:999px;padding:.7rem 1.1rem;font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:.95rem}
        .btn-k{background:var(--k);color:#fff}.btn-g{background:var(--g);color:var(--k)}.btn-w{background:#25d366;color:#fff}.btn-l{background:#fff;border:1.5px solid var(--line);color:var(--ink)}
        .hero{background:var(--k);color:#fff;border-radius:32px;padding:34px 24px;position:relative;overflow:hidden;margin-bottom:18px}
        .hero::after{content:"";position:absolute;right:-70px;top:-70px;width:260px;height:260px;border-radius:50%;background:var(--g);opacity:.35;filter:blur(8px)}
        .hero small{color:var(--lime);font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.75rem}
        .hero h1{font-size:clamp(1.9rem,6vw,3rem);line-height:1.05;margin:.4rem 0 .7rem;letter-spacing:-.03em;position:relative;z-index:1}
        .hero h1 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
        .hero p{color:#cfd8d2;margin:0 0 14px;position:relative;z-index:1;max-width:640px}
        .indice{display:flex;flex-wrap:wrap;gap:8px;position:relative;z-index:1}
        .indice a{background:#1a201c;color:#fff;border-radius:999px;padding:.4rem .8rem;font-weight:700;font-size:.82rem;text-decoration:none}
        .card{background:#fff;border:1.5px solid var(--line);border-radius:26px;padding:22px;margin-bottom:16px}
        .card h2{margin:0 0 6px;font-size:1.4rem;letter-spacing:-.02em;display:flex;align-items:center;gap:.6rem}
        .card h2 .n{width:34px;height:34px;border-radius:12px;background:var(--g);color:var(--k);display:grid;place-items:center;font-size:1rem;flex:none}
        .card .sub{color:var(--mut);font-weight:600;margin:0 0 16px}
        .meta{background:var(--g);border-radius:26px;padding:22px;color:var(--k);margin-bottom:16px}
        .meta h2{margin:0 0 4px;font-size:clamp(1.4rem,4.5vw,2rem);letter-spacing:-.03em;line-height:1.1}
        .meta p{margin:0 0 14px;font-weight:600}
        .calc{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}
        .calc div{background:rgba(255,255,255,.55);border-radius:18px;padding:12px}
        .calc small{font-weight:800;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;opacity:.75}
        .calc b{display:block;font-size:1.5rem;letter-spacing:-.03em}
        .sl label{display:flex;justify-content:space-between;font-weight:800;margin-bottom:4px}
        .sl input{width:100%;accent-color:#0b0d0c;height:28px}
        .ej{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .ej div{background:#fff;border-radius:18px;padding:14px;text-align:center}
        .ej b{display:block;font-size:1.25rem}
        .ej small{color:var(--mut);font-weight:700}
        .pasos{counter-reset:p;list-style:none;margin:0;padding:0}
        .pasos li{position:relative;padding:12px 0 12px 54px;border-bottom:1px dashed var(--line)}
        .pasos li::before{counter-increment:p;content:counter(p);position:absolute;left:0;top:12px;width:38px;height:38px;border-radius:13px;background:var(--k);color:var(--lime);display:grid;place-items:center;font-weight:800}
        .pasos b{display:block}
        .pasos span{color:var(--mut);font-weight:500}
        .guion{background:var(--bg);border-radius:18px;padding:14px 16px;margin-bottom:10px;white-space:pre-line;font-weight:500;position:relative}
        .guion .t{font-weight:800;display:block;margin-bottom:4px;color:#11702f}
        .guion button{margin-top:10px}
        details.obj{border:1.5px solid var(--line);border-radius:16px;padding:12px 14px;margin-bottom:8px}
        details.obj summary{font-weight:800;cursor:pointer}
        details.obj p{margin:.5rem 0 0;color:var(--mut);font-weight:500}
        .check label{display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px dashed var(--line);font-weight:600;cursor:pointer}
        .check input{width:20px;height:20px;accent-color:#16b34a;flex:none;margin-top:2px}
        .tabla{width:100%;border-collapse:collapse}
        .tabla td,.tabla th{padding:9px 6px;border-bottom:1px solid var(--line);text-align:left}
        .tabla td:last-child,.tabla th:last-child{text-align:right}
        .tabla th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--mut)}
        .regla{background:#fff5d6;color:#8a6400;border-radius:16px;padding:12px 14px;font-weight:700;margin-top:10px}
        .dos{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        @media (max-width:640px){.calc,.ej{grid-template-columns:1fr 1fr}.calc div:last-child,.ej div:last-child{grid-column:1/-1}.dos{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="w">
    <div class="top">
        <a href="{{ url('/') }}" class="brand"><b>L</b>LocalPro</a>
        <a class="btn btn-k" href="{{ auth()->user()->es_superadmin ? route('admin.red.index') : route('asesor.index') }}"><i class="fa-solid fa-arrow-left"></i> Mi panel</a>
    </div>

    <section class="hero">
        <small>Capacitación del equipo</small>
        <h1>Cómo ganar un <em>salario mínimo</em> en una semana… o en 3 días.</h1>
        <p>Todo lo que necesita un asesor de LocalPro para vender desde el primer día: cuánto se gana, qué decir, cómo responder dudas y la rutina que funciona.</p>
        <div class="indice">
            <a href="#meta">💰 La meta</a><a href="#producto">📱 Qué vendemos</a><a href="#conjuntos">🏢 Conjuntos</a><a href="#pasos">🪜 Paso a paso</a><a href="#guiones">💬 Guiones</a><a href="#dudas">🙋 Dudas</a><a href="#rutina">✅ Rutina</a><a href="#crecer">🚀 Crecer</a>
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
                <li>Caja (POS) para vender rápido, con o sin lector de código.</li>
                <li><b>Stock actualizado</b> al instante con cada venta: inventario con tallas, colores y alertas de lo que se acaba.</li>
                <li>Abrir y cerrar caja con cuadre exacto.</li>
                <li>Factura por WhatsApp o impresa.</li>
            </ul>
            <ul style="margin:0;padding-left:1.1rem;font-weight:600">
                <li>Catálogo en línea para recibir pedidos por WhatsApp.</li>
                <li>Clientes, vendedores y reportes de ventas.</li>
                <li>Sin instalar nada: funciona en celular y computador.</li>
                <li><b>3 meses de prueba gratis</b> y luego solo {{ $f($plan) }} al año.</li>
            </ul>
        </div>
        <p class="regla" style="background:#e8f8ec;color:#11702f">💡 Frase clave: <i>"Por menos de {{ $f(ceil($plan / 12 / 100) * 100) }} al mes tienes tu negocio organizado y sabes cuánto ganas cada día."</i></p>
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
            <li><b>Inscríbelo ahí mismo con tu enlace</b><span>Que se registre con <b>tu enlace</b> (o escriba tu código) para que quede a tu nombre. Arranca con 3 meses gratis.</span></li>
            <li><b>Ayúdale a subir sus primeros productos</b><span>Acompáñalo 10 minutos: crear 3 productos y hacer su primera venta. El que vende el primer día, se queda.</span></li>
            <li><b>Haz seguimiento</b><span>Escríbele a los 3 días, al mes y antes de que termine la prueba. Cuando paga su plan anual, <b>ganas {{ $f($com) }}</b>.</span></li>
        </ol>
        <div class="regla">📌 Tu enlace para inscribir negocios: <span style="word-break:break-all">{{ $enlace }}</span></div>
    </section>

    <section class="card" id="guiones">
        <h2><span class="n">💬</span>Guiones listos para usar</h2>
        <p class="sub">Cópialos, ajústalos a tu estilo y envíalos.</p>
        @php
            $guiones = [
                ['Primer mensaje por WhatsApp', "¡Hola! 👋 Soy {nombre}, asesor de LocalPro. Ayudamos a negocios como el tuyo a vender más rápido y saber exactamente cuánto ganan cada día, desde el celular.\n\nTe regalo 3 meses gratis para que lo pruebes. ¿Te muestro en 5 minutos cómo funciona? 😊"],
                ['En persona (en el local)', "Hola, ¿cómo está? Soy {nombre}, de LocalPro. Una pregunta rápida: ¿cómo lleva el control de lo que vende y de lo que tiene en inventario?\n\n(Escuchar)\n\nMire, con esto lo hace desde el celular: vende, ve lo que le queda y al final del día sabe cuánto ganó. ¿Le muestro una venta de ejemplo? Son 2 minutos."],
                ['Cuando se termina la prueba', "¡Hola {negocio}! 🙌 Ya llevas 3 meses usando LocalPro. Tu prueba gratis termina pronto.\n\nPara seguir con todo (ventas, inventario, caja y catálogo) el plan anual es de {plan}, menos de {mes} al mes. ¿Te ayudo a activarlo hoy?"],
                ['Para sumar asesores a tu equipo', "¡Hola! Estoy trabajando con LocalPro y me está yendo muy bien. Ganas {comision} por cada negocio que inscribes y no necesitas invertir.\n\n¿Te gustaría trabajar conmigo? Te enseño todo. Inscríbete aquí: {enlace_equipo}"],
            ];
            $rep = ['{nombre}' => $yo ? strtok($yo->nombre, ' ') : '(tu nombre)', '{plan}' => $f($plan), '{mes}' => $f(ceil($plan / 12 / 100) * 100), '{comision}' => $f($com), '{enlace_equipo}' => $yo?->enlace_reclutar ?? '(tu enlace)'];
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
            ['"Es muy caro."', 'Son ' . $f($plan) . ' al año: menos de ' . $f(ceil($plan / 12 / 100) * 100) . ' al mes. Con evitar una sola pérdida de mercancía o un error de caja ya se paga. Y los primeros 3 meses son gratis.'],
            ['"Yo lo anoto en un cuaderno."', 'El cuaderno no te dice qué se está acabando, cuánto ganaste hoy ni cuál es tu producto más vendido. LocalPro sí, y sin hacer cuentas.'],
            ['"No sé de tecnología."', 'Si sabes usar WhatsApp, sabes usar LocalPro. Yo te ayudo a subir tus primeros productos ahora mismo.'],
            ['"No tengo computador ni impresora."', 'Funciona en el celular. La factura se manda por WhatsApp; la impresora es opcional.'],
            ['"Lo voy a pensar."', 'Perfecto, por eso son 3 meses gratis: lo usas sin pagar nada y decides con tu negocio funcionando. Inscribámoslo ya y lo pruebas hoy.'],
            ['"¿Y si se me va el internet?"', 'Tus datos quedan guardados en la nube; apenas vuelve la señal sigues donde ibas. No se pierde nada.'],
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

    <section class="card" id="crecer">
        <h2><span class="n">🚀</span>Cómo crecer y ganar más</h2>
        <p class="sub">Cuando cumples la meta subes de rango y también ganas por tu equipo.</p>
        <table class="tabla">
            <thead><tr><th>Quién gana</th><th>Negocio nuevo</th><th>Cada renovación</th></tr></thead>
            <tbody>
            @foreach (['venta' => 'Quien inscribe el negocio', 'lider_zona' => 'Líder de zona', 'lider_ciudad' => 'Líder de ciudad', 'lider_departamento' => 'Líder de departamento', 'lider_pais' => 'Líder de país'] as $k => $n)
                <tr><td><b>{{ $n }}</b></td><td>{{ $f($valores['nueva'][$k]) }}</td><td>{{ $f($valores['renovacion'][$k]) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <ul style="font-weight:600;margin:12px 0 0;padding-left:1.1rem">
            <li><b>Meta para subir de rango:</b> 10 negocios activos que pagaron su plan anual + 10 asesores sumados a tu equipo.</li>
            <li>Las pruebas gratis cuentan cuando el negocio <b>paga</b> su plan anual: ahí ganas tu comisión completa.</li>
            <li>Un líder de zona maneja hasta 10 asesores de su comuna.</li>
        </ul>
        <div class="regla">⚠️ Para estar activo y recibir comisiones debes tener al menos un negocio con pago. Si no, la comisión queda para LocalPro.</div>
    </section>

    <div style="text-align:center;margin:20px 0">
        @if ($yo)
        <a class="btn btn-w" target="_blank" href="https://wa.me/?text={{ rawurlencode('Equipo 🙌 esta es la capacitación de LocalPro: cuánto ganamos, qué decir y la rutina diaria. Entren desde su panel → Capacitación.') }}"><i class="fa-brands fa-whatsapp"></i> Compartir con mi equipo</a>
        @endif
        <a class="btn btn-g" href="{{ route('asesor.simulador') }}"><i class="fa-solid fa-calculator"></i> Abrir simulador</a>
    </div>
</div>

<script>
(function () {
    var COM = {{ (float) $com }}, SAL = {{ (float) $salario }};
    var fmt = function (n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var r = document.getElementById('planes');
    function calc() {
        var p = +r.value;
        document.getElementById('vPlanes').textContent = p;
        document.getElementById('gana').textContent = fmt(p * COM);
        document.getElementById('salarios').textContent = (p * COM / SAL).toFixed(1).replace('.', ',') + ' salarios';
        document.getElementById('semana').textContent = Math.ceil(p / 6) + ' al día';
    }
    r.addEventListener('input', calc); calc();
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
@include('layouts.partials.lp-ui')
</body>
</html>
