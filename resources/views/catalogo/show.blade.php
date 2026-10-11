<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $tienda->nombre }} · Catálogo</title>
<meta name="description" content="{{ $tienda->descripcion ?: 'Catálogo de ' . $tienda->nombre }}. Pida por WhatsApp.">
<meta property="og:title" content="{{ $tienda->nombre }}">
<meta property="og:description" content="{{ $tienda->descripcion ?: 'Vea nuestros productos y pida por WhatsApp' }}">
@if ($tienda->logo_url)<meta property="og:image" content="{{ $tienda->logo_url }}">@endif
<meta name="theme-color" content="#0b0d0c">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--g:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);--g2:#16b34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--wa:#25d366}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;padding-bottom:96px}
button{font:inherit;border:0;background:none;cursor:pointer;color:inherit}
.hd{background:var(--k);color:#fff;padding:28px 16px 70px;position:relative;overflow:hidden}
.hd::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(47,209,90,.45),transparent 70%);right:-90px;top:-120px}
.wrap{width:min(1100px,100%);margin:0 auto}
.hd .wrap{display:flex;gap:16px;align-items:center;position:relative;z-index:1}
.logo{width:76px;height:76px;border-radius:22px;background:#fff;display:grid;place-items:center;overflow:hidden;flex:none;font-weight:800;font-size:2rem;color:var(--g2)}
.logo img{width:100%;height:100%;object-fit:cover}
.hd h1{font-size:clamp(1.5rem,5vw,2.2rem);font-weight:800;letter-spacing:-.03em;line-height:1.1}
.hd p{color:#a9b5ad;font-size:.92rem;margin-top:4px}
.panel{width:min(1100px,100% - 24px);margin:-46px auto 0;position:relative;z-index:2}
.search{background:#fff;border-radius:20px;box-shadow:0 20px 40px -24px rgba(0,0,0,.35);padding:10px;display:flex;gap:8px;align-items:center}
.search input{flex:1;border:0;outline:none;font:inherit;font-weight:600;font-size:1rem;padding:.6rem .7rem;min-width:0}
.cats{display:flex;gap:8px;overflow-x:auto;padding:14px 2px 4px;scrollbar-width:none}
.cats button{white-space:nowrap;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:.5rem .95rem;font-weight:700;font-size:.88rem}
.cats button.on{background:var(--k);color:#fff;border-color:var(--k)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-top:12px}
.card{background:#fff;border:1.5px solid var(--line);border-radius:20px;overflow:hidden;text-align:left;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
.card:hover{transform:translateY(-3px);box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.card.off{opacity:.5}
.ph{aspect-ratio:1/1;background:var(--bg);display:grid;place-items:center;font-size:2.4rem;font-weight:800;color:var(--g2);overflow:hidden}
.ph img{width:100%;height:100%;object-fit:cover}
.card .b{padding:10px 12px 12px;display:flex;flex-direction:column;gap:4px}
.card b{font-size:.92rem;line-height:1.25}
.card .pr{font-weight:800;font-size:1.02rem}
.card small{color:var(--mut);font-size:.75rem;font-weight:600}
.agot{color:#b0206a!important}
.empty{text-align:center;color:var(--mut);padding:50px 10px;font-weight:600;grid-column:1/-1}
.ov{position:fixed;inset:0;background:rgba(11,13,12,.55);display:none;align-items:flex-end;justify-content:center;z-index:40}
.ov.on{display:flex}
.sheet{background:#fff;width:min(560px,100%);max-height:88vh;overflow:auto;border-radius:26px 26px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));animation:up .25s cubic-bezier(.2,.8,.2,1)}
@keyframes up{from{transform:translateY(40px);opacity:0}}
@media(min-width:700px){.ov{align-items:center}.sheet{border-radius:26px}}
.sh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}
.sh h3{font-size:1.25rem;font-weight:800}
.x{width:38px;height:38px;border-radius:12px;background:var(--bg);font-size:1.2rem;flex:none}
.big{width:100%;aspect-ratio:4/3;border-radius:18px;background:var(--bg);overflow:hidden;margin-bottom:12px;display:grid;place-items:center;font-size:3rem;font-weight:800;color:var(--g2)}
.big img{width:100%;height:100%;object-fit:cover}
.lbl{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);margin:12px 0 8px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:2px solid var(--line);border-radius:14px;padding:.55rem .85rem;font-weight:800}
.chip.on{background:var(--k);border-color:var(--k);color:#fff}
.qty{display:flex;align-items:center;gap:10px;margin-top:14px}
.qty button{width:42px;height:42px;border-radius:14px;background:var(--bg);font-size:1.3rem;font-weight:800}
.qty span{font-weight:800;font-size:1.2rem;min-width:28px;text-align:center}
.btn{width:100%;height:56px;border-radius:18px;font-weight:800;font-size:1.05rem;margin-top:16px;display:flex;align-items:center;justify-content:center;gap:.5rem}
.btn-g{background:var(--g);color:var(--k)}
.btn-wa{background:var(--wa);color:#fff}
.btn:disabled{opacity:.4}
.bar{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));height:64px;border-radius:20px;background:var(--k);color:#fff;display:none;align-items:center;justify-content:space-between;padding:0 18px;font-weight:800;z-index:30;box-shadow:0 18px 40px -16px rgba(0,0,0,.6);max-width:640px;margin:0 auto}
.bar.on{display:flex}
.bar .n{background:var(--g);color:var(--k);border-radius:999px;padding:.15rem .6rem;margin-right:.5rem}
.it{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px dashed var(--line)}
.it .t{flex:1;min-width:0}
.it .t b{display:block;font-size:.92rem}
.it .t small{color:var(--mut);font-weight:600}
.it .q{display:flex;align-items:center;background:var(--bg);border-radius:12px}
.it .q button{width:32px;height:32px;font-weight:800}
.tot{display:flex;justify-content:space-between;font-weight:800;font-size:1.2rem;margin-top:12px}
.in{width:100%;border:2px solid var(--line);border-radius:14px;padding:.75rem .9rem;font:inherit;font-weight:600;margin-top:8px}
.ft{text-align:center;color:var(--mut);font-size:.8rem;padding:30px 0 10px}
.ft a{color:var(--g2);font-weight:800;text-decoration:none}
</style>
</head>
<body>
<header class="hd">
    <div class="wrap">
        <div class="logo">@if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="{{ $tienda->nombre }}">@else{{ mb_substr($tienda->nombre, 0, 1) }}@endif</div>
        <div>
            <h1>{{ $tienda->nombre }}</h1>
            <p>{{ $tienda->descripcion ?: $tienda->tipo_nombre }}{{ $tienda->ciudad ? ' · ' . $tienda->ciudad . ($tienda->departamento && $tienda->departamento !== $tienda->ciudad ? ', ' . $tienda->departamento : '') : '' }}</p>
        </div>
    </div>
</header>

<main class="panel">
    <div class="search"><span style="padding-left:6px">🔎</span><input id="q" type="search" placeholder="¿Qué está buscando?" autocomplete="off"></div>
    @if ($categorias->count())
    <div class="cats" id="cats"><button class="on" data-c="">Todo</button>@foreach ($categorias as $c)<button data-c="{{ $c }}">{{ $c }}</button>@endforeach</div>
    @endif
    <div class="grid" id="grid"></div>
    <p class="ft">Catálogo creado con <a href="{{ url('/') }}">LocalPro</a> · Desarrollado por Jhoset Mejía - DiCreWeb</p>
</main>

<button class="bar" id="bar"><span><span class="n" id="barN">0</span>Ver pedido</span><span id="barT"></span></button>

<div class="ov" id="ovP"><div class="sheet">
    <div class="sh"><div><h3 id="pNom"></h3><small id="pPrecio" style="font-weight:800;font-size:1.1rem"></small></div><button class="x" data-close>&times;</button></div>
    <div class="big" id="pImg"></div>
    <p id="pDesc" style="color:var(--mut)"></p>
    <div id="pVarW"><div class="lbl" id="pVarLbl">Elija una opción</div><div class="chips" id="pVars"></div></div>
    <div class="qty"><button id="qMenos">−</button><span id="qN">1</span><button id="qMas">+</button></div>
    <button class="btn btn-g" id="pAdd">Agregar al pedido</button>
</div></div>

<div class="ov" id="ovC"><div class="sheet">
    <div class="sh"><h3>Su pedido</h3><button class="x" data-close>&times;</button></div>
    <div id="cItems"></div>
    <div class="tot"><span>Total</span><span id="cTot"></span></div>
    <input class="in" id="cNom" placeholder="Su nombre" maxlength="60">
    <input class="in" id="cNota" placeholder="Nota o dirección de entrega (opcional)" maxlength="200">
    @if ($tienda->telefono)
    <button class="btn btn-wa" id="cEnviar">Pedir por WhatsApp</button>
    @else
    <p style="color:#b0206a;font-weight:700;margin-top:12px">Esta tienda aún no tiene WhatsApp configurado.</p>
    @endif
</div></div>

<script>
(function () {
    var P = @json($productos), M = @json(moneda()), WA = @json(numero_whatsapp($tienda->telefono)), TIENDA = @json($tienda->nombre);
    var KEY = 'lp_pedido_' + @json($tienda->slug);
    var $ = function (id) { return document.getElementById(id); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    var cart = []; try { cart = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { cart = []; }
    var guardar = function () { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} };
    var cat = '', actual = null, varSel = null, cant = 1;

    function pintar() {
        var q = $('q').value.trim().toLowerCase();
        var lista = P.filter(function (p) { return (!q || p.nombre.toLowerCase().indexOf(q) !== -1) && (!cat || p.categorias.indexOf(cat) !== -1); });
        $('grid').innerHTML = lista.length ? lista.map(function (p) {
            var i = P.indexOf(p);
            return '<button class="card ' + (p.disponible ? '' : 'off') + '" data-i="' + i + '"><div class="ph">' + (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : esc(p.nombre.charAt(0))) + '</div>' +
                '<div class="b"><b>' + esc(p.nombre) + '</b><span class="pr">' + fmt(p.precio) + '</span>' +
                (p.disponible ? (p.variantes.length > 1 ? '<small>' + p.variantes.length + ' opciones</small>' : '') : '<small class="agot">Agotado</small>') + '</div></button>';
        }).join('') : '<div class="empty">No encontramos productos con esa búsqueda.</div>';
    }
    $('q').addEventListener('input', pintar);
    if ($('cats')) $('cats').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        cat = b.dataset.c; document.querySelectorAll('#cats button').forEach(function (x) { x.classList.toggle('on', x === b); }); pintar();
    });
    $('grid').addEventListener('click', function (e) {
        var c = e.target.closest('.card'); if (!c) return;
        var p = P[+c.dataset.i]; if (!p.disponible) return;
        actual = p; cant = 1; varSel = p.variantes.length === 1 ? p.variantes[0] : null;
        $('pNom').textContent = p.nombre; $('pDesc').textContent = p.descripcion || '';
        $('pImg').innerHTML = p.img ? '<img src="' + esc(p.img) + '" alt="">' : esc(p.nombre.charAt(0));
        $('pVarW').style.display = p.variantes.length > 1 ? '' : 'none';
        $('pVarLbl').textContent = p.variantes.some(function (v) { return v.talla || v.color; }) ? 'Talla / color' : 'Opción';
        pintarVars(); abrir('ovP');
    });
    function pintarVars() {
        $('pVars').innerHTML = actual.variantes.map(function (v, i) { return '<button class="chip ' + (varSel === v ? 'on' : '') + '" data-v="' + i + '">' + esc(v.etiqueta) + '</button>'; }).join('');
        $('qN').textContent = cant;
        $('pPrecio').textContent = fmt(varSel ? varSel.precio : actual.precio);
        $('pAdd').disabled = !varSel;
        $('pAdd').textContent = varSel ? 'Agregar · ' + fmt(varSel.precio * cant) : 'Elija una opción';
    }
    $('pVars').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b) return; varSel = actual.variantes[+b.dataset.v]; pintarVars(); });
    $('qMenos').addEventListener('click', function () { if (cant > 1) { cant--; pintarVars(); } });
    $('qMas').addEventListener('click', function () { if (cant < 99) { cant++; pintarVars(); } });
    $('pAdd').addEventListener('click', function () {
        var ex = cart.find(function (x) { return x.id === varSel.id; });
        if (ex) ex.cant += cant; else cart.push({ id: varSel.id, nombre: actual.nombre, op: actual.variantes.length > 1 ? varSel.etiqueta : '', precio: varSel.precio, cant: cant });
        guardar(); barra(); cerrar('ovP');
    });

    function total() { return cart.reduce(function (a, i) { return a + i.precio * i.cant; }, 0); }
    function barra() {
        var n = cart.reduce(function (a, i) { return a + i.cant; }, 0);
        $('bar').classList.toggle('on', n > 0); $('barN').textContent = n; $('barT').textContent = fmt(total());
    }
    function pintarCarrito() {
        $('cItems').innerHTML = cart.length ? cart.map(function (i, k) {
            return '<div class="it"><div class="t"><b>' + esc(i.nombre) + '</b><small>' + esc(i.op) + (i.op ? ' · ' : '') + fmt(i.precio) + '</small></div>' +
                '<div class="q"><button data-k="' + k + '" data-d="-1">−</button><span style="min-width:22px;text-align:center;font-weight:800">' + i.cant + '</span><button data-k="' + k + '" data-d="1">+</button></div></div>';
        }).join('') : '<p style="color:var(--mut)">Su pedido está vacío.</p>';
        $('cTot').textContent = fmt(total());
        if ($('cEnviar')) $('cEnviar').disabled = !cart.length;
    }
    $('cItems').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var i = cart[+b.dataset.k]; i.cant += +b.dataset.d; if (i.cant <= 0) cart.splice(+b.dataset.k, 1);
        guardar(); barra(); pintarCarrito();
    });
    $('bar').addEventListener('click', function () { pintarCarrito(); abrir('ovC'); });
    if ($('cEnviar')) $('cEnviar').addEventListener('click', function () {
        var nom = $('cNom').value.trim(), nota = $('cNota').value.trim();
        var msg = '¡Hola ' + TIENDA + '! 👋 Quiero hacer este pedido:\n\n' +
            cart.map(function (i) { return '• ' + i.cant + ' × ' + i.nombre + (i.op ? ' (' + i.op + ')' : '') + ' — ' + fmt(i.precio * i.cant); }).join('\n') +
            '\n\n*Total: ' + fmt(total()) + '*' + (nom ? '\nNombre: ' + nom : '') + (nota ? '\nNota: ' + nota : '');
        window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
    });

    function abrir(id) { $(id).classList.add('on'); }
    function cerrar(id) { $(id).classList.remove('on'); }
    document.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', function () { cerrar(b.closest('.ov').id); }); });
    document.querySelectorAll('.ov').forEach(function (o) { o.addEventListener('click', function (e) { if (e.target === o) cerrar(o.id); }); });

    pintar(); barra();
})();
</script>
</body>
</html>
