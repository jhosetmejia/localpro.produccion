<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<meta name="theme-color" content="#0b0d0c">
<title>LocalProCut · Edite sus videos gratis en el navegador</title>
<meta name="description" content="Editor de videos gratis de LocalProo, sin marca de agua: subtítulos automáticos con estilos, transiciones, filtro de belleza y exportar en 4K a 60 fps. Descargue la app LocalProCut en su celular.">
<meta property="og:title" content="LocalProCut · Edite sus videos con LocalProo">
<meta property="og:description" content="Subtítulos automáticos, transiciones, filtro de belleza y 4K a 60 fps. Gratis en el navegador.">
<link rel="icon" href="/icons/cut-192.png" type="image/png">
<link rel="manifest" href="/cut.webmanifest">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="LocalProCut">
<link rel="apple-touch-icon" href="/icons/cut-apple-touch.png">
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
.cut-app{display:flex;align-items:center;gap:.35rem;background:var(--f3);border:1px solid var(--b);border-radius:999px;padding:.35rem .7rem;font-size:.78rem;font-weight:800;color:var(--v)}
.cut-app img{width:18px;height:18px;border-radius:5px}
@media (max-height:720px){.cut-linea{height:168px}.cut-ctrl{padding-top:0;padding-bottom:0}}
@media (max-width:440px){.cut-top{gap:.4rem;padding-left:8px;padding-right:10px}.cut-app span{display:none}.cut-app{padding:.3rem .45rem}.cut-logo{font-size:.86rem;gap:.3rem}.cut-logo i{width:22px;height:22px;font-size:.7rem}.cut-exp{padding:.45rem .8rem!important;font-size:.85rem}}
.es-cutapp .solo-web{display:none!important}
.cut-vacio .app-baja{margin-top:.4rem;display:flex;align-items:center;gap:.55rem;background:var(--f3);border:1px solid var(--b);border-radius:16px;padding:.6rem .9rem;color:var(--t);font-weight:700;font-size:.88rem}
.cut-vacio .app-baja img{width:34px;height:34px;border-radius:9px}
.cut-vacio .app-baja small,.cut-amigo .app-baja small{display:block;color:var(--m);font-weight:600;font-size:.74rem}
.app-baja{position:relative;overflow:hidden;text-align:left}
.app-baja .flecha{font-style:normal;margin-left:auto;width:30px;height:30px;border-radius:50%;background:var(--v);color:var(--f);display:grid;place-items:center;font-weight:900;animation:baja 1.6s ease-in-out infinite}
.app-baja::after{content:"";position:absolute;top:0;bottom:0;width:40%;left:-60%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.14),transparent);animation:brillo 3.2s ease-in-out infinite}
@keyframes baja{0%,100%{transform:translateY(-2px)}50%{transform:translateY(3px)}}
@keyframes brillo{0%,55%{left:-60%}100%{left:130%}}
.cut-reg{display:flex;align-items:center;gap:.45rem;justify-content:center;margin-top:.15rem;color:var(--v);font-weight:800;font-size:.92rem;text-decoration:none;border:1.5px dashed rgba(166,243,74,.45);border-radius:16px;padding:.6rem 1rem}
.cut-reg span{text-decoration:underline;text-underline-offset:3px}
/* Animación de la pantalla inicial: celular editando un video, funciones flotando y el ícono de la app */
.cut-hero{position:relative;width:250px;height:190px;flex:0 0 auto;margin-bottom:.2rem}
.cut-hero .cel{position:absolute;left:50%;top:6px;width:96px;height:172px;margin-left:-48px;border-radius:20px;background:#050605;border:3px solid #2b332e;box-shadow:0 18px 40px rgba(0,0,0,.55),0 0 50px rgba(166,243,74,.12);animation:flotar 4s ease-in-out infinite}
.cut-hero .pant{position:absolute;inset:6px;border-radius:14px;overflow:hidden;background:linear-gradient(160deg,#16b84e,#7a3dff 55%,#ff3d6e);background-size:200% 200%;animation:fondoMov 6s ease infinite}
.cut-hero .luz{position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.35),transparent 45%);animation:fondoMov 5s ease infinite reverse}
.cut-hero .sub{position:absolute;left:0;right:0;top:36%;text-align:center;font-family:Montserrat,sans-serif;font-weight:900;font-size:.8rem;line-height:1.05;letter-spacing:-.01em;color:#fff;-webkit-text-stroke:.6px #000;animation:pop 2.4s ease-in-out infinite}
.cut-hero .sub em{font-style:normal;color:var(--v)}
.cut-hero .lin{position:absolute;left:5px;right:5px;bottom:7px;height:18px;display:flex;gap:2px}
.cut-hero .lin i{flex:1;border-radius:3px;background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.35)}
.cut-hero .lin span{position:absolute;top:-3px;bottom:-3px;width:2px;background:#fff;border-radius:2px;left:0;animation:cabezal 3.5s linear infinite}
.cut-hero .flota{position:absolute;background:var(--f3);border:1px solid var(--b);border-radius:999px;padding:.25rem .6rem;font-size:.7rem;font-weight:800;color:#e6ece8;white-space:nowrap;box-shadow:0 6px 18px rgba(0,0,0,.4);animation:flotar 3.6s ease-in-out infinite}
.cut-hero .f1{left:0;top:22px;animation-delay:-.4s}.cut-hero .f2{right:0;top:44px;animation-delay:-1.3s}
.cut-hero .f3{left:4px;top:112px;animation-delay:-2.1s}.cut-hero .f4{right:-6px;top:132px;animation-delay:-.9s}
.cut-hero .icono{position:absolute;left:50%;bottom:-6px;width:46px;height:46px;margin-left:12px;border-radius:13px;box-shadow:0 8px 22px rgba(22,184,78,.45);animation:rebote 2.4s cubic-bezier(.3,1.6,.5,1) infinite}
@keyframes flotar{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@keyframes fondoMov{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@keyframes pop{0%,15%{transform:scale(.6);opacity:0}25%,80%{transform:scale(1);opacity:1}100%{transform:scale(1.08);opacity:0}}
@keyframes cabezal{from{left:0}to{left:100%}}
@keyframes rebote{0%,60%,100%{transform:translateY(0) scale(1)}30%{transform:translateY(-12px) scale(1.06)}}
@media (prefers-reduced-motion:reduce){.cut-hero *,.app-baja .flecha,.app-baja::after{animation:none!important}}
@media (max-height:860px){.cut-hero{transform:scale(.72);margin:-26px 0 -30px}}
@media (max-height:740px){.cut-hero{display:none}}
/* Al terminar de exportar: programar en redes (crea la cuenta de creador si no tiene) */
.cut-programar{margin-top:.8rem;width:100%;display:flex;align-items:center;gap:.7rem;border:0;border-radius:16px;padding:.85rem 1rem;font-size:1.5rem;
  background:linear-gradient(120deg,#7a3dff,#ff3d6e);color:#fff;text-align:left;box-shadow:0 10px 26px -10px rgba(122,61,255,.7);animation:pulsoProg 2.4s ease-in-out infinite}
.cut-programar span{font-size:1rem;font-weight:800;line-height:1.2}
.cut-programar small{display:block;font-size:.74rem;font-weight:600;opacity:.9}
@keyframes pulsoProg{0%,100%{transform:scale(1)}50%{transform:scale(1.02)}}
/* Al terminar de exportar: compartir con un amigo y conocer LocalPro */
.cut-amigo{margin-top:1rem;border-top:1px solid var(--b);padding-top:.9rem;display:flex;flex-direction:column;gap:.55rem}
.cut-amigo>b{font-size:1rem}
.cut-amigo p{margin:0;font-size:.84rem}
.cut-amigo-b{display:flex;gap:.5rem;flex-wrap:wrap}
.cut-amigo-b .wa{flex:1 1 180px;display:flex;align-items:center;justify-content:center;gap:.45rem;background:#25d366;color:#06290f;font-weight:800;border-radius:14px;padding:.75rem;text-decoration:none}
.cut-amigo-b .wa svg{width:20px;height:20px}
.cut-amigo-b .cut-btn-sec{flex:1 1 140px}
.cut-amigo .app-baja{display:flex;align-items:center;gap:.55rem;background:var(--f3);border:1px solid var(--b);border-radius:16px;padding:.6rem .9rem;color:var(--t);font-weight:700;font-size:.88rem}
.cut-amigo .app-baja img{width:34px;height:34px;border-radius:9px}
.cut-lp{color:#cfd8d2;font-weight:700;font-size:.84rem;text-decoration:none;background:var(--f3);border:1px solid var(--b);border-radius:14px;padding:.65rem .8rem}
.cut-guia li{display:flex;gap:.6rem;align-items:center;padding:.55rem 0;border-top:1px solid var(--b);font-weight:700;font-size:.92rem}
.cut-guia ol{list-style:none;padding:0;margin:0 0 .6rem;counter-reset:p}
.cut-guia li::before{counter-increment:p;content:counter(p);flex:0 0 26px;height:26px;border-radius:50%;background:var(--v);color:var(--f);display:grid;place-items:center;font-weight:900;font-size:.8rem}
.cut-guia h3{display:flex;align-items:center;gap:.6rem}
.cut-guia h3 img{width:42px;height:42px;border-radius:11px}
/* Animación al descargar la app: el ícono cae en la pantalla del celular y, al terminar, confeti */
.inst{position:relative;height:170px;margin:-4px 0 6px;display:flex;justify-content:center}
.inst .tel{position:absolute;bottom:0;width:120px;height:160px;border-radius:24px 24px 0 0;background:#050605;border:3px solid #2b332e;border-bottom:0;box-shadow:0 0 50px rgba(166,243,74,.15)}
.inst .tel-pant{position:absolute;inset:14px 12px 0;display:grid;grid-template-columns:repeat(3,1fr);gap:9px;align-content:start}
.inst .tel-pant span,.inst .hueco{aspect-ratio:1;border-radius:9px;background:#1f2622}
.inst .hueco{background:transparent;border:2px dashed rgba(166,243,74,.55);animation:latir 1.2s ease-in-out infinite}
.inst .cae{position:absolute;width:30px;height:30px;border-radius:9px;left:calc(50% + 12px);top:66px;opacity:0;animation:caer 2.6s cubic-bezier(.35,1.4,.55,1) infinite}
.cut-guia.listo .inst .cae{animation:none;opacity:1;transform:scale(1.25);box-shadow:0 0 0 4px var(--v),0 0 30px rgba(166,243,74,.6)}
.cut-guia.listo .inst .hueco{opacity:0}
.inst .confeti{position:absolute;inset:0;pointer-events:none;display:none}
.cut-guia.listo .inst .confeti{display:block}
.inst .confeti i{position:absolute;top:70px;left:50%;width:8px;height:12px;border-radius:2px;background:var(--v);animation:confeti 1.4s ease-out forwards}
.inst .confeti i:nth-child(3n){background:#ff3d6e}.inst .confeti i:nth-child(3n+1){background:#ffd23d}.inst .confeti i:nth-child(4n){background:#39d5ff}
.inst .confeti i:nth-child(1){--x:-110px;--y:-60px}.inst .confeti i:nth-child(2){--x:-80px;--y:-110px}.inst .confeti i:nth-child(3){--x:-40px;--y:-130px}.inst .confeti i:nth-child(4){--x:0px;--y:-140px}.inst .confeti i:nth-child(5){--x:40px;--y:-125px}
.inst .confeti i:nth-child(6){--x:85px;--y:-105px}.inst .confeti i:nth-child(7){--x:115px;--y:-50px}.inst .confeti i:nth-child(8){--x:-130px;--y:10px}.inst .confeti i:nth-child(9){--x:130px;--y:15px}.inst .confeti i:nth-child(10){--x:20px;--y:-90px}
@keyframes caer{0%{opacity:0;transform:translateY(-90px) scale(1.6) rotate(-12deg)}25%{opacity:1}55%,85%{opacity:1;transform:translateY(0) scale(1) rotate(0)}100%{opacity:0;transform:translateY(0) scale(.9)}}
@keyframes latir{50%{transform:scale(.88);opacity:.6}}
@keyframes confeti{0%{transform:translate(-50%,0) rotate(0);opacity:1}100%{transform:translate(calc(-50% + var(--x)),var(--y)) rotate(540deg);opacity:0}}
@media (prefers-reduced-motion:reduce){.inst *{animation:none!important}.inst .cae{opacity:1}}
.cut-opinar{display:flex;align-items:center;gap:.3rem;background:transparent;border:1px solid var(--b);border-radius:999px;padding:.35rem .7rem;font-size:.8rem;font-weight:700;color:var(--t)}
@media (max-width:440px){.cut-opinar span{display:none}.cut-opinar{padding:.3rem .5rem}}
.cut-exp{background:linear-gradient(90deg,var(--v),var(--v2));border:0;border-radius:999px;padding:.5rem 1.05rem;font-weight:800;color:var(--f)!important}
#cutVistaCaja{flex:1 1 0;min-height:120px;position:relative;overflow:hidden;background:radial-gradient(circle at 50% 40%,#161c18,#0b0d0c 70%)}
#cutLienzo{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:auto;height:auto;max-width:calc(100% - 20px);max-height:calc(100% - 20px);background:#000;border-radius:6px;box-shadow:0 8px 30px rgba(0,0,0,.5)}
.cut-vacio{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;text-align:center;padding:16px 20px;gap:.55rem;background:var(--f);overflow-y:auto}
.cut-vacio::before,.cut-vacio::after{content:"";flex:1 1 0}
.cut-vacio>*{flex-shrink:0}
.cut-vacio h2{font-family:Unbounded,sans-serif;font-size:1.35rem;margin:.2rem 0 0;line-height:1.2}
.cut-vacio h2 b{color:var(--v)}
.cut-vacio p{margin:0;color:var(--m);max-width:34ch}
.cut-vacio .grande{margin-top:.8rem;background:linear-gradient(90deg,var(--v),var(--v2));border:0;border-radius:18px;padding:1rem 1.6rem;font-weight:800;color:var(--f);font-size:1.05rem;display:flex;align-items:center;gap:.5rem;box-shadow:0 10px 30px rgba(22,184,78,.25)}
.cut-vacio .grande span{font-size:1.4rem;line-height:1}
.cut-vacio ul{list-style:none;padding:0;margin:.9rem 0 0;display:flex;flex-wrap:wrap;justify-content:center;gap:.4rem;max-width:420px}
.cut-vacio.con-proys .cut-hero{display:none}
.cut-proys{width:100%;max-width:460px;text-align:left;margin-top:.3rem}
.cut-proys h3{font-size:.85rem;margin:.2rem 0 .45rem;color:var(--m);font-weight:700;display:flex;justify-content:space-between}
.cut-proys .lista{display:flex;gap:.55rem;overflow-x:auto;padding-bottom:.3rem;scroll-snap-type:x mandatory}
.cut-proy{position:relative;flex:0 0 112px;scroll-snap-align:start;background:var(--f3);border:1px solid var(--b);border-radius:14px;overflow:hidden;color:var(--t);padding:0;text-align:left}
.cut-proy img,.cut-proy .sin{display:block;width:100%;aspect-ratio:9/12;object-fit:cover;background:#111}
.cut-proy .sin{display:grid;place-items:center;font-size:1.6rem}
.cut-proy span{display:block;padding:.35rem .45rem .45rem;font-size:.72rem;line-height:1.25}
.cut-proy small{display:block;color:var(--m);font-size:.66rem}
.cut-proy .bor{position:absolute;top:5px;right:5px;width:26px;height:26px;border-radius:50%;border:0;background:rgba(0,0,0,.6);color:#fff;font-size:.8rem;display:grid;place-items:center}
.cut-vacio li{background:var(--f3);border:1px solid var(--b);border-radius:999px;padding:.3rem .7rem;font-size:.8rem;color:#cfd8d2}
.cut-ctrl{display:flex;align-items:center;gap:.4rem;padding:4px 12px;flex:0 0 auto}
.cut-ctrl .sp{flex:1}
.cut-ctrl .ic{width:38px;height:38px;border:0;background:transparent;color:var(--t);display:grid;place-items:center;border-radius:10px}
.cut-ctrl .ic svg{width:22px;height:22px}
.cut-ctrl .ic:disabled{opacity:.3}
#cutPlay{width:44px;height:44px;border-radius:50%;border:0;background:transparent;color:var(--t);display:grid;place-items:center}
#cutPlay svg{width:26px;height:26px}
#cutPlay .pa{display:none}#cutPlay.on .pl{display:none}#cutPlay.on .pa{display:block}
.cut-linea{position:relative;flex:0 0 auto;height:176px;background:var(--f)}
#cutTiempo{position:absolute;left:12px;top:2px;z-index:6;font-variant-numeric:tabular-nums;font-size:.78rem;font-weight:700;color:#cfd8d2;background:var(--f);padding:0 4px}
.cut-zoomt{position:absolute;right:8px;top:0;z-index:6;display:flex;gap:4px}
.cut-zoomt button{width:24px;height:20px;border-radius:6px;border:1px solid var(--b);background:var(--f2);font-weight:800;font-size:.8rem;line-height:1;padding:0}
#cutLinea{position:absolute;inset:0;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;touch-action:pan-x}
#cutLinea::-webkit-scrollbar{display:none}
#cutLineaIn{position:relative;height:100%}
#cutRegla{position:absolute;left:0;right:0;top:22px;height:14px}
#cutRegla span{position:absolute;transform:translateX(-50%);font-size:.62rem;color:var(--m);font-variant-numeric:tabular-nums}
#cutRegla i{position:absolute;top:6px;width:3px;height:3px;margin-left:-1.5px;border-radius:50%;background:var(--m)}
#cutIzq{position:absolute;top:40px;width:152px;height:170px;z-index:4;pointer-events:none}
#cutIzq .cut-portada{pointer-events:auto}
#cutIzq .cut-sil{pointer-events:auto}
#cutPistaV,#cutPistaA,#cutPistaT,#cutRegla{pointer-events:none}
#cutPistaV>*,#cutPistaA>*,#cutPistaT>*{pointer-events:auto}
.cut-sil{position:absolute;top:-4px;left:-6px;width:84px;height:68px;touch-action:manipulation;-webkit-user-select:none;user-select:none;border:0;background:transparent;color:#e6ece8;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;font-size:.66rem;font-weight:700;line-height:1.15;text-align:center;padding:0}
.cut-sil svg{width:26px;height:26px}
.cut-sil.on{color:#ff8fb8}
.cut-fila-ic{position:absolute;right:6px;width:30px;height:30px;border-radius:8px;background:var(--f3);display:grid;place-items:center;color:#cfd8d2}
.cut-fila-ic svg{width:17px;height:17px}
.cut-ph{position:absolute;left:50%;top:22px;bottom:4px;width:2px;margin-left:-1px;background:#fff;border-radius:2px;pointer-events:none;z-index:5;box-shadow:0 0 6px rgba(0,0,0,.6)}
#cutPistaV{position:absolute;left:0;right:0;top:42px;height:58px}
#cutPistaA{position:absolute;left:0;right:0;top:107px;height:30px}
#cutPistaT{position:absolute;left:0;right:0;top:141px;height:30px}
#cutPistaC{position:absolute;left:0;right:0;top:175px;height:30px}
body.con-capas .cut-linea{height:210px}
.cut-capa{position:absolute;top:0;height:28px;border-radius:6px;background:#7a3dff;border:1px solid #a98bff;color:#fff;font-size:.68rem;font-weight:700;padding:6px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
.cut-capa.sel{border-color:#fff;box-shadow:0 0 0 1px #fff}
.cut-portada{position:absolute;top:-2px;left:82px;width:64px;height:62px;border-radius:10px;border:0;padding:0;background:#2a332d center/cover;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;overflow:hidden;box-shadow:inset 0 0 0 100px rgba(0,0,0,.35);touch-action:manipulation}
.cut-portada svg{width:18px;height:18px}
.cut-portada small{font-size:.62rem;font-weight:800;text-shadow:0 1px 3px rgba(0,0,0,.8)}
.cut-portada-v{display:grid;place-items:center;margin-bottom:.6rem}
.cut-portada-v img{max-height:160px;border-radius:10px}
.cut-portada-v span{color:var(--m);font-size:.82rem}
.cut-efs,.cut-fils{display:grid;grid-template-columns:repeat(auto-fill,minmax(74px,1fr));gap:.5rem;margin-bottom:.4rem}
.cut-efs button,.cut-fils button{background:var(--f3);border:1.5px solid var(--b);border-radius:12px;padding:.5rem .2rem;display:flex;flex-direction:column;align-items:center;gap:.25rem}
.cut-efs i{font-style:normal;font-size:1.4rem;line-height:1}
.cut-fils i{width:44px;height:44px;border-radius:10px}
.cut-efs small,.cut-fils small{font-size:.66rem;font-weight:700;color:#cfd8d2;text-align:center;line-height:1.1}
.cut-efs .on,.cut-fils .on{border-color:var(--v);background:#1d2a1f}
.cut-emos{display:grid;grid-template-columns:repeat(auto-fill,minmax(46px,1fr));gap:.35rem}
.cut-emos button{font-size:1.6rem;line-height:1;background:var(--f3);border:0;border-radius:10px;padding:.4rem 0;font-family:"Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif}
#cutClave.on{color:var(--v)}
.cut-clip{position:absolute;top:0;height:58px;border-radius:6px;background:#2a332d center/cover;border:2px solid transparent;overflow:hidden;cursor:pointer}
.cut-clip{-webkit-touch-callout:none;-webkit-user-select:none;user-select:none}
/* Cambiar el orden de los clips: cuadritos con la miniatura (mantener presionado un clip) */
.cut-orden{position:fixed;z-index:40;height:72px;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;background:linear-gradient(180deg,rgba(11,13,12,.94),rgba(11,13,12,.94));border-radius:12px;touch-action:none}
.cut-orden::-webkit-scrollbar{display:none}
.cut-orden .fila{display:flex;gap:8px;padding:10px 14px;height:100%;box-sizing:border-box}
.cut-orden .cuad{flex:0 0 52px;height:52px;border-radius:10px;background:#2a332d left center/auto 100% no-repeat;border:2px solid #3a443e;position:relative;transition:transform .15s}
.cut-orden .cuad span{position:absolute;left:3px;bottom:2px;font-size:.58rem;font-weight:800;background:rgba(0,0,0,.6);border-radius:4px;padding:0 3px;color:#fff}
.cut-orden .hueco{background:transparent;border:2px dashed var(--v)}
.cut-orden .fantasma{position:absolute;top:4px;width:52px;height:60px;flex:none;border:2px solid #fff;transform:scale(1.15);box-shadow:0 10px 26px rgba(0,0,0,.6);z-index:2;pointer-events:none}
body.ordenando #cutPistaV{opacity:.12}
body.ordenando{-webkit-user-select:none;user-select:none}
.cut-clip span{position:absolute;left:4px;bottom:3px;font-size:.64rem;font-weight:700;background:rgba(0,0,0,.6);padding:1px 5px;border-radius:5px}
.cut-clip em{position:absolute;left:4px;top:3px;font-style:normal;font-size:.68rem;font-weight:800;padding:1px 2px;color:#fff;display:flex;align-items:center;gap:4px;text-shadow:0 1px 3px rgba(0,0,0,.9)}
.cut-clip em:empty{display:none}
.cut-clip em svg{width:13px;height:13px;filter:drop-shadow(0 1px 2px rgba(0,0,0,.8))}
.cut-clip em .ef{font-style:normal}
.cut-clip.sel{border-color:#fff;border-width:3px 2px;box-shadow:0 0 0 1px #fff;overflow:visible;z-index:4}
/* Tiradores blancos a los lados del clip seleccionado (como CapCut): se arrastran para recortar el inicio o el final */
.cut-tir{position:absolute;top:-3px;bottom:-3px;width:20px;background:#fff;z-index:4;touch-action:none;cursor:ew-resize;display:grid;place-items:center;box-shadow:0 2px 8px rgba(0,0,0,.45)}
.cut-tir::after{content:"";width:3px;height:18px;border-radius:2px;background:#8a958f}
.cut-tir::before{content:"";position:absolute;top:-8px;bottom:-8px;left:-10px;right:-10px} /* área de toque más grande */
.cut-tir.izq{left:-20px;border-radius:9px 0 0 9px}
.cut-tir.der{right:-20px;border-radius:0 9px 9px 0}
.cut-tir:active{background:var(--v)}
body.recortando .cut-clip.sel>span{font-size:.85rem;background:var(--v);color:#0b0d0c}
.cut-volver{flex:0 0 auto;width:44px;align-self:stretch;margin-right:4px;border:0;border-radius:10px;background:var(--f3);position:sticky;left:0;z-index:2;box-shadow:8px 0 10px var(--f);color:#cfd8d2;display:grid;place-items:center}
.cut-volver svg{width:22px;height:22px}
.cut-sep{flex:0 0 1px;align-self:center;height:28px;background:var(--b);margin:0 4px}
.cut-clip.mudo{filter:saturate(.6)}
.cut-rombo{position:absolute;top:15px;width:26px;height:28px;border-radius:6px;border:0;background:#fff;color:var(--f);z-index:3;display:grid;place-items:center;box-shadow:0 2px 8px rgba(0,0,0,.5);padding:0}
.cut-rombo svg{width:15px;height:15px}
.cut-rombo.on{background:var(--v)}
.cut-mas{position:absolute;top:8px;width:42px;height:42px;border-radius:9px;border:0;background:#fff;color:var(--f);display:grid;place-items:center;padding:0}
.cut-mas svg{width:24px;height:24px}
.cut-agregar{position:absolute;top:0;height:30px;min-width:150px;border:0;border-radius:6px;background:var(--f2);color:#cfd8d2;font-size:.74rem;font-weight:700;text-align:left;padding:0 10px;white-space:nowrap}
.cut-mus{position:absolute;top:0;height:28px;border-radius:6px;background:#1f6fa8 no-repeat;border:1px solid #3a93d1;color:#fff;font-size:.68rem;font-weight:700;padding:6px 8px;white-space:nowrap;overflow:hidden;cursor:pointer}
.cut-mus span{position:relative;text-shadow:0 1px 3px rgba(0,0,0,.7)}
.cut-mus.sel{border-color:#fff}
.cut-sub{position:absolute;top:0;height:28px;border-radius:6px;background:#f26b1d;border:1px solid #ff9a5c;color:#fff;font-size:.68rem;font-weight:700;padding:6px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
.cut-acc-audio button{flex:1 1 0;min-width:120px;padding:.8rem .5rem;font-size:.82rem}
.cut-voz-rec{position:fixed;left:50%;top:max(14px,env(safe-area-inset-top));transform:translateX(-50%);z-index:70;display:flex;align-items:center;gap:.5rem;padding:.6rem 1.1rem;border:0;border-radius:999px;background:#e5243b;color:#fff;font:600 .9rem/1 system-ui,sans-serif;box-shadow:0 6px 24px rgba(229,36,59,.45)}
.cut-voz-rec i{width:10px;height:10px;border-radius:50%;background:#fff;animation:vozlat 1s infinite}
.cut-voz-rec b{background:rgba(0,0,0,.25);padding:.25rem .55rem;border-radius:999px}
@keyframes vozlat{50%{opacity:.25}}
.cut-panel-h{display:flex;align-items:center;gap:.5rem;margin:-4px 0 8px}
.cut-panel-h button{width:30px;height:30px;border-radius:9px;border:1px solid var(--b);background:var(--f2);font-size:1.2rem;line-height:1;padding:0}
.cut-panel-h b{font-size:.92rem}
.cut-val{font-size:.78rem;min-width:44px;text-align:right}
body.pantalla .cut-top,body.pantalla .cut-linea,body.pantalla #cutPanel,body.pantalla .cut-tabs{display:none!important}
#cutPanel{flex:0 1 auto;min-height:96px;max-height:36vh;overflow-y:auto;padding:12px 14px;background:var(--f2);border-top:1px solid var(--b);border-radius:16px 16px 0 0}
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
.cut-modo{display:flex;justify-content:center;margin-bottom:.6rem}
.cut-vel-num{text-align:center;margin:.2rem 0 .3rem}
.cut-vel-num b{display:block;font-family:Unbounded,sans-serif;font-size:1.5rem;color:var(--v)}
.cut-vel-num small{color:var(--m);font-size:.78rem}
.cut-vel-rango{width:100%;accent-color:var(--v)}
.cut-vel-marcas{display:flex;justify-content:space-between;font-size:.7rem;color:var(--m);margin:-.1rem 0 .6rem}
.cut-curvas{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem}
.cut-curvas button{background:var(--f2);border:1px solid var(--b);border-radius:12px;padding:.45rem .2rem;display:flex;flex-direction:column;align-items:center;gap:.2rem;color:#cfd8d2}
.cut-curvas button.on{border-color:var(--v);color:var(--v);background:#1b2a17}
.cut-curvas svg{width:100%;max-width:64px;height:30px}
.cut-curvas small{font-size:.66rem;font-weight:700}
.cut-formatos{display:grid;grid-template-columns:repeat(4,1fr);gap:.45rem}
.cut-formatos button{background:var(--f2);border:1px solid var(--b);border-radius:14px;padding:.6rem .3rem;display:flex;flex-direction:column;align-items:center;gap:.25rem}
.cut-formatos button.on{border-color:var(--v);color:var(--v)}
.cut-formatos .caja-asp{height:34px;max-width:46px;border:2px solid currentColor;border-radius:4px;display:block}
.cut-formatos small{font-size:.62rem;color:var(--m);text-align:center;line-height:1.15}
.cut-tabs{flex:0 0 auto;display:flex;gap:2px;overflow-x:auto;scrollbar-width:none;border-top:1px solid var(--b);background:var(--f);padding:6px 6px calc(env(safe-area-inset-bottom,0px) + 6px)}
.cut-tabs::-webkit-scrollbar{display:none}
.cut-tab{flex:0 0 auto;min-width:64px;max-width:84px;line-height:1.1;text-align:center;background:transparent;border:0;display:flex;flex-direction:column;align-items:center;gap:3px;font-size:.68rem;font-weight:700;color:#cfd8d2;padding:4px 6px;white-space:normal}
.cut-tab svg{width:24px;height:24px}
.cut-tab.on{color:var(--v)}
.cut-modal{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:50;display:flex;align-items:flex-end;justify-content:center}
#cutPregunta{z-index:56} /* las preguntas salen encima de la ventana de exportar */
@media (min-width:700px){.cut-modal{align-items:center}}
.cut-hoja{background:var(--f2);border:1px solid var(--b);border-radius:22px 22px 0 0;width:100%;max-width:520px;padding:18px 18px calc(env(safe-area-inset-bottom,0px) + 18px);max-height:90dvh;overflow-y:auto}
@media (min-width:700px){.cut-hoja{border-radius:22px}}
.cut-hoja{position:relative}
.cut-x{position:absolute;top:10px;right:10px;width:36px;height:36px;border-radius:50%;border:1px solid var(--b);background:var(--f3);color:var(--t);display:grid;place-items:center;padding:0;z-index:2}
.cut-x svg{width:18px;height:18px}
.cut-hoja h3{padding-right:44px;margin:0 0 .4rem;font-family:Unbounded,sans-serif;font-size:1.05rem}
.cut-hoja p{color:var(--m);margin:.2rem 0 .8rem;font-size:.9rem}
.cut-hoja .fila-b{display:flex;gap:.5rem;justify-content:flex-end;margin-top:.9rem;flex-wrap:wrap}
.cut-hoja textarea,.cut-hoja input[type=number],.cut-lista input{width:100%;background:var(--f);border:1px solid var(--b);color:var(--t);border-radius:12px;padding:.6rem;font:inherit}
.cut-hoja .dos{display:flex;gap:.5rem;margin-top:.5rem}
.cut-hoja .dos label{flex:1;font-size:.78rem;color:var(--m);font-weight:700}
.cut-hoja .dos select{width:100%;margin-top:.25rem;background:var(--f3);color:var(--t);border:1px solid var(--b);border-radius:10px;padding:.45rem}
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
/* Los videos quedan dentro de la pantalla (2 px, casi invisibles): el iPhone deja de dibujar los que están fuera */
#cutVideos{position:fixed;left:0;bottom:0;width:2px;height:2px;overflow:hidden;opacity:.01;pointer-events:none;z-index:-1}
#cutVideos video,#cutVideos audio{width:2px;height:2px;display:block}
@media (min-width:1000px){
  body{display:grid;grid-template-columns:1fr 380px;grid-template-rows:auto 1fr auto auto auto;height:100dvh}
  .cut-top{grid-column:1/3}
  #cutVistaCaja{grid-column:1;grid-row:2}
  .cut-ctrl{grid-column:1;grid-row:3}
  .cut-linea{grid-column:1/3;grid-row:4}
  #cutPanel{grid-column:2;grid-row:2/4;max-height:none;border-left:1px solid var(--b);border-radius:0}
  .cut-tabs{grid-column:1/3;grid-row:5;justify-content:flex-start;gap:1rem}
  .cut-tabs>:first-child{margin-left:auto}.cut-tabs>:last-child{margin-right:auto}
}
</style>
</head>
<body>
<header class="cut-top">
    <a class="volver solo-web" href="{{ url('/') }}" aria-label="Cerrar"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
    <div class="cut-logo"><i>✂</i>LocalPro<b>Cut</b></div>
    <span class="sp"></span>
    <button type="button" class="cut-app solo-web" data-instalar><img src="/icons/cut-192.png" alt=""><span>Descargar app</span></button>
    <button type="button" class="cut-opinar" id="cutOpinar" aria-label="Opinar o reportar una falla" title="¿Algo falla? Cuéntenos">💬<span>Opinar</span></button>
    <button type="button" class="cut-exp" id="cutBtnExportar">Exportar</button>
</header>

<div id="cutVistaCaja">
    <canvas id="cutLienzo" width="540" height="960"></canvas>
    <div class="cut-vacio" id="cutVacio">
        <div class="cut-hero solo-web" aria-hidden="true">
            <div class="cel">
                <div class="pant"><i class="luz"></i><b class="sub">¡VENDE<br><em>MÁS!</em></b><div class="lin"><i></i><i></i><i></i><span></span></div></div>
            </div>
            <span class="flota f1">✨ Subtítulos</span><span class="flota f2">🎬 4K · 60 fps</span><span class="flota f3">💄 Belleza</span><span class="flota f4">◆ Transiciones</span>
            <img class="icono" src="/icons/cut-192.png" alt="">
        </div>
        <h2>Edite sus videos con <b>LocalPro</b></h2>
        <p>Subtítulos automáticos, transiciones, belleza y 4K a 60 fps. <b style="color:var(--t)">Gratis y sin marca de agua.</b></p>
        <button type="button" class="grande" data-agregar><span>+</span> Agregar videos o fotos</button>
        <div class="cut-proys" id="cutProys" hidden></div>
        <button type="button" class="app-baja solo-web" data-instalar><img src="/icons/cut-192.png" alt=""><span>Descargar la app LocalProCut<small>Con su propio ícono, más rápida y a pantalla completa</small></span><i class="flecha">⬇</i></button>
        @guest
        <a class="cut-reg" href="{{ route('registro', ['desde' => 'cut']) }}" target="_blank" rel="noopener" data-contar="clic_registro">🚀 <span>Regístrese y siga usándolo gratis</span></a>
        @endguest
    </div>
</div>

<div class="cut-ctrl">
    <button type="button" class="ic" id="cutPantalla" aria-label="Pantalla completa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg></button>
    <span class="sp"></span>
    <button type="button" id="cutPlay" aria-label="Reproducir">
        <svg class="pl" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4.5v15l13-7.5z"/></svg>
        <svg class="pa" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
    </button>
    <span class="sp"></span>
    <button type="button" class="ic" id="cutClave" aria-label="Fotograma clave" title="Fotograma clave"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3 21 12 12 21 3 12z"/><path d="M12 9v6M9 12h6" stroke-linecap="round"/></svg></button>
    <button type="button" class="ic" id="cutDeshacer" aria-label="Deshacer" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg></button>
    <button type="button" class="ic" id="cutRehacer" aria-label="Rehacer" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg></button>
</div>

<div class="cut-linea">
    <span id="cutTiempo">00:00 / 00:00</span>
    <div class="cut-zoomt"><button type="button" id="cutZoomMenos" aria-label="Alejar">−</button><button type="button" id="cutZoomMas" aria-label="Acercar">+</button></div>
    <div id="cutLinea"><div id="cutLineaIn"><div id="cutRegla"></div><div id="cutIzq"></div><div id="cutPistaV"></div><div id="cutPistaA"></div><div id="cutPistaT"></div><div id="cutPistaC"></div></div></div>
    <div class="cut-ph"></div>
</div>

<section id="cutPanel" hidden>
    <div class="cut-panel-h"><button type="button" id="cutPanelCerrar" aria-label="Cerrar">‹</button><b id="cutPanelTit"></b></div>
    <div id="cutPanelCuerpo"></div>
</section>

<nav class="cut-tabs">
    <button type="button" class="cut-tab" data-tab="editar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/><path d="M8 16 18 3M16 16 6 3"/></svg>Editar</button>
    <button type="button" class="cut-tab" data-tab="audio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/></svg>Audio</button>
    <button type="button" class="cut-tab" data-tab="texto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5h14M12 5v15"/></svg>Texto</button>
    <button type="button" class="cut-tab" data-tab="efectos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6L3.4 9.3l6-.7z"/></svg>Efectos</button>
    <button type="button" class="cut-tab" data-tab="superposicion"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="10" height="10" rx="1"/><path d="m7 15 3-3 2 2 2-2 3 3"/></svg>Superposición</button>
    <button type="button" class="cut-tab" data-tab="subtitulos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M6 12h4M14 12h4M6 15h8"/></svg>Subtítulos</button>
    <button type="button" class="cut-tab" data-tab="filtros"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4.5"/><circle cx="8" cy="15" r="4.5"/><circle cx="16" cy="15" r="4.5"/></svg>Filtros</button>
    <button type="button" class="cut-tab" data-tab="ajustar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>Ajustar</button>
    <button type="button" class="cut-tab" data-tab="stickers"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a8 8 0 1 1-8-8"/><path d="M20 12a8 8 0 0 0-8-8v4a4 4 0 0 0 4 4z"/></svg>Stickers</button>
    <button type="button" class="cut-tab" data-tab="formato"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="16" rx="1"/></svg>Relación de aspecto</button>
    <button type="button" class="cut-tab" data-tab="fondo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 14 14 4M4 20 20 4M10 20 20 10"/></svg>Fondo</button>
</nav>

<input type="file" id="cutArchivo" accept="video/*,image/*" multiple hidden>
<input type="file" id="cutMusica" accept="audio/*,.mp3,.m4a,.aac,.wav,.ogg" hidden>
<input type="file" id="cutExtraer" accept="video/*" hidden>
<input type="file" id="cutCapaFoto" accept="image/*" hidden>
<input type="file" id="cutSustituir" accept="video/*,image/*" hidden>
<div id="cutVideos"></div>
<div class="cut-toasts" id="cutToasts"></div>
<div class="cut-cargando" id="cutCargando" hidden><div><i></i><span>Cargando…</span></div></div>

<div class="cut-modal" id="cutGuiaApp" hidden>
    <div class="cut-hoja cut-guia">
        <div class="inst" aria-hidden="true">
            <div class="tel"><div class="tel-pant"><span></span><span></span><span></span><span></span><span></span><b class="hueco"></b></div></div>
            <img class="cae" src="/icons/cut-192.png" alt="">
            <div class="confeti"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </div>
        <h3 data-titulo>Descargar LocalProCut</h3>
        <p data-nota></p>
        <ol data-pasos></ol>
        <div class="fila-b"><button type="button" class="cut-btn-sec" data-cerrar>Entendido</button><button type="button" class="cut-exp" data-ya hidden>⬇ Instalar ahora</button></div>
    </div>
</div>

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
        <div class="dos">
            <label>Animación de entrada<select data-ai></select></label>
            <label>Animación de salida<select data-ao></select></label>
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
            <div class="fila-b"><button type="button" class="cut-btn-sec" data-otra>Exportar otra vez</button><button type="button" class="cut-btn-sec" data-compartir>Compartir video</button><button type="button" class="cut-exp" data-bajar>Descargar</button></div>
            <button type="button" class="cut-programar" data-programar>📅 <span>Programar en redes sociales<small>Publíquelo o prográmelo en Facebook, Instagram y YouTube</small></span></button>
            <div class="cut-amigo">
                <b>¿Le gustó? 🎉 Comparta LocalProCut con un amigo y siga usándolo gratis</b>
                <p>Editor de videos gratis, sin marca de agua, con subtítulos automáticos y 4K. Entre más gente lo use, más funciones le agregamos.</p>
                <div class="cut-amigo-b">
                    <a class="wa" href="#" target="_blank" rel="noopener" data-wa>@include('cut._wa-icono') Compartir por WhatsApp</a>
                    <button type="button" class="cut-btn-sec" data-enlace>🔗 Compartir enlace</button>
                </div>
                @guest
                <a class="cut-reg" href="{{ route('registro', ['desde' => 'cut-exportar']) }}" target="_blank" rel="noopener" data-contar="clic_registro">🚀 <span>Regístrese y siga usándolo gratis</span></a>
                @endguest
                <button type="button" class="app-baja solo-web" data-instalar><img src="/icons/cut-192.png" alt=""><span>Descargar la app LocalProCut<small>Edite más rápido desde su pantalla de inicio</small></span><i class="flecha">⬇</i></button>
                <a class="cut-lp" href="{{ route('funciones', ['desde' => 'cut']) }}" target="_blank" rel="noopener" data-contar="clic_funciones">💼 Mire todo lo que LocalPro hace por su negocio: ventas, inventario, tienda en línea, redes… →</a>
            </div>
        </div>
    </div>
</div>

<script>
/* Contador de LocalProCut: visitas, qué hace la persona, compartir con un amigo (Admin → LocalProCut en números).
   Solo datos de uso: ningún video ni nombre de archivo sale del equipo. */
(function(){
  var vis, nuevo = false;
  function aleatorio(){ return (window.crypto && crypto.randomUUID ? crypto.randomUUID().replace(/-/g, '') : Math.random().toString(36).slice(2) + Date.now().toString(36)).slice(0, 24); }
  try { vis = localStorage.getItem('cutVisitante'); if (!/^[a-z0-9]{8,32}$/.test(vis || '')) { vis = aleatorio(); localStorage.setItem('cutVisitante', vis); nuevo = true; } } catch (x) { vis = aleatorio(); nuevo = true; }
  window.cutVisitante = vis;
  var q = new URLSearchParams(location.search);
  var app = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  var visita = null, cola = [];
  function post(url, cuerpo){
    return fetch(url, { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cuerpo) })
      .then(function(r){ return r.ok ? r.json() : null; }).catch(function(){ return null; });
  }
  function recordar(){ visita.t = Date.now(); try { sessionStorage.setItem('cutVisita', JSON.stringify(visita)); } catch (x) {} }
  function enviar(ev){ if (!visita) { if (cola.length < 30) cola.push(ev); return; } ev.id = visita.id; ev.firma = visita.firma; post('/cut/evento', ev); recordar(); }
  window.cutContar = function(tipo, datos){ enviar({ tipo: tipo, datos: datos || null }); };
  // Recargar la página dentro de la misma media hora es la misma visita
  var antes = null; try { antes = JSON.parse(sessionStorage.getItem('cutVisita') || 'null'); } catch (x) {}
  if (antes && antes.id && Date.now() - antes.t < 30 * 60000) { visita = antes; recordar(); }
  else post('/cut/visita', {
    visitante: vis, nuevo: nuevo, app: app, idioma: (navigator.language || '').slice(0, 20),
    pantalla: (screen.width || 0) + 'x' + (screen.height || 0), referencia: document.referrer.slice(0, 480),
    origen: (q.get('ref') || (q.get('app') ? 'app' : '') || '').slice(0, 60) || null, invito: q.get('r')
  }).then(function(d){ if (d && d.id) { visita = { id: d.id, firma: d.firma }; recordar(); cola.splice(0).forEach(enviar); } });
  // Tiempo con la pantalla abierta (cada minuto) → "Ahora mismo" en el panel
  var desde = Date.now();
  function latido(){ if (!visita) return; var sg = Math.min(600, Math.round((Date.now() - desde) / 1000)); desde = Date.now(); if (sg > 0) enviar({ tipo: 'latido', segundos: sg }); }
  setInterval(function(){ if (!document.hidden) latido(); }, 60000);
  document.addEventListener('visibilitychange', function(){ if (document.hidden) latido(); else desde = Date.now(); });
  // Compartir con un amigo: el enlace lleva quién invitó (para saber cuántos llegan por recomendación)
  var url = location.origin + '/cut?r=' + vis;
  var texto = 'Mira este editor de videos gratis y sin marca de agua 🎬 Subtítulos automáticos, transiciones, filtro de belleza y 4K. Yo lo uso 👉 ';
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-wa]').forEach(function(a){ a.href = 'https://wa.me/?text=' + encodeURIComponent(texto + url + '&ref=wa'); });
  });
  document.addEventListener('click', function(e){
    var t = e.target.closest ? e.target : null; if (!t) return;
    if (t.closest('[data-wa]')) cutContar('compartio_wa');
    var c = t.closest('[data-contar]'); if (c) cutContar(c.dataset.contar);
    if (t.closest('[data-instalar]')) cutContar('clic_app');
    var en = t.closest('[data-enlace]');
    if (en) {
      var u = url + '&ref=enlace';
      cutContar('compartio');
      if (navigator.share) navigator.share({ title: 'LocalProCut · editor de videos gratis', text: texto, url: u }).catch(function(){});
      else if (navigator.clipboard) navigator.clipboard.writeText(texto + u).then(function(){ en.textContent = '✅ Enlace copiado: péguelo en un chat'; });
    }
  });
  window.addEventListener('appinstalled', function(){ cutContar('instalo'); });
})();
</script>
<script>
/* Descargar LocalProCut como app aparte (Android: instalador del navegador · iPhone: pasos de Safari) */
(function(){
  // Dentro de la app LocalProCut (abierta desde su ícono) no se muestra "Descargar"
  var standalone = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  try { if (/[?&]app=1/.test(location.search)) sessionStorage.setItem('cutApp', '1'); } catch (x) {}
  // Dentro de cualquier app instalada (LocalProCut o la app de LocalPro) ya está en la app: no se ofrece descargar.
  // Ojo: si en el celular ya está la app de LocalPro, el celular abre /cut dentro de ella (es el mismo sitio).
  if (standalone) document.documentElement.classList.add('es-cutapp');
  if ('serviceWorker' in navigator) window.addEventListener('load', function(){ navigator.serviceWorker.register('/sw.js?v=3').catch(function(){}); });
  var aviso = null;
  window.addEventListener('beforeinstallprompt', function(e){ e.preventDefault(); aviso = e; });
  var ua = navigator.userAgent || '';
  var iOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var compartir = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#3ea6ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px"><path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>';
  var m = document.getElementById('cutGuiaApp'), hoja = m.querySelector('.cut-guia');
  function guia(titulo, nota, pasos, conBoton){
    hoja.classList.remove('listo');
    m.querySelector('[data-titulo]').textContent = titulo;
    m.querySelector('[data-nota]').textContent = nota;
    m.querySelector('[data-pasos]').innerHTML = pasos.map(function(p){ return '<li><span>' + p + '</span></li>'; }).join('');
    var ya = m.querySelector('[data-ya]'), cerrar = m.querySelector('[data-cerrar]');
    ya.hidden = !conBoton; cerrar.textContent = conBoton ? 'Ahora no' : 'Entendido';
    m.hidden = false;
    cerrar.onclick = function(){ m.hidden = true; };
    ya.onclick = function(){
      if (!aviso) return;
      var p = aviso; aviso = null;
      ya.disabled = true; ya.textContent = 'Instalando…';
      p.prompt();
      p.userChoice.then(function(r){ if (r && r.outcome === 'accepted') listo(); else m.hidden = true; }).catch(function(){ m.hidden = true; })
        .finally(function(){ ya.disabled = false; ya.textContent = '⬇ Instalar ahora'; });
    };
  }
  function listo(){
    guia('¡Listo! Ya tiene LocalProCut 🎉', 'Búsquela en su pantalla de inicio con este ícono. Se abre directo al editor, a pantalla completa.', [], false);
    hoja.classList.add('listo');
    document.querySelectorAll('[data-instalar]').forEach(function(b){ b.hidden = true; });
  }
  window.addEventListener('appinstalled', listo);
  document.addEventListener('click', function(e){
    var b = e.target.closest && e.target.closest('[data-instalar]');
    if (!b) return;
    if (aviso) return guia('Descargar LocalProCut', 'Le queda con su propio ícono en la pantalla de inicio: se abre directo al editor, rápido y a pantalla completa.', [
      'Toque <b>Instalar ahora</b>', 'Confirme con <b>Instalar</b>', 'Listo: busque el ícono <b>LocalProCut</b>'
    ], true);
    if (iOS) return guia('Descargar LocalProCut', 'Le queda con su propio ícono en la pantalla de inicio y se abre a pantalla completa, como una app.', [
      'Abra esta página en <b>Safari</b>',
      'Toque <b>Compartir</b> ' + compartir + ' (abajo en Safari)',
      'Baje y toque <b>Agregar a inicio</b>',
      'Toque <b>Agregar</b>. Listo: busque el ícono <b>LocalProCut</b>'
    ]);
    guia('Descargar LocalProCut', 'Le queda con su propio ícono en la pantalla de inicio. Si ya tiene la app de LocalPro, el editor también está ahí: menú → Editar videos.', [
      'Toque el menú <b>⋮</b> del navegador (arriba a la derecha)',
      'Toque <b>Instalar aplicación</b> o <b>Agregar a pantalla principal</b>',
      'Confirme con <b>Instalar</b>. Busque el ícono <b>LocalProCut</b>'
    ]);
  });
})();
</script>
{{-- Botón "Opinar / reportar falla" + errores que se reportan solos (Admin → Opiniones y fallas) --}}
<script>window.LP_OPINION = {!! json_encode(['modulo' => 'cut', 'tema' => 'oscuro', 'boton' => false, 'sesion' => auth()->check()]) !!};</script>
<script src="/js/lp-opinion.js?v={{ @filemtime(public_path('js/lp-opinion.js')) }}"></script>
<script>document.getElementById('cutOpinar').addEventListener('click', function () { if (window.cutContar) cutContar('opino'); if (window.LPOpinion) LPOpinion.abrir(); });</script>
<script>
/* Si el editor no arranca (navegador viejo o archivo bloqueado), mostrar el motivo en pantalla */
(function(){
  var errores = [];
  function mostrar(msg){
    var v = document.getElementById('cutVacio');
    if (!v || window.__cut) return;
    v.hidden = false;
    v.innerHTML = '<h2>No se pudo abrir el editor</h2><p>Actualice su iPhone/Android y use Safari o Chrome actualizados. Ya le avisamos a LocalPro; si quiere, cuéntenos qué celular usa.</p><button type="button" class="grande" data-avisar>💬 Contarle a LocalPro</button><p style="font-size:.75rem;color:#ff8fb8;word-break:break-word">' + String(msg).replace(/</g,'&lt;') + '</p>';
    if (window.LPOpinion) {
      LPOpinion.error(new Error('El editor no arrancó: ' + String(msg).slice(0, 400)), { donde: 'arranque' });
      v.querySelector('[data-avisar]').onclick = function(){ LPOpinion.abrir({ tipo: 'falla', mensaje: 'El editor no me abre. Mi celular es: ', datos: { arranque: String(msg) } }); };
    } else v.querySelector('[data-avisar]').hidden = true;
  }
  window.addEventListener('error', function(e){ errores.push((e.message || 'Error') + (e.filename ? ' · ' + e.filename.split('/').pop() + ':' + e.lineno : '')); }, true);
  window.addEventListener('unhandledrejection', function(e){ errores.push(String(e.reason && e.reason.message || e.reason)); });
  window.addEventListener('load', function(){ setTimeout(function(){ if (!window.__cut) mostrar(errores.join(' | ') || 'El editor no cargó (' + navigator.userAgent + ')'); }, 4000); });
})();
</script>
@php $cv = fn ($f) => '/js/cut/' . $f . '?v=' . @filemtime(public_path('js/cut/' . $f)); @endphp
<script type="importmap">{!! json_encode(['imports' => collect(['motor.js', 'subtitulos.js', 'belleza.js', 'mediabunny.min.js'])->mapWithKeys(fn ($f) => ['/js/cut/' . $f => $cv($f)])->all()], JSON_UNESCAPED_SLASHES) !!}</script>
<script type="module" src="{{ $cv('cut.js') }}"></script>
</body>
</html>
