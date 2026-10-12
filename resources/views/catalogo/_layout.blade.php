@php
    $M = moneda();
    $wa = numero_whatsapp($tienda->telefono);
    $lugar = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo')</title>
<meta name="description" content="@yield('descripcion')">
<link rel="canonical" href="@yield('canonical')">
<meta name="robots" content="{{ $tienda->en_google ? 'index, follow, max-image-preview:large' : 'noindex, follow' }}">
<meta property="og:type" content="@yield('og_tipo', 'website')">
<meta property="og:site_name" content="{{ $tienda->nombre }}">
<meta property="og:title" content="@yield('titulo')">
<meta property="og:description" content="@yield('descripcion')">
<meta property="og:url" content="@yield('canonical')">
@hasSection('imagen')<meta property="og:image" content="@yield('imagen')">@elseif ($tienda->logo_url)<meta property="og:image" content="{{ $tienda->logo_url }}">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0b0d0c">
@if ($tienda->logo_url)<link rel="icon" href="{{ $tienda->logo_url }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
@stack('jsonld')
<style>
:root{--g:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);--g2:#16b34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--wa:#25d366}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;scroll-padding-top:130px}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;padding-bottom:96px}
a{color:inherit;text-decoration:none}
button{font:inherit;border:0;background:none;cursor:pointer;color:inherit}
.wrap{width:min(1180px,100% - 24px);margin:0 auto}
/* Barra superior */
.top{position:sticky;top:0;z-index:30;background:rgba(11,13,12,.94);backdrop-filter:blur(10px);color:#fff}
.top .wrap{display:flex;align-items:center;gap:12px;height:64px}
.top .marca{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
.top .lg{width:40px;height:40px;border-radius:12px;background:#fff;display:grid;place-items:center;overflow:hidden;flex:none;font-weight:800;color:var(--g2)}
.top .lg img{width:100%;height:100%;object-fit:cover}
.top b{font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top .acc{display:flex;gap:8px}
.top .ib{height:40px;border-radius:12px;background:rgba(255,255,255,.1);display:flex;align-items:center;gap:6px;padding:0 12px;font-weight:700;font-size:.88rem;color:#fff;position:relative}
.top .ib.wa{background:var(--wa)}
.top .ib .n{position:absolute;top:-5px;right:-5px;background:#a6f34a;color:var(--k);border-radius:999px;font-size:.7rem;font-weight:800;min-width:20px;height:20px;display:none;place-items:center;padding:0 5px}
.top .ib .n.on{display:grid}
@media(max-width:520px){.top .d-sm{display:none}}
/* Tarjetas de producto */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
@media(max-width:520px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}}
.card{background:#fff;border:1.5px solid var(--line);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;min-width:0}
.card:hover{transform:translateY(-3px);box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.card.off{opacity:.55}
.card .ph{aspect-ratio:1/1;background:var(--bg);display:grid;place-items:center;font-size:2.6rem;font-weight:800;color:var(--g2);overflow:hidden;position:relative}
.card .ph img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.card:hover .ph img{transform:scale(1.04)}
.card .tag{position:absolute;left:10px;top:10px;background:#fff;border-radius:999px;padding:.2rem .6rem;font-size:.7rem;font-weight:800;box-shadow:0 4px 10px -6px rgba(0,0,0,.4)}
.card .tag.agot{background:#b0206a;color:#fff}
.card .b{padding:10px 12px 12px;display:flex;flex-direction:column;gap:4px;flex:1}
.card h3{font-size:.95rem;font-weight:700;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card .pr{font-weight:800;font-size:1.08rem}
.card .pr small{font-weight:700;color:var(--mut);font-size:.72rem;margin-right:3px}
.card .op{color:var(--mut);font-size:.75rem;font-weight:600}
.card .bt{display:flex;gap:6px;margin-top:auto;padding-top:8px}
.btn-c{flex:1;height:42px;border-radius:13px;background:var(--wa);color:#fff;font-weight:800;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-c:hover{filter:brightness(1.05)}
.btn-car{width:42px;height:42px;border-radius:13px;background:var(--bg);font-size:1.15rem;font-weight:800;flex:none;display:grid;place-items:center}
.btn-car:hover{background:#e3efe5}
/* Carrito */
.ov{position:fixed;inset:0;background:rgba(11,13,12,.55);display:none;align-items:flex-end;justify-content:center;z-index:40}
.ov.on{display:flex}
.sheet{background:#fff;width:min(560px,100%);max-height:88vh;overflow:auto;border-radius:26px 26px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));animation:up .25s cubic-bezier(.2,.8,.2,1)}
@keyframes up{from{transform:translateY(40px);opacity:0}}
@media(min-width:700px){.ov{align-items:center}.sheet{border-radius:26px}}
.sh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}
.sh h3{font-size:1.25rem;font-weight:800}
.x{width:38px;height:38px;border-radius:12px;background:var(--bg);font-size:1.2rem;flex:none}
.it{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px dashed var(--line)}
.it .t{flex:1;min-width:0}.it .t b{display:block;font-size:.92rem}.it .t small{color:var(--mut);font-weight:600}
.it .q{display:flex;align-items:center;background:var(--bg);border-radius:12px}.it .q button{width:32px;height:32px;font-weight:800}
.tot{display:flex;justify-content:space-between;font-weight:800;font-size:1.2rem;margin-top:12px}
.in{width:100%;border:2px solid var(--line);border-radius:14px;padding:.75rem .9rem;font:inherit;font-weight:600;margin-top:8px}
.btn{width:100%;height:56px;border-radius:18px;font-weight:800;font-size:1.05rem;margin-top:14px;display:flex;align-items:center;justify-content:center;gap:.5rem}
.btn-wa{background:var(--wa);color:#fff}.btn-g{background:var(--g);color:var(--k)}.btn-l{background:var(--bg);color:var(--k)}
.btn:disabled{opacity:.4}
.bar{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));height:64px;border-radius:20px;background:var(--k);color:#fff;display:none;align-items:center;justify-content:space-between;padding:0 18px;font-weight:800;z-index:30;box-shadow:0 18px 40px -16px rgba(0,0,0,.6);max-width:640px;margin:0 auto}
.bar.on{display:flex}
.bar .n{background:var(--g);color:var(--k);border-radius:999px;padding:.15rem .6rem;margin-right:.5rem}
.toast{position:fixed;left:50%;top:76px;transform:translate(-50%,-10px);background:var(--k);color:#fff;border-radius:14px;padding:.7rem 1rem;font-weight:700;opacity:0;transition:all .25s;z-index:50;pointer-events:none}
.toast.on{opacity:1;transform:translate(-50%,0)}
/* Pie */
.pie{background:var(--k);color:#a9b5ad;margin-top:40px;padding:30px 0 40px;font-size:.88rem}
.pie .wrap{display:flex;gap:24px;flex-wrap:wrap;justify-content:space-between}
.pie b{color:#fff;display:block;margin-bottom:6px;font-size:1rem}
.pie a{color:#a6f34a;font-weight:700}
.pie .lp{width:100%;text-align:center;border-top:1px solid rgba(255,255,255,.1);padding-top:18px;margin-top:6px;font-size:.78rem}
@stack('css')
</style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a class="marca" href="{{ $tienda->url_catalogo }}">
            <span class="lg">@if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="{{ $tienda->nombre }}">@else{{ mb_substr($tienda->nombre, 0, 1) }}@endif</span>
            <b>{{ $tienda->nombre }}</b>
        </a>
        <div class="acc">
            @if ($wa)<a class="ib wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="WhatsApp">💬 <span class="d-sm">WhatsApp</span></a>@endif
            <button class="ib" id="btnCarrito" aria-label="Carrito">🛒<span class="n" id="topN">0</span></button>
        </div>
    </div>
</header>

@yield('contenido')

<footer class="pie">
    <div class="wrap">
        <div><b>{{ $tienda->nombre }}</b>{{ $tienda->descripcion ?: $tienda->tipo_nombre }}</div>
        <div>
            @if ($tienda->direccion || $lugar)<div>📍 {{ trim($tienda->direccion . ($tienda->direccion && $lugar ? ' · ' : '') . $lugar) }}</div>@endif
            @if ($wa)<div>💬 <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">Escríbanos por WhatsApp</a></div>@endif
        </div>
        <div class="lp">Tienda en línea creada con <a href="{{ url('/') }}">LocalPro</a></div>
    </div>
</footer>

<button class="bar" id="bar"><span><span class="n" id="barN">0</span>Ver mi pedido</span><span id="barT"></span></button>
<div class="toast" id="toast"></div>

<div class="ov" id="ovC"><div class="sheet">
    <div class="sh"><h3>Mi pedido</h3><button class="x" data-close>&times;</button></div>
    <div id="cItems"></div>
    <div class="tot"><span>Total</span><span id="cTot"></span></div>
    <input class="in" id="cNom" placeholder="Su nombre" maxlength="60">
    <input class="in" id="cNota" placeholder="Dirección de entrega o nota (opcional)" maxlength="200">
    @if ($wa)
    <button class="btn btn-wa" id="cEnviar">💬 Hacer el pedido por WhatsApp</button>
    @else
    <p style="color:#b0206a;font-weight:700;margin-top:12px">Esta tienda aún no tiene WhatsApp configurado.</p>
    @endif
</div></div>

<script>
// Carrito y botón Comprar (WhatsApp con el producto exacto). Compartido entre la tienda y cada producto.
window.LPC = (function () {
    var M = @json($M), WA = @json($wa), TIENDA = @json($tienda->nombre), KEY = 'lp_pedido_' + @json($tienda->slug);
    var $ = function (id) { return document.getElementById(id); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    var cart = []; try { cart = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { cart = []; }
    var guardar = function () { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} };
    function total() { return cart.reduce(function (a, i) { return a + i.precio * i.cant; }, 0); }
    function toast(t) { var x = $('toast'); x.textContent = t; x.classList.add('on'); clearTimeout(x._t); x._t = setTimeout(function () { x.classList.remove('on'); }, 1800); }
    function barra() {
        var n = cart.reduce(function (a, i) { return a + i.cant; }, 0);
        $('bar').classList.toggle('on', n > 0); $('barN').textContent = n; $('barT').textContent = fmt(total());
        $('topN').textContent = n; $('topN').classList.toggle('on', n > 0);
    }
    function pintar() {
        $('cItems').innerHTML = cart.length ? cart.map(function (i, k) {
            return '<div class="it"><div class="t"><b>' + esc(i.nombre) + '</b><small>' + (i.codigo ? 'Cód. ' + esc(i.codigo) + ' · ' : '') + esc(i.op) + (i.op ? ' · ' : '') + fmt(i.precio) + '</small></div>' +
                '<div class="q"><button data-k="' + k + '" data-d="-1">−</button><span style="min-width:22px;text-align:center;font-weight:800">' + i.cant + '</span><button data-k="' + k + '" data-d="1">+</button></div></div>';
        }).join('') : '<p style="color:var(--mut)">Su pedido está vacío.</p>';
        $('cTot').textContent = fmt(total());
        if ($('cEnviar')) $('cEnviar').disabled = !cart.length;
    }
    function abrir() { pintar(); $('ovC').classList.add('on'); }
    $('cItems').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var i = cart[+b.dataset.k]; i.cant += +b.dataset.d; if (i.cant <= 0) cart.splice(+b.dataset.k, 1);
        guardar(); barra(); pintar();
    });
    $('bar').addEventListener('click', abrir);
    $('btnCarrito').addEventListener('click', abrir);
    document.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('.ov').classList.remove('on'); }); });
    document.querySelectorAll('.ov').forEach(function (o) { o.addEventListener('click', function (e) { if (e.target === o) o.classList.remove('on'); }); });
    if ($('cEnviar')) $('cEnviar').addEventListener('click', function () {
        var nom = $('cNom').value.trim(), nota = $('cNota').value.trim();
        var msg = '¡Hola ' + TIENDA + '! 👋 Quiero hacer este pedido:\n\n' +
            cart.map(function (i) { return '• ' + i.cant + ' × ' + i.nombre + (i.op ? ' (' + i.op + ')' : '') + (i.codigo ? ' [Cód. ' + i.codigo + ']' : '') + ' — ' + fmt(i.precio * i.cant); }).join('\n') +
            '\n\n*Total: ' + fmt(total()) + '*' + (nom ? '\nNombre: ' + nom : '') + (nota ? '\nEntrega / nota: ' + nota : '');
        window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
    });
    barra();
    return {
        fmt: fmt, esc: esc,
        agregar: function (p, v, cant) {
            cant = cant || 1;
            var ex = cart.find(function (x) { return x.id === v.id; });
            if (ex) ex.cant += cant; else cart.push({ id: v.id, nombre: p.nombre, codigo: p.codigo, op: p.variantes.length > 1 ? v.etiqueta : '', precio: v.precio, cant: cant });
            guardar(); barra(); toast('✓ ' + p.nombre + ' agregado al pedido');
        },
        // "Comprar": abre WhatsApp diciendo exactamente qué producto quiere
        comprar: function (p, v, cant) {
            if (!WA) { toast('Esta tienda aún no tiene WhatsApp'); return; }
            cant = cant || 1;
            var msg = '¡Hola ' + TIENDA + '! 👋 Quiero este producto:\n\n' +
                '🛍️ *' + p.nombre + '*\n' +
                (p.codigo ? 'Código: ' + p.codigo + '\n' : '') +
                (v && p.variantes.length > 1 ? 'Opción: ' + v.etiqueta + '\n' : '') +
                'Cantidad: ' + cant + (p.unidad ? ' ' + p.unidad : '') + '\n' +
                'Precio: ' + fmt((v ? v.precio : p.precio) * cant) + '\n\n' + p.url;
            window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
        }
    };
})();
</script>
@stack('js')
</body>
</html>
