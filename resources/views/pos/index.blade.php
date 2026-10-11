<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('layouts.partials.pwa')
<title>POS · {{ $negocio->nombre }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous" defer></script>
<style>
:root{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--g2:#16b34a;--lime:#a6f34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--gold:#ffcc33;--pink:#ff5d8f}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;height:100dvh}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;overflow:hidden;width:100%;max-width:100vw}
button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
input{font:inherit}
.app{display:grid;grid-template-rows:calc(64px + env(safe-area-inset-top)) 1fr;height:100vh;height:100dvh}
/* Barra superior */
.top{display:flex;align-items:center;gap:14px;padding:env(safe-area-inset-top) 18px 0;background:var(--k);color:#fff}
.mbar{display:none}
.cx{display:none}
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
.main{display:grid;grid-template-columns:minmax(0,1fr) 400px;min-height:0;min-width:0}
.main>*,.cat,.search{min-width:0}
.top{min-width:0;overflow:hidden}
.top .pill{min-width:0;overflow:hidden;text-overflow:ellipsis}
.cat{display:flex;flex-direction:column;min-height:0;padding:18px}
.search{position:relative;margin-bottom:14px}
.search input{width:100%;min-width:0;text-overflow:ellipsis;height:56px;border-radius:18px;border:2px solid var(--line);background:#fff;padding:0 56px 0 52px;font-size:1.05rem;font-weight:600;outline:none;transition:border-color .15s,box-shadow .15s}
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
.ch > div{display:flex;align-items:center;gap:6px}
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
.cli{display:block}
.cli-busca{display:flex;gap:8px}
.cli-busca input{flex:1}
.cli-nuevo{flex:none;border:1.5px solid #c9efd2;background:#f2fbf3;color:#11702f;border-radius:12px;padding:0 .8rem;font-weight:800;font-size:.85rem;white-space:nowrap}
.cli input{border:1.5px solid var(--line);border-radius:12px;padding:.55rem .7rem;font-weight:600;font-size:.88rem;min-width:0}
.cli input:focus,.line input:focus{outline:none;border-color:#2fd15a}
.cli{position:relative}
.cli-res{position:absolute;left:0;right:0;bottom:calc(100% + 6px);background:#fff;border:1.5px solid var(--line);border-radius:14px;box-shadow:0 20px 40px -20px rgba(0,0,0,.35);overflow:hidden;display:none;z-index:5}
.cli-res button{display:block;width:100%;text-align:left;padding:.6rem .8rem;border-bottom:1px solid var(--line);font-size:.86rem}
.cli-res button:hover{background:#f2fbf3}
.cli-res small{color:var(--mut);display:block;font-size:.75rem}
.cli-sel{display:none;justify-content:space-between;align-items:center;background:#f2fbf3;border:1.5px solid #c9efd2;border-radius:12px;padding:.45rem .7rem;font-weight:700;font-size:.86rem;color:#11702f}
.credit{margin-top:10px;width:100%;border:2px dashed var(--line);border-radius:14px;padding:.6rem;font-weight:800;color:var(--g2)}
.total{display:flex;justify-content:space-between;align-items:baseline;font-weight:800}
.total span:last-child{font-size:2rem;letter-spacing:-.03em}
.pay{height:62px;border-radius:18px;background:var(--g);color:var(--k);font-weight:800;font-size:1.15rem;display:flex;align-items:center;justify-content:center;gap:.6rem;box-shadow:0 16px 30px -14px rgba(47,209,90,.95);transition:transform .15s,filter .15s}
.pay:hover{filter:brightness(1.05);transform:translateY(-1px)}
.pay:disabled{opacity:.4;box-shadow:none;cursor:not-allowed;transform:none}
kbd{background:rgba(0,0,0,.12);border-radius:6px;padding:.05rem .35rem;font-size:.7rem;font-family:inherit}
/* Modales */
.ov{position:fixed;inset:0;background:rgba(11,13,12,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:50;padding:calc(16px + env(safe-area-inset-top)) 16px calc(16px + env(safe-area-inset-bottom))}
.ov.on{display:flex}
.md{background:#fff;border-radius:28px;width:min(560px,100%);max-height:100%;overflow:auto;padding:22px;animation:up .22s cubic-bezier(.2,.8,.2,1)}
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
.sum .cli-l{font-size:.85rem;color:#cfd8d2;font-weight:600}
.sum .cli-l span:last-child{text-align:right;max-width:65%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#pOk{position:sticky;bottom:0;z-index:2;box-shadow:0 0 0 10px #fff}
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
.toast .ti{width:24px;height:24px;border-radius:50%;background:var(--lime);color:var(--k);display:grid;place-items:center;font-size:.8rem;flex:none}
.toast.bad{background:#9b1c5a}
.toast.bad .ti{background:#fff;color:#9b1c5a}
.cart.bump{animation:bump .4s ease}
@keyframes bump{0%{transform:scale(1)}35%{transform:scale(1.02)}100%{transform:scale(1)}}
.fcli{display:grid;gap:10px}
.fcli label{display:flex;flex-direction:column;gap:4px;font-weight:700;font-size:.82rem;color:var(--mut)}
.fcli input{border:1.5px solid var(--line);border-radius:12px;padding:.7rem .8rem;font-weight:600;font-size:1rem;color:var(--ink)}
.fcli input:focus{outline:none;border-color:#2fd15a}
.vya{color:#0a8a3c;font-weight:700;font-size:.85rem;min-height:1.1em;margin:.2rem 0 .4rem}
/* Celular / tablet vertical: todo en una sola pantalla que baja (buscador, venta y productos) */
@media (max-width:900px){
  body{overflow:auto;overflow-x:hidden}
  html,body{height:auto;min-height:100%}
  .app{height:auto;min-height:100dvh;grid-template-rows:auto 1fr;align-content:start}
  .top{min-height:calc(64px + env(safe-area-inset-top))}
  .main{display:flex;flex-direction:column;padding:12px 12px calc(24px + env(safe-area-inset-bottom));gap:12px}
  .cat{display:contents}
  .search{order:1;margin:0;position:sticky;top:8px;z-index:20}
  .search input{height:52px;font-size:.98rem;padding:0 50px 0 46px;box-shadow:0 10px 24px -18px rgba(0,0,0,.4)}
  .cart{order:2;position:static;border:1.5px solid var(--line);border-radius:22px;overflow:visible}
  .grid{order:3;overflow:visible;padding-bottom:0;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
  .ch{padding:12px 14px}
  .items{flex:none;overflow:visible;padding:2px 10px}
  .cempty{padding:14px;font-size:.9rem}
  .cempty i{display:inline;font-size:1.1rem;margin:0 .35rem 0 0}
  .cf{padding:12px 14px 14px}
  body:not(.hay) .cf .line,body:not(.hay) .cf .total{display:none}
  .total span:last-child{font-size:1.7rem}
  .pay{height:58px}
  .cli-res{bottom:auto;top:calc(100% + 6px)}
  .ov{align-items:flex-start;padding-top:calc(76px + env(safe-area-inset-top));backdrop-filter:none}
  .ov .md{max-height:calc(100% - 90px)}
  #ovPay .md{padding:16px 16px 14px}
  #ovPay .mh{margin-bottom:6px}
  #ovPay .mh small{display:none}
  #ovPay .lbl{margin:6px 0}
  .methods{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
  .mt{padding:.5rem .25rem;font-size:.78rem;border-radius:12px;gap:.2rem;text-align:center;line-height:1.15}
  .mt i,.mt svg{font-size:1rem}
  .prow input{width:130px}
  .credit{padding:.45rem;font-size:.85rem;margin-top:8px}
  .sum{margin-top:10px;padding:10px 14px;gap:4px}
  .sum .big{font-size:1.25rem}
  #pOk{margin-top:12px}
  .card{transition:none}
  .card:hover{transform:none;box-shadow:none}
  .toast{top:calc(74px + env(safe-area-inset-top));bottom:auto;transform:translateX(-50%) translateY(-160px);width:max-content;max-width:calc(100vw - 24px);font-size:.92rem}
  .toast.on{transform:translateX(-50%) translateY(0)}
  .top .pill{flex:0 1 auto}
  .top .pill.hide-m{display:none}
  .top{gap:8px;padding:env(safe-area-inset-top) 10px 0}
  .brand span:last-child{display:none}
  .tbtn{flex:none;flex-direction:column;gap:2px;height:48px;padding:0 .55rem;font-size:.66rem;justify-content:center}
  .tbtn span{display:block;line-height:1}
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
        <a class="tbtn" href="{{ route('cajas.show', $caja) }}" title="Ver las ventas de mi turno"><i class="fa-solid fa-receipt"></i><span>Mi turno</span></a>
        <a class="tbtn" href="{{ route('cajas.cerrar') }}" title="Cerrar caja y hacer el cuadre"><i class="fa-solid fa-lock"></i><span>Cerrar caja</span></a>
        <a class="tbtn" href="{{ route('panel') }}" title="Volver al panel"><i class="fa-solid fa-house"></i><span>Panel</span></a>
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
            <div class="ch"><h2><i class="fa-solid fa-bag-shopping me-1" style="color:#16b34a"></i> Venta actual</h2><div><button id="clear">Vaciar</button></div></div>
            <div class="items" id="items"></div>
            <div class="cf">
                <div class="line"><span>Subtotal</span><b id="sub">$0</b></div>
                <div class="line"><span>Descuento</span><input id="desc" type="number" min="0" step="{{ paso_dinero() }}" placeholder="0" inputmode="decimal"></div>
                <div class="cli">
                    <div class="cli-sel" id="cliSel"><span id="cliSelTxt"></span><button type="button" id="cliQuitar" aria-label="Quitar cliente">&times;</button></div>
                    <div class="cli-busca" id="cliBusca">
                        <input id="cn" placeholder="Cliente: nombre, cédula o WhatsApp" maxlength="80" autocomplete="off">
                        <button type="button" class="cli-nuevo" id="cliNuevo"><i class="fa-solid fa-user-plus"></i> Nuevo</button>
                    </div>
                    <input type="hidden" id="ct">
                    <div class="cli-res" id="cliRes"></div>
                </div>
                <div class="total"><span>Total</span><span id="tot">$0</span></div>
                <button class="pay" id="payBtn" disabled><i class="fa-solid fa-money-bill-wave"></i> Cobrar <kbd>F2</kbd></button>
            </div>
        </aside>
    </div>
</div>


<!-- Elegir talla y color -->
<div class="ov" id="ovVar">
    <div class="md">
        <div class="mh"><div><h3 id="vNom"></h3><small id="vInfo"></small></div><button class="x" data-close>&times;</button></div>
        <div id="vTallasW"><div class="lbl">Talla</div><div class="chips" id="vTallas"></div></div>
        <div id="vColoresW"><div class="lbl">Color</div><div class="chips" id="vColores"></div></div>
        <div class="vya" id="vYa"></div>
        <button class="mbtn" id="vAdd" disabled>Agregar a la venta</button>
    </div>
</div>

<!-- Cliente nuevo -->
<div class="ov" id="ovCli">
    <div class="md">
        <div class="mh"><div><h3>Cliente nuevo</h3><small>Queda guardado para las próximas ventas</small></div><button class="x" data-close>&times;</button></div>
        <div class="fcli">
            <label>Nombre completo *<input id="ncNom" maxlength="80" autocomplete="off"></label>
            <label>Cédula<input id="ncDoc" maxlength="20" inputmode="numeric" autocomplete="off"></label>
            <label>WhatsApp<input id="ncTel" maxlength="20" inputmode="tel" autocomplete="off"></label>
            <label>Dirección<input id="ncDir" maxlength="80" autocomplete="off"></label>
        </div>
        <div class="err" id="ncErr"></div>
        <button class="mbtn" id="ncOk">Guardar cliente</button>
    </div>
</div>

<!-- Cobrar -->
<div class="ov" id="ovPay">
    <div class="md">
        <div class="mh"><div><h3>Cobrar</h3><small>Puede combinar varios métodos de pago</small></div><button class="x" data-close>&times;</button></div>
        <div class="lbl">Método de pago</div>
        <div class="methods" id="methods"></div>
        <div id="prows"></div>
        <button type="button" class="credit" id="usarSaldo"><i class="fa-solid fa-ticket"></i> Usar saldo a favor (nota crédito)</button>
        <div class="sum">
            <div class="cli-l"><span>Cliente</span><span id="pCli"></span></div>
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

<div class="toast" id="toast" role="status" aria-live="polite"><b class="ti">✓</b><span></span></div>

<script>
(function () {
    var URL_BUSCAR = @json(route('pos.buscar')), URL_VENTA = @json(route('pos.store'));
    var URL_NUEVO = @json(route('productos.create'));
    var URL_CLIENTES = @json(route('pos.clientes')), URL_SALDO = @json(route('pos.saldo'));
    var URL_CLIENTE_NUEVO = @json(route('pos.clientes.crear'));
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

    var productos = [], cart = [], actual = null, sel = { talla: null, color: null }, pagos = {}, ultima = null, enviando = false, clienteId = null, credito = null;

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
            g.innerHTML = $('q').value.trim()
                ? '<div class="empty"><i class="fa-solid fa-magnifying-glass"></i>No se encontró "' + esc($('q').value.trim()) + '".</div>'
                : '<div class="empty"><i class="fa-solid fa-box-open"></i>Aún no tiene productos para vender.<br><a href="' + URL_NUEVO + '" style="display:inline-block;margin-top:12px;background:#0b0d0c;color:#fff;border-radius:999px;padding:.7rem 1.2rem;font-weight:800;text-decoration:none">+ Crear mi primer producto</a></div>';
            return;
        }
        g.innerHTML = productos.map(function (p, i) {
            var cls = !p.controla ? '' : (p.stock <= 0 ? 'zero' : (p.stock <= 5 ? 'low' : ''));
            var off = p.controla && p.stock <= 0;
            return '<button class="card ' + (off ? 'off' : '') + '" data-i="' + i + '">' +
                '<div class="thumb">' + (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : esc(p.nombre.charAt(0))) + '</div>' +
                '<b>' + esc(p.nombre) + '</b>' +
                '<div class="row"><span class="price">' + fmt(p.precio) + '</span><span class="stk ' + cls + '">' + (p.controla ? p.stock + ' und' : 'Disponible') + '</span></div></button>';
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
        var conUnidades = p.controla ? p.variantes.filter(function (v) { return libre(v) > 0; }) : p.variantes;
        if (conUnidades.length === 1) { agregar(p, conUnidades[0]); return; }
        actual = p; sel = { talla: null, color: null };
        $('vNom').textContent = p.nombre;
        $('vInfo').textContent = 'Código ' + p.codigo + (p.controla ? ' · ' + p.stock + ' unidades' : '');
        pintarChips();
        abrir('ovVar');
    }
    function enVenta(v) { var it = cart.find(function (x) { return x.variante_id === v.id; }); return it ? it.cantidad : 0; }
    function libre(v) { return Math.max(v.stock - enVenta(v), 0); }
    function uniq(a) { return a.filter(function (x, i) { return a.indexOf(x) === i; }); }
    function pintarChips() {
        var vs = actual.variantes;
        var tallas = uniq(vs.map(function (v) { return v.talla; }));
        var colores = uniq(vs.map(function (v) { return v.color; }));
        var hayT = tallas.some(function (x) { return x; }), hayC = colores.some(function (x) { return x; });
        if (!hayT || tallas.length === 1) sel.talla = tallas[0];
        if (!hayC || colores.length === 1) sel.color = colores[0];
        $('vTallasW').style.display = hayT ? '' : 'none';
        $('vColoresW').style.display = hayC ? '' : 'none';
        $('vTallas').innerHTML = tallas.map(function (t) {
            var st = vs.filter(function (v) { return v.talla === t && (!sel.color || v.color === sel.color); }).reduce(function (a, v) { return a + libre(v); }, 0);
            return '<button class="chip ' + (sel.talla === t ? 'sel' : '') + '" data-t="' + esc(t) + '" ' + (actual.controla && st <= 0 ? 'disabled' : '') + '>' + esc(t || 'Única') + (actual.controla ? '<small>' + st + ' und</small>' : '') + '</button>';
        }).join('');
        $('vColores').innerHTML = colores.map(function (c) {
            var st = vs.filter(function (v) { return v.color === c && (!sel.talla || v.talla === sel.talla); }).reduce(function (a, v) { return a + libre(v); }, 0);
            return '<button class="chip ' + (sel.color === c ? 'sel' : '') + '" data-c="' + esc(c) + '" ' + (actual.controla && st <= 0 ? 'disabled' : '') + '>' + esc(c || 'Único') + (actual.controla ? '<small>' + st + ' und</small>' : '') + '</button>';
        }).join('');
        var v = varianteElegida();
        var ya = v ? enVenta(v) : 0;
        $('vAdd').disabled = !v || (actual.controla && libre(v) <= 0);
        $('vAdd').textContent = !v ? 'Elija talla y color'
            : (actual.controla && libre(v) <= 0 ? 'Ya están todas en la venta (' + ya + ')'
            : 'Agregar · ' + fmt(v.precio) + (actual.controla ? ' (quedan ' + libre(v) + ')' : ''));
        $('vYa').textContent = ya ? 'Ya tiene ' + ya + ' de esta en la venta.' : '';
    }
    function varianteElegida() {
        if (!actual) return null;
        return actual.variantes.find(function (v) { return v.talla === sel.talla && v.color === sel.color; }) || null;
    }
    $('vTallas').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b || b.disabled) return; sel.talla = actual.variantes.find(function (v) { return String(v.talla || '') === b.dataset.t; }).talla; pintarChips(); agregarSiListo(); });
    $('vColores').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b || b.disabled) return; sel.color = actual.variantes.find(function (v) { return String(v.color || '') === b.dataset.c; }).color; pintarChips(); agregarSiListo(); });
    function agregarSiListo() {
        var v = varianteElegida();
        if (v && (!actual.controla || libre(v) > 0)) { agregar(actual, v); cerrar('ovVar'); }
    }
    $('vAdd').addEventListener('click', function () { var v = varianteElegida(); if (v) { agregar(actual, v); cerrar('ovVar'); } });

    // ---------- Carrito ----------
    function agregar(p, v) {
        var it = cart.find(function (x) { return x.variante_id === v.id; });
        var enCarrito = it ? it.cantidad : 0;
        if (p.controla && enCarrito + 1 > v.stock) { toast('Ya agregó las ' + v.stock + ' unidades de ' + p.nombre + ' ' + v.etiqueta + '. Toque "Ver venta y cobrar".', true); avisarFab(); return; }
        if (it) it.cantidad++;
        else cart.push({ variante_id: v.id, nombre: p.nombre, etiqueta: v.etiqueta, precio: v.precio, stock: v.stock, controla: p.controla, cantidad: 1 });
        pintarCarrito();
        toast(p.nombre + ' · ' + v.etiqueta + ' agregado');
        avisarFab();
        if (window.innerWidth > 900) $('q').focus();
    }
    function avisarFab() { var f = $('cart'); f.classList.remove('bump'); void f.offsetWidth; f.classList.add('bump'); }
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
        document.body.classList.toggle('hay', cart.length > 0);
        $('payBtn').disabled = !cart.length;
    }
    $('items').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        if (b.dataset.rm !== undefined) { cart.splice(+b.dataset.rm, 1); }
        else {
            var i = cart[+b.dataset.k], n = i.cantidad + (+b.dataset.d);
            if (i.controla && n > i.stock) { toast('Solo hay ' + i.stock + ' disponibles', true); return; }
            if (n <= 0) cart.splice(+b.dataset.k, 1); else i.cantidad = n;
        }
        pintarCarrito();
    });
    $('desc').addEventListener('input', pintarCarrito);
    $('clear').addEventListener('click', function () { if (cart.length && confirm('¿Vaciar la venta actual?')) { cart = []; pintarCarrito(); } });

    // ---------- Cobro ----------
    var ICONOS = { efectivo: 'fa-money-bill-wave', otro: 'fa-mobile-screen-button' };
    function abrirCobro() {
        if (!cart.length) return;
        $('toast').classList.remove('on'); $('cart').classList.remove('open');
        $('pCli').textContent = clienteId || $('cn').value.trim() ? $('cn').value.trim() : 'Consumidor final (sin cliente)';
        pagos = {}; credito = null;
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
            if (!m && credito && String(credito.metodo_id) === id) m = { id: credito.metodo_id, nombre: 'Saldo a favor ' + credito.codigo + ' (máx. ' + fmt(credito.saldo) + ')', es_efectivo: false };
            var quick = m.es_efectivo ? '<div class="quick">' + [total()].concat(M.decimales ? [10, 20, 50, 100] : [20000, 50000, 100000]).filter(function (v, i, a) { return v > 0 && a.indexOf(v) === i; }).map(function (v, i) { return '<button data-q="' + v + '" data-id="' + id + '">' + (i === 0 ? 'Exacto ' : '') + fmt(v) + '</button>'; }).join('') + '</div>' : '';
            return '<div class="prow"><b>' + esc(m.nombre) + '</b><input data-id="' + id + '" type="number" min="0" step="' + (M.decimales ? '0.01' : '1') + '" inputmode="decimal" placeholder="' + (ids.length === 1 ? redondear(total()) : '0') + '" value="' + pagos[id] + '"></div>' + quick;
        }).join('');
        resumen();
    }
    function montos() {
        var ids = Object.keys(pagos);
        return ids.map(function (id) {
            var v = pagos[id] === '' ? (ids.length === 1 ? total() : 0) : parseFloat(pagos[id]) || 0;
            var p = { metodo_id: +id, monto: v };
            if (credito && +id === credito.metodo_id) { p.monto = Math.min(v, credito.saldo); p.referencia = credito.codigo; }
            return p;
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
    $('usarSaldo').addEventListener('click', function () {
        var c = prompt('Código de la nota crédito (ej. NC-000012):');
        if (!c) return;
        fetch(URL_SALDO + '?codigo=' + encodeURIComponent(c.trim()), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok) { toast(res.d.message || 'Nota crédito no válida', true); return; }
                credito = res.d;
                Object.keys(pagos).forEach(function (k) { if (pagos[k] === '') pagos[k] = '0'; });
                pagos[credito.metodo_id] = String(redondear(Math.min(credito.saldo, total())));
                var falta = total() - Math.min(credito.saldo, total());
                var otro = Object.keys(pagos).find(function (k) { return +k !== credito.metodo_id; });
                if (otro) pagos[otro] = falta > 0 ? String(redondear(falta)) : '0';
                pintarMetodos();
                toast('Saldo a favor aplicado: ' + fmt(Math.min(credito.saldo, total())));
            });
    });

    // ---------- Clientes ----------
    var tc;
    $('cn').addEventListener('input', function () {
        if (clienteId) return;
        clearTimeout(tc); var v = this.value.trim();
        if (v.length < 2) { $('cliRes').style.display = 'none'; return; }
        tc = setTimeout(function () {
            fetch(URL_CLIENTES + '?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
                $('cliRes').innerHTML = l.map(function (c, i) { return '<button type="button" data-i="' + i + '">' + esc(c.nombre) + '<small>' + esc([c.documento && c.documento.indexOf('TEL-') !== 0 ? 'CC ' + c.documento : '', c.telefono || ''].filter(Boolean).join(' · ')) + '</small></button>'; }).join('') +
                    '<button type="button" data-nuevo="1" style="color:#11702f;font-weight:800">' + (l.length ? '+ Crear otro cliente' : '+ No está registrado: crearlo') + '</button>';
                $('cliRes')._l = l; $('cliRes').style.display = 'block';
            });
        }, 250);
    });
    $('cliRes').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]');
        if (!b) { if (e.target.closest('[data-nuevo]')) abrirNuevoCliente(); return; }
        elegirCliente($('cliRes')._l[+b.dataset.i]);
    });
    function elegirCliente(c) {
        clienteId = c.id; $('cn').value = c.nombre; $('ct').value = c.telefono || '';
        $('cliSelTxt').textContent = '👤 ' + c.nombre + (c.documento && c.documento.indexOf('TEL-') !== 0 ? ' · CC ' + c.documento : '') + (c.telefono ? ' · ' + c.telefono : '');
        $('cliSel').style.display = 'flex'; $('cliBusca').style.display = 'none'; $('cliRes').style.display = 'none';
    }
    function quitarCliente() { clienteId = null; $('cliSel').style.display = 'none'; $('cliBusca').style.display = ''; $('cn').value = ''; $('ct').value = ''; }
    function abrirNuevoCliente() {
        var t = $('cn').value.trim();
        $('ncNom').value = /^[\d\s+-]+$/.test(t) ? '' : t;
        $('ncDoc').value = ''; $('ncTel').value = /^[\d\s+-]{7,}$/.test(t) ? t : ''; $('ncDir').value = ''; $('ncErr').textContent = '';
        $('cliRes').style.display = 'none';
        abrir('ovCli'); setTimeout(function () { ($('ncNom').value ? $('ncDoc') : $('ncNom')).focus(); }, 50);
    }
    $('cliNuevo').addEventListener('click', abrirNuevoCliente);
    $('ncOk').addEventListener('click', function () {
        var nom = $('ncNom').value.trim();
        if (nom.length < 2) { $('ncErr').textContent = 'Escriba el nombre del cliente.'; $('ncNom').focus(); return; }
        var btn = this; btn.disabled = true; btn.textContent = 'Guardando…'; $('ncErr').textContent = '';
        fetch(URL_CLIENTE_NUEVO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ nombre: nom, documento: $('ncDoc').value.trim(), telefono: $('ncTel').value.trim(), direccion: $('ncDir').value.trim() })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            if (!res.ok) {
                var m = res.d.message || 'No se pudo guardar.';
                if (res.d.errors) m = res.d.errors[Object.keys(res.d.errors)[0]][0];
                $('ncErr').textContent = m; return;
            }
            elegirCliente(res.d); cerrar('ovCli');
            toast(res.d.existia ? 'Ese cliente ya estaba registrado: quedó elegido' : 'Cliente guardado y elegido');
        }).catch(function () { $('ncErr').textContent = 'Sin conexión. Intente de nuevo.'; })
          .finally(function () { btn.disabled = false; btn.textContent = 'Guardar cliente'; });
    });
    $('cliQuitar').addEventListener('click', quitarCliente);
    document.addEventListener('click', function (e) { if (!e.target.closest('.cli')) $('cliRes').style.display = 'none'; });

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
                cliente_id: clienteId,
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
        $('desc').value = ''; quitarCliente();
        pintarCarrito(); cerrar('ovOk'); $('cart').classList.remove('open');
        buscar($('q').value.trim());
        if (window.innerWidth > 900) $('q').focus(); else window.scrollTo(0, 0);
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
        t.querySelector('.ti').textContent = bad ? '!' : '✓';
        t.classList.toggle('bad', !!bad);
        t.classList.add('on'); clearTimeout(tt); tt = setTimeout(function () { t.classList.remove('on'); }, bad ? 3200 : 1800);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); abrirCobro(); }
        else if (e.key === 'Escape') { ['ovVar', 'ovPay', 'ovCli'].forEach(cerrar); $('cart').classList.remove('open'); }
        else if (e.key === 'Enter') {
            if ($('ovCli').classList.contains('on')) { e.preventDefault(); $('ncOk').click(); }
            else if ($('ovPay').classList.contains('on')) { e.preventDefault(); confirmar(); }
            else if ($('ovOk').classList.contains('on') && document.activeElement !== $('okTel')) { e.preventDefault(); nuevaVenta(); }
        }
    });
    window.addEventListener('beforeunload', function (e) { if (cart.length && !ultima) { e.preventDefault(); e.returnValue = ''; } });

    if (window.innerWidth < 600) $('q').placeholder = 'Buscar o escanear…';
    if (window.innerWidth <= 900) $('q').blur();
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
