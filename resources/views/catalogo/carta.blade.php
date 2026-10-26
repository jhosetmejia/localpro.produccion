@php
    $M = moneda();
    $wa = numero_whatsapp($tienda->telefono);
    $lugar = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
    // Colores de cada diseño: fondo, tarjeta, texto, texto suave, acento, texto sobre acento, línea, fondo del encabezado
    $temas = [
        'bistro'   => ['#14110f', '#1d1916', '#f5ede1', '#b3a693', '#d9a441', '#14110f', 'rgba(217,164,65,.22)', 'radial-gradient(120% 90% at 85% 0%,#3a2a14 0%,#14110f 60%)'],
        'fresca'   => ['#fbf4ea', '#ffffff', '#2b1d14', '#7a6656', '#c4532d', '#ffffff', '#ecdccb', 'radial-gradient(120% 90% at 85% 0%,#f6d9c2 0%,#fbf4ea 62%)'],
        'parrilla' => ['#121214', '#1c1c1f', '#f4f4f5', '#a1a1aa', '#e63b2e', '#ffffff', 'rgba(230,59,46,.22)', 'radial-gradient(120% 90% at 85% 0%,#4a1410 0%,#121214 62%)'],
        'verde'    => ['#f3f8f1', '#ffffff', '#16301f', '#5d7465', '#2f7d4a', '#ffffff', '#d7e7d6', 'radial-gradient(120% 90% at 85% 0%,#cfe8cf 0%,#f3f8f1 62%)'],
    ];
    [$cBg, $cCard, $cInk, $cMut, $cAcc, $cOnAcc, $cLine, $cHero] = $temas[$tema];
    $oscuro = in_array($tema, ['bistro', 'parrilla'], true);
    $titulo = 'Carta · ' . $tienda->nombre . ($lugar ? ' · ' . $lugar : '');
    $desc = \Illuminate\Support\Str::limit(($tienda->descripcion ?: 'Nuestra carta') . '. ' . implode(', ', array_slice(array_keys($secciones), 0, 5)) . '. Pida por WhatsApp.', 158);
    $datos = collect($secciones)->flatten(1)->where('disponible', true)->keyBy('id')->map(fn ($p) => ['id' => $p['id'], 'nombre' => $p['nombre'], 'codigo' => $p['codigo'], 'variantes' => $p['variantes']]);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $titulo }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ str_replace('://www.', '://', $tienda->url_carta) }}">
<meta name="robots" content="{{ $tienda->en_google ? 'index, follow, max-image-preview:large' : 'noindex, follow' }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $tienda->nombre }}">
<meta property="og:title" content="{{ $titulo }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $tienda->url_carta }}">
@if ($tienda->logo_url)<meta property="og:image" content="{{ $tienda->logo_url }}"><link rel="icon" href="{{ $tienda->logo_url }}">@endif
<meta name="theme-color" content="{{ $cBg }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:{{ $cBg }};--card:{{ $cCard }};--ink:{{ $cInk }};--mut:{{ $cMut }};--acc:{{ $cAcc }};--on:{{ $cOnAcc }};--line:{{ $cLine }};--wa:#25d366}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;scroll-padding-top:74px;background:var(--bg)}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;padding-bottom:110px}
img{color:transparent;font-size:0}
a{color:inherit}
.wrap{max-width:820px;margin:0 auto;padding:0 16px}
.serif{font-family:'Fraunces',Georgia,serif;letter-spacing:-.01em}

/* Encabezado */
.hero{background:{{ $cHero }};padding:34px 0 26px;text-align:center;position:relative}
.hero .lg{width:92px;height:92px;border-radius:50%;margin:0 auto 14px;display:grid;place-items:center;background:var(--card);border:2px solid var(--acc);overflow:hidden;font:800 2.2rem 'Fraunces',serif;color:var(--acc);box-shadow:0 14px 30px -16px rgba(0,0,0,.6)}
.hero .lg img{width:100%;height:100%;object-fit:cover}
.hero .eti{display:inline-flex;align-items:center;gap:10px;font-size:.72rem;font-weight:800;letter-spacing:.28em;text-transform:uppercase;color:var(--acc)}
.hero .eti::before,.hero .eti::after{content:"";width:26px;height:1.5px;background:var(--acc);opacity:.7}
.hero h1{font-size:clamp(2rem,8vw,3.2rem);font-weight:800;line-height:1.05;margin:8px 0 8px}
.hero p{color:var(--mut);max-width:520px;margin:0 auto;line-height:1.5}
.hero .datos{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:16px}
.hero .datos span,.hero .datos a{border:1px solid var(--line);background:color-mix(in srgb,var(--card) 70%,transparent);border-radius:999px;padding:.35rem .8rem;font-size:.8rem;font-weight:700;text-decoration:none}
.hero .datos a.wa{background:var(--wa);border-color:var(--wa);color:#fff}

/* Categorías */
.nav{position:sticky;top:0;z-index:30;background:color-mix(in srgb,var(--bg) 92%,transparent);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.nav .wrap{display:flex;gap:8px;overflow-x:auto;padding:12px 16px;scrollbar-width:none}
.nav .wrap::-webkit-scrollbar{display:none}
.nav a{flex:none;text-decoration:none;font-weight:800;font-size:.86rem;padding:.45rem .95rem;border-radius:999px;border:1.5px solid var(--line);color:var(--mut);white-space:nowrap}
.nav a.on{background:var(--acc);border-color:var(--acc);color:var(--on)}
.buscar{margin:18px 0 4px;display:flex;align-items:center;gap:8px;background:var(--card);border:1.5px solid var(--line);border-radius:16px;padding:0 14px}
.buscar input{flex:1;min-width:0;border:0;outline:none;background:transparent;color:var(--ink);font:inherit;font-weight:600;font-size:1rem;padding:.8rem 0}
.buscar input::placeholder{color:var(--mut)}

/* Secciones y platos */
.sec{padding-top:26px}
.sec h2{font-size:1.7rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:6px}
.sec h2::after{content:"";flex:1;height:1.5px;background:linear-gradient(90deg,var(--acc),transparent);opacity:.6}
.sec .n{color:var(--mut);font-size:.8rem;font-weight:700;margin-bottom:8px}
.plato{display:flex;gap:14px;align-items:flex-start;padding:16px 0;border-bottom:1px dashed var(--line)}
.plato:last-child{border-bottom:0}
.plato .tx{flex:1;min-width:0}
.plato .fila{display:flex;align-items:baseline;gap:8px}
.plato h3{font-size:1.06rem;font-weight:800;line-height:1.25}
.plato .pts{flex:1;border-bottom:2px dotted var(--line);transform:translateY(-4px);min-width:16px}
.plato .pr{font-weight:800;color:var(--acc);white-space:nowrap;font-size:1.04rem}
.plato .pr s{color:var(--mut);font-weight:600;font-size:.8rem;margin-right:4px}
.plato .ds{color:var(--mut);font-size:.88rem;line-height:1.45;margin-top:4px}
.plato .ops{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.plato .ops button,.plato .add{font:inherit;cursor:pointer;border:1.5px solid var(--acc);background:transparent;color:var(--acc);border-radius:999px;padding:.38rem .85rem;font-weight:800;font-size:.82rem}
.plato .ops button:active,.plato .add:active{transform:scale(.96)}
.plato .add{margin-top:10px;background:var(--acc);color:var(--on)}
.plato .ft{position:relative;width:92px;height:92px;flex:none;border-radius:18px;overflow:hidden;background:var(--card);border:1px solid var(--line)}
.plato .ft img{width:100%;height:100%;object-fit:cover}
.plato .ft .pm{position:absolute;left:6px;top:6px;background:#e11d48;color:#fff;border-radius:999px;padding:.1rem .45rem;font-size:.68rem;font-weight:900}
.plato .q{display:inline-flex;align-items:center;gap:10px;margin-top:10px;background:var(--acc);color:var(--on);border-radius:999px;padding:.2rem .3rem;font-weight:800}
.plato .q button{width:30px;height:30px;border-radius:50%;border:0;background:rgba(0,0,0,.18);color:inherit;font:800 1.1rem/1 inherit;cursor:pointer}
.plato.off{opacity:.5}
.plato.off h3{text-decoration:line-through;text-decoration-thickness:1.5px}
.plato .agot{display:inline-block;margin-top:8px;border:1.5px solid var(--mut);color:var(--mut);border-radius:999px;padding:.2rem .7rem;font-size:.74rem;font-weight:800}
.vacio{text-align:center;color:var(--mut);padding:50px 10px}

/* Pie */
.pie{text-align:center;color:var(--mut);font-size:.84rem;padding:40px 16px 10px;line-height:1.7}
.pie b{color:var(--ink)}
.pie a{color:var(--acc);font-weight:800;text-decoration:none}

/* Barra y hoja del pedido */
.bar{position:fixed;left:50%;bottom:calc(14px + env(safe-area-inset-bottom));transform:translate(-50%,140%);width:min(560px,calc(100% - 24px));z-index:50;display:flex;justify-content:space-between;align-items:center;gap:10px;background:var(--acc);color:var(--on);border:0;border-radius:999px;padding:.95rem 1.3rem;font:800 1rem 'Plus Jakarta Sans',sans-serif;cursor:pointer;box-shadow:0 18px 36px -14px rgba(0,0,0,.6);transition:transform .25s}
.bar.on{transform:translate(-50%,0)}
.bar .c{display:inline-grid;place-items:center;min-width:26px;height:26px;border-radius:999px;background:rgba(0,0,0,.2);margin-right:8px;font-size:.85rem}
.ov{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:60;display:none;align-items:flex-end;justify-content:center}
.ov.on{display:flex}
.sheet{background:var(--card);color:var(--ink);width:min(560px,100%);max-height:88vh;overflow:auto;border-radius:26px 26px 0 0;padding:20px 18px calc(22px + env(safe-area-inset-bottom))}
@media (min-width:600px){.ov{align-items:center}.sheet{border-radius:26px}}
.sheet .cab{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.sheet h3{font-size:1.4rem;font-weight:800}
.sheet .x{border:0;background:transparent;color:var(--mut);font-size:1.8rem;cursor:pointer;line-height:1}
.it{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px dashed var(--line)}
.it small{display:block;color:var(--mut);font-weight:600}
.it .q{display:flex;align-items:center;gap:8px}
.it .q button{width:30px;height:30px;border-radius:50%;border:1.5px solid var(--line);background:transparent;color:var(--ink);font-weight:800;cursor:pointer}
.tot{display:flex;justify-content:space-between;font-weight:800;font-size:1.15rem;margin:14px 0}
.modos{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:8px}
.modos button{font:inherit;font-weight:800;font-size:.82rem;padding:.6rem .3rem;border-radius:14px;border:1.5px solid var(--line);background:transparent;color:var(--ink);cursor:pointer}
.modos button.on{background:var(--acc);border-color:var(--acc);color:var(--on)}
.in{width:100%;font:inherit;font-weight:600;color:var(--ink);background:var(--bg);border:1.5px solid var(--line);border-radius:14px;padding:.75rem .9rem;margin-top:8px;outline:none}
.enviar{width:100%;margin-top:14px;border:0;border-radius:999px;padding:1rem;font:800 1.02rem 'Plus Jakarta Sans',sans-serif;background:var(--wa);color:#fff;cursor:pointer}
.enviar:disabled{opacity:.5}
.toast{position:fixed;left:50%;top:18px;transform:translate(-50%,-160%);z-index:70;background:var(--ink);color:var(--bg);font-weight:800;font-size:.9rem;border-radius:999px;padding:.6rem 1.1rem;transition:transform .25s;max-width:calc(100% - 32px)}
.toast.on{transform:translate(-50%,0)}
.foto-rota{display:grid;place-items:center;width:100%;height:100%;font:800 1.8rem 'Fraunces',serif;color:var(--acc)}
</style>
<script>
document.addEventListener('error', function (e) {
    var i = e.target;
    if (!i || i.tagName !== 'IMG' || i.dataset.rota) return;
    i.dataset.rota = 1;
    var s = document.createElement('span'); s.className = 'foto-rota';
    s.textContent = ((i.getAttribute('alt') || '').trim().charAt(0) || '·').toUpperCase();
    i.replaceWith(s);
}, true);
</script>
</head>
<body class="{{ $oscuro ? 'oscuro' : '' }}">

<header class="hero">
    <div class="wrap">
        <div class="lg">@if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="{{ $tienda->nombre }}">@else{{ mb_strtoupper(mb_substr($tienda->nombre, 0, 1)) }}@endif</div>
        <span class="eti">Nuestra carta</span>
        <h1 class="serif">{{ $tienda->nombre }}</h1>
        @if ($tienda->descripcion)<p>{{ $tienda->descripcion }}</p>@endif
        <div class="datos">
            @if ($tienda->direccion || $lugar)<span>📍 {{ trim($tienda->direccion . ($tienda->direccion && $lugar ? ' · ' : '') . $lugar) }}</span>@endif
            @if ($wa)<a class="wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">💬 Escríbanos</a>@endif
        </div>
    </div>
</header>

@if (count($secciones) > 1)
<nav class="nav" id="nav"><div class="wrap">
    @foreach (array_keys($secciones) as $i => $cat)
    <a href="#c{{ $i }}" data-c="{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}">{{ $cat }}</a>
    @endforeach
</div></nav>
@endif

<main class="wrap">
    @if ($total > 8)
    <label class="buscar"><span>🔎</span><input id="q" type="search" placeholder="¿Qué se le antoja?" autocomplete="off"></label>
    @endif

    @forelse ($secciones as $cat => $platos)
    <section class="sec" id="c{{ $loop->index }}" data-c="{{ $loop->index }}">
        <h2 class="serif">{{ $cat }}</h2>
        @foreach ($platos as $p)
        @php $varias = count($p['variantes']) > 1; @endphp
        <article class="plato {{ $p['disponible'] ? '' : 'off' }}" data-q="{{ mb_strtolower($p['nombre'] . ' ' . $p['descripcion'] . ' ' . $cat) }}" data-id="{{ $p['id'] }}">
            <div class="tx">
                <div class="fila">
                    <h3>{{ $p['nombre'] }}</h3><span class="pts"></span>
                    <span class="pr">@if ($p['antes'])<s>{{ dinero($p['antes']) }}</s>@endif{{ $p['desde'] ? 'Desde ' . dinero($p['desde']) : dinero($p['precio']) }}</span>
                </div>
                @if ($p['descripcion'])<p class="ds">{{ $p['descripcion'] }}</p>@endif
                @if (!$p['disponible'])
                    <span class="agot">{{ $p['desactivado'] ? 'Agotado por hoy' : 'Agotado' }}</span>
                @elseif ($varias)
                    <div class="ops">@foreach ($p['variantes'] as $v)<button type="button" data-v="{{ $v['id'] }}">+ {{ $v['etiqueta'] }} · {{ dinero($v['precio']) }}</button>@endforeach</div>
                @else
                    <span class="ctl" data-v="{{ $p['variantes'][0]['id'] ?? '' }}"><button type="button" class="add" data-v="{{ $p['variantes'][0]['id'] ?? '' }}">+ Agregar</button></span>
                @endif
            </div>
            @if ($p['img'])
            <div class="ft"><img src="{{ $p['img'] }}" alt="{{ $p['nombre'] }}" loading="lazy">@if ($p['descuento'])<span class="pm">-{{ $p['descuento'] }}%</span>@endif</div>
            @endif
        </article>
        @endforeach
    </section>
    @empty
    <div class="vacio"><b class="serif" style="font-size:1.4rem;display:block;color:var(--ink)">Muy pronto nuestra carta</b>Estamos preparando el menú. Escríbanos para pedir.</div>
    @endforelse
    <div class="vacio" id="nada" style="display:none">No encontramos ese plato. Pruebe con otra palabra.</div>
</main>

<footer class="pie">
    <div><b>{{ $tienda->nombre }}</b>@if ($lugar) · {{ $lugar }}@endif</div>
    <div><a href="{{ $tienda->url_catalogo }}">Ver nuestra tienda en línea</a></div>
    <div style="margin-top:10px;font-size:.76rem">Carta virtual creada con <b>LocalProo</b> · <a href="{{ (app()->environment('production') && config('app.dominio') ? 'https://' . config('app.dominio') . '/registro' : route('registro')) . '?desde=' . rawurlencode('carta:' . \Illuminate\Support\Str::limit($tienda->slug, 70, '')) . '&tipo=restaurante' }}">Cree la carta de su restaurante</a></div>
</footer>

<button class="bar" id="bar"><span><span class="c" id="barN">0</span>Ver mi pedido</span><span id="barT"></span></button>
<div class="toast" id="toast"></div>

<div class="ov" id="ov"><div class="sheet">
    <div class="cab"><h3 class="serif">Mi pedido</h3><button class="x" id="cerrar" aria-label="Cerrar">&times;</button></div>
    <div id="items"></div>
    <div class="tot"><span>Total</span><span id="tot"></span></div>
    <div class="modos" id="modos">
        <button type="button" data-m="Domicilio" class="on">🛵 Domicilio</button>
        <button type="button" data-m="Para recoger">🥡 Recoger</button>
        <button type="button" data-m="En la mesa">🍽️ En la mesa</button>
    </div>
    <input class="in" id="nom" placeholder="Su nombre" maxlength="60">
    <input class="in" id="dir" placeholder="Dirección de entrega" maxlength="160">
    <input class="in" id="nota" placeholder="Nota: sin cebolla, salsa aparte… (opcional)" maxlength="200">
    @if ($wa)
    <button class="enviar" id="enviar">💬 Enviar pedido por WhatsApp</button>
    @else
    <p style="color:#e11d48;font-weight:700;margin-top:12px">Este negocio aún no tiene WhatsApp configurado.</p>
    @endif
</div></div>

<script>
(function () {
    var M = @json($M), WA = @json($wa), NEG = @json($tienda->nombre), KEY = 'lp_carta_' + @json($tienda->slug);
    var P = @json($datos), TASA_BS = {{ tasa_bs() }};
    var $ = function (id) { return document.getElementById(id); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    var bs = function (n) { if (!(TASA_BS > 0)) return ''; var t = (Math.round(n * TASA_BS * 100) / 100).toFixed(2).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); return ' · Bs ' + t.join(','); };
    var VAR = {}; Object.keys(P).forEach(function (k) { P[k].variantes.forEach(function (v) { VAR[v.id] = { p: P[k], v: v }; }); });
    var cart = []; try { cart = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { cart = []; }
    cart = cart.filter(function (i) { return VAR[i.id]; }); // lo desactivado o borrado sale solo del pedido
    var modo = 'Domicilio';
    function guardar() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }
    function total() { return cart.reduce(function (a, i) { return a + i.precio * i.cant; }, 0); }
    function toast(t) { var x = $('toast'); x.textContent = t; x.classList.add('on'); clearTimeout(x._t); x._t = setTimeout(function () { x.classList.remove('on'); }, 1700); }
    function cantDe(id) { var i = cart.find(function (x) { return x.id === id; }); return i ? i.cant : 0; }
    function controles() {
        document.querySelectorAll('.ctl').forEach(function (c) {
            var id = +c.dataset.v, n = cantDe(id);
            c.innerHTML = n ? '<span class="q"><button type="button" data-d="-1" data-v="' + id + '">−</button>' + n + '<button type="button" data-d="1" data-v="' + id + '">+</button></span>'
                : '<button type="button" class="add" data-v="' + id + '">+ Agregar</button>';
        });
    }
    function barra() {
        var n = cart.reduce(function (a, i) { return a + i.cant; }, 0);
        $('bar').classList.toggle('on', n > 0); $('barN').textContent = n; $('barT').textContent = fmt(total());
        controles();
    }
    function pintar() {
        $('items').innerHTML = cart.length ? cart.map(function (i, k) {
            return '<div class="it"><div><b>' + esc(i.nombre) + '</b><small>' + (i.op ? esc(i.op) + ' · ' : '') + fmt(i.precio) + '</small></div>' +
                '<div class="q"><button data-k="' + k + '" data-d="-1">−</button><b>' + i.cant + '</b><button data-k="' + k + '" data-d="1">+</button></div></div>';
        }).join('') : '<p style="color:var(--mut)">Su pedido está vacío.</p>';
        $('tot').textContent = fmt(total()) + bs(total());
        if ($('enviar')) $('enviar').disabled = !cart.length;
    }
    function sumar(id, d) {
        var x = VAR[id]; if (!x) return;
        var i = cart.find(function (c) { return c.id === id; });
        if (i) i.cant += d; else if (d > 0) { i = { id: id, nombre: x.p.nombre, op: x.p.variantes.length > 1 ? x.v.etiqueta : '', precio: x.v.precio, cant: d }; cart.push(i); }
        if (i && i.cant <= 0) cart.splice(cart.indexOf(i), 1);
        if (d > 0) toast('✓ ' + x.p.nombre + ' agregado');
        guardar(); barra();
    }
    document.querySelector('main').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-v]'); if (!b || !b.dataset.v) return;
        sumar(+b.dataset.v, +(b.dataset.d || 1));
    });
    $('items').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-k]'); if (!b) return;
        var i = cart[+b.dataset.k]; i.cant += +b.dataset.d; if (i.cant <= 0) cart.splice(+b.dataset.k, 1);
        guardar(); barra(); pintar();
    });
    $('modos').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        modo = b.dataset.m;
        [].forEach.call(this.children, function (x) { x.classList.toggle('on', x === b); });
        $('dir').placeholder = modo === 'Domicilio' ? 'Dirección de entrega' : modo === 'En la mesa' ? 'Número de mesa' : 'Hora en que pasa a recoger (opcional)';
    });
    $('bar').addEventListener('click', function () { pintar(); $('ov').classList.add('on'); });
    $('cerrar').addEventListener('click', function () { $('ov').classList.remove('on'); });
    $('ov').addEventListener('click', function (e) { if (e.target === this) this.classList.remove('on'); });
    if ($('enviar')) $('enviar').addEventListener('click', function () {
        var nom = $('nom').value.trim(), dir = $('dir').value.trim(), nota = $('nota').value.trim();
        if (modo === 'Domicilio' && !dir) { $('dir').focus(); toast('Escriba la dirección de entrega'); return; }
        var msg = '¡Hola ' + NEG + '! Quiero hacer este pedido de la carta:\n\n' +
            cart.map(function (i) { return '- ' + i.cant + ' × ' + i.nombre + (i.op ? ' (' + i.op + ')' : '') + ' — ' + fmt(i.precio * i.cant); }).join('\n') +
            '\n\n*Total: ' + fmt(total()) + bs(total()) + '*\n' + modo + (dir ? ': ' + dir : '') + (nom ? '\nNombre: ' + nom : '') + (nota ? '\nNota: ' + nota : '') +
            '\n\n_Pedido desde la carta virtual de LocalProo_';
        window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
    });

    // Buscador
    if ($('q')) $('q').addEventListener('input', function () {
        var t = this.value.trim().toLowerCase(), hay = false;
        document.querySelectorAll('.sec').forEach(function (s) {
            var vis = 0;
            s.querySelectorAll('.plato').forEach(function (p) { var ok = !t || p.dataset.q.indexOf(t) > -1; p.style.display = ok ? '' : 'none'; if (ok) vis++; });
            s.style.display = vis ? '' : 'none'; if (vis) hay = true;
        });
        $('nada').style.display = hay ? 'none' : '';
    });

    // La categoría que se está viendo queda marcada arriba
    var nav = $('nav');
    if (nav && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (en) {
                if (!en.isIntersecting) return;
                nav.querySelectorAll('a').forEach(function (a) { a.classList.toggle('on', a.dataset.c === en.target.dataset.c); });
                var on = nav.querySelector('a.on'); if (on) on.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            });
        }, { rootMargin: '-80px 0px -70% 0px' });
        document.querySelectorAll('.sec').forEach(function (s) { io.observe(s); });
    }
    barra();
})();
</script>
</body>
</html>
