<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>LP Chat</title>
{{-- 2026-11-17 (Jhoset): LP Chat, igual a WhatsApp a primera vista.
     Arriba: Mis chats · General · Conocer gente · Empresas. Abajo: Mensajes · Estados · Tienda (Marketplace). --}}
<style>
body{height:100dvh;overflow:hidden}
.app{display:flex;height:100dvh;max-width:1600px;margin:0 auto;background:var(--panel)}
@media (min-width:1300px){body{padding:19px 0;background:radial-gradient(1200px 500px at 50% -200px,rgba(166,243,74,.16),transparent 70%),var(--fondo)}.app{height:calc(100dvh - 38px);border:1px solid var(--borde);border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.6)}}
.lado{width:430px;max-width:44%;border-right:1px solid var(--borde);display:flex;flex-direction:column;min-width:0;background:var(--panel)}
.main{flex:1;display:flex;flex-direction:column;min-width:0;position:relative;background:var(--wall)}
.cab{flex:none;background:var(--panel);color:var(--txt);padding-top:env(safe-area-inset-top,0px);border-bottom:1px solid var(--borde)}
.cab .f1{height:56px;display:flex;align-items:center;gap:4px;padding:0 6px 0 16px}
.cab h1{font-size:1.4rem;margin:0;flex:1;font-family:Montserrat,Figtree,sans-serif;font-weight:900;letter-spacing:-.02em}
.cab h1 b{color:var(--verde)}
.cab .logo{width:30px;height:30px;border-radius:9px;margin-right:8px;flex:none}
.cab .ib{color:inherit}
.tabs{display:flex;gap:6px;overflow-x:auto;scrollbar-width:none;padding:2px 10px 10px}
.tabs::-webkit-scrollbar{display:none}
.tabs button{flex:0 0 auto;color:var(--txt2);font-weight:700;font-size:.84rem;padding:7px 13px;border-radius:999px;background:var(--panel2);border:1px solid var(--borde);white-space:nowrap;position:relative}
.tabs button.on{color:var(--sobre-verde);background:var(--verde);border-color:var(--verde)}
/* contador en pestaña */
.tabs button.on .pt{background:var(--sobre-verde);color:var(--verde)}
.tabs .pt{display:inline-grid;place-items:center;background:var(--verde);color:var(--sobre-verde);border-radius:9px;min-width:18px;height:18px;font-size:.68rem;padding:0 5px;margin-left:4px;vertical-align:1px}
.empresa-on{background:var(--grad);color:var(--sobre-verde);font-weight:600;font-size:.82rem;padding:6px 14px;display:flex;gap:8px;align-items:center}
.empresa-on button{margin-left:auto;border:0;background:#0b0d0c;color:var(--verde);font-weight:700;border-radius:12px;padding:3px 10px;font-size:.78rem}
.ib{background:none;border:0;width:42px;height:42px;border-radius:50%;display:grid;place-items:center;color:var(--txt2);position:relative;flex:none}
.ib:hover{background:rgba(255,255,255,.06)}
.ib svg{width:24px;height:24px;fill:currentColor}
.av{width:49px;height:49px;border-radius:50%;flex:none;background:#26302a center/cover no-repeat;display:grid;place-items:center;color:#fff;font-weight:700;font-size:1.1rem;position:relative}
.av.p{width:40px;height:40px;font-size:.95rem}
.av.cuad{border-radius:12px}
.av .on{position:absolute;right:1px;bottom:1px;width:12px;height:12px;border-radius:50%;background:var(--verde);border:2px solid var(--panel)}
.av.anillo{box-shadow:0 0 0 2px var(--panel),0 0 0 4.5px var(--verde),0 0 14px rgba(166,243,74,.35)}
.av.anillo.visto{box-shadow:0 0 0 2px var(--panel),0 0 0 4px #8696a0}
.pane{display:flex;flex-direction:column;flex:1;min-height:0}
.buscar{padding:8px 12px;flex:none}
.buscar input{width:100%;border:1px solid var(--borde);outline:none;background:var(--panel2);border-radius:14px;padding:9px 14px 9px 40px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%238696a0'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:12px center;background-size:20px}
.chips{display:flex;gap:8px;padding:2px 12px 8px;flex:none;overflow-x:auto}
.chip{border:1px solid var(--borde);background:var(--panel2);color:var(--txt2);border-radius:999px;padding:6px 13px;font-size:.86rem;white-space:nowrap}
.chip.on{background:var(--chip);color:var(--chipt);border-color:rgba(166,243,74,.35);font-weight:700}
.lista{flex:1;overflow-y:auto;min-height:0}
.it{display:flex;align-items:center;gap:13px;padding:0 0 0 14px;cursor:pointer}
.it:hover{background:var(--hover)}
.it.act{background:var(--sel);box-shadow:inset 3px 0 0 var(--verde)}
.it .tx{flex:1;min-width:0;border-bottom:1px solid var(--borde);padding:12px 15px 12px 0;display:flex;flex-direction:column;gap:2px}
.it .r1,.it .r2{display:flex;align-items:center;gap:6px}
.it .n{flex:1;font-size:1.02rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.it .h{font-size:.75rem;color:var(--txt2)}
.it .h.nl{color:var(--verde);font-weight:700}
.it .u{flex:1;font-size:.88rem;color:var(--txt2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.it .b{background:var(--verde);color:var(--sobre-verde);border-radius:11px;min-width:21px;height:21px;padding:0 6px;font-size:.75rem;display:grid;place-items:center;font-weight:700}
.tag{font-size:.66rem;font-weight:700;background:var(--chip);color:var(--chipt);border-radius:999px;padding:1px 6px;white-space:nowrap}
.solic{display:flex;align-items:center;gap:13px;padding:12px 15px 12px 14px;cursor:pointer;border-bottom:1px solid var(--borde)}
.solic:hover{background:var(--hover)}
.solic .ic{width:49px;height:49px;border-radius:50%;background:var(--chip);border:1px solid rgba(166,243,74,.3);color:var(--chipt);display:grid;place-items:center;flex:none}
.solic .ic svg{width:24px;fill:currentColor}
.solic small{display:block;color:var(--txt2)}
.cajita{margin:12px;border-radius:16px;background:var(--chip);border:1px solid rgba(166,243,74,.25);padding:14px;font-size:.92rem}
.cajita b.t{display:block;margin-bottom:4px;font-size:1rem}
.cajita .lk{display:flex;gap:6px;margin-top:10px}
.cajita input{flex:1;min-width:0;border:1px solid var(--borde);border-radius:10px;padding:8px 10px;background:var(--panel);font-size:.85rem}
.bt{border:0;border-radius:12px;padding:8px 14px;font-weight:800;background:var(--verde);color:var(--sobre-verde);white-space:nowrap;text-decoration:none;display:inline-block;text-align:center}
.bt.sec{background:var(--panel3);color:var(--txt);border:1px solid var(--borde)}
.bt.rojo{background:#ff3b6b;color:#fff}
.bt.w{background:#25d366;color:#0b0d0c}
.bt[disabled]{opacity:.5}
.nav{display:flex;border-top:1px solid var(--borde);flex:none;background:var(--panel);padding-bottom:env(safe-area-inset-bottom,0px)}
.nav button{flex:1;background:none;border:0;padding:8px 0 10px;color:var(--txt2);font-size:.76rem;display:flex;flex-direction:column;align-items:center;gap:3px;position:relative}
.nav button svg{width:24px;height:24px;fill:currentColor}
.nav button .pill{padding:3px 18px;border-radius:16px;display:grid;place-items:center}
.nav button.on{color:var(--txt);font-weight:700}
.nav button.on .pill{background:var(--chip);color:var(--chipt)}
.nav .pt{position:absolute;top:3px;left:calc(50% + 8px);background:var(--verde);color:var(--sobre-verde);font-size:.65rem;min-width:17px;height:17px;border-radius:9px;display:grid;place-items:center;padding:0 4px;font-weight:700}
.nav .pt.punto{min-width:10px;width:10px;height:10px;padding:0;top:6px}
.vacio{padding:30px 22px;text-align:center;color:var(--txt2)}
.fab{position:absolute;right:18px;bottom:78px;width:56px;height:56px;border-radius:18px;background:var(--grad);color:var(--sobre-verde);border:0;box-shadow:0 8px 24px rgba(166,243,74,.28);display:grid;place-items:center;z-index:5}
.fab svg{width:26px;fill:var(--sobre-verde)}
.fab.s2{bottom:144px;width:44px;height:44px;border-radius:12px;background:var(--panel3);color:var(--txt);box-shadow:none;border:1px solid var(--borde)}
.fab.s2 svg{fill:var(--txt2);width:22px}
.lado{position:relative}
/* Conocer gente */
.cupo{margin:6px 12px 4px;padding:10px 12px;border-radius:12px;background:var(--chip);border:1px solid rgba(166,243,74,.25);font-size:.86rem;line-height:1.4}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:6px 12px 16px}
.card{background:var(--panel2);border:1px solid var(--borde);border-radius:16px;overflow:hidden;cursor:pointer;display:flex;flex-direction:column;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none}
.card .ft{aspect-ratio:3/4;background:#26302a center/cover no-repeat;display:grid;place-items:center;font-size:2.6rem;color:#fff;font-weight:700;position:relative}
.card .ft:after{content:"";position:absolute;inset:0} /* capa encima: no se puede arrastrar/guardar la foto con un clic */
.card .ft .on{position:absolute;top:8px;right:8px;width:12px;height:12px;border-radius:50%;background:var(--verde);border:2px solid #0b0d0c;z-index:1}
.card .ft .og{position:absolute;left:8px;top:8px;z-index:1;font-size:.66rem;font-weight:800;background:rgba(11,13,12,.75);color:var(--verde);border-radius:6px;padding:2px 6px}
.card .cd{padding:8px 10px 10px;display:flex;flex-direction:column;gap:2px;flex:1}
.card .cn{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card .cs{font-size:.78rem;color:var(--txt2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card .bt{margin-top:6px;padding:6px;font-size:.85rem}
/* Empresas */
.emp{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid var(--borde);align-items:flex-start}
.emp .tx{flex:1;min-width:0}
.emp .n{font-weight:600}
.emp .d{font-size:.86rem;color:var(--txt2);margin:2px 0 6px}
.emp .acc{display:flex;gap:6px;flex-wrap:wrap}
.emp .bt{padding:5px 12px;font-size:.82rem}
/* Estados */
.est-mio{display:flex;gap:13px;align-items:center;padding:12px 14px;cursor:pointer}
.est-mio .mas{position:absolute;right:-2px;bottom:-2px;width:20px;height:20px;border-radius:50%;background:var(--verde);color:var(--sobre-verde);border:2px solid var(--panel);display:grid;place-items:center;font-size:.9rem;font-weight:700}
.sub{padding:14px 16px 6px;font-size:.78rem;color:var(--verde);font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.visor{position:fixed;inset:0;background:#000;z-index:80;display:flex;flex-direction:column;color:#fff;user-select:none;-webkit-user-select:none}
.visor .barras{display:flex;gap:3px;padding:calc(env(safe-area-inset-top,0px) + 8px) 8px 6px}
.visor .barras i{flex:1;height:3px;background:rgba(255,255,255,.35);border-radius:2px;overflow:hidden}
.visor .barras i b{display:block;height:100%;width:0;background:var(--verde)}
.visor .vtop{display:flex;align-items:center;gap:10px;padding:4px 10px}
.visor .vtop .ib{color:#fff}
.visor .cont{flex:1;display:grid;place-items:center;position:relative;min-height:0}
.visor .cont img,.visor .cont video{max-width:100%;max-height:100%;object-fit:contain;pointer-events:none}
.visor .txtest{padding:30px;font-size:1.6rem;text-align:center;line-height:1.35;white-space:pre-wrap;width:100%;height:100%;display:grid;place-items:center}
.visor .zona{position:absolute;top:0;bottom:0;width:35%;z-index:2}
.visor .pie{padding:12px 16px calc(env(safe-area-inset-bottom,0px) + 14px);text-align:center;font-size:.9rem}
/* Tienda */
.tienda{flex:1;border:0;width:100%;min-height:0;background:var(--panel)}
/* Conversación */
.bienv{margin:auto;text-align:center;color:var(--txt2);max-width:460px;padding:30px}
.bienv img{width:96px;height:96px;border-radius:24px}
.bienv h2{color:var(--txt);font-family:Montserrat,Figtree,sans-serif;font-weight:900;font-size:2rem;margin:16px 0 8px}
.bienv h2 b{color:var(--verde)}
.conv{display:flex;flex-direction:column;height:100%;min-height:0}
.ccab{height:60px;flex:none;display:flex;align-items:center;gap:10px;padding:0 6px 0 6px;background:var(--panel);border-bottom:1px solid var(--borde);cursor:pointer}
@media (max-width:760px){.ccab{height:calc(58px + env(safe-area-inset-top,0px));padding-top:env(safe-area-inset-top,0px)}}
.ccab .tx{flex:1;min-width:0}
.ccab .n{font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ccab .s{font-size:.8rem;color:var(--txt2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.volver{display:none}
.msgs{flex:1;overflow-y:auto;padding:14px 6% 10px;display:flex;flex-direction:column;gap:2px;background-color:var(--wall);background-size:28px 28px;background-image:radial-gradient(rgba(166,243,74,.07) 1px,transparent 1.4px)}
.dia{align-self:center;background:var(--panel3);color:var(--txt2);font-size:.72rem;font-weight:700;padding:5px 12px;border-radius:999px;margin:10px 0 6px;text-transform:uppercase;letter-spacing:.04em}
.sis{align-self:center;background:rgba(166,243,74,.08);color:#c9d6cf;border:1px solid rgba(166,243,74,.18);font-size:.78rem;padding:5px 12px;border-radius:8px;margin:4px 0;text-align:center;max-width:88%}
.m{max-width:min(78%,520px);padding:7px 10px 8px 11px;border-radius:16px;position:relative;word-wrap:break-word;white-space:pre-wrap;line-height:1.36;font-size:.94rem;margin-top:1px}
.m.yo{align-self:flex-end;background:var(--grad);color:var(--sobre-verde)}
.m.yo .meta{color:rgba(11,13,12,.6)}
.m.yo a{color:#0b0d0c;font-weight:700}
.m.yo .tk{fill:rgba(11,13,12,.45)}
.m.yo .tk.l{fill:#0b0d0c}
.m.el{align-self:flex-start;background:var(--burbuja-el);border:1px solid var(--borde)}
.m.pri.yo{border-bottom-right-radius:5px;margin-top:8px}
.m.pri.el{border-bottom-left-radius:5px;margin-top:8px}
.m .au{display:block;font-size:.8rem;font-weight:600;margin-bottom:2px}
.m img,.m video{display:block;max-width:100%;width:280px;max-height:340px;object-fit:cover;border-radius:6px;margin:-2px -4px 4px;background:#000}
.m img{cursor:zoom-in}
.m audio{width:250px;max-width:100%;display:block;margin:2px 0 4px}
.m .uv{display:flex;align-items:center;gap:10px;padding:6px 4px;cursor:pointer;font-weight:800;color:var(--verde)}
.m.yo .uv,.m.yo .uv.off{color:#0b0d0c}
.m .uv .c{width:30px;height:30px;border-radius:50%;border:2px dashed currentColor;display:grid;place-items:center;font-size:.85rem}
.m .uv.off{color:var(--txt2);cursor:default;font-weight:400}
.m .borrado{color:var(--txt2);font-style:italic;font-size:.88rem}
.m .meta{float:right;margin:8px 0 -6px 12px;font-size:.68rem;color:var(--txt2);display:flex;align-items:center;gap:3px;position:relative;top:4px}
.m .tk{width:16px;height:11px;fill:#8696a0}
.m .tk.l{fill:var(--azul)}
.m a{color:var(--verde)}
.barra{flex:none;display:flex;align-items:flex-end;gap:6px;padding:8px 8px calc(env(safe-area-inset-bottom,0px) + 8px);background:var(--panel);border-top:1px solid var(--borde)}
.barra .caja{flex:1;display:flex;align-items:flex-end;background:var(--panel3);border:1px solid var(--borde);border-radius:16px;padding:0 4px 0 6px;min-width:0}
.barra textarea{flex:1;border:0;outline:none;resize:none;padding:11px 6px;background:transparent;max-height:120px;height:44px;line-height:1.3;min-width:0}
.barra .env{background:var(--grad);color:var(--sobre-verde);width:46px;height:46px;border-radius:14px}
.barra .env svg{fill:var(--sobre-verde)}
.grab{flex:1;display:flex;align-items:center;gap:10px;background:var(--panel3);border-radius:16px;padding:0 14px;height:46px;color:#ea0038;font-weight:600}
.grab .pto{width:10px;height:10px;border-radius:50%;background:#ea0038;animation:lat 1s infinite}
@keyframes lat{50%{opacity:.2}}
.aviso{flex:none;background:var(--panel2);padding:12px 16px;text-align:center;color:var(--txt2);font-size:.9rem}
.aviso .acc{display:flex;gap:8px;justify-content:center;margin-top:10px;flex-wrap:wrap}
.prev{display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--panel2);border-top:1px solid var(--borde)}
.prev img,.prev video{height:64px;border-radius:6px}
.uvbtn{border:2px solid var(--txt2);background:none;color:var(--txt2);border-radius:50%;width:34px;height:34px;font-weight:700}
.uvbtn.on{border-color:var(--verde);background:var(--verde);color:var(--sobre-verde)}
/* Menús y ventanas */
.menu{position:fixed;background:var(--panel2);border:1px solid var(--borde);border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.55);padding:6px 0;z-index:90;min-width:220px;color:var(--txt)}
.menu button{display:block;width:100%;text-align:left;background:none;border:0;padding:11px 20px;font-size:.93rem}
.menu button:hover{background:var(--hover)}
.velo{position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);z-index:85;display:grid;place-items:center;padding:14px}
.ven{background:var(--panel2);border:1px solid var(--borde);border-radius:18px;width:100%;max-width:460px;max-height:90dvh;display:flex;flex-direction:column;overflow:hidden}
.ven .vc{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--panel);color:var(--txt);border-bottom:1px solid var(--borde)}
.ven .vc h3{margin:0;font-size:1.08rem;flex:1;font-weight:800}
.ven .vc .ib{color:var(--txt2)}
.ven .vb{padding:16px;overflow-y:auto}
.ven label{display:block;font-size:.8rem;font-weight:700;color:var(--verde);margin:12px 0 4px}
.ven input[type=text],.ven textarea,.ven input[type=password]{width:100%;border:1px solid var(--borde);border-radius:12px;background:var(--panel3);padding:10px 12px;outline:none}
.ven input:focus,.ven textarea:focus{border-color:var(--verde)}
.ven .fila{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--borde)}
.ven .fila .tx{flex:1;min-width:0}
.ven .fila small{color:var(--txt2);display:block}
.ven .pie{display:flex;gap:8px;justify-content:flex-end;padding:12px 16px;border-top:1px solid var(--borde);flex-wrap:wrap}
.fgr{width:150px;height:150px;border-radius:50%;margin:6px auto 10px;background:#26302a center/cover;display:grid;place-items:center;font-size:3.4rem;color:#fff;font-weight:700;cursor:pointer;position:relative}
.colores{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.colores button{width:30px;height:30px;border-radius:50%;border:2px solid transparent}
.colores button.on{border-color:var(--txt)}
.toast{position:fixed;left:50%;bottom:100px;transform:translateX(-50%);background:#1f2622;color:var(--txt);border:1px solid rgba(166,243,74,.35);padding:10px 18px;border-radius:12px;z-index:99;font-size:.9rem;max-width:90%;text-align:center}
.zoom{position:fixed;inset:0;background:rgba(0,0,0,.94);z-index:95;display:grid;place-items:center}
.zoom img,.zoom video{max-width:96vw;max-height:88vh}
.zoom .x{position:absolute;top:calc(env(safe-area-inset-top,0px) + 10px);right:10px;color:#fff}
.zoom audio{width:min(90vw,420px)}
.sw{position:relative;width:40px;height:22px;flex:none}
.sw input{opacity:0;width:0;height:0}
.sw span{position:absolute;inset:0;background:#8696a0;border-radius:11px;transition:.2s}
.sw span:before{content:"";position:absolute;width:18px;height:18px;left:2px;top:2px;background:#fff;border-radius:50%;transition:.2s}
.sw input:checked+span{background:var(--verde2)}
.sw input:checked+span:before{transform:translateX(18px)}
@media (max-width:760px){
  .lado{width:100%;max-width:none;border:0}
  .main{position:fixed;inset:0;z-index:20;transform:translateX(100%);transition:transform .2s}
  .app.abierto .main{transform:none}
  .volver{display:grid}
  .msgs{padding:10px 10px}
}
@media (min-width:761px){.fab{bottom:80px}}
</style>
</head>
<body>
<div class="app" id="app">
    <aside class="lado">
        <div class="cab">
            <div class="f1">
                <img class="logo" src="/icons/lpchat-192.png" alt=""><h1 id="tit"><span>LP</span> <b>Chat</b></h1>
                <button class="ib" id="b-instalar" title="Instalar la app" hidden><svg viewBox="0 0 24 24"><path d="M5 20h14v-2H5v2zM19 9h-4V3H9v6H5l7 7 7-7z"/></svg></button>
                <button class="ib" id="b-cam" title="Subir estado"><svg viewBox="0 0 24 24"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 2 7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg></button>
                <button class="ib" id="b-menu" title="Menú"><svg viewBox="0 0 24 24"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"/></svg></button>
            </div>
            <div class="tabs" id="tabs">
                <button class="on" data-t="mis">Mis chats<span class="pt" id="pt-mis" hidden></span></button>
                <button data-t="general">General<span class="pt" id="pt-general" hidden></span></button>
                <button data-t="conocer">Conocer gente</button>
                <button data-t="empresas">Empresas</button>
            </div>
        </div>
        @if ($yo->tienda_id)
            <div class="empresa-on">🏪 Estás usando LP Chat como <b>{{ $yo->nombre }}</b><button id="b-volver-cuenta">Mi cuenta</button></div>
        @endif

        {{-- MENSAJES: Mis chats / General --}}
        <section class="pane" id="p-mis">
            <div class="buscar"><input class="q-chats" type="search" placeholder="Buscar"></div>
            <div class="chips">
                <button class="chip on" data-f="todos">Todos</button>
                <button class="chip" data-f="noleidos">No leídos</button>
                <button class="chip" data-f="grupos">Grupos</button>
            </div>
            <div class="lista" id="lista-mis"></div>
        </section>
        <section class="pane" id="p-general" hidden>
            <div class="buscar"><input class="q-chats" type="search" placeholder="Buscar"></div>
            <div class="lista" id="lista-general"></div>
        </section>
        <section class="pane" id="p-solic" hidden>
            <div class="it" data-ir="general" style="padding:10px 14px;gap:8px;color:var(--verde2);font-weight:600">← Volver a General</div>
            <div class="vacio" style="padding:4px 18px 10px;text-align:left;font-size:.88rem">Mensajes de personas que todavía no conoces. No saben que los viste hasta que aceptes. Si descartas, ya no te llegan.</div>
            <div class="lista" id="lista-solic"></div>
        </section>
        {{-- CONOCER GENTE --}}
        <section class="pane" id="p-conocer" hidden>
            <div class="cupo" id="cupo">Cargando…</div>
            <div class="chips">
                <button class="chip on" data-o="">Todos</button>
                <button class="chip" data-o="pfn">🥊 Pasto Fight Night</button>
                <button class="chip" data-o="nes">✨ Nariño ExpoShow</button>
                <button class="chip" data-o="localproo">LocalProo</button>
            </div>
            <div class="lista"><div class="grid" id="grid"></div><div class="vacio" style="font-size:.82rem;padding:10px 20px 20px">Cada día te mostramos personas distintas. 🔒 Por seguridad no se ven correos ni celulares.</div></div>
        </section>
        {{-- EMPRESAS --}}
        <section class="pane" id="p-empresas" hidden>
            <div class="buscar"><input id="q-emp" type="search" placeholder="Buscar empresa, producto o ciudad"></div>
            <div class="lista"><div id="lista-emp"></div><div class="vacio" id="emp-mas" hidden><button class="bt sec" id="b-emp-mas">Ver más empresas</button></div></div>
        </section>
        {{-- ESTADOS --}}
        <section class="pane" id="p-estados" hidden>
            <div class="lista">
                <div class="est-mio" id="est-mio">
                    <div style="position:relative">{!! '' !!}<div class="av" id="est-mio-av"></div><span class="mas">+</span></div>
                    <div><div style="font-weight:600">Mi estado</div><small style="color:var(--txt2)" id="est-mio-tx">Toca para subir una foto, video o texto</small></div>
                </div>
                <div class="sub">Recientes · duran 24 horas</div>
                <div id="lista-est"></div>
            </div>
        </section>
        {{-- TIENDA (Marketplace de LocalProo) --}}
        <section class="pane" id="p-tienda" hidden>
            <iframe class="tienda" id="ifr-tienda" title="Marketplace LocalProo" loading="lazy"></iframe>
        </section>

        <button class="fab s2" id="fab2" title="Subir estado de texto" hidden><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
        <button class="fab" id="fab" title="Nuevo chat"><svg viewBox="0 0 24 24"><path d="M19.005 3.175H4.674C3.642 3.175 3 3.789 3 4.821V21.02l3.544-3.514h12.461c1.033 0 2.064-1.06 2.064-2.093V4.821c-.001-1.032-1.032-1.646-2.064-1.646zm-4.989 9.869H7.041V11.1h6.975v1.944zm3-4H7.041V7.1h9.975v1.944z"/></svg></button>

        <nav class="nav">
            <button class="on" data-n="mensajes"><span class="pill"><svg viewBox="0 0 24 24"><path d="M19.005 3.175H4.674C3.642 3.175 3 3.789 3 4.821V21.02l3.544-3.514h12.461c1.033 0 2.064-1.06 2.064-2.093V4.821c-.001-1.032-1.032-1.646-2.064-1.646z"/></svg></span>Mensajes<span class="pt" id="pt-msj" hidden></span></button>
            <button data-n="estados"><span class="pill"><svg viewBox="0 0 24 24"><path d="M12 20.664a9.163 9.163 0 0 1-6.521-2.702.977.977 0 0 1 1.381-1.381 7.269 7.269 0 0 0 10.024.244.977.977 0 0 1 1.313 1.445A9.192 9.192 0 0 1 12 20.664zm7.965-6.112a.977.977 0 0 1-.944-1.229 7.26 7.26 0 0 0-4.8-8.804.977.977 0 0 1 .594-1.86 9.212 9.212 0 0 1 6.092 11.169.976.976 0 0 1-.942.724zm-16.025-.39a.977.977 0 0 1-.953-.769 9.21 9.21 0 0 1 6.626-10.86.975.975 0 1 1 .52 1.882l-.015.004a7.259 7.259 0 0 0-5.223 8.558.978.978 0 0 1-.955 1.185z"/><circle cx="12" cy="12" r="4"/></svg></span>Estados<span class="pt punto" id="pt-est" hidden></span></button>
            <button data-n="tienda"><span class="pill"><svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg></span>Tienda</button>
        </nav>
    </aside>
    <main class="main" id="main">
        <div class="bienv" id="bienv">
            <img src="/icons/lpchat-192.png" alt="">
            <h2>LP <b>Chat</b></h2>
            <p>La red social nariñense: chatea con tus amigos, crea grupos, conoce gente y escríbele a las empresas.</p>
            <p style="font-size:.8rem;margin-top:30px">🕒 Los chats guardan los últimos 7 días · 🔒 Una plataforma de <b style="color:var(--verde)">LocalProo</b></p>
        </div>
        <div class="conv" id="conv" hidden>
            <div class="ccab" id="ccab">
                <button class="ib volver" id="b-volver" title="Volver"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></button>
                <div class="av p" id="c-av"></div>
                <div class="tx"><div class="n" id="c-n"></div><div class="s" id="c-s"></div></div>
                <button class="ib" id="b-cmenu" title="Más"><svg viewBox="0 0 24 24"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"/></svg></button>
            </div>
            <div class="msgs" id="msgs"></div>
            <div class="aviso" id="aviso" hidden></div>
            <div class="prev" id="prev" hidden><div id="prev-m"></div><span style="flex:1;color:var(--txt2);font-size:.86rem" id="prev-t">Listo para enviar</span><button class="uvbtn" id="b-uv" title="Ver una sola vez">1</button><button class="ib" id="prev-x" title="Quitar">✕</button></div>
            <form class="barra" id="barra">
                <div class="caja" id="caja">
                    <button type="button" class="ib" id="b-adj" title="Adjuntar foto o video"><svg viewBox="0 0 24 24"><path d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z"/></svg></button>
                    <textarea id="txt" rows="1" placeholder="Mensaje" maxlength="4000"></textarea>
                    <label class="ib" title="Cámara" style="cursor:pointer"><svg viewBox="0 0 24 24"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 2 7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg><input type="file" id="f-cam" accept="image/*,video/*" capture="environment" hidden></label>
                </div>
                <div class="grab" id="grab" hidden><span class="pto"></span><span id="grab-t">0:00</span><span style="flex:1;color:var(--txt2);font-weight:400;font-size:.85rem">Grabando…</span><button type="button" class="ib" id="grab-x" title="Cancelar" style="color:#ea0038">🗑</button></div>
                <button type="button" class="ib env" id="b-mic" title="Nota de voz"><svg viewBox="0 0 24 24"><path d="M11.999 14.942c2.001 0 3.531-1.53 3.531-3.531V4.35c0-2.001-1.53-3.531-3.531-3.531S8.469 2.35 8.469 4.35v7.061c0 2.001 1.53 3.531 3.53 3.531zm6.238-3.53c0 3.531-2.942 6.002-6.237 6.002s-6.237-2.471-6.237-6.002H3.761c0 4.001 3.178 7.297 7.061 7.885v3.884h2.354v-3.884c3.884-.588 7.061-3.884 7.061-7.885h-2z"/></svg></button>
                <button class="ib env" id="b-env" title="Enviar" hidden><svg viewBox="0 0 24 24"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg></button>
                <input type="file" id="f-adj" accept="image/*,video/*" hidden>
            </form>
        </div>
    </main>
</div>
<input type="file" id="f-est" accept="image/*,video/*" hidden>
<div id="capa"></div>
<script>
(function () {
'use strict';
var YO = {!! json_encode(['id' => $yo->id, 'nombre' => $yo->nombre, 'link' => $yo->link, 'foto' => $yo->foto_url, 'empresa' => (bool) $yo->tienda_id], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) !!};
var EMPRESA = {!! json_encode($empresa && ! $yo->tienda_id ? $empresa->nombre : null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
var U = '/chat/api';
var CSRF = document.querySelector('meta[name=csrf-token]').content;
var $ = function (id) { return document.getElementById(id); };
var st = { nav: 'mensajes', tab: 'mis', filtro: 'todos', chats: { mis: [], general: [] }, solicitudes: 0, abierto: 0, ultimo: 0, info: null, adj: null, uv: false, escT: 0, sinLeer: 0, primera: true, origen: '', empPag: 1 };
var COL = ['#16b84e', '#3b82f6', '#7c3aed', '#e11d74', '#f59e0b', '#0ea5e9', '#ef4444', '#14b8a6', '#8b5cf6', '#22c55e'];
var DIAS = 7;

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function enlaces(s) { return esc(s).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>'); }
function inicial(n) { return esc((String(n || '?').trim()[0] || '?').toUpperCase()); }
function color(n) { var h = 0; String(n || '').split('').forEach(function (c) { h = (h * 31 + c.charCodeAt(0)) % 997; }); return COL[h % COL.length]; }
function avatar(o, cls, grupo) {
    var s = o.foto ? 'background-image:url(\'' + esc(o.foto) + '\')' : 'background-color:' + (grupo ? '#26302a' : color(o.nombre));
    return '<div class="av ' + (cls || '') + (o.empresa ? ' cuad' : '') + '" style="' + s + '">' + (o.foto ? '' : (grupo ? '👥' : inicial(o.nombre))) + (o.en_linea ? '<span class="on"></span>' : '') + '</div>';
}
function toast(t) { var d = document.createElement('div'); d.className = 'toast'; d.textContent = t; document.body.appendChild(d); setTimeout(function () { d.remove(); }, 3400); }
function api(url, opt) {
    opt = opt || {};
    var o = { method: opt.method || 'GET', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };
    if (opt.body instanceof FormData) o.body = opt.body;
    else if (opt.body) { o.body = JSON.stringify(opt.body); o.headers['Content-Type'] = 'application/json'; }
    return fetch(url, o).then(function (r) {
        if (r.status === 401 || r.status === 419) { location.href = '/chat/entrar'; throw new Error('sesion'); }
        return r.json().catch(function () { return { ok: false }; }).then(function (j) {
            if (!r.ok) { j.ok = false; if (!j.mensaje) j.mensaje = j.message || (r.status === 413 ? 'El archivo pesa mucho.' : r.status === 429 ? 'Vas muy rápido, espera un momento.' : ''); }
            return j;
        });
    });
}
function post(url, body) { return api(url, { method: 'POST', body: body || {} }); }
var TK = '<svg class="tk" viewBox="0 0 16 11"><path d="M11.07.65 10.4.12a.43.43 0 0 0-.6.07L4.94 6.43 2.6 4.33a.43.43 0 0 0-.6.02l-.58.6a.43.43 0 0 0 .02.6L4.7 8.53a.43.43 0 0 0 .62-.04l5.82-7.24a.43.43 0 0 0-.07-.6z"/><path d="M15.07.65 14.4.12a.43.43 0 0 0-.6.07L8.94 6.43l-.66-.59-.86 1.07L8.7 8.53a.43.43 0 0 0 .62-.04l5.82-7.24a.43.43 0 0 0-.07-.6z"/></svg>';

/* ================= Navegación ================= */
function irNav(n) {
    st.nav = n;
    document.querySelectorAll('.nav button').forEach(function (b) { b.classList.toggle('on', b.dataset.n === n); });
    $('tabs').hidden = n !== 'mensajes';
    $('tit').innerHTML = n === 'estados' ? 'Estados' : n === 'tienda' ? '<b>Tienda</b> LocalProo' : 'LP <b>Chat</b>';
    if (n === 'mensajes') return irTab(st.tab);
    ['mis', 'general', 'solic', 'conocer', 'empresas'].forEach(function (x) { $('p-' + x).hidden = true; });
    $('p-estados').hidden = n !== 'estados'; $('p-tienda').hidden = n !== 'tienda';
    $('fab').hidden = n === 'tienda'; $('fab2').hidden = n !== 'estados';
    $('fab').innerHTML = n === 'estados' ? '<svg viewBox="0 0 24 24"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 2 7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg>' : FAB_CHAT;
    if (n === 'estados') cargarEstados();
    if (n === 'tienda' && !$('ifr-tienda').src) $('ifr-tienda').src = '/marketplace?app=lpchat';
}
var FAB_CHAT = $('fab').innerHTML;
function irTab(t) {
    st.tab = t; st.nav = 'mensajes';
    document.querySelectorAll('#tabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.t === (t === 'solic' ? 'general' : t)); });
    ['mis', 'general', 'solic', 'conocer', 'empresas'].forEach(function (x) { $('p-' + x).hidden = x !== t; });
    $('p-estados').hidden = true; $('p-tienda').hidden = true; $('fab2').hidden = true;
    $('fab').hidden = t === 'conocer' || t === 'empresas'; $('fab').innerHTML = FAB_CHAT;
    if (t === 'solic') cargarSolic();
    if (t === 'conocer' && !$('grid').dataset.ok) conocer();
    if (t === 'empresas' && !$('lista-emp').dataset.ok) empresas(true);
}
document.querySelectorAll('.nav button').forEach(function (b) { b.onclick = function () { irNav(b.dataset.n); }; });
document.querySelectorAll('#tabs button').forEach(function (b) { b.onclick = function () { irTab(b.dataset.t); }; });
$('fab').onclick = function () { if (st.nav === 'estados') $('f-est').click(); else nuevoChat(); };
$('fab2').onclick = estadoTexto;
$('b-cam').onclick = function () { $('f-est').click(); };

/* ================= Listas de chats ================= */
function pintar(sec) {
    var box = $('lista-' + sec), q = (box.parentNode.querySelector('.q-chats') || {}).value || '';
    q = q.trim().toLowerCase();
    var l = st.chats[sec].filter(function (c) {
        if (sec === 'mis' && st.filtro === 'noleidos' && !c.sin_leer) return false;
        if (sec === 'mis' && st.filtro === 'grupos' && c.tipo !== 'grupo') return false;
        return !q || (c.nombre || '').toLowerCase().indexOf(q) >= 0;
    });
    var h = '';
    if (sec === 'general' && st.solicitudes && !q) {
        h += '<div class="solic" data-ir="solic"><div class="ic"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg></div><div style="flex:1"><b>Solicitudes de mensaje</b><small>' + st.solicitudes + ' persona' + (st.solicitudes === 1 ? '' : 's') + ' que no conoces te escribió</small></div><span class="b" style="background:var(--verde);color:#0b0d0c;border-radius:11px;padding:2px 8px;font-size:.75rem;font-weight:800">' + st.solicitudes + '</span></div>';
    }
    h += l.map(itemHtml).join('');
    if (!st.chats[sec].length) {
        h += sec === 'mis'
            ? '<div class="cajita"><b class="t">Comparte este link e inicia a chatear 💬</b>Mándale tu link a tus amigos: quien lo abra queda en <b>Mis chats</b> contigo.<div class="lk"><input readonly id="link-' + sec + '" value="' + esc(YO.link) + '"><button class="bt" data-copiar="link-' + sec + '">Copiar</button></div><div class="lk"><a class="bt w" style="flex:1" target="_blank" href="https://wa.me/?text=' + encodeURIComponent('¡Hablemos por LP Chat, la red social nariñense! Abre mi link: ' + YO.link) + '">Enviar por WhatsApp</a><button class="bt sec" data-compartir>Compartir</button></div></div>'
            : '<div class="vacio">Aquí llegan las conversaciones con la gente que conoces en <b>Conocer gente</b> y con las <b>Empresas</b>.<br><br><button class="bt" data-tab="conocer">Conocer gente</button> <button class="bt sec" data-tab="empresas">Ver empresas</button></div>';
    } else if (!l.length) h += '<div class="vacio">No hay chats aquí.</div>';
    box.innerHTML = h;
}
function itemHtml(c) {
    return '<div class="it' + (c.id === st.abierto ? ' act' : '') + '" data-chat="' + c.id + '">' + avatar(c, '', c.tipo === 'grupo') +
        '<div class="tx"><div class="r1"><span class="n">' + esc(c.nombre) + '</span>' + (c.empresa ? '<span class="tag">Empresa</span>' : '') + '<span class="h' + (c.sin_leer ? ' nl' : '') + '">' + esc(c.hora) + '</span></div>' +
        '<div class="r2"><span class="u">' + (c.mio ? TK + ' ' : '') + esc(c.ultimo || (c.tipo === 'grupo' ? 'Grupo creado' : '')) + '</span>' + (c.sin_leer ? '<span class="b">' + c.sin_leer + '</span>' : '') + '</div></div></div>';
}
function cargar(sec) {
    return api(U + '/chats?tab=' + sec).then(function (j) {
        if (!j.ok) return;
        st.chats[sec] = j.chats; st.solicitudes = j.solicitudes;
        var tot = j.sin_leer_mis + j.sin_leer_general;
        $('pt-mis').hidden = !j.sin_leer_mis; $('pt-mis').textContent = j.sin_leer_mis;
        $('pt-general').hidden = !(j.sin_leer_general + j.solicitudes); $('pt-general').textContent = j.sin_leer_general + j.solicitudes;
        $('pt-msj').hidden = !tot; $('pt-msj').textContent = tot;
        $('pt-est').hidden = !j.estados_nuevos;
        document.title = (tot ? '(' + tot + ') ' : '') + 'LP Chat';
        if (!st.primera && tot > st.sinLeer && document.hidden) avisar(j.chats);
        st.sinLeer = tot; st.primera = false;
        pintar(sec);
    }).catch(function () {});
}
function cargarChats() { cargar('mis'); return cargar('general'); }
function cargarSolic() {
    api(U + '/chats?tab=solicitudes').then(function (j) {
        if (j.ok) $('lista-solic').innerHTML = j.chats.length ? j.chats.map(itemHtml).join('') : '<div class="vacio">No tienes solicitudes. 🙌</div>';
    });
}
function avisar(chats) {
    try {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        var c = chats.filter(function (x) { return x.sin_leer; })[0];
        if (c) new Notification(c.nombre, { body: c.ultimo, icon: '/icons/lpchat-192.png', tag: 'lpchat-' + c.id });
    } catch (e) {}
}
document.querySelectorAll('#p-mis .chip').forEach(function (b) {
    b.onclick = function () { st.filtro = b.dataset.f; document.querySelectorAll('#p-mis .chip').forEach(function (x) { x.classList.toggle('on', x === b); }); pintar('mis'); };
});
document.querySelectorAll('.q-chats').forEach(function (i) { i.oninput = function () { pintar(i.closest('section').id.slice(2)); }; });

/* ================= Conocer gente ================= */
function conocer() {
    api(U + '/conocer?origen=' + st.origen).then(function (j) {
        if (!j.ok) { $('grid').innerHTML = '<div class="vacio" style="grid-column:1/-1">' + esc(j.mensaje || 'No disponible.') + '</div>'; return; }
        $('grid').dataset.ok = 1;
        pintarCupo(j.quedan, j.limite);
        $('grid').innerHTML = j.perfiles.length ? j.perfiles.map(function (p) {
            var fs = p.foto ? 'background-image:url(\'' + esc(p.foto) + '\')' : 'background-color:' + color(p.nombre);
            var og = { pfn: 'PFN', nes: 'NES', localproo: 'LocalProo' }[p.origen] || '';
            return '<div class="card" data-perfil="' + p.id + '"><div class="ft" style="' + fs + '">' + (p.foto ? '' : inicial(p.nombre)) + (og ? '<span class="og">' + og + '</span>' : '') + (p.en_linea ? '<span class="on"></span>' : '') + '</div>' +
                '<div class="cd"><span class="cn">' + esc(p.nombre) + '</span><span class="cs">' + esc(p.etiqueta || p.ciudad || p.visto || '') + '</span>' + (p.bio ? '<span class="cs">' + esc(p.bio) + '</span>' : '') +
                '<button class="bt" data-saludar="' + p.id + '">Saludar 👋</button></div></div>';
        }).join('') : '<div class="vacio" style="grid-column:1/-1">No hay más personas por hoy. Vuelve mañana 🙌</div>';
    });
}
function pintarCupo(q, l) {
    st.quedan = q;
    $('cupo').innerHTML = q > 0 ? '👋 Hoy puedes saludar a <b>' + q + ' de ' + l + '</b> personas nuevas. Les llega como solicitud y lo verás en <b>General</b>.' : '✋ Ya saludaste a ' + l + ' personas hoy. Mañana puedes conocer a más.';
}
document.querySelectorAll('#p-conocer .chip').forEach(function (b) {
    b.onclick = function () { st.origen = b.dataset.o; document.querySelectorAll('#p-conocer .chip').forEach(function (x) { x.classList.toggle('on', x === b); }); conocer(); };
});
$('grid').addEventListener('contextmenu', function (e) { e.preventDefault(); });
function saludar(id) {
    post(U + '/abrir', { usuario_id: id }).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se pudo.');
        cerrarVentana(); irNav('mensajes'); irTab('general'); abrirChat(j.chat); cargarChats(); delete $('grid').dataset.ok;
    });
}
function verPerfil(id) {
    api(U + '/perfil/' + id).then(function (j) {
        if (!j.ok) return toast('Este perfil no está disponible.');
        var p = j.perfil;
        ventana(esc(p.nombre), '<div class="fgr" style="' + (p.foto ? 'background-image:url(\'' + esc(p.foto) + '\')' : 'background-color:' + color(p.nombre)) + (p.empresa ? ';border-radius:24px' : '') + '" oncontextmenu="return false">' + (p.foto ? '' : inicial(p.nombre)) + '</div>' +
            '<div style="text-align:center"><div style="font-size:1.3rem">' + esc(p.nombre) + '</div><div style="color:var(--txt2)">' + esc([p.etiqueta, p.ciudad].filter(Boolean).join(' · ')) + '</div>' +
            '<div style="color:var(--txt2);font-size:.85rem;margin-top:4px">' + esc(p.visto || '') + '</div>' + (p.bio ? '<p>' + esc(p.bio) + '</p>' : '') + '</div>',
            p.id !== YO.id && !p.tiene_chat && !p.empresa ? '<button class="bt" data-saludar="' + p.id + '">Saludar 👋</button>' : '');
    });
}

/* ================= Empresas ================= */
var tqe;
function empresas(reset) {
    if (reset) { st.empPag = 1; $('lista-emp').innerHTML = ''; }
    api(U + '/empresas?q=' + encodeURIComponent($('q-emp').value.trim()) + '&pagina=' + st.empPag).then(function (j) {
        if (!j.ok) return;
        $('lista-emp').dataset.ok = 1;
        if (reset && !j.empresas.length) $('lista-emp').innerHTML = '<div class="vacio">No encontramos empresas.</div>';
        $('lista-emp').insertAdjacentHTML('beforeend', j.empresas.map(function (e) {
            var lg = e.logo ? 'background-image:url(\'' + esc(e.logo) + '\');background-size:contain;background-color:#fff' : 'background-color:' + color(e.nombre);
            return '<div class="emp"><div class="av cuad" style="' + lg + '">' + (e.logo ? '' : inicial(e.nombre)) + '</div><div class="tx"><div class="n">' + esc(e.nombre) + '</div>' +
                '<div class="d">' + esc(e.descripcion || '') + (e.ciudad ? ' · 📍 ' + esc(e.ciudad) : '') + '</div><div class="acc"><button class="bt" data-empresa="' + e.id + '">Escribir</button>' +
                (e.tienda ? '<a class="bt sec" href="' + esc(e.tienda) + '" target="_blank" rel="noopener">Ver tienda</a>' : '') + '</div></div></div>';
        }).join(''));
        $('emp-mas').hidden = !j.hay_mas;
    });
}
$('q-emp').oninput = function () { clearTimeout(tqe); tqe = setTimeout(function () { empresas(true); }, 350); };
$('b-emp-mas').onclick = function () { st.empPag++; empresas(false); };
function escribirEmpresa(id) {
    post(U + '/empresas/contactar', { tienda_id: id }).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se pudo.');
        irTab('general'); abrirChat(j.chat); cargarChats();
    });
}

/* ================= Estados ================= */
function cargarEstados() {
    var mi = $('est-mio-av');
    mi.outerHTML = avatar({ nombre: YO.nombre, foto: YO.foto }).replace('class="av', 'id="est-mio-av" class="av');
    api(U + '/estados').then(function (j) {
        if (!j.ok) return;
        var mio = j.estados.filter(function (e) { return e.yo; })[0];
        $('est-mio-tx').textContent = mio ? mio.cantidad + ' estado' + (mio.cantidad === 1 ? '' : 's') + ' · ' + mio.hora + ' · toca para ver' : 'Toca para subir una foto, video o texto';
        $('est-mio').dataset.ver = mio ? YO.id : '';
        var otros = j.estados.filter(function (e) { return !e.yo; });
        var nuevos = otros.filter(function (e) { return e.sin_ver; }), vistos = otros.filter(function (e) { return !e.sin_ver; });
        var fila = function (e) { return '<div class="it" data-estado="' + e.id + '">' + avatar(e, 'anillo' + (e.sin_ver ? '' : ' visto')) + '<div class="tx"><div class="r1"><span class="n">' + esc(e.nombre) + '</span>' + (e.amigo ? '<span class="tag">Mis chats</span>' : '') + '</div><div class="r2"><span class="u">' + esc(e.hora) + '</span></div></div></div>'; };
        $('lista-est').innerHTML = (nuevos.length ? nuevos.map(fila).join('') : '') + (vistos.length ? '<div class="sub">Vistos</div>' + vistos.map(fila).join('') : '') + (!otros.length ? '<div class="vacio">Todavía no hay estados. ¡Sube el primero! 📸</div>' : '');
    });
}
$('est-mio').onclick = function () { if (this.dataset.ver) verEstados(+this.dataset.ver); else $('f-est').click(); };
$('f-est').onchange = function () {
    var f = this.files[0]; this.value = '';
    if (!f) return;
    if (f.size > 30 * 1024 * 1024) return toast('Máximo 30 MB.');
    var url = URL.createObjectURL(f), esVideo = f.type.indexOf('video') === 0;
    ventana('Nuevo estado', (esVideo ? '<video src="' + url + '" controls playsinline style="width:100%;max-height:50vh;border-radius:8px"></video>' : '<img src="' + url + '" style="width:100%;max-height:50vh;object-fit:contain;border-radius:8px">') +
        '<label>Agrega un texto (opcional)</label><input type="text" id="est-tx" maxlength="700" placeholder="Escribe algo…"><p style="color:var(--txt2);font-size:.82rem">Lo podrán ver todos en LP Chat durante 24 horas.</p>',
        '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="est-ok">Publicar</button>');
    $('est-ok').onclick = function () {
        var fd = new FormData(); fd.append('archivo', f); fd.append('texto', $('est-tx').value);
        this.disabled = true; this.textContent = 'Subiendo…';
        api(U + '/estados', { method: 'POST', body: fd }).then(function (j) {
            if (!j.ok) { toast(j.mensaje || 'No se pudo subir.'); return cerrarVentana(); }
            cerrarVentana(); toast('Estado publicado.'); irNav('estados');
        });
    };
};
function estadoTexto() {
    var cols = ['#16b84e', '#0b0d0c', '#7c3aed', '#e11d74', '#f59e0b', '#3b82f6', '#ef4444', '#14b8a6'], col = cols[0];
    ventana('Estado de texto', '<textarea id="et-t" maxlength="700" rows="4" placeholder="Escribe tu estado" style="width:100%;color:#fff;font-size:1.3rem;text-align:center;border-radius:10px;padding:20px;background:' + col + ';border:0"></textarea><div class="colores">' + cols.map(function (c, i) { return '<button type="button" data-col="' + c + '" class="' + (i ? '' : 'on') + '" style="background:' + c + '"></button>'; }).join('') + '</div>',
        '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="et-ok">Publicar</button>');
    document.querySelectorAll('[data-col]').forEach(function (b) { b.onclick = function () { col = b.dataset.col; $('et-t').style.background = col; document.querySelectorAll('[data-col]').forEach(function (x) { x.classList.toggle('on', x === b); }); }; });
    $('et-t').focus();
    $('et-ok').onclick = function () {
        if (!$('et-t').value.trim()) return toast('Escribe algo.');
        post(U + '/estados', { texto: $('et-t').value, color: col }).then(function (j) {
            if (!j.ok) return toast(j.mensaje || 'No se pudo.');
            cerrarVentana(); toast('Estado publicado.'); irNav('estados');
        });
    };
}
var visor = null;
function verEstados(uid) {
    api(U + '/estados/de/' + uid).then(function (j) {
        if (!j.ok || !j.estados.length) return toast('Este estado ya no está.');
        var i = j.estados.findIndex(function (e) { return !e.visto; }); if (i < 0 || j.yo) i = 0;
        var d = document.createElement('div'); d.className = 'visor';
        d.innerHTML = '<div class="barras">' + j.estados.map(function () { return '<i><b></b></i>'; }).join('') + '</div>' +
            '<div class="vtop">' + avatar(j.usuario, 'p') + '<div style="flex:1;min-width:0"><div>' + (j.yo ? 'Mi estado' : esc(j.usuario.nombre)) + '</div><small id="v-h" style="opacity:.8"></small></div>' +
            (j.yo ? '<button class="ib" id="v-del" title="Borrar">🗑</button>' : '') + '<button class="ib" id="v-x" title="Cerrar">✕</button></div>' +
            '<div class="cont" id="v-c"></div><div class="pie" id="v-p"></div>';
        d.addEventListener('contextmenu', function (e) { e.preventDefault(); });
        document.body.appendChild(d);
        visor = { el: d, j: j, i: i, t: null };
        $('v-x').onclick = cerrarVisor;
        if (j.yo) $('v-del').onclick = function () { var e = j.estados[visor.i]; if (confirm('¿Borrar este estado?')) post(U + '/estados/' + e.id + '/borrar').then(function () { cerrarVisor(); cargarEstados(); }); };
        mostrarEstado();
    });
}
function mostrarEstado() {
    var v = visor; if (!v) return;
    clearInterval(v.t);
    var e = v.j.estados[v.i], c = $('v-c'), bars = v.el.querySelectorAll('.barras b');
    bars.forEach(function (b, k) { b.style.width = k < v.i ? '100%' : '0'; });
    $('v-h').textContent = e.hora;
    $('v-p').innerHTML = v.j.yo ? '👁 ' + (e.vistas || 0) + ' vista' + (e.vistas === 1 ? '' : 's') : (e.tipo !== 'texto' && e.texto ? esc(e.texto) : '') + (!v.j.yo && !v.j.usuario.empresa ? '' : '');
    var dur = 6000, ini = Date.now(), vid = null;
    if (e.tipo === 'texto') c.innerHTML = '<div class="txtest" style="background:' + esc(e.color || '#16b84e') + '">' + enlaces(e.texto) + '</div>';
    else if (e.tipo === 'foto') c.innerHTML = '<img src="' + esc(e.url) + '" alt="">';
    else { c.innerHTML = '<video src="' + esc(e.url) + '" autoplay playsinline></video>'; vid = c.querySelector('video'); dur = 30000; vid.onloadedmetadata = function () { dur = Math.min(60, vid.duration || 30) * 1000; }; vid.onended = siguiente; }
    c.insertAdjacentHTML('beforeend', '<div class="zona" style="left:0" id="v-ant"></div><div class="zona" style="right:0" id="v-sig"></div>');
    $('v-ant').onclick = function () { if (v.i > 0) { v.i--; mostrarEstado(); } };
    $('v-sig').onclick = siguiente;
    if (!v.j.yo && !e.visto) { e.visto = true; post(U + '/estados/' + e.id + '/visto'); }
    v.t = setInterval(function () {
        var p = vid ? (vid.currentTime * 1000) / dur : (Date.now() - ini) / dur;
        bars[v.i].style.width = Math.min(100, p * 100) + '%';
        if (!vid && p >= 1) siguiente();
    }, 80);
}
function siguiente() { var v = visor; if (!v) return; if (v.i < v.j.estados.length - 1) { v.i++; mostrarEstado(); } else cerrarVisor(); }
function cerrarVisor() { if (!visor) return; clearInterval(visor.t); visor.el.remove(); visor = null; if (st.nav === 'estados') cargarEstados(); }

/* ================= Conversación ================= */
var cargando = false;
function abrirChat(id) {
    if (!id) return;
    st.abierto = id; st.ultimo = 0; st.info = null;
    $('bienv').hidden = true; $('conv').hidden = false; $('msgs').innerHTML = ''; $('msgs').dataset.dia = ''; $('msgs').dataset.autor = ''; $('aviso').hidden = true;
    $('app').classList.add('abierto'); quitarAdj();
    if (location.search.indexOf('chat=' + id) < 0) history.pushState({ chat: id }, '', '/chat?chat=' + id);
    ['mis', 'general'].forEach(pintar);
    traer(true);
    if (window.innerWidth > 760) $('txt').focus();
}
function cerrarChat(sinHist) {
    st.abierto = 0; $('app').classList.remove('abierto'); $('conv').hidden = true; $('bienv').hidden = false;
    if (!sinHist && location.search) history.replaceState(null, '', '/chat');
    pararGrab(true); cargarChats();
}
$('b-volver').onclick = function (e) { e.stopPropagation(); if (history.state && history.state.chat) history.back(); else cerrarChat(); };
window.addEventListener('popstate', function () { if (visor) return cerrarVisor(); if (st.abierto) cerrarChat(true); });
function traer(primera) {
    if (!st.abierto || cargando) return;
    cargando = true;
    var id = st.abierto;
    api(U + '/chat/' + id + (st.ultimo ? '?desde=' + st.ultimo : '')).then(function (j) {
        cargando = false;
        if (id !== st.abierto) return;
        if (!j.ok) { toast('Esta conversación no está disponible.'); return cerrarChat(); }
        st.info = j.chat; st.info.seccion = j.seccion;
        $('c-av').outerHTML = avatar(j.chat.otro || j.chat, 'p', j.chat.tipo === 'grupo').replace('class="av', 'id="c-av" class="av');
        $('c-n').textContent = j.chat.nombre;
        var s = $('c-s');
        s.textContent = j.escribiendo.length ? (j.chat.tipo === 'grupo' ? j.escribiendo.join(', ') + ' escribiendo…' : (j.escribiendo[0].indexOf('🎤') > 0 ? 'grabando audio…' : 'escribiendo…')) : (j.chat.sub || (j.chat.tipo === 'directo' ? 'toca para ver el perfil' : ''));
        var box = $('msgs'), abajo = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
        if (primera) box.insertAdjacentHTML('beforeend', '<div class="sis">🕒 Este chat guarda los últimos ' + DIAS + ' días. Las fotos, videos y audios se borran solos después.</div>');
        j.mensajes.forEach(function (m) { pintarMsg(box, m); });
        if (j.mensajes.length) st.ultimo = j.mensajes[j.mensajes.length - 1].id;
        box.querySelectorAll('.m.yo .tk:not(.l)').forEach(function (t) { if (+t.closest('.m').dataset.m <= j.leido_otros) t.classList.add('l'); });
        if (primera || abajo || (j.mensajes.length && j.mensajes[j.mensajes.length - 1].mio)) box.scrollTop = box.scrollHeight;
        if (primera && !j.mensajes.length) box.insertAdjacentHTML('beforeend', '<div class="sis">' + (j.chat.tipo === 'grupo' ? 'Escribe el primer mensaje del grupo.' : 'Saluda a ' + esc(j.chat.nombre) + ' 👋') + '</div>');
        var av = $('aviso');
        if (j.solicitud) {
            av.innerHTML = '<b>' + esc(j.chat.nombre) + '</b> quiere hablar contigo. No sabe que lo viste hasta que aceptes.<div class="acc"><button class="bt" data-acc="aceptar">Aceptar</button><button class="bt sec" data-acc="descartar">Descartar</button><button class="bt rojo" data-acc="bloquear">Bloquear</button></div>';
            av.hidden = false; $('barra').hidden = true;
        } else if (!j.puede) { av.textContent = j.motivo || 'No puedes escribir aquí.'; av.hidden = false; $('barra').hidden = true; }
        else { av.hidden = true; $('barra').hidden = false; }
        if (primera) cargarChats();
    }).catch(function () { cargando = false; });
}
function pintarMsg(box, m) {
    if (box.querySelector('[data-m="' + m.id + '"]')) return;
    if (m.dia !== box.dataset.dia) { box.insertAdjacentHTML('beforeend', '<div class="dia">' + diaTxt(m.dia) + '</div>'); box.dataset.dia = m.dia; box.dataset.autor = ''; }
    if (m.sistema) { box.insertAdjacentHTML('beforeend', '<div class="sis" data-m="' + m.id + '">' + esc(m.texto) + '</div>'); box.dataset.autor = ''; return; }
    var pri = box.dataset.autor !== String(m.autor_id); box.dataset.autor = m.autor_id;
    var media = '';
    var nom = { foto: 'Foto', video: 'Video', audio: 'Audio' }[m.adj] || 'Archivo';
    if (m.una_vez) {
        media = m.mio ? '<div class="uv off"><span class="c">1</span>' + nom + (m.abierto ? ' · abierto' : ' · para ver una vez') + '</div>'
            : (m.abierto ? '<div class="uv off"><span class="c">1</span>' + nom + ' · ya lo abriste</div>' : '<div class="uv" data-uv="' + m.id + '" data-t="' + m.adj + '"><span class="c">1</span>Ver ' + nom.toLowerCase() + ' una vez</div>');
    } else if (m.adj && !m.url) media = '<div class="borrado">' + nom + ' borrado</div>';
    else if (m.adj === 'foto') media = '<img src="' + esc(m.url) + '" alt="Foto" loading="lazy">';
    else if (m.adj === 'video') media = '<video src="' + esc(m.url) + '#t=0.1" controls playsinline preload="metadata"></video>';
    else if (m.adj === 'audio') media = '<audio src="' + esc(m.url) + '" controls preload="metadata"></audio>';
    box.insertAdjacentHTML('beforeend', '<div class="m ' + (m.mio ? 'yo' : 'el') + (pri ? ' pri' : '') + '" data-m="' + m.id + '">' +
        (m.autor && pri ? '<span class="au" style="color:' + color(m.autor) + '">' + esc(m.autor) + '</span>' : '') + media + (m.texto ? enlaces(m.texto) : '') +
        '<span class="meta">' + esc(m.hora) + (m.mio ? TK.replace('class="tk"', 'class="tk' + (m.leido ? ' l' : '') + '"') : '') + '</span></div>');
}
function diaTxt(d) {
    var hoy = new Date(), ay = new Date(); ay.setDate(hoy.getDate() - 1);
    var iso = function (x) { return x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0'); };
    if (d === iso(hoy)) return 'Hoy';
    if (d === iso(ay)) return 'Ayer';
    return new Date(d + 'T12:00:00').toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });
}
/* Ver una vez: se pide una sola vez como blob y se muestra; al cerrar ya no está. */
$('msgs').addEventListener('click', function (e) {
    var uv = e.target.closest('[data-uv]');
    if (uv) {
        var id = uv.dataset.uv, t = uv.dataset.t;
        uv.outerHTML = '<div class="uv off"><span class="c">1</span>Abriendo…</div>';
        fetch('/chat/adjunto/' + id, { credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw 0; return r.blob(); }).then(function (b) {
            var u = URL.createObjectURL(b);
            zoom(t === 'foto' ? '<img src="' + u + '">' : t === 'video' ? '<video src="' + u + '" autoplay controls playsinline controlsList="nodownload"></video>' : '<audio src="' + u + '" autoplay controls controlsList="nodownload"></audio>', function () { URL.revokeObjectURL(u); });
            var mm = $('msgs').querySelector('[data-m="' + id + '"] .uv'); if (mm) mm.innerHTML = '<span class="c">1</span>Ya lo abriste';
        }).catch(function () { toast('Ya se abrió o se borró.'); });
        return;
    }
    var img = e.target.closest('.m img'); if (img) zoom('<img src="' + esc(img.src) + '">');
});
function zoom(html, alCerrar) {
    var z = document.createElement('div'); z.className = 'zoom'; z.innerHTML = html + '<button class="ib x">✕</button>';
    z.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    z.onclick = function (e) { if (e.target === z || e.target.closest('.x')) { z.remove(); if (alCerrar) alCerrar(); } };
    document.body.appendChild(z);
}

/* ---------- Escribir, adjuntar y grabar ---------- */
function actualizarBotones() { var hay = $('txt').value.trim() || st.adj; $('b-env').hidden = !hay; $('b-mic').hidden = !!hay || grab.activo; }
function autoAlto() { var t = $('txt'); t.style.height = '44px'; t.style.height = Math.min(t.scrollHeight, 120) + 'px'; }
$('txt').addEventListener('input', function () {
    autoAlto(); actualizarBotones();
    if (Date.now() - st.escT > 3000 && st.abierto) { st.escT = Date.now(); post(U + '/chat/' + st.abierto + '/escribiendo').catch(function () {}); }
});
$('txt').addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey && window.innerWidth > 760) { e.preventDefault(); enviar(); } });
$('barra').onsubmit = function (e) { e.preventDefault(); enviar(); };
$('b-adj').onclick = function () { $('f-adj').click(); };
[$('f-adj'), $('f-cam')].forEach(function (inp) {
    inp.onchange = function () {
        var f = this.files[0]; this.value = '';
        if (!f) return;
        var video = f.type.indexOf('video') === 0;
        if (f.size > (video ? 30 : 10) * 1024 * 1024) return toast('El archivo pesa mucho (máximo ' + (video ? 30 : 10) + ' MB).');
        ponerAdj(f, video ? 'video' : 'foto');
    };
});
function ponerAdj(f, tipo, esAudio) {
    st.adj = { f: f, tipo: tipo, audio: !!esAudio }; st.uv = false;
    var u = URL.createObjectURL(f);
    $('prev-m').innerHTML = tipo === 'foto' ? '<img src="' + u + '">' : tipo === 'video' ? '<video src="' + u + '" muted></video>' : '<audio src="' + u + '" controls style="height:36px;max-width:180px"></audio>';
    $('prev-t').textContent = tipo === 'audio' ? 'Nota de voz' : 'Listo para enviar';
    $('b-uv').hidden = !st.info || st.info.tipo !== 'directo'; $('b-uv').classList.remove('on');
    $('prev').hidden = false; actualizarBotones();
}
function quitarAdj() { st.adj = null; st.uv = false; $('prev').hidden = true; actualizarBotones(); }
$('prev-x').onclick = quitarAdj;
$('b-uv').onclick = function () { st.uv = !st.uv; this.classList.toggle('on', st.uv); toast(st.uv ? 'Se podrá ver una sola vez.' : 'Se podrá ver normal.'); };
function enviar() {
    var t = $('txt').value.trim();
    if (!t && !st.adj) return;
    var fd = new FormData(); fd.append('texto', t);
    if (st.adj) { fd.append('archivo', st.adj.f, st.adj.f.name || ('audio.' + (st.adj.f.type.indexOf('mp4') > 0 ? 'm4a' : 'webm'))); if (st.adj.audio) fd.append('es_audio', 1); if (st.uv) fd.append('una_vez', 1); }
    var tenia = st.adj;
    $('txt').value = ''; autoAlto(); quitarAdj();
    if (tenia) toast('Enviando…');
    api(U + '/chat/' + st.abierto + '/enviar', { method: 'POST', body: fd }).then(function (j) {
        if (!j.ok) { toast(j.mensaje || 'No se envió.'); $('txt').value = t; actualizarBotones(); return; }
        traer(false); cargarChats();
    }).catch(function () { toast('Sin conexión. No se envió.'); $('txt').value = t; actualizarBotones(); });
}
var grab = { activo: false };
$('b-mic').onclick = function () {
    if (grab.activo) return pararGrab(false);
    if (!navigator.mediaDevices || !window.MediaRecorder) return toast('Tu celular no deja grabar audio aquí.');
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function (s) {
        var tipo = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg'].filter(function (x) { return MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(x); })[0];
        var r = new MediaRecorder(s, tipo ? { mimeType: tipo } : undefined), partes = [];
        grab = { activo: true, r: r, s: s, ini: Date.now(), cancelar: false };
        r.ondataavailable = function (e) { if (e.data.size) partes.push(e.data); };
        r.onstop = function () {
            s.getTracks().forEach(function (t) { t.stop(); });
            if (grab.cancelar || Date.now() - grab.ini < 800) return;
            var b = new Blob(partes, { type: r.mimeType || 'audio/webm' });
            ponerAdj(new File([b], 'nota-de-voz.' + ((r.mimeType || '').indexOf('mp4') >= 0 ? 'm4a' : 'webm'), { type: b.type }), 'audio', true);
            enviar();
        };
        r.start();
        $('caja').hidden = true; $('grab').hidden = false;
        $('b-mic').innerHTML = $('b-env').innerHTML; $('b-mic').title = 'Enviar audio';
        grab.t = setInterval(function () {
            var sg = Math.floor((Date.now() - grab.ini) / 1000);
            $('grab-t').textContent = Math.floor(sg / 60) + ':' + String(sg % 60).padStart(2, '0');
            if (sg % 3 === 0 && st.abierto) post(U + '/chat/' + st.abierto + '/escribiendo', { audio: 1 }).catch(function () {});
            if (sg >= 300) pararGrab(false);
        }, 500);
    }).catch(function () { toast('Permite el micrófono para enviar notas de voz.'); });
};
var MIC = $('b-mic').innerHTML;
function pararGrab(cancelar) {
    if (!grab.activo) return;
    grab.cancelar = cancelar; grab.activo = false; clearInterval(grab.t);
    try { grab.r.stop(); } catch (e) {}
    $('caja').hidden = false; $('grab').hidden = true; $('b-mic').innerHTML = MIC; $('b-mic').title = 'Nota de voz';
    actualizarBotones();
}
$('grab-x').onclick = function () { pararGrab(true); };

$('aviso').addEventListener('click', function (e) { var b = e.target.closest('[data-acc]'); if (b) accion(b.dataset.acc); });
function accion(a, extra) {
    post(U + '/chat/' + st.abierto + '/' + a, extra).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se pudo.');
        toast({ aceptar: 'Aceptaste. Ya pueden hablar.', descartar: 'Descartada. Ya no te llegan sus mensajes.', bloquear: 'Bloqueado.', desbloquear: 'Desbloqueado.', denunciar: 'Gracias. Quedó bloqueado y lo revisaremos.', mover: 'Chat movido.', borrar: st.info && st.info.tipo === 'grupo' ? 'Saliste del grupo.' : 'Chat borrado.' }[a]);
        if (a === 'aceptar') { $('msgs').innerHTML = ''; $('msgs').dataset.dia = ''; st.ultimo = 0; traer(true); if (st.tab === 'solic') irTab('general'); }
        else if (['descartar', 'borrar', 'denunciar', 'bloquear'].indexOf(a) >= 0 && (a !== 'bloquear' || st.tab === 'solic')) { cerrarChat(); if (st.tab === 'solic') cargarSolic(); }
        else traer(false);
        cargarChats();
    });
}

/* ================= Menús y ventanas ================= */
function menu(boton, items) {
    cerrarMenu();
    var r = boton.getBoundingClientRect(), m = document.createElement('div');
    m.className = 'menu'; m.id = 'menu';
    m.style.top = Math.min(r.bottom + 4, window.innerHeight - 40 * items.length - 20) + 'px'; m.style.right = Math.max(8, window.innerWidth - r.right) + 'px';
    items.forEach(function (it) { var b = document.createElement('button'); b.textContent = it[0]; b.onclick = function () { cerrarMenu(); it[1](); }; m.appendChild(b); });
    document.body.appendChild(m);
    setTimeout(function () { document.addEventListener('click', cerrarMenu, { once: true }); }, 0);
}
function cerrarMenu() { var m = $('menu'); if (m) m.remove(); }
function salirForm() { var f = document.createElement('form'); f.method = 'post'; f.action = '/chat/salir'; f.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">'; document.body.appendChild(f); f.submit(); }
function usarComo(emp) { post(U + '/cuenta', { empresa: emp ? 1 : 0 }).then(function (j) { if (j.ok) location.href = '/chat'; else toast(j.mensaje || 'No se pudo.'); }); }
if ($('b-volver-cuenta')) $('b-volver-cuenta').onclick = function () { usarComo(false); };
$('b-menu').onclick = function (e) {
    e.stopPropagation();
    var it = [['Nuevo grupo', nuevoGrupo], ['Nuevo chat', nuevoChat], ['Mi link para compartir', miLink]];
    if (!YO.empresa) it.push(['Mi perfil', miPerfil]);
    if (EMPRESA) it.push(['Usar como empresa: ' + EMPRESA, function () { usarComo(true); }]);
    if (YO.empresa) it.push(['Volver a mi cuenta', function () { usarComo(false); }]);
    if ('Notification' in window && Notification.permission === 'default') it.push(['Activar avisos de mensajes', function () { Notification.requestPermission(); }]);
    if (instalar) it.push(['Instalar la app', function () { $('b-instalar').click(); }]);
    it.push(['Condiciones de uso', function () { window.open('/chat/terminos', '_blank'); }]);
    it.push(['Cerrar sesión', salirForm]);
    menu(this, it);
};
$('b-cmenu').onclick = function (e) {
    e.stopPropagation();
    var i = st.info; if (!i) return;
    var it = [];
    if (i.tipo === 'grupo') { it.push(['Info del grupo', infoGrupo]); it.push(['Salir del grupo', function () { if (confirm('¿Salir de "' + i.nombre + '"?')) accion('borrar'); }]); }
    else {
        it.push([i.otro && i.otro.empresa ? 'Ver empresa' : 'Ver perfil', function () { verPerfil(i.otro.id); }]);
        if (i.tienda) it.push(['Ver su tienda', function () { window.open(i.tienda, '_blank'); }]);
        it.push([i.seccion === 'mis' ? 'Mover a General' : 'Mover a Mis chats', function () { accion('mover'); }]);
        it.push(['Bloquear', function () { if (confirm('¿Bloquear a ' + i.nombre + '? No podrán escribirse.')) accion('bloquear'); }]);
        it.push(['Desbloquear', function () { accion('desbloquear'); }]);
        it.push(['Denunciar', function () { var m = prompt('¿Por qué denuncias a ' + i.nombre + '?'); if (m !== null) accion('denunciar', { motivo: m }); }]);
        it.push(['Borrar chat', function () { if (confirm('¿Borrar este chat? Si te vuelve a escribir, aparece otra vez.')) accion('borrar'); }]);
    }
    menu(this, it);
};
$('ccab').onclick = function (e) {
    if (e.target.closest('.ib') || !st.info) return;
    if (st.info.tipo === 'grupo') infoGrupo(); else if (st.info.otro) verPerfil(st.info.otro.id);
};
function ventana(titulo, cuerpo, pie) {
    $('capa').innerHTML = '<div class="velo" id="velo"><div class="ven"><div class="vc"><h3>' + titulo + '</h3><button class="ib" data-cerrar>✕</button></div><div class="vb">' + cuerpo + '</div>' + (pie ? '<div class="pie">' + pie + '</div>' : '') + '</div></div>';
}
function cerrarVentana() { $('capa').innerHTML = ''; }
$('capa').addEventListener('click', function (e) { if (e.target.id === 'velo' || e.target.closest('[data-cerrar]')) cerrarVentana(); });
function miLink() {
    ventana('Mi link', '<div class="cajita" style="margin:0"><b class="t">Comparte este link e inicia a chatear 💬</b>Quien lo abra queda en <b>Mis chats</b> contigo de una vez, sin solicitud.<div class="lk"><input readonly id="ml" value="' + esc(YO.link) + '"><button class="bt" data-copiar="ml">Copiar</button></div><div class="lk"><a class="bt w" style="flex:1" target="_blank" href="https://wa.me/?text=' + encodeURIComponent('¡Hablemos por LP Chat, la red social nariñense! Abre mi link: ' + YO.link) + '">Enviar por WhatsApp</a><button class="bt sec" data-compartir>Compartir</button></div></div>');
}
function nuevoChat() {
    ventana('Nuevo chat', '<p style="color:var(--txt2);margin-top:0">Escribe el <b>correo</b> o el <b>celular</b> de la persona, como en WhatsApp. Queda en <b>Mis chats</b>.</p><label>Correo o celular</label><input type="text" id="nc-id" placeholder="amigo@correo.com o 300 123 4567"><div id="nc-msg" style="margin-top:10px;color:var(--txt2);font-size:.9rem"></div>' +
        '<div class="fila" style="cursor:pointer" id="nc-grupo"><div class="av p" style="background:#26302a">👥</div><div class="tx">Nuevo grupo</div></div><div class="fila" style="cursor:pointer" id="nc-link"><div class="av p" style="background:#53bdeb">🔗</div><div class="tx">Compartir mi link</div></div><div id="nc-cont"></div>',
        '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="nc-ok">Chatear</button>');
    $('nc-grupo').onclick = nuevoGrupo; $('nc-link').onclick = miLink;
    $('nc-ok').onclick = function () {
        post(U + '/nuevo', { identificador: $('nc-id').value }).then(function (j) {
            if (!j.ok) { $('nc-msg').innerHTML = esc(j.mensaje || 'No se encontró.') + (j.invitar ? '<div style="margin-top:8px"><a class="bt w" target="_blank" href="https://wa.me/?text=' + encodeURIComponent('¡Hablemos por LP Chat! Abre mi link: ' + YO.link) + '">Invitar por WhatsApp</a></div>' : ''); return; }
            cerrarVentana(); irNav('mensajes'); irTab('mis'); abrirChat(j.chat);
        });
    };
    $('nc-id').onkeydown = function (e) { if (e.key === 'Enter') $('nc-ok').click(); };
    api(U + '/contactos').then(function (j) {
        if (!j.ok || !j.contactos.length || !$('nc-cont')) return;
        $('nc-cont').innerHTML = '<label>Mis chats</label>' + j.contactos.map(function (c) { return '<div class="fila" data-contacto="' + c.id + '" style="cursor:pointer">' + avatar(c, 'p') + '<div class="tx">' + esc(c.nombre) + '<small>' + esc(c.bio || c.etiqueta || '') + '</small></div></div>'; }).join('');
    });
}
function nuevoGrupo() {
    api(U + '/contactos').then(function (j) {
        var c = j.contactos || [];
        ventana('Nuevo grupo', '<label>Nombre del grupo</label><input type="text" id="ng-n" maxlength="80" placeholder="Ej: Parche del sábado">' +
            '<label>Agrega a tus contactos de Mis chats</label>' + (c.length ? c.map(function (x) { return '<label class="fila" style="color:var(--txt);margin:0;font-size:.95rem;cursor:pointer"><input type="checkbox" value="' + x.id + '" class="ng-c">' + avatar(x, 'p') + '<div class="tx">' + esc(x.nombre) + '</div></label>'; }).join('') : '<p style="color:var(--txt2);font-size:.9rem">Aún no tienes contactos. Crea el grupo y comparte su link.</p>'),
            '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="ng-ok">Crear grupo</button>');
        $('ng-ok').onclick = function () {
            if (!$('ng-n').value.trim()) return toast('Ponle un nombre al grupo.');
            post(U + '/grupo', { nombre: $('ng-n').value, ids: [].slice.call(document.querySelectorAll('.ng-c:checked')).map(function (x) { return +x.value; }) }).then(function (r) {
                if (!r.ok) return toast(r.mensaje || 'No se pudo crear.');
                cerrarVentana(); irNav('mensajes'); irTab('mis'); abrirChat(r.chat); cargarChats();
            });
        };
    });
}
function infoGrupo() {
    api(U + '/grupo/' + st.abierto).then(function (j) {
        if (!j.ok) return;
        var g = j.chat, adm = g.admin;
        var h = '<div class="fgr" style="' + (g.foto ? 'background-image:url(\'' + esc(g.foto) + '\')' : 'background-color:#26302a') + '">' + (g.foto ? '' : '👥') + '</div>' +
            '<div style="text-align:center"><div style="font-size:1.3rem">' + esc(g.nombre) + '</div><div style="color:var(--txt2)">Grupo · ' + esc(g.sub) + '</div>' + (g.descripcion ? '<p>' + esc(g.descripcion) + '</p>' : '') + '</div>';
        if (adm) {
            h += '<label>Link para unirse al grupo</label><div style="display:flex;gap:6px"><input type="text" readonly id="g-link" value="' + esc(g.link || '') + '"><button class="bt" data-copiar="g-link">Copiar</button></div>' +
                '<div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap"><a class="bt w" target="_blank" href="https://wa.me/?text=' + encodeURIComponent('Únete a mi grupo "' + g.nombre + '" en LP Chat: ' + (g.link || '')) + '">Enviar por WhatsApp</a><button class="bt sec" id="g-relink">Cambiar link</button><button class="bt sec" id="g-edit">Editar grupo</button></div>' +
                '<label>Agregar participante (correo o celular)</label><div style="display:flex;gap:6px"><input type="text" id="g-add" placeholder="correo o celular"><button class="bt" id="g-add-ok">Agregar</button></div>';
        }
        h += '<label>' + j.miembros.length + ' participantes</label>' + j.miembros.map(function (m) {
            return '<div class="fila">' + avatar(m, 'p') + '<div class="tx">' + (m.yo ? 'Tú' : esc(m.nombre)) + '<small>' + esc(m.bio || m.etiqueta || '') + '</small></div>' +
                (m.admin ? '<span class="tag">Admin</span>' : '') + (adm && !m.yo ? '<button class="ib" data-gm="' + m.id + '" data-adm="' + (m.admin ? 1 : 0) + '" data-nom="' + esc(m.nombre) + '">⋮</button>' : '') + '</div>';
        }).join('');
        ventana('Info del grupo', h, '<button class="bt rojo" id="g-salir">Salir del grupo</button>');
        $('g-salir').onclick = function () { if (confirm('¿Salir del grupo?')) { cerrarVentana(); accion('borrar'); } };
        if (!adm) return;
        $('g-add-ok').onclick = function () { gAcc('agregar', { identificador: $('g-add').value }); };
        $('g-relink').onclick = function () { if (confirm('El link anterior dejará de servir. ¿Seguir?')) gAcc('link', {}); };
        $('g-edit').onclick = function () {
            ventana('Editar grupo', '<label>Nombre</label><input type="text" id="ge-n" maxlength="80" value="' + esc(g.nombre) + '"><label>Descripción</label><input type="text" id="ge-d" maxlength="200" value="' + esc(g.descripcion || '') + '"><label>Foto del grupo</label><input type="file" id="ge-f" accept="image/*">', '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="ge-ok">Guardar</button>');
            $('ge-ok').onclick = function () { var fd = new FormData(); fd.append('nombre', $('ge-n').value); fd.append('descripcion', $('ge-d').value); if ($('ge-f').files[0]) fd.append('foto', $('ge-f').files[0]); gAcc('editar', fd); };
        };
        document.querySelectorAll('[data-gm]').forEach(function (b) {
            b.onclick = function (e) {
                e.stopPropagation();
                var uid = +b.dataset.gm, esAdm = b.dataset.adm === '1';
                menu(b, [[esAdm ? 'Quitar como admin' : 'Hacer admin del grupo', function () { gAcc('admin', { usuario_id: uid, quitar: esAdm }); }],
                    ['Sacar del grupo', function () { if (confirm('¿Sacar a ' + b.dataset.nom + '?')) gAcc('quitar', { usuario_id: uid }); }]]);
            };
        });
    });
}
function gAcc(a, body) {
    api(U + '/grupo/' + st.abierto + '/' + a, { method: 'POST', body: body }).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se pudo.');
        toast('Listo.'); infoGrupo(); traer(false);
    });
}
function miPerfil() {
    api(U + '/perfil').then(function (j) {
        var p = j.perfil;
        ventana('Mi perfil', '<div class="fgr" id="pf-foto" style="' + (p.foto ? 'background-image:url(\'' + esc(p.foto) + '\')' : 'background-color:' + color(p.nombre)) + '" title="Cambiar foto">' + (p.foto ? '' : inicial(p.nombre)) + '</div><input type="file" id="pf-f" accept="image/*" hidden>' +
            '<div style="text-align:center;color:var(--txt2);font-size:.85rem">Toca la foto para cambiarla</div>' +
            '<label>Tu nombre</label><input type="text" id="pf-n" maxlength="80" value="' + esc(p.nombre) + '">' +
            '<label>Info (lo que te gusta, a qué te dedicas)</label><input type="text" id="pf-b" maxlength="160" value="' + esc(p.bio || '') + '">' +
            '<label>Ciudad</label><input type="text" id="pf-c" maxlength="60" value="' + esc(p.ciudad || '') + '">' +
            '<label>Correo</label><input type="text" id="pf-e" value="' + esc(p.email || '') + '">' +
            '<label>Celular</label><input type="text" id="pf-cel" value="' + esc(p.celular || '') + '">' +
            '<label>' + (p.tiene_clave ? 'Cambiar clave (opcional)' : 'Crea una clave para entrar con tu correo o celular') + '</label><input type="password" id="pf-p" minlength="6" autocomplete="new-password">' +
            '<div class="fila" style="margin-top:14px"><div class="tx">Salir en "Conocer gente"<small>Si lo apagas, solo te escriben con tu link, tu correo o tu celular.</small></div><label class="sw"><input type="checkbox" id="pf-k"' + (p.conocer ? ' checked' : '') + '><span></span></label></div>',
            '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="pf-ok">Guardar</button>');
        $('pf-foto').onclick = function () { $('pf-f').click(); };
        $('pf-f').onchange = function () { if (this.files[0]) { $('pf-foto').style.backgroundImage = 'url(' + URL.createObjectURL(this.files[0]) + ')'; $('pf-foto').textContent = ''; } };
        $('pf-ok').onclick = function () {
            var fd = new FormData();
            fd.append('nombre', $('pf-n').value); fd.append('bio', $('pf-b').value); fd.append('ciudad', $('pf-c').value);
            fd.append('email', $('pf-e').value); fd.append('celular', $('pf-cel').value); fd.append('conocer', $('pf-k').checked ? 1 : 0);
            if ($('pf-p').value) fd.append('password', $('pf-p').value);
            if ($('pf-f').files[0]) fd.append('foto', $('pf-f').files[0]);
            api(U + '/perfil', { method: 'POST', body: fd }).then(function (r) {
                if (!r.ok) return toast(r.mensaje || 'Revisa los datos.');
                YO.nombre = r.perfil.nombre; YO.foto = r.perfil.foto; toast('Perfil guardado.'); cerrarVentana();
            });
        };
    });
}

/* ================= Clics generales ================= */
document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-chat]'); if (c) { abrirChat(+c.dataset.chat); return; }
    var s = e.target.closest('[data-saludar]'); if (s) { e.stopPropagation(); saludar(+s.dataset.saludar); return; }
    var em = e.target.closest('[data-empresa]'); if (em) { escribirEmpresa(+em.dataset.empresa); return; }
    var ct = e.target.closest('[data-contacto]'); if (ct) { post(U + '/abrir', { usuario_id: +ct.dataset.contacto }).then(function (j) { if (j.ok) { cerrarVentana(); irTab('mis'); abrirChat(j.chat); } }); return; }
    var es = e.target.closest('[data-estado]'); if (es) { verEstados(+es.dataset.estado); return; }
    var p = e.target.closest('[data-perfil]'); if (p) { verPerfil(+p.dataset.perfil); return; }
    var i = e.target.closest('[data-ir]'); if (i) { irTab(i.dataset.ir); return; }
    var tb = e.target.closest('[data-tab]'); if (tb) { irTab(tb.dataset.tab); return; }
    if (e.target.closest('[data-compartir]')) {
        if (navigator.share) navigator.share({ title: 'LP Chat', text: '¡Hablemos por LP Chat, la red social nariñense! Abre mi link:', url: YO.link }).catch(function () {});
        else copiar(YO.link); return;
    }
    var k = e.target.closest('[data-copiar]'); if (k) copiar($(k.dataset.copiar).value);
});
function copiar(v) {
    (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast('Link copiado.'); }, function () {
        var t = document.createElement('textarea'); t.value = v; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); toast('Link copiado.');
    });
}

/* ================= Instalar app ================= */
var instalar = null;
window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); instalar = e; $('b-instalar').hidden = false; });
$('b-instalar').onclick = function () { if (instalar) { instalar.prompt(); instalar.userChoice.finally(function () { instalar = null; $('b-instalar').hidden = true; }); } };
if (/iphone|ipad/i.test(navigator.userAgent) && !navigator.standalone) {
    try { if (!localStorage.getItem('lpchat_ios')) { setTimeout(function () { toast('Para instalar LP Chat: toca Compartir ⬆️ y luego "Agregar a inicio".'); }, 2500); localStorage.setItem('lpchat_ios', 1); } } catch (e) {}
}
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(function () {});

/* ================= Arranque ================= */
cargarChats();
var abrir = {{ (int) $abrir }};
if (abrir) { history.replaceState({ chat: abrir }, '', '/chat?chat=' + abrir); abrirChat(abrir); }
setInterval(function () { if (!document.hidden) cargarChats(); }, 6000);
setInterval(function () { if (st.abierto && !document.hidden) traer(false); }, 2500);
document.addEventListener('visibilitychange', function () { if (!document.hidden) { cargarChats(); traer(false); } });
})();
</script>
</body>
</html>
