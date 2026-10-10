<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>POS · {{ $negocio->nombre }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous" defer></script>
<style>
:root{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--g2:#16b34a;--lime:#a6f34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--gold:#ffcc33;--pink:#ff5d8f}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;overflow:hidden}
button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
input{font:inherit}
.app{display:grid;grid-template-rows:64px 1fr;height:100vh;height:100dvh}
/* Barra superior */
.top{display:flex;align-items:center;gap:14px;padding:0 18px;background:var(--k);color:#fff}
.brand{display:flex;align-items:center;gap:.55rem;font-weight:800;letter-spacing:.06em}
.mk{width:30px;height:30px;position:relative;flex:none}
.mk::before,.mk::after{content:"";position:absolute;width:17px;height:17px;border-radius:5px;transform:rotate(45deg);top:6px}
.mk::before{left:2px;background:var(--g2)}.mk::after{left:11px;background:var(--lime);mix-blend-mode:screen}
.pill{background:#1a201c;border-radius:999px;padding:.4rem .8rem;font-size:.8rem;font-weight:700;color:#cfd8d2;display:flex;gap:.45rem;align-items:center;white-space:nowrap}
.pill i{color:var(--lime)}
.top .sp{flex:1}
.tbtn{background:#1a201c;color:#fff;border-radius:12px;height:40px;padding:0 .9rem;font-weight:700;font-size:.85rem;display:flex;align-items:center;gap:.45rem;text-decoration:none;white-space:nowrap}
.tbtn:hover{background:#252c27}
.tbtn.red{color:#ff9fbd}
/* Cuerpo */
.main{display:grid;grid-template-columns:1fr 400px;min-height:0}
.cat{display:flex;flex-direction:column;min-height:0;padding:18px}
.search{position:relative;margin-bottom:14px}
.search input{width:100%;height:56px;border-radius:18px;border:2px solid var(--line);background:#fff;padding:0 56px 0 52px;font-size:1.05rem;font-weight:600;outline:none;transition:border-color .15s,box-shadow .15s}
.search input:focus{border-color:#2fd15a;box-shadow:0 0 0 5px rgba(47,209,90,.15)}
.search .ic{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#93a097;font-size:1.1rem}
.search .scan{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:var(--bg);width:38px;height:38px;border-radius:12px;display:grid;place-items:center;color:var(--g2)}
.grid{flex:1;overflow:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;align-content:start;padding-bottom:90px}
.card{background:#fff;border:1.5px solid var(--line);border-radius:20px;padding:12px;text-align:left;transition:transform .15s,box-shadow .15s,border-color .15s;display:flex;flex-direction:column;gap:8px;position:relative}
.card:hover{transform:translateY(-3px);border-color:#bfeccb;box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.card:active{transform:scale(.97)}
.card.off{opacity:.45}
.thumb{aspect-ratio:1/1;border-radius:14px;background:var(--bg);display:grid;place-items:center;overflow:hidden;font-weight:800;font-size:2rem;color:var(--g2)}
.thumb img{width:100%;height:100%;object-fit:cover}
.card b{font-size:.92rem;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card .row{display:flex;justify-content:space-between;align-items:center;gap:6px}
.price{font-weight:800}
.stk{font-size:.72rem;font-weight:800;border-radius:999px;padding:.15rem .5rem;background:#e8f8ec;color:#11702f}
.stk.low{background:#fff5d6;color:#8a6400}.stk.zero{background:#ffe9f3;color:#b0206a}
.empty{grid-column:1/-1;text-align:center;color:var(--mut);padding:50px 10px;font-weight:600}
.empty i{font-size:2.4rem;color:#bfeccb;display:block;margin-bottom:10px}
/* Carrito */
.cart{background:#fff;border-left:1.5px solid var(--line);display:flex;flex-direction:column;min-height:0}
.ch{padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid var(--line)}
.ch h2{font-size:1.1rem;font-weight:800}
.ch button{color:var(--mut);font-weight:700;font-size:.82rem}
.items{flex:1;overflow:auto;padding:6px 14px}
.it{display:flex;gap:10px;align-items:center;padding:10px 4px;border-bottom:1px dashed var(--line);animation:pop .25s ease}
@keyframes pop{from{opacity:0;transform:translateX(10px)}}
.it .t{flex:1;min-width:0}
.it .t b{display:block;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.it .t small{color:var(--mut);font-weight:600;font-size:.78rem}
.qty{display:flex;align-items:center;background:var(--bg);border-radius:12px}
.qty button{width:32px;height:32px;font-weight:800;font-size:1rem}
.qty span{min-width:22px;text-align:center;font-weight:800}
.it .s{font-weight:800;min-width:76px;text-align:right}
.rm{color:#c4cdc7;width:26px}
.rm:hover{color:var(--pink)}
.cempty{text-align:center;color:var(--mut);padding:40px 16px;font-weight:600}
.cempty i{font-size:2.6rem;color:#d7eedd;display:block;margin-bottom:10px}
.cf{padding:14px 18px 18px;border-top:1.5px solid var(--line);display:flex;flex-direction:column;gap:10px}
.line{display:flex;justify-content:space-between;align-items:center;color:var(--mut);font-weight:600;font-size:.92rem}
.line input{width:120px;text-align:right;border:1.5px solid var(--line);border-radius:10px;padding:.35rem .6rem;font-weight:700}
.cli{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.cli input{border:1.5px solid var(--line);border-radius:12px;padding:.55rem .7rem;font-weight:600;font-size:.88rem;min-width:0}
.cli input:focus,.line input:focus{outline:none;border-color:#2fd15a}
.total{display:flex;justify-content:space-between;align-items:baseline;font-weight:800}
.total span:last-child{font-size:2rem;letter-spacing:-.03em}
.pay{height:62px;border-radius:18px;background:var(--g);color:var(--k);font-weight:800;font-size:1.15rem;display:flex;align-items:center;justify-content:center;gap:.6rem;box-shadow:0 16px 30px -14px rgba(47,209,90,.95);transition:transform .15s,filter .15s}
.pay:hover{filter:brightness(1.05);transform:translateY(-1px)}
.pay:disabled{opacity:.4;box-shadow:none;cursor:not-allowed;transform:none}
kbd{background:rgba(0,0,0,.12);border-radius:6px;padding:.05rem .35rem;font-size:.7rem;font-family:inherit}
/* Modales */
.ov{position:fixed;inset:0;background:rgba(11,13,12,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:50;padding:16px}
.ov.on{display:flex}
.md{background:#fff;border-radius:28px;width:min(560px,100%);max-height:92vh;overflow:auto;padding:22px;animation:up .22s cubic-bezier(.2,.8,.2,1)}
@keyframes up{from{opacity:0;transform:translateY(20px) scale(.98)}}
.mh{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:14px}
.mh h3{font-size:1.25rem;font-weight:800;line-height:1.2}
.mh small{color:var(--mut);font-weight:600}
.x{width:38px;height:38px;border-radius:12px;background:var(--bg);flex:none;font-size:1.1rem}
.lbl{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);margin:12px 0 8px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:2px solid var(--line);border-radius:14px;padding:.6rem .85rem;font-weight:800;min-width:64px;text-align:center;background:#fff;transition:all .12s}
.chip small{display:block;font-size:.7rem;font-weight:700;color:var(--mut)}
.chip:hover{border-color:#2fd15a}
.chip.sel{background:var(--k);border-color:var(--k);color:#fff}
.chip.sel small{color:var(--lime)}
.chip:disabled{opacity:.35;text-decoration:line-through;cursor:not-allowed}
.mbtn{width:100%;height:56px;border-radius:16px;background:var(--g);color:var(--k);font-weight:800;font-size:1.05rem;margin-top:18px}
.mbtn:disabled{opacity:.4}
.mbtn.dark{background:var(--k);color:#fff}
.mbtn.wa{background:#25d366;color:#fff}
.methods{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px}
.mt{border:2px solid var(--line);border-radius:16px;padding:.8rem .6rem;font-weight:800;font-size:.9rem;display:flex;flex-direction:column;align-items:center;gap:.35rem;background:#fff}
.mt i{font-size:1.2rem;color:var(--g2)}
.mt.sel{border-color:#2fd15a;background:#f2fbf3;box-shadow:0 0 0 4px rgba(47,209,90,.15)}
.prow{display:flex;gap:8px;align-items:center;margin-top:8px;background:var(--bg);border-radius:14px;padding:8px 10px}
.prow b{flex:1;font-size:.9rem}
.prow input{width:150px;text-align:right;border:1.5px solid var(--line);border-radius:10px;padding:.5rem .6rem;font-weight:800;font-size:1rem}
.prow input:focus{outline:none;border-color:#2fd15a}
.quick{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.quick button{background:#e8f8ec;color:#11702f;border-radius:999px;padding:.35rem .75rem;font-weight:800;font-size:.82rem}
.sum{margin-top:14px;border-radius:18px;background:var(--k);color:#fff;padding:14px 16px;display:grid;gap:6px}
.sum div{display:flex;justify-content:space-between;font-weight:700}
.sum .big{font-size:1.4rem;color:var(--lime)}
.sum .warn{color:#ff9fbd}
.err{background:#ffe9f3;color:#9b1c5a;border-radius:14px;padding:.7rem .9rem;font-weight:700;font-size:.9rem;margin-top:12px;display:none}
.ok{text-align:center}
.ok .badge{width:84px;height:84px;border-radius:26px;background:var(--g);display:grid;place-items:center;margin:4px auto 14px;font-size:2.2rem;color:var(--k);animation:bounce .5s cubic-bezier(.2,1.6,.4,1)}
@keyframes bounce{from{transform:scale(.3)}}
.ok h3{font-size:1.6rem;font-weight:800}
.ok .chg{font-size:2.6rem;font-weight:800;letter-spacing:-.03em;margin:6px 0}
.ok p{color:var(--mut);font-weight:600}
.wa-in{display:flex;gap:8px;margin-top:14px}
.wa-in input{flex:1;border:2px solid var(--line);border-radius:14px;padding:.7rem .9rem;font-weight:700}
.toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%) translateY(120px);background:var(--k);color:#fff;border-radius:16px;padding:.8rem 1.1rem;font-weight:700;z-index:60;transition:transform .25s;display:flex;gap:.5rem;align-items:center;max-width:92vw}
.toast.on{transform:translateX(-50%) translateY(0)}
.toast i{color:var(--lime)}
.fab{display:none}
/* Celular / tablet vertical */
@media (max-width:900px){
  .main{grid-template-columns:1fr}
  .cart{position:fixed;inset:auto 0 0 0;height:82vh;border-radius:26px 26px 0 0;border:0;box-shadow:0 -20px 50px rgba(0,0,0,.25);transform:translateY(100%);transition:transform .28s cubic-bezier(.2,.8,.2,1);z-index:40}
  .cart.open{transform:none}
  .fab{display:flex;position:fixed;left:14px;right:14px;bottom:14px;height:60px;border-radius:18px;background:var(--k);color:#fff;align-items:center;justify-content:space-between;padding:0 18px;font-weight:800;z-index:30;box-shadow:0 18px 40px -16px rgba(0,0,0,.6)}
  .fab .n{background:var(--g);color:var(--k);border-radius:999px;padding:.15rem .6rem;margin-right:.5rem}
  .top .pill.hide-m,.tbtn span{display:none}
  .grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}
}
</style>
</head>
<body>
<div class="app">
    <header class="top">
        <div class="brand"><span class="mk"></span><span>LOCALPRO</span></div>
        <div class="pill hide-m"><i class="fa-solid fa-store"></i>{{ $negocio->nombre }}</div>
        <div class="pill"><i class="fa-solid fa-cash-register"></i>Caja #{{ $caja->id }}</div>
        <div class="pill hide-m"><i class="fa-solid fa-user"></i>{{ auth()->user()->name }}</div>
        <div class="sp"></div>
        <a class="tbtn" href="{{ route('cajas.show', $caja) }}"><i class="fa-solid fa-receipt"></i><span>Mi turno</span></a>
        <a class="tbtn" href="{{ route('cajas.cerrar') }}"><i class="fa-solid fa-lock"></i><span>Cerrar caja</span></a>
        <a class="tbtn" href="{{ route('panel') }}"><i class="fa-solid fa-house"></i><span>Panel</span></a>
    </header>

    <div class="main">
        <section class="cat">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass ic"></i>
                <input id="q" type="search" placeholder="Buscar prenda o escanear código de barras…" autocomplete="off" autofocus>
                <span class="scan" title="Compatible con lector de código de barras"><i class="fa-solid fa-barcode"></i></span>
            </div>
            <div class="grid" id="grid"></div>
        </section>

        <aside class="cart" id="cart">
            <div class="ch"><h2><i class="fa-solid fa-bag-shopping me-1" style="color:#16b34a"></i> Venta actual</h2><button id="clear">Vaciar</button></div>
            <div class="items" id="items"></div>
            <div class="cf">
                <div class="line"><span>Subtotal</span><b id="sub">$0</b></div>
                <div class="line"><span>Descuento</span><input id="desc" type="number" min="0" step="{{ paso_dinero() }}" placeholder="0" inputmode="decimal"></div>
                <div class="cli">
                    <input id="cn" placeholder="Cliente (opcional)" maxlength="80">
                    <input id="ct" placeholder="WhatsApp (opcional)" maxlength="20" inputmode="tel">
                </div>
                <div class="total"><span>Total</span><span id="tot">$0</span></div>
                <button class="pay" id="payBtn" disabled><i class="fa-solid fa-money-bill-wave"></i> Cobrar <kbd>F2</kbd></button>
            </div>
        </aside>
    </div>
</div>

<button class="fab" id="fab"><span><span class="n" id="fabN">0</span>Ver venta</span><span id="fabT">$0</span></button>

<!-- Elegir talla y color -->
<div class="ov" id="ovVar">
    <div class="md">
        <div class="mh"><div><h3 id="vNom"></h3><small id="vInfo"></small></div><button class="x" data-close>&times;</button></div>
        <div id="vTallasW"><div class="lbl">Talla</div><div class="chips" id="vTallas"></div></div>
        <div id="vColoresW"><div class="lbl">Color</div><div class="chips" id="vColores"></div></div>
        <button class="mbtn" id="vAdd" disabled>Agregar a la venta</button>
    </div>
</div>

<!-- Cobrar -->
<div class="ov" id="ovPay">
    <div class="md">
        <div class="mh"><div><h3>Cobrar</h3><small>Puede combinar varios métodos de pago</small></div><button class="x" data-close>&times;</button></div>
        <div class="lbl">Método de pago</div>
        <div class="methods" id="methods"></div>
        <div id="prows"></div>
        <div class="sum">
            <div><span>Total a cobrar</span><span id="pTot">$0</span></div>
            <div><span>Recibido</span><span id="pRec">$0</span></div>
            <div class="big" id="pChgRow"><span id="pChgLbl">Cambio</span><span id="pChg">$0</span></div>
        </div>
        <div class="err" id="pErr"></div>
        <button class="mbtn" id="pOk">Confirmar venta <kbd>Enter</kbd></button>
    </div>
</div>

<!-- Venta lista -->
<div class="ov" id="ovOk">
    <div class="md ok">
        <div class="badge"><i class="fa-solid fa-check"></i></div>
        <h3>¡Venta registrada!</h3>
        <p id="okNum"></p>
        <div id="okChgW"><p style="margin-top:10px">Cambio a entregar</p><div class="chg" id="okChg"></div></div>
        <button class="mbtn dark" id="okPrint"><i class="fa-solid fa-print"></i> Imprimir tiquete</button>
        <div class="wa-in"><input id="okTel" placeholder="WhatsApp del cliente" inputmode="tel" maxlength="20"></div>
        <button class="mbtn wa" id="okWa" style="margin-top:8px"><i class="fa-brands fa-whatsapp"></i> Enviar factura por WhatsApp</button>
        <button class="mbtn" id="okNew">Nueva venta <kbd>Enter</kbd></button>
    </div>
</div>

<div class="toast" id="toast"><i class="fa-solid fa-circle-check"></i><span></span></div>

<script>
(function () {
    var URL_BUSCAR = @json(route('pos.buscar')), URL_VENTA = @json(route('pos.store'));
    var NEGOCIO = @json($negocio->nombre);
    var METODOS = @json($metodos);
    var CSRF = document.querySelector('meta[name=csrf-token]').content;
    var $ = function (id) { return document.getElementById(id); };
    var M = @json(moneda());
    var redondear = function (n) { return Number((n || 0).toFixed(M.decimales)); };
    var fmt = function (n) {
        n = redondear(n);
        var t = Math.abs(n).toFixed(M.decimales).split('.');
        t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles);
        return (n < 0 ? '-' : '') + M.simbolo + t.join(M.decimal);
    };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };

    var productos = [], cart = [], actual = null, sel = { talla: null, color: null }, pagos = {}, ultima = null, enviando = false;

    // ---------- Catálogo ----------
    var timer;
    function buscar(q) {
        fetch(URL_BUSCAR + '?q=' + encodeURIComponent(q || ''), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                productos = d.productos;
                pintarGrid();
                if (d.exacta) {
                    var v = buscarVariante(d.exacta);
                    if (v) { agregar(v.p, v.v); $('q').value = ''; buscar(''); }
                }
            })
            .catch(function () { toast('No se pudo buscar. Revise la conexión.', true); });
    }
    function buscarVariante(id) {
        for (var i = 0; i < productos.length; i++) for (var j = 0; j < productos[i].variantes.length; j++)
            if (productos[i].variantes[j].id === id) return { p: productos[i], v: productos[i].variantes[j] };
        return null;
    }
    function pintarGrid() {
        var g = $('grid');
        if (!productos.length) {
            g.innerHTML = '<div class="empty"><i class="fa-solid fa-shirt"></i>No hay prendas con tallas y colores para mostrar.<br><small>Cree productos y agrégueles tallas/colores en Inventario.</small></div>';
            return;
        }
        g.innerHTML = productos.map(function (p, i) {
            var cls = p.stock <= 0 ? 'zero' : (p.stock <= 5 ? 'low' : '');
            return '<button class="card ' + (p.stock <= 0 ? 'off' : '') + '" data-i="' + i + '">' +
                '<div class="thumb">' + (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : esc(p.nombre.charAt(0))) + '</div>' +
                '<b>' + esc(p.nombre) + '</b>' +
                '<div class="row"><span class="price">' + fmt(p.precio) + '</span><span class="stk ' + cls + '">' + p.stock + ' und</span></div></button>';
        }).join('');
    }
    $('grid').addEventListener('click', function (e) {
        var c = e.target.closest('.card'); if (!c) return;
        abrirVariantes(productos[+c.dataset.i]);
    });
    $('q').addEventListener('input', function () { clearTimeout(timer); var v = this.value; timer = setTimeout(function () { buscar(v); }, 220); });
    $('q').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); buscar(this.value.trim()); } });

    // ---------- Talla y color ----------
    function abrirVariantes(p) {
        if (p.variantes.length === 1) { agregar(p, p.variantes[0]); return; }
        actual = p; sel = { talla: null, color: null };
        $('vNom').textContent = p.nombre;
        $('vInfo').textContent = 'Código ' + p.codigo + ' · ' + p.stock + ' unidades';
        pintarChips();
        abrir('ovVar');
    }
    function uniq(a) { return a.filter(function (x, i) { return a.indexOf(x) === i; }); }
    function pintarChips() {
        var vs = actual.variantes;
        var tallas = uniq(vs.map(function (v) { return v.talla; }));
        var colores = uniq(vs.map(function (v) { return v.color; }));
        var hayT = tallas.some(function (x) { return x; }), hayC = colores.some(function (x) { return x; });
        if (!hayT) sel.talla = tallas[0];
        if (!hayC) sel.color = colores[0];
        $('vTallasW').style.display = hayT ? '' : 'none';
        $('vColoresW').style.display = hayC ? '' : 'none';
        $('vTallas').innerHTML = tallas.map(function (t) {
            var st = vs.filter(function (v) { return v.talla === t && (!sel.color || v.color === sel.color); }).reduce(function (a, v) { return a + v.stock; }, 0);
            return '<button class="chip ' + (sel.talla === t ? 'sel' : '') + '" data-t="' + esc(t) + '" ' + (st <= 0 ? 'disabled' : '') + '>' + esc(t || 'Única') + '<small>' + st + ' und</small></button>';
        }).join('');
        $('vColores').innerHTML = colores.map(function (c) {
            var st = vs.filter(function (v) { return v.color === c && (!sel.talla || v.talla === sel.talla); }).reduce(function (a, v) { return a + v.stock; }, 0);
            return '<button class="chip ' + (sel.color === c ? 'sel' : '') + '" data-c="' + esc(c) + '" ' + (st <= 0 ? 'disabled' : '') + '>' + esc(c || 'Único') + '<small>' + st + ' und</small></button>';
        }).join('');
        var v = varianteElegida();
        $('vAdd').disabled = !v || v.stock <= 0;
        $('vAdd').textContent = v ? 'Agregar · ' + fmt(v.precio) + ' (' + v.stock + ' disp.)' : 'Elija talla y color';
    }
    function varianteElegida() {
        if (!actual) return null;
        return actual.variantes.find(function (v) { return v.talla === sel.talla && v.color === sel.color; }) || null;
    }
    $('vTallas').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b || b.disabled) return; sel.talla = actual.variantes.find(function (v) { return String(v.talla || '') === b.dataset.t; }).talla; pintarChips(); });
    $('vColores').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b || b.disabled) return; sel.color = actual.variantes.find(function (v) { return String(v.color || '') === b.dataset.c; }).color; pintarChips(); });
    $('vAdd').addEventListener('click', function () { var v = varianteElegida(); if (v) { agregar(actual, v); cerrar('ovVar'); } });

    // ---------- Carrito ----------
    function agregar(p, v) {
        var it = cart.find(function (x) { return x.variante_id === v.id; });
        var enCarrito = it ? it.cantidad : 0;
        if (enCarrito + 1 > v.stock) { toast('Solo hay ' + v.stock + ' de ' + p.nombre + ' ' + v.etiqueta, true); return; }
        if (it) it.cantidad++;
        else cart.push({ variante_id: v.id, nombre: p.nombre, etiqueta: v.etiqueta, precio: v.precio, stock: v.stock, cantidad: 1 });
        pintarCarrito();
        toast(p.nombre + ' · ' + v.etiqueta + ' agregado');
        $('q').focus();
    }
    function subtotal() { return cart.reduce(function (a, i) { return a + i.precio * i.cantidad; }, 0); }
    function descuento() { return Math.min(Math.max(parseFloat($('desc').value) || 0, 0), subtotal()); }
    function total() { return Math.max(subtotal() - descuento(), 0); }
    function pintarCarrito() {
        var el = $('items');
        if (!cart.length) el.innerHTML = '<div class="cempty"><i class="fa-solid fa-bag-shopping"></i>Toque una prenda o escanee su código para agregarla.</div>';
        else el.innerHTML = cart.map(function (i, k) {
            return '<div class="it"><div class="t"><b>' + esc(i.nombre) + '</b><small>' + esc(i.etiqueta) + ' · ' + fmt(i.precio) + '</small></div>' +
                '<div class="qty"><button data-k="' + k + '" data-d="-1">−</button><span>' + i.cantidad + '</span><button data-k="' + k + '" data-d="1">+</button></div>' +
                '<div class="s">' + fmt(i.precio * i.cantidad) + '</div><button class="rm" data-rm="' + k + '" aria-label="Quitar"><i class="fa-solid fa-xmark"></i></button></div>';
        }).join('');
        $('sub').textContent = fmt(subtotal());
        $('tot').textContent = fmt(total());
        $('fabT').textContent = fmt(total());
        $('fabN').textContent = cart.reduce(function (a, i) { return a + i.cantidad; }, 0);
        $('payBtn').disabled = !cart.length;
    }
    $('items').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        if (b.dataset.rm !== undefined) { cart.splice(+b.dataset.rm, 1); }
        else {
            var i = cart[+b.dataset.k], n = i.cantidad + (+b.dataset.d);
            if (n > i.stock) { toast('Solo hay ' + i.stock + ' disponibles', true); return; }
            if (n <= 0) cart.splice(+b.dataset.k, 1); else i.cantidad = n;
        }
        pintarCarrito();
    });
    $('desc').addEventListener('input', pintarCarrito);
    $('clear').addEventListener('click', function () { if (cart.length && confirm('¿Vaciar la venta actual?')) { cart = []; pintarCarrito(); } });
    $('fab').addEventListener('click', function () { $('cart').classList.add('open'); });

    // ---------- Cobro ----------
    var ICONOS = { efectivo: 'fa-money-bill-wave', otro: 'fa-mobile-screen-button' };
    function abrirCobro() {
        if (!cart.length) return;
        pagos = {};
        var ef = METODOS.find(function (m) { return m.es_efectivo; }) || METODOS[0];
        if (ef) pagos[ef.id] = '';
        pintarMetodos();
        abrir('ovPay');
        setTimeout(function () { var i = document.querySelector('#prows input'); if (i) i.focus(); }, 60);
    }
    function pintarMetodos() {
        $('methods').innerHTML = METODOS.map(function (m) {
            return '<button class="mt ' + (m.id in pagos ? 'sel' : '') + '" data-m="' + m.id + '"><i class="fa-solid ' + (m.es_efectivo ? ICONOS.efectivo : ICONOS.otro) + '"></i>' + esc(m.nombre) + '</button>';
        }).join('');
        var ids = Object.keys(pagos);
        $('prows').innerHTML = ids.map(function (id) {
            var m = METODOS.find(function (x) { return String(x.id) === id; });
            var quick = m.es_efectivo ? '<div class="quick">' + [total()].concat(M.decimales ? [10, 20, 50, 100] : [20000, 50000, 100000]).filter(function (v, i, a) { return v > 0 && a.indexOf(v) === i; }).map(function (v, i) { return '<button data-q="' + v + '" data-id="' + id + '">' + (i === 0 ? 'Exacto ' : '') + fmt(v) + '</button>'; }).join('') + '</div>' : '';
            return '<div class="prow"><b>' + esc(m.nombre) + '</b><input data-id="' + id + '" type="number" min="0" step="' + (M.decimales ? '0.01' : '1') + '" inputmode="decimal" placeholder="' + (ids.length === 1 ? redondear(total()) : '0') + '" value="' + pagos[id] + '"></div>' + quick;
        }).join('');
        resumen();
    }
    function montos() {
        var ids = Object.keys(pagos);
        return ids.map(function (id) {
            var v = pagos[id] === '' ? (ids.length === 1 ? total() : 0) : parseFloat(pagos[id]) || 0;
            return { metodo_id: +id, monto: v };
        });
    }
    function resumen() {
        var rec = montos().reduce(function (a, p) { return a + p.monto; }, 0), t = total(), d = rec - t;
        $('pTot').textContent = fmt(t);
        $('pRec').textContent = fmt(rec);
        $('pChgLbl').textContent = d >= 0 ? 'Cambio' : 'Falta';
        $('pChg').textContent = fmt(Math.abs(d));
        $('pChgRow').classList.toggle('warn', d < 0);
        $('pOk').disabled = d < -0.5 || enviando;
        $('pErr').style.display = 'none';
    }
    $('methods').addEventListener('click', function (e) {
        var b = e.target.closest('.mt'); if (!b) return;
        var id = b.dataset.m;
        if (id in pagos) { if (Object.keys(pagos).length > 1) delete pagos[id]; }
        else {
            // El método nuevo propone lo que falta por pagar
            var explicito = Object.keys(pagos).reduce(function (a, k) { return a + (parseFloat(pagos[k]) || 0); }, 0);
            var falta = total() - explicito;
            pagos[id] = falta > 0 ? String(redondear(falta)) : '';
        }
        pintarMetodos();
    });
    $('prows').addEventListener('input', function (e) { if (e.target.dataset.id) { pagos[e.target.dataset.id] = e.target.value; resumen(); } });
    $('prows').addEventListener('click', function (e) { var b = e.target.closest('[data-q]'); if (!b) return; pagos[b.dataset.id] = b.dataset.q; pintarMetodos(); });
    $('payBtn').addEventListener('click', abrirCobro);

    function confirmar() {
        if (enviando || $('pOk').disabled) return;
        enviando = true; $('pOk').disabled = true; $('pOk').textContent = 'Registrando…';
        fetch(URL_VENTA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                items: cart.map(function (i) { return { variante_id: i.variante_id, cantidad: i.cantidad }; }),
                descuento: descuento(),
                pagos: montos().filter(function (p) { return p.monto > 0; }),
                cliente_nombre: $('cn').value.trim() || null,
                cliente_telefono: $('ct').value.trim() || null
            })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            if (!res.ok) {
                var m = res.d.message || 'No se pudo registrar la venta.';
                if (res.d.errors) { var k = Object.keys(res.d.errors)[0]; m = res.d.errors[k][0]; }
                $('pErr').textContent = m; $('pErr').style.display = 'block';
                return;
            }
            ultima = res.d;
            cerrar('ovPay');
            $('okNum').textContent = ultima.numero + ' · ' + fmt(ultima.total);
            $('okChgW').style.display = ultima.cambio > 0 ? '' : 'none';
            $('okChg').textContent = fmt(ultima.cambio);
            $('okTel').value = $('ct').value.trim();
            abrir('ovOk');
        }).catch(function () {
            $('pErr').textContent = 'Sin conexión. La venta NO se registró, intente de nuevo.'; $('pErr').style.display = 'block';
        }).finally(function () {
            enviando = false; $('pOk').innerHTML = 'Confirmar venta <kbd>Enter</kbd>'; resumen();
        });
    }
    $('pOk').addEventListener('click', confirmar);

    // ---------- Después de vender ----------
    $('okPrint').addEventListener('click', function () { if (ultima) window.open(ultima.ticket_url + '?imprimir=1', 'tiquete', 'width=420,height=720'); });
    $('okWa').addEventListener('click', function () {
        if (!ultima) return;
        var t = $('okTel').value.replace(/\D/g, '');
        if (t.length < 7) { $('okTel').focus(); toast('Escriba el WhatsApp del cliente', true); return; }
        if (t.indexOf(M.indicativo) !== 0 || t.length <= 10) t = M.indicativo + t.replace(/^0+/, '');
        var nombre = $('cn').value.trim();
        var msg = 'Hola' + (nombre ? ' ' + nombre : '') + ', gracias por su compra en ' + NEGOCIO + ' 🛍️\n' +
            'Factura ' + ultima.numero + ' por ' + fmt(ultima.total) + '.\nVea y descargue su factura aquí: ' + ultima.factura_url;
        window.open('https://wa.me/' + t + '?text=' + encodeURIComponent(msg), '_blank');
    });
    function nuevaVenta() {
        cart = []; ultima = null;
        $('desc').value = ''; $('cn').value = ''; $('ct').value = '';
        pintarCarrito(); cerrar('ovOk'); $('cart').classList.remove('open');
        buscar($('q').value.trim());
        $('q').focus();
    }
    $('okNew').addEventListener('click', nuevaVenta);

    // ---------- Utilidades ----------
    function abrir(id) { $(id).classList.add('on'); }
    function cerrar(id) { $(id).classList.remove('on'); }
    document.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', function () { cerrar(b.closest('.ov').id); }); });
    document.querySelectorAll('.ov').forEach(function (o) { o.addEventListener('click', function (e) { if (e.target === o && o.id !== 'ovOk') cerrar(o.id); }); });
    var tt;
    function toast(m, bad) {
        var t = $('toast'); t.querySelector('span').textContent = m;
        t.querySelector('i').className = 'fa-solid ' + (bad ? 'fa-circle-exclamation' : 'fa-circle-check');
        t.querySelector('i').style.color = bad ? '#ff9fbd' : '';
        t.classList.add('on'); clearTimeout(tt); tt = setTimeout(function () { t.classList.remove('on'); }, 1800);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); abrirCobro(); }
        else if (e.key === 'Escape') { ['ovVar', 'ovPay'].forEach(cerrar); $('cart').classList.remove('open'); }
        else if (e.key === 'Enter') {
            if ($('ovPay').classList.contains('on')) { e.preventDefault(); confirmar(); }
            else if ($('ovOk').classList.contains('on') && document.activeElement !== $('okTel')) { e.preventDefault(); nuevaVenta(); }
        }
    });
    window.addEventListener('beforeunload', function (e) { if (cart.length && !ultima) { e.preventDefault(); e.returnValue = ''; } });

    pintarCarrito();
    buscar('');
    @if (session('bienvenida'))
    setTimeout(function () { toast('¡Su tienda está lista! Toque un producto para hacer su primera venta 🎉'); }, 600);
    @endif
    @if (session('success'))
    setTimeout(function () { toast(@json(session('success'))); }, 400);
    @endif
})();
</script>
</body>
</html>
