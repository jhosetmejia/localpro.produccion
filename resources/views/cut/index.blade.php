<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<meta name="theme-color" content="#0b0d0c">
<title>LocalProCut · Edite sus videos gratis en el navegador</title>
<meta name="description" content="Editor de videos de LocalProo: subtítulos automáticos con estilos, transiciones, filtro de belleza y exportar en 4K a 60 fps. Desde el celular o el computador, sin instalar nada.">
<meta property="og:title" content="LocalProCut · Edite sus videos con LocalProo">
<meta property="og:description" content="Subtítulos automáticos, transiciones, filtro de belleza y 4K a 60 fps. Gratis en el navegador.">
<link rel="icon" href="/favicon.ico">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700;800&family=Montserrat:wght@700;800;900&family=Playfair+Display:ital,wght@1,700&family=Space+Mono:wght@700&family=Unbounded:wght@700;800&display=swap" rel="stylesheet">
<style>
:root{--v:#a6f34a;--v2:#16b84e;--f:#0b0d0c;--f2:#151a17;--f3:#1f2622;--b:#2b332e;--t:#f3f7f4;--m:#9aa6a0}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;height:100%;background:var(--f);color:var(--t);font-family:Figtree,system-ui,sans-serif;overscroll-behavior:none}
body{display:flex;flex-direction:column;height:100dvh;overflow:hidden}
button{font:inherit;color:inherit;cursor:pointer}
[hidden]{display:none!important}
.cut-top{display:flex;align-items:center;gap:.6rem;padding:calc(env(safe-area-inset-top,0px) + 8px) 14px 8px;border-bottom:1px solid var(--b);flex:0 0 auto}
.cut-top a.volver{color:var(--t);text-decoration:none;font-size:1.3rem;line-height:1;padding:.2rem .4rem;border-radius:10px}
.cut-logo{font-family:Unbounded,sans-serif;font-weight:800;font-size:1.02rem;letter-spacing:-.02em;display:flex;align-items:center;gap:.4rem}
.cut-logo i{font-style:normal;width:26px;height:26px;border-radius:8px;background:linear-gradient(135deg,var(--v),var(--v2));display:grid;place-items:center;color:var(--f);font-size:.8rem}
.cut-logo b{color:var(--v)}
.cut-top .sp{flex:1}
.cut-top .nuevo{background:transparent;border:1px solid var(--b);border-radius:999px;padding:.35rem .75rem;font-size:.8rem;color:var(--m)}
.cut-exp{background:linear-gradient(90deg,var(--v),var(--v2));border:0;border-radius:999px;padding:.5rem 1.05rem;font-weight:800;color:var(--f)!important}
#cutVistaCaja{flex:1 1 0;min-height:150px;position:relative;overflow:hidden;background:radial-gradient(circle at 50% 40%,#161c18,#0b0d0c 70%)}
#cutLienzo{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:auto;height:auto;max-width:calc(100% - 20px);max-height:calc(100% - 20px);background:#000;border-radius:6px;box-shadow:0 8px 30px rgba(0,0,0,.5)}
.cut-vacio{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px;gap:.6rem;background:var(--f)}
.cut-vacio h2{font-family:Unbounded,sans-serif;font-size:1.35rem;margin:.2rem 0 0;line-height:1.2}
.cut-vacio h2 b{color:var(--v)}
.cut-vacio p{margin:0;color:var(--m);max-width:34ch}
.cut-vacio .grande{margin-top:.8rem;background:linear-gradient(90deg,var(--v),var(--v2));border:0;border-radius:18px;padding:1rem 1.6rem;font-weight:800;color:var(--f);font-size:1.05rem;display:flex;align-items:center;gap:.5rem;box-shadow:0 10px 30px rgba(22,184,78,.25)}
.cut-vacio .grande span{font-size:1.4rem;line-height:1}
.cut-vacio ul{list-style:none;padding:0;margin:.9rem 0 0;display:flex;flex-wrap:wrap;justify-content:center;gap:.4rem;max-width:420px}
.cut-vacio li{background:var(--f3);border:1px solid var(--b);border-radius:999px;padding:.3rem .7rem;font-size:.8rem;color:#cfd8d2}
.cut-ctrl{display:flex;align-items:center;gap:.6rem;padding:6px 14px;flex:0 0 auto}
#cutPlay{width:40px;height:40px;border-radius:50%;border:0;background:var(--t);color:var(--f);display:grid;place-items:center}
#cutPlay svg{width:18px;height:18px}
#cutPlay .pa{display:none}#cutPlay.on .pl{display:none}#cutPlay.on .pa{display:block}
#cutTiempo{font-variant-numeric:tabular-nums;font-size:.85rem;color:var(--m)}
.cut-ctrl .sp{flex:1}
.cut-ctrl .zm{width:32px;height:32px;border-radius:10px;border:1px solid var(--b);background:var(--f2);font-weight:800}
.cut-linea{position:relative;flex:0 0 auto;height:118px;border-top:1px solid var(--b);border-bottom:1px solid var(--b);background:var(--f2)}
#cutLinea{position:absolute;inset:0;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;touch-action:pan-x}
#cutLinea::-webkit-scrollbar{display:none}
#cutLineaIn{position:relative;height:100%}
.cut-ph{position:absolute;left:50%;top:6px;bottom:6px;width:2px;margin-left:-1px;background:#fff;border-radius:2px;pointer-events:none;z-index:5;box-shadow:0 0 6px rgba(0,0,0,.6)}
.cut-ph::before{content:"";position:absolute;top:-4px;left:-4px;width:10px;height:10px;border-radius:50%;background:#fff}
#cutPistaV{position:absolute;left:0;right:0;top:12px;height:58px}
#cutPistaS{position:absolute;left:0;right:0;top:78px;height:28px}
.cut-clip{position:absolute;top:0;height:58px;border-radius:9px;background:#2a332d center/cover;border:2px solid transparent;overflow:hidden;cursor:pointer}
.cut-clip span{position:absolute;left:4px;bottom:3px;font-size:.66rem;font-weight:700;background:rgba(0,0,0,.6);padding:1px 5px;border-radius:5px}
.cut-clip.sel{border-color:var(--v);box-shadow:0 0 0 2px rgba(166,243,74,.25)}
.cut-clip.mudo{filter:saturate(.6)}
.cut-rombo{position:absolute;top:16px;width:26px;height:26px;border-radius:8px;border:0;background:#fff;color:var(--f);font-size:.85rem;z-index:3;display:grid;place-items:center;box-shadow:0 2px 8px rgba(0,0,0,.5)}
.cut-rombo.on{background:var(--v)}
.cut-mas{position:absolute;top:8px;width:42px;height:42px;border-radius:12px;border:0;background:var(--t);color:var(--f);font-size:1.5rem;font-weight:700;line-height:1}
.cut-sub{position:absolute;top:0;height:26px;border-radius:7px;background:#3b2a52;border:1px solid #6b4aa0;color:#e9dcff;font-size:.68rem;font-weight:700;padding:5px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
#cutPanel{flex:0 0 auto;max-height:36vh;overflow-y:auto;padding:12px 14px;background:var(--f)}
.cut-nota{color:var(--m);font-size:.85rem;margin:.3rem 0;text-align:center}
.cut-acciones{display:flex;gap:.4rem;overflow-x:auto;padding-bottom:4px}
.cut-acciones button{flex:0 0 auto;min-width:74px;background:var(--f2);border:1px solid var(--b);border-radius:14px;padding:.55rem .5rem;font-size:.74rem;font-weight:700;display:flex;flex-direction:column;align-items:center;gap:.25rem}
.cut-acciones button i{font-style:normal;font-size:1.15rem}
.cut-fila{display:flex;align-items:center;gap:.7rem;margin:.6rem 0}
.cut-fila>small{flex:0 0 96px;color:var(--m);font-weight:600;font-size:.78rem}
.cut-fila input[type=range]{flex:1;accent-color:var(--v)}
.cut-chips{display:flex;gap:.35rem;flex-wrap:wrap}
.cut-chip{background:var(--f2);border:1px solid var(--b);border-radius:999px;padding:.35rem .75rem;font-size:.8rem;font-weight:700}
.cut-chip.on{background:var(--v);border-color:var(--v);color:var(--f)}
.cut-chip:disabled{opacity:.35;cursor:not-allowed}
.cut-trans{display:grid;grid-template-columns:repeat(auto-fill,minmax(82px,1fr));gap:.45rem}
.cut-trans button{background:var(--f2);border:1px solid var(--b);border-radius:14px;padding:.5rem .3rem;font-size:.72rem;font-weight:700;display:flex;flex-direction:column;align-items:center;gap:.3rem}
.cut-trans button.on{border-color:var(--v);background:#1b2a17;color:var(--v)}
.cut-trans .ic{width:38px;height:26px;border-radius:6px;background:linear-gradient(90deg,#3a4a40 50%,#7d8f84 50%);position:relative;overflow:hidden}
.cut-trans .ic-ninguna{background:linear-gradient(90deg,#3a4a40 49%,#0b0d0c 49%,#0b0d0c 51%,#7d8f84 51%)}
.cut-trans .ic-fundido{background:linear-gradient(90deg,#3a4a40,#000 50%,#7d8f84)}
.cut-trans .ic-disolver{background:linear-gradient(90deg,#3a4a40,#7d8f84)}
.cut-trans .ic-circulo{background:radial-gradient(circle,#7d8f84 35%,#3a4a40 37%)}
.cut-trans .ic-destello{background:linear-gradient(90deg,#3a4a40,#fff 50%,#7d8f84)}
.cut-trans .ic-zoom,.cut-trans .ic-zoomin{background:radial-gradient(circle,#7d8f84 20%,#3a4a40 22%,#3a4a40 40%,#7d8f84 42%)}
.cut-trans .ic-desenfoque{background:linear-gradient(90deg,#3a4a40,#5b6c61,#7d8f84);filter:blur(1.5px)}
.cut-trans .ic-giro{background:conic-gradient(#3a4a40 0 50%,#7d8f84 0)}
.cut-trans .ic-arriba{background:linear-gradient(180deg,#3a4a40 50%,#7d8f84 50%)}
.cut-btn{width:100%;background:linear-gradient(90deg,var(--v),var(--v2));border:0;border-radius:14px;padding:.8rem;font-weight:800;color:var(--f);font-size:.95rem}
.cut-btn:disabled{opacity:.6}
.cut-btn-sec{background:var(--f2);border:1px solid var(--b);border-radius:12px;padding:.55rem .8rem;font-weight:700;font-size:.82rem}
.cut-acciones2{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem}
.cut-gen-op{display:flex;gap:.4rem;margin-top:.45rem}
.cut-gen-op select{flex:1;background:var(--f2);border:1px solid var(--b);color:var(--t);border-radius:10px;padding:.45rem;font:inherit;font-size:.82rem}
.cut-progreso{margin-top:.5rem;background:var(--f2);border-radius:999px;height:26px;position:relative;overflow:hidden}
.cut-progreso i{position:absolute;inset:0 auto 0 0;background:linear-gradient(90deg,var(--v),var(--v2));transition:width .3s;width:0}
.cut-progreso span{position:relative;display:block;text-align:center;font-size:.74rem;font-weight:700;line-height:26px;color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.7)}
.cut-estilos{display:flex;gap:.45rem;overflow-x:auto;margin:.7rem 0 .2rem;padding-bottom:4px}
.est{flex:0 0 86px;height:72px;border-radius:14px;border:2px solid var(--b);background:#2b3530;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.25rem;padding:.3rem}
.est.on{border-color:var(--v)}
.est b{font-size:1.05rem;line-height:1}
.est small{font-size:.62rem;color:#cfd8d2;font-weight:700;text-align:center;line-height:1.1}
.est-tiktok b{font-family:Montserrat;font-weight:900;text-transform:uppercase;color:var(--v);-webkit-text-stroke:1px #000}
.est-resaltado b{font-family:Montserrat;background:var(--v);color:var(--f);padding:2px 6px;border-radius:5px}
.est-karaoke b{font-family:Montserrat;color:#ffe14d;-webkit-text-stroke:1px #000}
.est-pop b{font-family:Montserrat;font-weight:900;font-size:1.3rem;text-transform:uppercase;-webkit-text-stroke:1px #000}
.est-clasico b{font-family:Montserrat;-webkit-text-stroke:1px #000}
.est-caja b{font-family:Montserrat;background:rgba(0,0,0,.75);padding:3px 7px;border-radius:5px}
.est-neon b{font-family:Montserrat;color:#fff;text-shadow:0 0 6px #39d5ff,0 0 12px #39d5ff}
.est-maquina b{font-family:"Space Mono";background:#000;padding:2px 6px}
.est-elegante b{font-family:"Playfair Display";font-style:italic}
.cut-colores{display:flex;gap:.45rem}
.cut-colores button{width:28px;height:28px;border-radius:50%;border:2px solid var(--b)}
.cut-colores button.on{border-color:#fff;box-shadow:0 0 0 2px var(--f),0 0 0 4px #fff}
.cut-check{display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:.82rem;margin:.4rem 0}
.cut-check input{accent-color:var(--v);width:18px;height:18px}
.cut-switch{display:flex;align-items:center;gap:.7rem;font-weight:800;font-size:1rem;cursor:pointer}
.cut-switch input{display:none}
.cut-switch span{width:48px;height:28px;border-radius:999px;background:var(--b);position:relative;transition:.2s}
.cut-switch span::after{content:"";position:absolute;top:3px;left:3px;width:22px;height:22px;border-radius:50%;background:#fff;transition:.2s}
.cut-switch input:checked+span{background:var(--v2)}
.cut-switch input:checked+span::after{left:23px}
.cut-estado-bel{font-size:.8rem;color:var(--v);margin:.3rem 0;min-height:1em}
.cut-formatos{display:grid;grid-template-columns:repeat(4,1fr);gap:.45rem}
.cut-formatos button{background:var(--f2);border:1px solid var(--b);border-radius:14px;padding:.6rem .3rem;display:flex;flex-direction:column;align-items:center;gap:.25rem}
.cut-formatos button.on{border-color:var(--v);color:var(--v)}
.cut-formatos .caja-asp{height:34px;max-width:46px;border:2px solid currentColor;border-radius:4px;display:block}
.cut-formatos small{font-size:.62rem;color:var(--m);text-align:center;line-height:1.15}
.cut-tabs{flex:0 0 auto;display:flex;justify-content:space-around;border-top:1px solid var(--b);background:var(--f2);padding:6px 4px calc(env(safe-area-inset-bottom,0px) + 6px)}
.cut-tab{flex:1;background:transparent;border:0;display:flex;flex-direction:column;align-items:center;gap:2px;font-size:.66rem;font-weight:700;color:var(--m);padding:4px 0}
.cut-tab svg{width:22px;height:22px}
.cut-tab.on{color:var(--v)}
.cut-modal{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:50;display:flex;align-items:flex-end;justify-content:center}
@media (min-width:700px){.cut-modal{align-items:center}}
.cut-hoja{background:var(--f2);border:1px solid var(--b);border-radius:22px 22px 0 0;width:100%;max-width:520px;padding:18px 18px calc(env(safe-area-inset-bottom,0px) + 18px);max-height:90dvh;overflow-y:auto}
@media (min-width:700px){.cut-hoja{border-radius:22px}}
.cut-hoja h3{margin:0 0 .4rem;font-family:Unbounded,sans-serif;font-size:1.05rem}
.cut-hoja p{color:var(--m);margin:.2rem 0 .8rem;font-size:.9rem}
.cut-hoja .fila-b{display:flex;gap:.5rem;justify-content:flex-end;margin-top:.9rem;flex-wrap:wrap}
.cut-hoja textarea,.cut-hoja input[type=number],.cut-lista input{width:100%;background:var(--f);border:1px solid var(--b);color:var(--t);border-radius:12px;padding:.6rem;font:inherit}
.cut-hoja .dos{display:flex;gap:.5rem;margin-top:.5rem}
.cut-hoja .dos label{flex:1;font-size:.78rem;color:var(--m);font-weight:700}
.cut-lista{display:flex;flex-direction:column;gap:.4rem;max-height:55dvh;overflow-y:auto}
.cut-lista .it{display:flex;gap:.5rem;align-items:center}
.cut-lista small{flex:0 0 52px;color:var(--m);font-variant-numeric:tabular-nums}
.cut-exp-run{padding:.6rem 0}
.cut-exp-run .cut-progreso{height:34px}
.cut-exp-run .cut-progreso span{line-height:34px;font-size:.85rem}
.cut-exp-fin video{width:100%;max-height:46dvh;background:#000;border-radius:14px}
.cut-exp-info{font-size:.8rem;color:var(--m);margin:.5rem 0}
.cut-hoja .lbl{font-size:.78rem;color:var(--m);font-weight:700;margin:.7rem 0 .35rem}
.cut-toasts{position:fixed;left:50%;top:calc(env(safe-area-inset-top,0px) + 62px);transform:translateX(-50%);z-index:80;display:flex;flex-direction:column;gap:.4rem;width:min(92vw,420px);pointer-events:none}
.cut-toast{background:#fff;color:var(--f);border-radius:14px;padding:.65rem .9rem;font-weight:700;font-size:.88rem;box-shadow:0 8px 24px rgba(0,0,0,.35);transition:.4s}
.cut-toast.mal{background:#ffe3ee;color:#9b1c5a}
.cut-toast.fuera{opacity:0;transform:translateY(-8px)}
.cut-cargando{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:60;display:grid;place-items:center}
.cut-cargando div{background:var(--f2);border:1px solid var(--b);border-radius:18px;padding:1rem 1.3rem;font-weight:700;display:flex;align-items:center;gap:.7rem}
.cut-cargando i{width:22px;height:22px;border-radius:50%;border:3px solid var(--b);border-top-color:var(--v);animation:gira 0.8s linear infinite}
@keyframes gira{to{transform:rotate(360deg)}}
#cutVideos{position:fixed;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;left:-10px;top:-10px}
@media (min-width:1000px){
  body{display:grid;grid-template-columns:1fr 380px;grid-template-rows:auto 1fr auto auto auto;height:100dvh}
  .cut-top{grid-column:1/3}
  #cutVistaCaja{grid-column:1;grid-row:2}
  .cut-ctrl{grid-column:1;grid-row:3}
  .cut-linea{grid-column:1/3;grid-row:4;height:128px}
  #cutPanel{grid-column:2;grid-row:2/4;max-height:none;border-left:1px solid var(--b)}
  .cut-tabs{grid-column:1/3;grid-row:5;justify-content:center;gap:1.5rem}
  .cut-tab{flex:0 0 auto;min-width:80px}
}
</style>
</head>
<body>
<header class="cut-top">
    <a class="volver" href="{{ url('/') }}" aria-label="Volver">‹</a>
    <div class="cut-logo"><i>✂</i>LocalPro<b>Cut</b></div>
    <span class="sp"></span>
    <button type="button" class="nuevo" id="cutNuevo">Nuevo</button>
    <button type="button" class="cut-exp" id="cutBtnExportar">Exportar</button>
</header>

<div id="cutVistaCaja">
    <canvas id="cutLienzo" width="540" height="960"></canvas>
    <div class="cut-vacio" id="cutVacio">
        <h2>Edite sus videos con <b>LocalPro</b></h2>
        <p>Subtítulos automáticos, transiciones, filtro de belleza y exporte en 4K a 60 fps. Todo aquí mismo, sin instalar nada.</p>
        <button type="button" class="grande" data-agregar><span>+</span> Agregar videos o fotos</button>
        <ul><li>✨ Subtítulos con estilos</li><li>◆ Transiciones</li><li>💄 Belleza</li><li>🎬 4K · 60 fps</li></ul>
    </div>
</div>

<div class="cut-ctrl">
    <button type="button" id="cutPlay" aria-label="Reproducir">
        <svg class="pl" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4.5v15l13-7.5z"/></svg>
        <svg class="pa" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
    </button>
    <span id="cutTiempo">00:00.0 / 00:00.0</span>
    <span class="sp"></span>
    <button type="button" class="zm" id="cutZoomMenos" aria-label="Alejar">−</button>
    <button type="button" class="zm" id="cutZoomMas" aria-label="Acercar">+</button>
</div>

<div class="cut-linea">
    <div id="cutLinea"><div id="cutLineaIn"><div id="cutPistaV"></div><div id="cutPistaS" hidden></div></div></div>
    <div class="cut-ph"></div>
</div>

<section id="cutPanel"></section>

<nav class="cut-tabs">
    <button type="button" class="cut-tab" data-tab="agregar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M12 8v8M8 12h8"/></svg>Agregar</button>
    <button type="button" class="cut-tab on" data-tab="editar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12"/></svg>Editar</button>
    <button type="button" class="cut-tab" data-tab="transicion"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3 21 12 12 21 3 12z"/></svg>Transición</button>
    <button type="button" class="cut-tab" data-tab="subtitulos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M6 12h4M14 12h4M6 15h8"/></svg>Subtítulos</button>
    <button type="button" class="cut-tab" data-tab="belleza"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 14.5s1.3 2 3.5 2 3.5-2 3.5-2M9 9.5h.01M15 9.5h.01"/></svg>Belleza</button>
    <button type="button" class="cut-tab" data-tab="formato"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/></svg>Formato</button>
</nav>

<input type="file" id="cutArchivo" accept="video/*,image/*" multiple hidden>
<div id="cutVideos"></div>
<div class="cut-toasts" id="cutToasts"></div>
<div class="cut-cargando" id="cutCargando" hidden><div><i></i><span>Cargando…</span></div></div>

<div class="cut-modal" id="cutPregunta" hidden>
    <div class="cut-hoja"><h3></h3><p></p><div class="fila-b"><button type="button" class="cut-btn-sec" data-no></button><button type="button" class="cut-exp" data-si></button></div></div>
</div>

<div class="cut-modal" id="cutEditSub" hidden>
    <div class="cut-hoja">
        <h3>Editar subtítulo</h3>
        <textarea rows="2"></textarea>
        <div class="dos">
            <label>Empieza (seg)<input type="number" step="0.1" min="0" data-ini></label>
            <label>Termina (seg)<input type="number" step="0.1" min="0" data-fin></label>
        </div>
        <div class="fila-b"><button type="button" class="cut-btn-sec" data-quitar>🗑 Quitar</button><button type="button" class="cut-btn-sec" data-cerrar>Cancelar</button><button type="button" class="cut-exp" data-guardar>Guardar</button></div>
    </div>
</div>

<div class="cut-modal" id="cutListaSubs" hidden>
    <div class="cut-hoja">
        <h3>Textos de los subtítulos</h3>
        <p>Corrija las palabras que la inteligencia artificial no entendió bien.</p>
        <div class="cut-lista"></div>
        <div class="fila-b"><button type="button" class="cut-btn-sec" data-cerrar>Cancelar</button><button type="button" class="cut-exp" data-guardar>Guardar</button></div>
    </div>
</div>

<div class="cut-modal" id="cutExportar" hidden>
    <div class="cut-hoja">
        <h3>Exportar video</h3>
        <div class="cut-exp-op">
            <div class="lbl">Resolución</div>
            <div class="cut-chips" data-res></div>
            <div class="lbl">Fluidez</div>
            <div class="cut-chips" data-fps><button type="button" class="cut-chip" data-v="30">30 fps</button><button type="button" class="cut-chip" data-v="60">60 fps · más fluido</button></div>
            <div class="cut-exp-info" data-info></div>
            <p>El video se arma en su equipo: deje esta pantalla abierta hasta que termine. En 4K tarda más, sobre todo en el celular.</p>
            <div class="fila-b"><button type="button" class="cut-btn-sec" data-cerrar>Cancelar</button><button type="button" class="cut-exp" data-iniciar>Exportar</button></div>
        </div>
        <div class="cut-exp-run" hidden>
            <div class="cut-progreso"><i></i><span>Preparando…</span></div>
            <div class="fila-b"><button type="button" class="cut-btn-sec" data-cancelar>Cancelar</button></div>
        </div>
        <div class="cut-exp-fin" hidden>
            <video controls playsinline></video>
            <div class="fila-b"><button type="button" class="cut-btn-sec" data-otra>Exportar otra vez</button><button type="button" class="cut-btn-sec" data-compartir>Compartir</button><button type="button" class="cut-exp" data-bajar>Descargar</button></div>
        </div>
    </div>
</div>

@php $cv = fn ($f) => '/js/cut/' . $f . '?v=' . @filemtime(public_path('js/cut/' . $f)); @endphp
<script type="importmap">{!! json_encode(['imports' => collect(['motor.js', 'subtitulos.js', 'belleza.js', 'mediabunny.min.mjs'])->mapWithKeys(fn ($f) => ['/js/cut/' . $f => $cv($f)])->all()], JSON_UNESCAPED_SLASHES) !!}</script>
<script type="module" src="{{ $cv('cut.js') }}"></script>
</body>
</html>
