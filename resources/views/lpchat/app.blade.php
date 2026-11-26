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
.it .tk{width:16px;height:11px;fill:var(--txt2);vertical-align:-1px;flex:none}
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
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:6px 12px 10px}
/* 4 tarjetas por pantalla (2x2) en PC y celular: cada fila ocupa la mitad del alto visible */
#lista-k{container-type:size}
@supports (height:1cqh){
  #lista-k .grid{grid-auto-rows:calc((100cqh - 26px) / 2);padding-bottom:10px}
  #lista-k .card .ft{aspect-ratio:auto;flex:1;min-height:0}
}
.card{background:var(--panel2);border:1px solid var(--borde);border-radius:16px;overflow:hidden;cursor:pointer;display:flex;flex-direction:column;min-height:0;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none}
.card[hidden]{display:none}
.card .ft{aspect-ratio:3/4;background:#26302a center/cover no-repeat;display:grid;place-items:center;font-size:2.6rem;color:#fff;font-weight:700;position:relative}
.card .ft:after{content:"";position:absolute;inset:0} /* capa encima: no se puede arrastrar/guardar la foto con un clic */
.card .ft .on{position:absolute;top:8px;right:8px;width:12px;height:12px;border-radius:50%;background:var(--verde);border:2px solid #0b0d0c;z-index:1}
.card .ft .og{position:absolute;left:8px;top:8px;z-index:1;font-size:.66rem;font-weight:800;background:rgba(11,13,12,.75);color:var(--verde);border-radius:6px;padding:2px 6px}
.card .cd{padding:6px 8px 8px;display:flex;flex-direction:column;gap:1px;flex:none}
.card .cn{font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card .cs{font-size:.74rem;color:var(--txt2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card .bt{margin-top:5px;padding:6px;font-size:.84rem;width:100%}
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
/* 2026-11-26: menú de cada chat en la lista (flechita al pasar el mouse, como WhatsApp) */
.it .ics{display:flex;align-items:center;gap:3px;flex:none}
.it .ics svg{width:16px;height:16px;fill:var(--txt2)}
.it .b.gris{background:#5b6560;color:#fff}
.it .b.pto{min-width:0;width:12px;height:12px;padding:0}
.it .cfl{border:0;background:none;width:0;height:22px;overflow:hidden;opacity:0;padding:0;display:grid;place-items:center;flex:none;transition:width .12s,opacity .12s}
.it .cfl svg{width:22px;height:22px;fill:var(--txt2)}
.it:hover .cfl,.it.mon .cfl{width:22px;opacity:1}
@media (hover:none){.it .cfl{display:none}.it{-webkit-user-select:none;user-select:none;-webkit-touch-callout:none}}
.it.arch{padding:0 0 0 14px}
.it.arch .ic{width:49px;height:49px;display:grid;place-items:center;flex:none}
.it.arch .ic svg{width:22px;height:22px;fill:var(--verde)}
.mlist .sub{font-weight:700;color:var(--verde)}
/* 2026-11-26 (Jhoset): como WhatsApp: flechita y carita en cada mensaje, menú, reacciones, responder, fijar y destacar */
.m .mfl{position:absolute;top:2px;right:2px;width:32px;height:24px;border:0;padding:0 0 0 6px;border-radius:0 14px 0 16px;display:grid;place-items:center;opacity:0;transition:opacity .12s;z-index:1}
.m .mfl svg{width:20px;height:20px;fill:var(--txt2)}
.m.el .mfl{background:radial-gradient(circle at 70% 40%,var(--burbuja-el) 58%,rgba(26,32,28,0) 82%)}
.m.yo .mfl{background:radial-gradient(circle at 70% 40%,#62d64b 58%,rgba(98,214,75,0) 82%)}
.m.yo .mfl svg{fill:#0b0d0c}
.m.st .mfl,.m.solo-emo .mfl{background:var(--panel2);border-radius:50%;width:26px;height:26px;padding:0;top:0}
.m .mre{position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;border:7px solid transparent;background:var(--panel2);background-clip:padding-box;border-radius:50%;padding:0;display:grid;place-items:center;opacity:0;transition:opacity .12s}
.m .mre svg{width:19px;height:19px;fill:var(--txt2)}
.m .mre:hover svg{fill:var(--txt)}
.m.yo .mre{right:100%}
.m.el .mre{left:100%}
.m:hover .mfl,.m:hover .mre,.m.mon .mfl,.m.mon .mre{opacity:1}
@media (hover:none){.m .mfl,.m .mre{display:none}.m{-webkit-user-select:none;user-select:none;-webkit-touch-callout:none}}
.m .fw{display:flex;align-items:center;gap:4px;font-size:.76rem;font-style:italic;opacity:.72;margin-bottom:2px}
.m .fw svg{width:14px;height:14px;fill:currentColor}
.m .cita{display:block;border-left:4px solid var(--verde);background:rgba(0,0,0,.22);border-radius:8px;padding:5px 9px;margin:0 -4px 5px;font-size:.84rem;cursor:pointer;white-space:normal;line-height:1.3}
.m.yo .cita{background:rgba(11,13,12,.13);border-left-color:#0b0d0c}
.m .cita b{display:block;font-size:.78rem;color:var(--verde)}
.m.yo .cita b{color:#0b0d0c}
.m .cita span{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;opacity:.85;white-space:pre-wrap}
.m .borrado{display:inline-flex;align-items:center;gap:5px}
.m .borrado svg{width:15px;height:15px;fill:currentColor;flex:none}
.m.yo .borrado{color:rgba(11,13,12,.65)}
.m .meta .ed{font-style:italic}
.m .meta .es{font-size:.8rem}
.m.con-reac{margin-bottom:17px}
.m .reacs{position:absolute;bottom:-17px;display:flex;align-items:center;gap:1px;background:var(--panel2);border:1px solid var(--borde);border-radius:12px;padding:1px 6px;font-size:.88rem;cursor:pointer;white-space:nowrap;line-height:1.4;z-index:1;color:var(--txt)}
.m.yo .reacs{right:10px}
.m.el .reacs{left:10px}
.m .reacs .n{font-size:.72rem;color:var(--txt2);margin-left:3px}
.m .reacs.mia{border-color:rgba(166,243,74,.5)}
.m.flash{animation:mflash 1.4s}
@keyframes mflash{25%,60%{box-shadow:0 0 0 4px rgba(166,243,74,.6)}}
.mmenu{position:fixed;z-index:92;display:flex;flex-direction:column;gap:8px;align-items:flex-start;max-width:calc(100vw - 16px)}
.mmenu.der{align-items:flex-end}
.rbar{display:flex;align-items:center;gap:2px;background:var(--panel2);border:1px solid var(--borde);border-radius:999px;padding:4px 6px;box-shadow:0 10px 30px rgba(0,0,0,.5)}
.rbar button{border:0;background:none;font-size:1.55rem;width:42px;height:42px;border-radius:50%;padding:0;line-height:1;transition:transform .12s}
.rbar button:hover{transform:scale(1.18);background:var(--hover)}
.rbar button.on{background:var(--chip)}
.rbar .mas{font-size:1.4rem;color:var(--txt2);background:var(--panel3)}
.mlist{background:var(--panel2);border:1px solid var(--borde);border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.55);padding:6px 0;min-width:230px;color:var(--txt);overflow-y:auto}
.mlist button{display:flex;align-items:center;gap:14px;width:100%;text-align:left;background:none;border:0;padding:10px 18px;font-size:.93rem}
.mlist button:hover{background:var(--hover)}
.mlist button svg{width:20px;height:20px;fill:var(--txt2);flex:none}
.mlist hr{border:0;border-top:1px solid var(--borde);margin:6px 0}
.mlist .rojo{color:#ff5c7a}.mlist .rojo svg{fill:#ff5c7a}
.resp{flex:none;display:flex;align-items:center;gap:8px;padding:8px 10px 0;background:var(--panel);border-top:1px solid var(--borde)}
.resp .q{flex:1;min-width:0;border-left:4px solid var(--verde);background:var(--panel3);border-radius:10px;padding:6px 10px;font-size:.86rem}
.resp .q b{display:block;color:var(--verde);font-size:.8rem}
.resp .q span{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--txt2)}
.resp + .barra{border-top:0}
.fijado{flex:none;display:flex;align-items:center;gap:10px;padding:7px 8px 7px 16px;background:var(--panel);border-bottom:1px solid var(--borde);cursor:pointer}
.fijado>svg{width:18px;height:18px;fill:var(--txt2);flex:none}
.fijado .tx{flex:1;min-width:0;font-size:.88rem}
.fijado small{display:block;color:var(--verde);font-size:.74rem;font-weight:700}
.fijado span{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--txt2)}
.rpick{display:grid;grid-template-columns:repeat(auto-fill,minmax(42px,1fr))}
.rpick button{border:0;background:none;font-size:1.6rem;height:44px;border-radius:10px;padding:0}
.rpick button:hover{background:var(--panel3)}
.rpick .cat{grid-column:1/-1;font-size:.74rem;color:var(--txt2);font-weight:700;margin:10px 2px 4px;text-transform:uppercase}
.fsel{display:flex;align-items:center;gap:12px;padding:8px 2px;border-bottom:1px solid var(--borde);cursor:pointer;color:var(--txt)!important;margin:0!important;font-size:.95rem!important;font-weight:400!important}
.fsel input{width:18px;height:18px;accent-color:#16b84e;flex:none}
.fsel .tx{flex:1;min-width:0}
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
/* Emojis y stickers (2026-11-18) */
.emo{flex:none;background:var(--panel);border-top:1px solid var(--borde);display:flex;flex-direction:column;height:min(330px,42vh)}
.emo[hidden]{display:none}
.emo .et{display:flex;gap:2px;padding:6px 8px 0;overflow-x:auto;scrollbar-width:none;flex:none}
.emo .et::-webkit-scrollbar{display:none}
.emo .et button{flex:0 0 auto;border:0;background:none;font-size:1.25rem;width:40px;height:36px;border-radius:10px;opacity:.55;border-bottom:2px solid transparent}
.emo .et button.on{opacity:1;border-bottom-color:var(--verde);border-radius:10px 10px 0 0}
.emo .eb{flex:1;overflow-y:auto;min-height:0;padding:6px 8px}
.emo .eg{display:grid;grid-template-columns:repeat(auto-fill,minmax(40px,1fr))}
.emo .eg button{border:0;background:none;font-size:1.55rem;height:42px;border-radius:10px;cursor:pointer;line-height:1}
.emo .eg button:hover{background:var(--panel2)}
.emo .ecat{font-size:.74rem;color:var(--txt2);font-weight:700;margin:8px 2px 4px;text-transform:uppercase;letter-spacing:.04em}
.emo .sg{display:grid;grid-template-columns:repeat(auto-fill,minmax(82px,1fr));gap:8px}
.emo .sg .s{aspect-ratio:1;border-radius:12px;background:center/contain no-repeat;cursor:pointer;position:relative;border:1px solid transparent}
.emo .sg .s:hover{background-color:var(--panel2);border-color:var(--borde)}
.emo .sg .s .fv{position:absolute;right:2px;top:0;font-size:.8rem}
.emo .sg .cr{display:grid;place-items:center;align-content:center;gap:2px;border:1px dashed var(--borde);background:var(--panel2);color:var(--txt2);font-size:.78rem}
.emo .sg .cr b{font-size:1.7rem;color:var(--verde);line-height:1}
.emo .ep{display:flex;justify-content:center;gap:0;padding:6px;border-top:1px solid var(--borde);flex:none}
.emo .ep button{border:1px solid var(--borde);background:var(--panel2);color:var(--txt2);padding:6px 22px;font-weight:700;font-size:.85rem}
.emo .ep button:first-child{border-radius:999px 0 0 999px}
.emo .ep button:last-child{border-radius:0 999px 999px 0}
.emo .ep button.on{background:var(--verde);color:var(--sobre-verde);border-color:var(--verde)}
.m.st{background:none!important;border:0!important;padding:0;cursor:pointer}
.m.st img{width:150px;height:150px;object-fit:contain;display:block;border-radius:0}
.m.st .meta{display:inline-block;background:rgba(11,13,12,.65);color:#e9edef!important;border-radius:8px;padding:1px 6px;margin-top:2px}
.m.solo-emo{background:none!important;border:0!important;font-size:2.6rem;line-height:1.15;padding:0 2px}
.m.solo-emo .meta{font-size:.7rem;display:block;text-align:right;color:var(--txt2)!important}
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
/* 2026-11-17 (Jhoset): tamaños de letra iguales a WhatsApp (Android): nombre 17px, último mensaje 15px,
   hora 12.5px, burbujas 16px, foto 52px. Antes todo salía más chico porque la base era 15px. */
.it{gap:14px}
.it .tx{padding:14px 15px 14px 0;gap:3px}
.it .n{font-size:17px;font-weight:500}
.it .u{font-size:15px}
.it .h{font-size:12.5px}
.it .b{font-size:12.5px;min-width:22px;height:22px}
.av{width:52px;height:52px;font-size:19px}
.av.p{width:42px;height:42px;font-size:16px}
.solic .ic{width:52px;height:52px}
.solic b{font-size:17px;font-weight:500}.solic small{font-size:14.5px}
.buscar input{font-size:16.5px;padding:12px 16px 12px 44px;border-radius:24px}
.chip{font-size:14.5px;padding:7px 15px}
.tabs button{font-size:14.5px;padding:8px 15px}
.nav button{font-size:13.5px}
.ccab .n{font-size:17px;font-weight:500}
.ccab .s{font-size:13px}
.m{font-size:16px;line-height:1.4}
.m .au{font-size:14px}
.m .meta{font-size:11.5px}
.dia{font-size:12.5px}
.sis{font-size:13.5px}
.menu button{font-size:16px}
.vacio{font-size:15px}
/* 2026-11-25 (Jhoset): barra lateral tipo WhatsApp Web en computador */
.rail{display:none}
.solo-pc{display:none!important}
@media (min-width:761px){
  .rail{display:flex;flex-direction:column;align-items:center;gap:6px;width:64px;flex:none;padding:12px 0;background:var(--panel2);border-right:1px solid var(--borde)}
  .rail button{width:42px;height:42px;border-radius:50%;border:0;background:none;color:var(--txt2);display:grid;place-items:center;position:relative;cursor:pointer;padding:0}
  .rail button svg{width:24px;height:24px;fill:currentColor}
  .rail button:hover{background:rgba(255,255,255,.07);color:var(--txt)}
  .rail button.on{background:rgba(166,243,74,.16);color:var(--verde)}
  .rail-esp{flex:1}
  .rail .rb{position:absolute;top:2px;right:0;background:var(--verde);color:var(--sobre-verde);font-size:.62rem;font-weight:800;min-width:17px;height:17px;border-radius:9px;display:grid;place-items:center;padding:0 4px}
  .rail .rb.punto{min-width:10px;width:10px;height:10px;padding:0;top:6px;right:6px}
  .rail .rb[hidden]{display:none}
  .rail-av{width:34px;height:34px;border-radius:50%;background:#2a2f2c center/cover;display:grid;place-items:center;font-weight:800;color:var(--txt);font-size:.9rem}
  .lado .nav{display:none}
  .lado .fab{display:none}
  #tabs button[data-t="conocer"],#tabs button[data-t="empresas"]{display:none}
  .solo-pc{display:grid!important}
  .chip{background:none;border:1px solid var(--borde)}
}
</style>
</head>
<body>
{{-- 2026-11-25 (Jhoset): pantalla de carga tipo WhatsApp con el aviso de seguridad de los chats. --}}
<div id="lp-splash" style="position:fixed;inset:0;z-index:9999;background:#0b0d0c;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;transition:opacity .25s">
    <img src="/icons/lpchat-192.png?v=2" alt="" style="width:64px;height:64px;border-radius:18px;opacity:.9">
    <div style="color:#fff;font:700 1.15rem system-ui,sans-serif">LP <span style="color:#a6f34a">Chat</span></div>
    <div style="width:200px;height:4px;border-radius:4px;background:rgba(255,255,255,.12);overflow:hidden"><div id="lp-splash-barra" style="height:100%;width:15%;background:#fff;border-radius:4px;transition:width 2.5s ease-out"></div></div>
    <div style="color:#a1a1aa;font:500 .88rem system-ui,sans-serif;display:flex;align-items:center;gap:6px">🔒 Chats privados y cifrados</div>
</div>
<script>setTimeout(function () { var b = document.getElementById('lp-splash-barra'); if (b) b.style.width = '85%'; }, 30);
function quitarSplash() { var s = document.getElementById('lp-splash'); if (!s) return; document.getElementById('lp-splash-barra').style.width = '100%'; setTimeout(function () { s.style.opacity = '0'; setTimeout(function () { s.remove(); }, 260); }, 150); }
setTimeout(quitarSplash, 4000);</script>
<div class="app" id="app">
    {{-- 2026-11-25 (Jhoset): barra lateral como WhatsApp Web (solo en computador): Chats, Estados, Conocer gente, Empresas, Tienda; abajo Ajustes y Perfil. --}}
    <nav class="rail" id="rail">
        <button type="button" data-r="chats" title="Chats" aria-label="Chats"><svg viewBox="0 0 24 24"><path d="M19.005 3.175H4.674C3.642 3.175 3 3.789 3 4.821V21.02l3.544-3.514h12.461c1.033 0 2.064-1.06 2.064-2.093V4.821c-.001-1.032-1.032-1.646-2.064-1.646z"/></svg><span class="rb" id="rb-msj" hidden></span></button>
        <button type="button" data-r="estados" title="Estados" aria-label="Estados"><svg viewBox="0 0 24 24"><path d="M12 20.664a9.163 9.163 0 0 1-6.521-2.702.977.977 0 0 1 1.381-1.381 7.269 7.269 0 0 0 10.024.244.977.977 0 0 1 1.313 1.445A9.192 9.192 0 0 1 12 20.664zm7.965-6.112a.977.977 0 0 1-.944-1.229 7.26 7.26 0 0 0-4.8-8.804.977.977 0 0 1 .594-1.86 9.212 9.212 0 0 1 6.092 11.169.976.976 0 0 1-.942.724zm-16.025-.39a.977.977 0 0 1-.953-.769 9.21 9.21 0 0 1 6.626-10.86.975.975 0 1 1 .52 1.882l-.015.004a7.259 7.259 0 0 0-5.223 8.558.978.978 0 0 1-.955 1.185z"/><circle cx="12" cy="12" r="4"/></svg><span class="rb punto" id="rb-est" hidden></span></button>
        <button type="button" data-r="conocer" title="Conocer gente" aria-label="Conocer gente"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></button>
        <button type="button" data-r="empresas" title="Empresas" aria-label="Empresas"><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg></button>
        <button type="button" data-r="tienda" title="Tienda LocalProo" aria-label="Tienda LocalProo"><svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg></button>
        <span class="rail-esp"></span>
        <button type="button" data-r="ajustes" title="Ajustes y menú" aria-label="Ajustes y menú"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg></button>
        <button type="button" data-r="perfil" title="Mi perfil" aria-label="Mi perfil"><span class="rail-av" id="rail-av"></span></button>
    </nav>
    <aside class="lado">
        <div class="cab">
            <div class="f1">
                <img class="logo" src="/icons/lpchat-192.png?v=2" alt=""><h1 id="tit"><span>LP</span> <b>Chat</b></h1>
                <button class="ib" id="b-instalar" title="Instalar la app" hidden><svg viewBox="0 0 24 24"><path d="M5 20h14v-2H5v2zM19 9h-4V3H9v6H5l7 7 7-7z"/></svg></button>
                <button class="ib" id="b-cam" title="Subir estado"><svg viewBox="0 0 24 24"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 2 7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg></button>
                <button class="ib solo-pc" id="b-nuevo" title="Nuevo chat"><svg viewBox="0 0 24 24"><path d="M19.005 3.175H4.674C3.642 3.175 3 3.789 3 4.821V21.02l3.544-3.514h12.461c1.033 0 2.064-1.06 2.064-2.093V4.821c-.001-1.032-1.032-1.646-2.064-1.646zM15 11h-2v2a1 1 0 1 1-2 0v-2H9a1 1 0 1 1 0-2h2V7a1 1 0 1 1 2 0v2h2a1 1 0 1 1 0 2z"/></svg></button>
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
            <div class="buscar"><input class="q-chats" type="search" placeholder="Buscar un chat o iniciar uno nuevo"></div>
            <div class="chips">
                <button class="chip on" data-f="todos">Todos</button>
                <button class="chip" data-f="noleidos">No leídos</button>
                <button class="chip" data-f="favoritos">Favoritos</button>
                <button class="chip" data-f="grupos">Grupos</button>
                <span id="chips-listas" style="display:contents"></span>
                <button class="chip" data-nueva-lista title="Crear una lista">＋</button>
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
            <div class="buscar" style="padding-top:0"><input id="k-buscar" type="search" placeholder="Buscar por nombre…" autocomplete="off"></div>
            <div class="lista" id="lista-k"><div class="grid" id="grid"></div><div class="vacio" style="font-size:.82rem;padding:10px 20px 20px">Cada día te mostramos personas distintas. 🔒 Por seguridad no se ven correos ni celulares.</div></div>
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
            <img src="/icons/lpchat-192.png?v=2" alt="">
            <h2>LP <b>Chat</b></h2>
            <p>La red social nariñense: chatea con tus amigos, crea grupos, conoce gente y escríbele a las empresas.</p>
            <p style="font-size:.8rem;margin-top:30px">🔒 Chats privados y cifrados · Una plataforma de <b style="color:var(--verde)">LocalProo</b></p>
        </div>
        <div class="conv" id="conv" hidden>
            <div class="ccab" id="ccab">
                <button class="ib volver" id="b-volver" title="Volver"><svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg></button>
                <div class="av p" id="c-av"></div>
                <div class="tx"><div class="n" id="c-n"></div><div class="s" id="c-s"></div></div>
                <button class="ib" id="b-cmenu" title="Más"><svg viewBox="0 0 24 24"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"/></svg></button>
            </div>
            <div class="fijado" id="fijado" hidden><svg viewBox="0 0 24 24"><path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z"/></svg><div class="tx"><small>Mensaje fijado</small><span id="fijado-t"></span></div><button class="ib" id="fijado-x" title="Desfijar">✕</button></div>
            <div class="msgs" id="msgs"></div>
            <div class="aviso" id="aviso" hidden></div>
            <div class="prev" id="prev" hidden><div id="prev-m"></div><span style="flex:1;color:var(--txt2);font-size:.86rem" id="prev-t">Listo para enviar</span><button class="uvbtn" id="b-uv" title="Ver una sola vez">1</button><button class="ib" id="prev-x" title="Quitar">✕</button></div>
            <div class="resp" id="resp" hidden><div class="q"><b id="resp-a"></b><span id="resp-t"></span></div><button type="button" class="ib" id="resp-x" title="Cancelar">✕</button></div>
            <div class="emo" id="emo" hidden>
                <div class="et" id="emo-t"></div>
                <div class="eb" id="emo-b"></div>
                <div class="ep"><button type="button" class="on" data-emo-modo="e">😊 Emojis</button><button type="button" data-emo-modo="s">🧩 Stickers</button></div>
                <input type="file" id="f-st" accept="image/*" hidden>
            </div>
            <form class="barra" id="barra">
                <div class="caja" id="caja">
                    <button type="button" class="ib" id="b-adj" title="Adjuntar foto o video"><svg viewBox="0 0 24 24"><path d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z"/></svg></button>
                    <button type="button" class="ib" id="b-emo" title="Emojis y stickers"><svg viewBox="0 0 24 24"><path d="M9.153 11.603c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962zm-3.204 1.362c-.026-.307-.131 5.218 6.063 5.551 6.066-.25 6.066-5.551 6.066-5.551-6.078 1.416-12.129 0-12.129 0zm11.363 1.108s-.669 1.959-5.051 1.959c-3.505 0-5.388-1.164-5.607-1.959 0 0 5.912 1.055 10.658 0zM11.804 1.011C5.609 1.011.978 6.033.978 12.228s4.826 10.761 11.021 10.761S23.02 18.423 23.02 12.228c.001-6.195-5.021-11.217-11.216-11.217zM12 21.354c-5.273 0-9.381-3.886-9.381-9.159s3.942-9.548 9.215-9.548 9.548 4.275 9.548 9.548c-.001 5.272-4.109 9.159-9.382 9.159zm3.108-9.751c.795 0 1.439-.879 1.439-1.962s-.644-1.962-1.439-1.962-1.439.879-1.439 1.962.644 1.962 1.439 1.962z"/></svg></button>
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
var SUPER = {{ auth('web')->user()?->es_superadmin ? 'true' : 'false' }};
var CSRF = document.querySelector('meta[name=csrf-token]').content;
var $ = function (id) { return document.getElementById(id); };
var st = { nav: 'mensajes', tab: 'mis', filtro: 'todos', chats: { mis: [], general: [] }, solicitudes: 0, abierto: 0, ultimo: 0, info: null, adj: null, uv: false, escT: 0, sinLeer: 0, primera: true, origen: '', empPag: 1, msgs: {}, cambios: 0, resp: 0, nArch: 0, verArch: false, listas: [] };
var COL = ['#16b84e', '#3b82f6', '#7c3aed', '#e11d74', '#f59e0b', '#0ea5e9', '#ef4444', '#14b8a6', '#8b5cf6', '#22c55e'];
var DIAS = 90;

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function enlaces(s) { return esc(s).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>'); }
/* 2026-11-18: enlaces de chat (LP Chat, NES, PFN) se abren aquí mismo, como un número en WhatsApp. */
var RX_CHAT = /^https?:\/\/(www\.)?(localproo\.com\/chat(\/[ug]\/[a-z0-9]+\/?|\/?\?chat=\d+)|(sistema\.)?narinoexposhow\.com\/(lp)?chat\/?\?con=[up]?\d+|pastofightnight\.com\/(lp)?chat\/?\?con=[up]?\d+)/i;
document.addEventListener('click', function (e) {
    var a = e.target.closest('.m a[href]'); if (!a) return;
    var url = a.getAttribute('href').replace(/&amp;/g, '&');
    if (!RX_CHAT.test(url) || e.ctrlKey || e.metaKey) return;
    e.preventDefault(); e.stopPropagation();
    var m = a.closest('[data-m]');
    post(U + '/abrir-enlace', { url: url, mensaje: m ? +m.dataset.m : 0 }).then(function (j) {
        if (j.ok) { cerrarVentana(); irTab('mis'); abrirChat(j.chat); }
        else if (j.externo) window.open(url, '_blank', 'noopener');
        else toast(j.mensaje || 'No se pudo abrir el chat.');
    }).catch(function () { window.open(url, '_blank', 'noopener'); });
}, true);
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
    if (sec === 'mis' && st.verArch) return pintarArch(box, q);
    var l = st.chats[sec].filter(function (c) {
        if (sec === 'mis' && st.filtro === 'noleidos' && !c.sin_leer && !c.no_leido) return false;
        if (sec === 'mis' && st.filtro === 'grupos' && c.tipo !== 'grupo') return false;
        if (sec === 'mis' && st.filtro === 'favoritos' && !c.favorito) return false;
        if (sec === 'mis' && st.filtro.indexOf('lista:') === 0 && (c.listas || []).indexOf(+st.filtro.slice(6)) < 0) return false;
        return !q || (c.nombre || '').toLowerCase().indexOf(q) >= 0;
    });
    var h = '';
    if (sec === 'general' && st.solicitudes && !q) {
        h += '<div class="solic" data-ir="solic"><div class="ic"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg></div><div style="flex:1"><b>Solicitudes de mensaje</b><small>' + st.solicitudes + ' persona' + (st.solicitudes === 1 ? '' : 's') + ' que no conoces te escribió</small></div><span class="b" style="background:var(--verde);color:#0b0d0c;border-radius:11px;padding:2px 8px;font-size:.75rem;font-weight:800">' + st.solicitudes + '</span></div>';
    }
    if (st.nArch && !q && (sec === 'general' || st.filtro === 'todos')) h += '<div class="it arch" data-ver-arch><div class="ic">' + ico('archivar') + '</div><div class="tx"><div class="r1"><span class="n">Archivados</span><span class="h">' + st.nArch + '</span></div></div></div>';
    h += l.map(itemHtml).join('');
    if (!st.chats[sec].length) {
        h += sec === 'mis'
            ? '<div class="cajita"><b class="t">Comparte este link e inicia a chatear 💬</b>Mándale tu link a tus amigos: quien lo abra queda en <b>Mis chats</b> contigo.<div class="lk"><input readonly id="link-' + sec + '" value="' + esc(YO.link) + '"><button class="bt" data-copiar="link-' + sec + '">Copiar</button></div><div class="lk"><a class="bt w" style="flex:1" target="_blank" href="https://wa.me/?text=' + encodeURIComponent('¡Hablemos por LP Chat, la red social nariñense! Abre mi link: ' + YO.link) + '">Enviar por WhatsApp</a><button class="bt sec" data-compartir>Compartir</button></div></div>'
            : '<div class="vacio">Aquí llegan las conversaciones con la gente que conoces en <b>Conocer gente</b> y con las <b>Empresas</b>.<br><br><button class="bt" data-tab="conocer">Conocer gente</button> <button class="bt sec" data-tab="empresas">Ver empresas</button></div>';
    } else if (!l.length) h += '<div class="vacio">No hay chats aquí.</div>';
    box.innerHTML = h;
}
function itemHtml(c) {
    var nl = c.sin_leer || c.no_leido;
    var badge = c.sin_leer ? '<span class="b' + (c.silenciado ? ' gris' : '') + '">' + c.sin_leer + '</span>' : (c.no_leido ? '<span class="b pto"></span>' : '');
    var ics = (c.silenciado ? ico('silenciar') : '') + (c.fijado ? ico('fijar') : '') + (c.favorito ? ico('favorito') : '');
    return '<div class="it' + (c.id === st.abierto ? ' act' : '') + '" data-chat="' + c.id + '">' + avatar(c, '', c.tipo === 'grupo') +
        '<div class="tx"><div class="r1"><span class="n">' + esc(c.nombre) + '</span>' + (c.empresa ? '<span class="tag">Empresa</span>' : '') + '<span class="h' + (nl && !c.silenciado ? ' nl' : '') + '">' + esc(c.hora) + '</span></div>' +
        '<div class="r2"><span class="u">' + (c.mio ? TK + ' ' : '') + esc(c.ultimo || (c.tipo === 'grupo' ? 'Grupo creado' : '')) + '</span>' + (ics ? '<span class="ics">' + ics + '</span>' : '') + badge +
        '<button type="button" class="cfl" data-cfl title="Opciones del chat">' + ico('flecha') + '</button></div></div></div>';
}
function cargar(sec) {
    return api(U + '/chats?tab=' + sec).then(function (j) {
        if (!j.ok) return;
        st.chats[sec] = j.chats; st.solicitudes = j.solicitudes; st.nArch = j.archivados || 0;
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
function cargarChats() { if (st.verArch) cargarArch(); cargar('mis'); return cargar('general'); }
function cargarSolic() {
    api(U + '/chats?tab=solicitudes').then(function (j) {
        if (j.ok) $('lista-solic').innerHTML = j.chats.length ? j.chats.map(itemHtml).join('') : '<div class="vacio">No tienes solicitudes. 🙌</div>';
    });
}
function avisar(chats) {
    try {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        var c = chats.filter(function (x) { return x.sin_leer; })[0];
        if (c) new Notification(c.nombre, { body: c.ultimo, icon: '/icons/lpchat-192.png?v=2', tag: 'lpchat-' + c.id });
    } catch (e) {}
}
document.querySelectorAll('#p-mis .chip').forEach(function (b) {
    b.onclick = function () { st.filtro = b.dataset.f; st.verArch = false; document.querySelectorAll('#p-mis .chip').forEach(function (x) { x.classList.toggle('on', x === b); }); pintar('mis'); };
});
document.querySelectorAll('.q-chats').forEach(function (i) { i.oninput = function () { pintar(i.closest('section').id.slice(2)); }; });

/* ================= Conocer gente ================= */
function conocer() {
    api(U + '/conocer?origen=' + st.origen).then(function (j) {
        if (!j.ok) { $('grid').innerHTML = '<div class="vacio" style="grid-column:1/-1">' + esc(j.mensaje || 'No disponible.') + '</div>'; return; }
        $('grid').dataset.ok = 1;
        pintarCupo(j);
        $('grid').innerHTML = j.perfiles.length ? j.perfiles.map(function (p) {
            var fs = p.foto ? 'background-image:url(\'' + esc(p.foto) + '\')' : 'background-color:' + color(p.nombre);
            var og = { pfn: 'PFN', nes: 'NES', localproo: 'LocalProo' }[p.origen] || '';
            return '<div class="card" data-perfil="' + p.id + '" data-n="' + esc(sinTilde(p.nombre)) + '"><div class="ft" style="' + fs + '">' + (p.foto ? '' : inicial(p.nombre)) + (og ? '<span class="og">' + og + '</span>' : '') + (p.en_linea ? '<span class="on"></span>' : '') + '</div>' +
                '<div class="cd"><span class="cn">' + esc(p.nombre) + '</span><span class="cs">' + esc(p.etiqueta || p.ciudad || p.visto || '') + '</span>' +
                '<button class="bt" data-saludar="' + p.id + '">Saludar 👋</button></div></div>';
        }).join('') : '<div class="vacio" style="grid-column:1/-1">No hay más personas por hoy. Vuelve mañana 🙌</div>';
        filtrarConocer();
    });
}
function sinTilde(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
function filtrarConocer() {
    var q = sinTilde($('k-buscar').value.trim()), n = 0, cs = $('grid').querySelectorAll('.card');
    cs.forEach(function (c) { var v = !q || c.dataset.n.indexOf(q) > -1; c.hidden = !v; if (v) n++; });
    var x = $('k-nada');
    if (cs.length && !n) { if (!x) { x = document.createElement('div'); x.id = 'k-nada'; x.className = 'vacio'; x.style.gridColumn = '1/-1'; $('grid').appendChild(x); } x.textContent = 'Nadie con ese nombre en las personas de hoy.'; x.hidden = false; }
    else if (x) x.hidden = true;
}
$('k-buscar').addEventListener('input', filtrarConocer);
function pintarCupo(j) {
    var q = j.quedan, l = j.limite;
    st.quedan = q;
    var pro = j.pro ? '<span class="tag" style="background:var(--verde);color:#0b0d0c;margin-right:6px">⭐ PRO</span>' : '';
    var h = l === null ? pro + '👋 Puedes saludar a todas las personas que quieras.'
        : (q > 0 ? pro + '👋 Hoy puedes saludar a <b>' + q + ' de ' + l + '</b> personas nuevas. Les llega como solicitud y lo verás en <b>General</b>.'
                 : pro + '✋ Ya saludaste a ' + l + ' personas hoy.' + (j.pro ? ' Mañana puedes conocer a más.' : ''));
    if (!j.pro && l !== null) h += '<div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap"><button class="bt" data-pro style="padding:6px 12px">⭐ Pásate a Pro</button><span style="color:var(--txt2);font-size:.82rem">$' + Number(j.precio).toLocaleString('es-CO') + ' al mes · saluda hasta ' + j.limite_pro + ' personas al día</span></div>';
    else if (j.pro_hasta) h += '<div style="color:var(--txt2);font-size:.8rem;margin-top:4px">Tu Plan Pro va hasta el ' + esc(j.pro_hasta) + '.</div>';
    $('cupo').innerHTML = h;
}
function planPro(otroMes) {
    api(U + '/pro').then(function (j) {
        if (!j.ok) return;
        var precio = '$' + Number(j.precio).toLocaleString('es-CO');
        if (j.pro && otroMes !== true) return ventana('⭐ Plan Pro', '<p>Tienes Plan Pro' + (j.sin_limite ? ' sin límite' : ' hasta el <b>' + esc(j.hasta) + '</b>') + '. Saludas hasta <b>' + j.limite + '</b> personas nuevas al día en Conocer gente. 🙌</p>' + (j.sin_limite ? '' : '<p style="color:var(--txt2);font-size:.88rem">Si pagas otro mes antes de que se venza, se te suman los días.</p>'), j.sin_limite ? '' : '<button class="bt" id="pro-otra">Pagar otro mes</button>');
        var cuerpo = '<div style="text-align:center"><div class="marca-lp" style="font-size:1.6rem">LP Chat <b>Pro</b></div><div style="font-size:2rem;font-weight:900;color:var(--verde);margin:6px 0">' + precio + '<span style="font-size:.95rem;color:var(--txt2);font-weight:600"> / mes</span></div></div>' +
            '<div class="fila"><div class="tx">Gratis<small>Saludas a ' + j.gratis + ' personas nuevas al día</small></div></div>' +
            '<div class="fila"><div class="tx"><b style="color:var(--verde)">⭐ Pro</b><small>Saludas hasta ' + j.limite + ' personas al día y ves más perfiles cada día, por ' + j.dias + ' días</small></div></div>' +
            (j.pendiente ? '<div class="cupo" style="margin:14px 0 0">⏳ Ya recibimos tu comprobante. Apenas lo revisemos se activa tu Plan Pro.</div>' :
            '<label>1. Paga ' + precio + ' por Nequi al</label><div style="display:flex;gap:6px"><input type="text" readonly id="pro-nequi" value="' + esc(j.nequi) + '"><button class="bt" data-copiar="pro-nequi">Copiar</button></div>' +
            '<label>2. Sube la foto del comprobante</label><input type="file" id="pro-f" accept="image/*" style="width:100%">' +
            '<p style="color:var(--txt2);font-size:.82rem">También puedes mandarlo por <a href="' + esc(j.wa) + '" target="_blank" rel="noopener">WhatsApp</a>. Se activa apenas lo revisemos.</p>');
        ventana('⭐ Pásate a Pro', cuerpo, j.pendiente ? '<button class="bt sec" data-cerrar>Cerrar</button>' : '<button class="bt sec" data-cerrar>Ahora no</button><button class="bt" id="pro-ok">Enviar comprobante</button>');
        if ($('pro-ok')) $('pro-ok').onclick = function () {
            var f = $('pro-f').files[0]; if (!f) return toast('Elige la foto del comprobante.');
            var fd = new FormData(); fd.append('comprobante', f); this.disabled = true; this.textContent = 'Enviando…';
            api(U + '/pro', { method: 'POST', body: fd }).then(function (r) { toast(r.mensaje || (r.ok ? 'Listo.' : 'No se pudo.')); if (r.ok) cerrarVentana(); else planPro(); });
        };
    });
}
document.querySelectorAll('#p-conocer .chip').forEach(function (b) {
    b.onclick = function () { st.origen = b.dataset.o; document.querySelectorAll('#p-conocer .chip').forEach(function (x) { x.classList.toggle('on', x === b); }); conocer(); };
});
$('grid').addEventListener('contextmenu', function (e) { e.preventDefault(); });
function saludar(id) {
    post(U + '/abrir', { usuario_id: id }).then(function (j) {
        if (!j.ok) { toast(j.mensaje || 'No se pudo.'); if (j.pro) planPro(); return; }
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
/* 2026-11-25 (Jhoset): la dirección del chat es el usuario de la persona (localproo.com/chat/rafael-gomez), como Instagram. */
var URL_INICIAL = {!! json_encode($abrirUrl ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
function urlChat(id) {
    if (id === abrir && URL_INICIAL) return URL_INICIAL;
    var c = null;
    ['mis', 'general'].forEach(function (s) { (st.chats[s] || []).forEach(function (x) { if (x.id === id) c = x; }); });
    return c && c.usuario ? '/chat/' + c.usuario : '/chat?chat=' + id;
}
function abrirChat(id) {
    if (!id) return;
    st.abierto = id; st.ultimo = 0; st.info = null; st.msgs = {}; st.cambios = 0; cerrarMenuMsg(); quitarResp();
    $('bienv').hidden = true; $('conv').hidden = false; $('msgs').innerHTML = ''; $('msgs').dataset.dia = ''; $('msgs').dataset.autor = ''; $('aviso').hidden = true;
    $('app').classList.add('abierto'); quitarAdj();
    var url = urlChat(id);
    if (location.pathname + location.search !== url) history.pushState({ chat: id }, '', url);
    ['mis', 'general'].forEach(pintar);
    traer(true);
    if (window.innerWidth > 760) $('txt').focus();
}
function cerrarChat(sinHist) {
    st.abierto = 0; $('app').classList.remove('abierto'); $('conv').hidden = true; $('bienv').hidden = false;
    if (!sinHist && (location.search || location.pathname !== '/chat')) history.replaceState(null, '', '/chat');
    pararGrab(true); if ($('emo')) emoAbrir(false); cargarChats();
}
$('b-volver').onclick = function (e) { e.stopPropagation(); if (history.state && history.state.chat) history.back(); else cerrarChat(); };
window.addEventListener('popstate', function () { if (visor) return cerrarVisor(); if (st.abierto) cerrarChat(true); });
function traer(primera) {
    if (!st.abierto || cargando) return;
    cargando = true;
    var id = st.abierto;
    api(U + '/chat/' + id + (st.ultimo ? '?desde=' + st.ultimo + '&cambios=' + (st.cambios || 0) : '')).then(function (j) {
        cargando = false;
        if (id !== st.abierto) return;
        if (!j.ok) { toast('Esta conversación no está disponible.'); return cerrarChat(); }
        st.info = j.chat; st.info.seccion = j.seccion;
        $('c-av').outerHTML = avatar(j.chat.otro || j.chat, 'p', j.chat.tipo === 'grupo').replace('class="av', 'id="c-av" class="av');
        $('c-n').textContent = j.chat.nombre;
        var s = $('c-s');
        s.textContent = j.escribiendo.length ? (j.chat.tipo === 'grupo' ? j.escribiendo.join(', ') + ' escribiendo…' : (j.escribiendo[0].indexOf('🎤') > 0 ? 'grabando audio…' : 'escribiendo…')) : (j.chat.sub || (j.chat.tipo === 'directo' ? 'toca para ver el perfil' : ''));
        var box = $('msgs'), abajo = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
        if (primera) box.insertAdjacentHTML('beforeend', '<div class="sis">🔒 Chat privado: los mensajes se guardan cifrados y solo los ven las personas de este chat.</div>');
        j.mensajes.forEach(function (m) { pintarMsg(box, m); });
        (j.cambiados || []).forEach(repintarMsg);
        if (j.ahora) st.cambios = j.ahora;
        pintarFijado(j.chat.fijado);
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
    st.msgs[m.id] = m;
    box.insertAdjacentHTML('beforeend', msgHtml(m, pri));
}
/* 2026-11-26: un mensaje que cambió (reacción, editado, eliminado, destacado): se pinta de nuevo en su sitio. */
function repintarMsg(m) {
    var el = $('msgs').querySelector('.m[data-m="' + m.id + '"]');
    if (!el || m.sistema) return;
    var antes = st.msgs[m.id];
    if (antes && JSON.stringify(antes) === JSON.stringify(m)) return; // igual: no se toca (no corta un video que se está viendo)
    st.msgs[m.id] = m;
    var tmp = document.createElement('div'); tmp.innerHTML = msgHtml(m, el.classList.contains('pri'));
    el.replaceWith(tmp.firstChild);
}
var ICO = {
    flecha: 'M7.41 8.59 12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z',
    cara: 'M15.5 11c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5zM11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z',
    info: 'M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z',
    responder: 'M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z',
    copiar: 'M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z',
    reenviar: 'M14 9V5l7 7-7 7v-4.1c-5 0-8.5 1.6-11 5.1 1-5 4-10 11-11z',
    difusion: 'M18 11v2h4v-2h-4zm-2 6.61c.96.71 2.21 1.65 3.2 2.39.4-.53.8-1.07 1.2-1.6-.99-.74-2.24-1.68-3.2-2.4-.4.54-.8 1.08-1.2 1.61zM20.4 5.6c-.4-.53-.8-1.07-1.2-1.6-.99.74-2.24 1.68-3.2 2.4.4.53.8 1.07 1.2 1.6.96-.72 2.21-1.65 3.2-2.4zM4 9c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h1v4h2v-4h1l5 3V6L8 9H4zm11.5 3c0-1.33-.58-2.53-1.5-3.35v6.69c.92-.81 1.5-2.01 1.5-3.34z',
    fijar: 'M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z',
    destacar: 'M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z',
    editar: 'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z',
    nota: 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z',
    eliminar: 'M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5-1-1h-5l-1 1H5v2h14V4h-3.5z',
    archivar: 'M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5 6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z',
    silenciar: 'M20 18.69 7.84 6.14 5.27 3.49 4 4.76l2.8 2.8v.01c-.52.99-.8 2.16-.8 3.42v5l-2 2v1h13.73l2 2L21 19.72l-1-1.03zM12 22c1.11 0 2-.89 2-2h-4c0 1.11.89 2 2 2zm6-7.32V11c0-3.08-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68c-.15.03-.29.08-.42.12-.1.03-.2.07-.3.11h-.01c-.01 0-.01 0-.02.01-.23.09-.46.2-.68.31 0 0-.01 0-.01.01L18 14.68z',
    sonar: 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z',
    noleido: 'M22 6.98V16c0 1.1-.9 2-2 2H6l-4 4V4c0-1.1.9-2 2-2h10.1c-.06.32-.1.66-.1 1 0 2.76 2.24 5 5 5 1.13 0 2.16-.39 3-1.02zM16 3c0 1.66 1.34 3 3 3s3-1.34 3-3-1.34-3-3-3-3 1.34-3 3z',
    favorito: 'M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z',
    lista: 'M14 10H3v2h11v-2zm0-4H3v2h11V6zm4 8v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM3 16h7v-2H3v2z',
    vaciar: 'M7 11v2h10v-2H7zm5-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z',
    salir: 'M10.09 15.59 11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z',
    bloq: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-4.42 3.58-8 8-8 1.85 0 3.55.63 4.9 1.69L5.69 16.9A7.902 7.902 0 0 1 4 12zm8 8c-1.85 0-3.55-.63-4.9-1.69L18.31 7.1A7.902 7.902 0 0 1 20 12c0 4.42-3.58 8-8 8z'
};
function ico(n) { return '<svg viewBox="0 0 24 24"><path d="' + ICO[n] + '"/></svg>'; }
function msgHtml(m, pri) {
    var media = '';
    var nom = { foto: 'Foto', video: 'Video', audio: 'Audio', sticker: 'Sticker' }[m.adj] || 'Archivo';
    if (m.borrado) media = '<span class="borrado">' + ico('bloq') + (m.mio ? 'Eliminaste este mensaje' : 'Se eliminó este mensaje') + '</span>';
    else if (m.una_vez) {
        media = m.mio ? '<div class="uv off"><span class="c">1</span>' + nom + (m.abierto ? ' · abierto' : ' · para ver una vez') + '</div>'
            : (m.abierto ? '<div class="uv off"><span class="c">1</span>' + nom + ' · ya lo abriste</div>' : '<div class="uv" data-uv="' + m.id + '" data-t="' + m.adj + '"><span class="c">1</span>Ver ' + nom.toLowerCase() + ' una vez</div>');
    } else if (m.adj && !m.url) media = '<div class="borrado">' + nom + ' borrado</div>';
    else if (m.adj === 'sticker') media = '<img src="' + esc(m.url) + '" alt="Sticker" loading="lazy" draggable="false">';
    else if (m.adj === 'foto') media = '<img src="' + esc(m.url) + '" alt="Foto" loading="lazy">';
    else if (m.adj === 'video') media = '<video src="' + esc(m.url) + '#t=0.1" controls playsinline preload="metadata"></video>';
    else if (m.adj === 'audio') media = '<audio src="' + esc(m.url) + '" controls preload="metadata"></audio>';
    var extra = m.borrado ? '' : (m.adj === 'sticker' && m.url ? ' st' : (!m.adj && soloEmoji(m.texto) && !m.responde ? ' solo-emo' : ''));
    var rs = m.reacciones || [], reac = '';
    if (rs.length) {
        var tot = 0, mia = false; rs.forEach(function (r) { tot += r.n; if (r.mio) mia = true; });
        reac = '<span class="reacs' + (mia ? ' mia' : '') + '" data-reacs="' + m.id + '" title="Ver reacciones">' + rs.slice(0, 3).map(function (r) { return esc(r.e); }).join('') + (tot > 1 ? '<span class="n">' + tot + '</span>' : '') + '</span>';
    }
    return '<div class="m ' + (m.mio ? 'yo' : 'el') + (pri ? ' pri' : '') + extra + (reac ? ' con-reac' : '') + '" data-m="' + m.id + '"' + (extra === ' st' && !m.mio ? ' data-st="' + m.id + '"' : '') + '>' +
        (m.autor && pri ? '<span class="au" style="color:' + color(m.autor) + '">' + esc(m.autor) + '</span>' : '') +
        (m.reenviado ? '<span class="fw">' + ico('reenviar') + 'Reenviado</span>' : '') +
        (m.responde ? '<span class="cita" data-ir="' + m.responde.id + '"><b>' + esc(m.responde.autor) + '</b><span>' + esc(m.responde.texto) + '</span></span>' : '') +
        media + (m.texto && !m.borrado ? enlaces(m.texto) : '') +
        '<span class="meta">' + (m.destacado ? '<span class="es">★</span>' : '') + (m.editado ? '<span class="ed">Editado</span>' : '') + esc(m.hora) + (m.mio && !m.borrado ? TK.replace('class="tk"', 'class="tk' + (m.leido ? ' l' : '') + '"') : '') + '</span>' +
        '<button type="button" class="mfl" data-mfl title="Menú">' + ico('flecha') + '</button>' +
        (m.borrado ? '' : '<button type="button" class="mre" data-mre title="Reaccionar">' + ico('cara') + '</button>') + reac +
        '</div>';
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
    var stk = e.target.closest('.m.st');
    if (stk) {
        if (!stk.dataset.st) return;
        ventana('🧩 Sticker', '<div style="text-align:center"><img src="' + esc(stk.querySelector('img').src) + '" style="width:160px;height:160px;object-fit:contain"></div>', '<button class="bt" id="st-guardar">Agregar a mis stickers</button>');
        $('st-guardar').onclick = function () { post(U + '/stickers/guardar/' + stk.dataset.st).then(function (j) { cerrarVentana(); toast(j.ok ? 'Guardado en tus stickers ✨' : (j.mensaje || 'No se pudo guardar.')); if (j.ok) emo.sts = null; }); };
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
    if (st.resp) fd.append('responde_id', st.resp);
    quitarResp();
    if (st.adj) { fd.append('archivo', st.adj.f, st.adj.f.name || ('audio.' + (st.adj.f.type.indexOf('mp4') > 0 ? 'm4a' : 'webm'))); if (st.adj.audio) fd.append('es_audio', 1); if (st.uv) fd.append('una_vez', 1); }
    var tenia = st.adj;
    $('txt').value = ''; autoAlto(); quitarAdj();
    if (tenia) toast('Enviando…');
    api(U + '/chat/' + st.abierto + '/enviar', { method: 'POST', body: fd }).then(function (j) {
        if (!j.ok) { toast(j.mensaje || 'No se envió.'); $('txt').value = t; actualizarBotones(); return; }
        traer(false); cargarChats();
    }).catch(function () { toast('Sin conexión. No se envió.'); $('txt').value = t; actualizarBotones(); });
}
/* ---------- Menú de cada mensaje (2026-11-26, como WhatsApp) ----------
   Computador: flechita (arriba a la derecha del mensaje) o clic derecho = menú; carita al lado = reaccionar.
   Celular: mantener presionado el mensaje. */
var REAC = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
function urlM(id, acc) { return U + '/chat/' + st.abierto + '/m/' + id + (acc ? '/' + acc : ''); }
function cerrarMenuMsg() {
    var x = $('mmenu'); if (x) x.remove();
    document.querySelectorAll('.m.mon,.it.mon').forEach(function (e) { e.classList.remove('mon'); });
}
function puedeEscribirAqui() { return !$('barra').hidden; }
function abrirMenuMsg(id, ancla, soloReac) {
    cerrarMenuMsg(); cerrarMenu();
    var m = st.msgs[id], el = $('msgs').querySelector('.m[data-m="' + id + '"]');
    if (!m || !el) return;
    el.classList.add('mon');
    var d = document.createElement('div'); d.className = 'mmenu' + (m.mio ? ' der' : ''); d.id = 'mmenu';
    var h = '';
    if (!m.borrado && puedeEscribirAqui()) {
        var mia = (m.reacciones || []).filter(function (r) { return r.mio; })[0];
        h += '<div class="rbar">' + REAC.map(function (e) { return '<button type="button" data-reac="' + e + '"' + (mia && mia.e === e ? ' class="on"' : '') + '>' + e + '</button>'; }).join('') +
            '<button type="button" class="mas" data-reac-mas title="Más emojis">+</button></div>';
    }
    if (!soloReac) {
        var it = [];
        if (m.borrado) it.push(['eliminar', 'Eliminar', 'ocultar', true]);
        else {
            it.push(['info', 'Info. del mensaje', 'info']);
            if (puedeEscribirAqui()) it.push(['responder', 'Responder', 'responder']);
            if (m.texto) it.push(['copiar', 'Copiar', 'copiar']);
            if (puedeEscribirAqui()) it.push(['cara', 'Reaccionar', 'reaccionar']);
            if (!m.una_vez) { it.push(['reenviar', 'Reenviar', 'reenviar']); it.push(['difusion', 'Nueva difusión', 'difusion']); }
            if (puedeEscribirAqui()) it.push(['fijar', st.info && st.info.fijado && st.info.fijado.id === m.id ? 'Desfijar' : 'Fijar', 'fijar']);
            it.push(['destacar', m.destacado ? 'No destacar' : 'Destacar', 'destacar']);
            if (m.editable) it.push(['editar', 'Editar', 'editar']);
            if (m.texto) it.push(['nota', 'Añadir texto a la nota', 'nota']);
            it.push(['eliminar', 'Eliminar', 'eliminar', true]);
        }
        h += '<div class="mlist">' + it.map(function (x) { return (x[3] ? '<hr>' : '') + '<button type="button" data-macc="' + x[2] + '"' + (x[3] ? ' class="rojo"' : '') + '>' + ico(x[0]) + esc(x[1]) + '</button>'; }).join('') + '</div>';
    }
    d.innerHTML = h;
    document.body.appendChild(d);
    // Se ubica junto al mensaje (abajo si cabe; si no, arriba) sin salirse de la pantalla.
    var r = (ancla || el).getBoundingClientRect(), b = el.getBoundingClientRect(), W = window.innerWidth, H = window.innerHeight;
    var lst = d.querySelector('.mlist'); if (lst) lst.style.maxHeight = (H - 90) + 'px';
    var dw = d.offsetWidth, dh = d.offsetHeight;
    var left = m.mio ? b.right - dw : b.left;
    if (soloReac && ancla) left = m.mio ? r.right - dw + 20 : r.left - 20;
    left = Math.max(8, Math.min(left, W - dw - 8));
    var top = r.bottom + 6;
    if (top + dh > H - 8) top = r.top - dh - 6;
    if (top < 8) top = Math.max(8, (H - dh) / 2);
    d.style.left = left + 'px'; d.style.top = top + 'px';
    d.addEventListener('click', function (e) {
        e.stopPropagation();
        var rb = e.target.closest('[data-reac]');
        if (rb) { cerrarMenuMsg(); return reaccionar(id, rb.dataset.reac); }
        if (e.target.closest('[data-reac-mas]')) { cerrarMenuMsg(); return elegirReaccion(id); }
        var a = e.target.closest('[data-macc]'); if (!a) return;
        cerrarMenuMsg(); accionMsg(id, a.dataset.macc);
    });
}
document.addEventListener('click', function (e) { if ($('mmenu') && !e.target.closest('#mmenu')) cerrarMenuMsg(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cerrarMenuMsg(); } });
$('msgs').addEventListener('scroll', cerrarMenuMsg, { passive: true });
window.addEventListener('resize', cerrarMenuMsg);
/* Flechita, carita, reacciones y citas (va antes que el clic de fotos/stickers) */
$('msgs').addEventListener('click', function (e) {
    var b = e.target.closest('[data-mfl]');
    if (b) { e.stopPropagation(); e.preventDefault(); var id = +b.closest('.m').dataset.m; if ($('mmenu') && $('msgs').querySelector('.m.mon[data-m="' + id + '"]')) return cerrarMenuMsg(); return abrirMenuMsg(id, b); }
    b = e.target.closest('[data-mre]');
    if (b) { e.stopPropagation(); e.preventDefault(); return abrirMenuMsg(+b.closest('.m').dataset.m, b, true); }
    b = e.target.closest('[data-reacs]');
    if (b) { e.stopPropagation(); return verReacciones(+b.dataset.reacs); }
    b = e.target.closest('.cita[data-ir]');
    if (b) { e.stopPropagation(); return irAMsg(+b.dataset.ir); }
}, true);
/* Clic derecho (computador) o mantener presionado (celular) = menú */
var lp = { t: 0, x: 0, y: 0, hecho: 0 };
$('msgs').addEventListener('contextmenu', function (e) {
    var el = e.target.closest('.m[data-m]'); if (!el) return;
    if (e.target.closest('a,video,audio') && window.matchMedia('(hover:hover)').matches) return; // en el computador deja copiar links o guardar
    e.preventDefault();
    if (Date.now() - lp.hecho < 700) return;
    abrirMenuMsg(+el.dataset.m);
});
$('msgs').addEventListener('touchstart', function (e) {
    var el = e.target.closest('.m[data-m]'); if (!el || e.touches.length > 1) return;
    lp.x = e.touches[0].clientX; lp.y = e.touches[0].clientY;
    clearTimeout(lp.t);
    lp.t = setTimeout(function () { lp.hecho = Date.now(); if (navigator.vibrate) try { navigator.vibrate(15); } catch (x) {} abrirMenuMsg(+el.dataset.m); }, 480);
}, { passive: true });
$('msgs').addEventListener('touchmove', function (e) { if (Math.abs(e.touches[0].clientX - lp.x) > 10 || Math.abs(e.touches[0].clientY - lp.y) > 10) clearTimeout(lp.t); }, { passive: true });
$('msgs').addEventListener('touchend', function (e) { clearTimeout(lp.t); if (Date.now() - lp.hecho < 600) e.preventDefault(); });
$('msgs').addEventListener('touchcancel', function () { clearTimeout(lp.t); });

function irAMsg(id) {
    var el = id && $('msgs').querySelector('[data-m="' + id + '"]');
    if (!el) return toast('Ese mensaje es más antiguo y ya no está cargado aquí.');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.classList.remove('flash'); void el.offsetWidth; el.classList.add('flash');
}
function textoCorto(m) {
    if (m.borrado) return 'Se eliminó este mensaje';
    var nom = { foto: '📷 Foto', video: '🎥 Video', audio: '🎤 Audio', sticker: '🧩 Sticker' }[m.adj] || (m.adj ? '📎 Archivo' : '');
    return (nom + (nom && m.texto ? ' · ' : '') + (m.texto || '')).slice(0, 160);
}
function copiarTexto(v) {
    (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast('Mensaje copiado.'); }, function () {
        var t = document.createElement('textarea'); t.value = v; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (x) {} t.remove(); toast('Mensaje copiado.');
    });
}
function reaccionar(id, e) {
    var m = st.msgs[id]; if (!m) return;
    post(urlM(id, 'reaccionar'), { emoji: e }).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo reaccionar.'); traer(false); });
}
function elegirReaccion(id) {
    ventana('Reaccionar', '<div class="rpick">' + EMOJIS.map(function (c) { return '<div class="cat">' + c[1] + '</div>' + c[2].split(' ').map(function (x) { return '<button type="button" data-rp="' + x + '">' + x + '</button>'; }).join(''); }).join('') + '</div>');
    document.querySelector('.rpick').onclick = function (e) { var b = e.target.closest('[data-rp]'); if (!b) return; cerrarVentana(); emoUsar(b.dataset.rp); reaccionar(id, b.dataset.rp); };
}
function verReacciones(id) {
    var m = st.msgs[id]; if (!m || !(m.reacciones || []).length) return;
    var h = m.reacciones.map(function (r) {
        return '<div class="fila"><div style="font-size:1.7rem;width:42px;text-align:center">' + esc(r.e) + '</div><div class="tx">' + r.quien.map(esc).join(', ') + '<small>' + r.n + (r.n === 1 ? ' persona' : ' personas') + '</small></div>' +
            (r.mio ? '<button class="bt sec" data-quitar-reac>Quitar</button>' : '') + '</div>';
    }).join('');
    ventana('Reacciones', h);
    var q = document.querySelector('[data-quitar-reac]'); if (q) q.onclick = function () { cerrarVentana(); reaccionar(id, ''); };
}
function ponerResp(id) {
    var m = st.msgs[id]; if (!m) return;
    st.resp = id;
    $('resp-a').textContent = m.mio ? 'Tú' : (m.autor || (st.info && st.info.nombre) || '');
    $('resp-t').textContent = textoCorto(m);
    $('resp').hidden = false; $('txt').focus();
}
function quitarResp() { st.resp = 0; if ($('resp')) $('resp').hidden = true; }
$('resp-x').onclick = quitarResp;
function pintarFijado(f) {
    if (st.info) st.info.fijado = f || null;
    $('fijado').hidden = !f;
    if (f) { $('fijado-t').textContent = f.texto; $('fijado').dataset.id = f.id; }
}
$('fijado').onclick = function (e) { if (e.target.closest('#fijado-x')) return; irAMsg(+this.dataset.id); };
$('fijado-x').onclick = function (e) { e.stopPropagation(); var id = +$('fijado').dataset.id; post(urlM(id, 'fijar')).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo.'); toast('Mensaje desfijado.'); traer(false); }); };

function accionMsg(id, acc) {
    var m = st.msgs[id]; if (!m) return;
    if (acc === 'responder') return ponerResp(id);
    if (acc === 'copiar') return copiarTexto(m.texto || '');
    if (acc === 'reaccionar') return abrirMenuMsg(id, null, true);
    if (acc === 'reenviar' || acc === 'difusion') return reenviarMsg(id, acc === 'difusion');
    if (acc === 'info') {
        return api(urlM(id, 'info')).then(function (j) {
            if (!j.ok) return toast(j.mensaje || 'No se pudo.');
            var h = '<div class="m ' + (m.mio ? 'yo' : 'el') + '" style="max-width:100%;margin:0 0 12px auto;pointer-events:none">' + esc(textoCorto(m)) + '</div>' +
                '<div class="fila"><div class="tx">Enviado' + (m.mio ? '' : ' por ' + esc(j.autor)) + '<small>' + esc(j.enviado) + '</small></div></div>' +
                (j.editado ? '<div class="fila"><div class="tx">Editado<small>' + esc(j.editado) + '</small></div></div>' : '');
            if (j.mio) {
                if (st.info && st.info.tipo === 'directo') h += '<div class="fila"><div class="tx">' + (j.leyeron.length ? '<span style="color:var(--verde)">✓✓ Leído</span>' : '✓✓ Entregado') + '<small>' + (j.leyeron.length ? 'Ya lo vio' : 'Aún no lo ha visto') + '</small></div></div>';
                else h += '<label>Leído por (' + j.leyeron.length + ')</label>' + (j.leyeron.length ? j.leyeron.map(function (n) { return '<div class="fila"><div class="tx">' + esc(n) + '</div></div>'; }).join('') : '<p style="color:var(--txt2);margin:4px 0">Nadie todavía.</p>') +
                    '<label>Falta por leer (' + j.faltan.length + ')</label>' + j.faltan.map(function (n) { return '<div class="fila"><div class="tx">' + esc(n) + '</div></div>'; }).join('');
            }
            ventana('Info. del mensaje', h);
        });
    }
    if (acc === 'fijar') {
        return post(urlM(id, 'fijar')).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo.'); toast(j.fijado ? '📌 Mensaje fijado para todos en este chat.' : 'Mensaje desfijado.'); traer(false); });
    }
    if (acc === 'destacar') {
        return post(urlM(id, 'destacar')).then(function (j) {
            if (!j.ok) return toast(j.mensaje || 'No se pudo.');
            var n = JSON.parse(JSON.stringify(m)); n.destacado = j.destacado; repintarMsg(n);
            toast(j.destacado ? '★ Mensaje destacado.' : 'Ya no está destacado.');
        });
    }
    if (acc === 'nota') {
        return post(urlM(id, 'nota')).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo.'); toast('📝 Añadido a la nota del chat (menú ⋮ → Nota del chat).'); });
    }
    if (acc === 'editar') {
        ventana('Editar mensaje', '<textarea id="ed-t" rows="4" maxlength="4000" style="width:100%">' + esc(m.texto || '') + '</textarea><p style="color:var(--txt2);font-size:.82rem;margin:6px 0 0">Se puede editar durante 15 minutos. Los demás verán que dice "Editado".</p>',
            '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="ed-ok">Guardar</button>');
        var t = $('ed-t'); t.focus(); t.selectionStart = t.selectionEnd = t.value.length;
        $('ed-ok').onclick = function () {
            post(urlM(id, 'editar'), { texto: t.value }).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo editar.'); cerrarVentana(); traer(false); });
        };
        return;
    }
    if (acc === 'ocultar') return ocultarMsg(id);
    if (acc === 'eliminar') {
        ventana('¿Eliminar mensaje?', '<p style="margin:0;color:var(--txt2)">' + (m.borrable ? 'Puedes eliminarlo para todos (desaparece en este chat para todos) o solo para ti.' : 'Se quitará solo de tu pantalla; los demás lo siguen viendo.') + '</p>',
            '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt sec" id="el-mi">Eliminar para mí</button>' + (m.borrable ? '<button class="bt rojo" id="el-todos">Eliminar para todos</button>' : ''));
        $('el-mi').onclick = function () { cerrarVentana(); ocultarMsg(id); };
        if ($('el-todos')) $('el-todos').onclick = function () {
            cerrarVentana();
            post(urlM(id, 'eliminar')).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo eliminar.'); if (st.resp === id) quitarResp(); traer(false); cargarChats(); });
        };
    }
}
function ocultarMsg(id) {
    post(urlM(id, 'ocultar')).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se pudo eliminar.');
        var el = $('msgs').querySelector('[data-m="' + id + '"]'); if (el) el.remove();
        delete st.msgs[id]; if (st.resp === id) quitarResp();
        toast('Mensaje eliminado para ti.');
    });
}
/* Reenviar (hasta 5 chats) · Nueva difusión (el mismo mensaje a muchos contactos a la vez, hasta 50, cada uno lo recibe en su chat) */
function reenviarMsg(id, difusion) {
    var max = difusion ? 50 : 5, vistos = {}, lista = [];
    ['mis', 'general'].forEach(function (s) { (st.chats[s] || []).forEach(function (c) { if (!vistos[c.id] && (!difusion || c.tipo === 'directo')) { vistos[c.id] = 1; lista.push(c); } }); });
    if (!lista.length) return toast(difusion ? 'Aún no tienes contactos para la difusión.' : 'No tienes chats para reenviar.');
    ventana(difusion ? 'Nueva difusión' : 'Reenviar mensaje',
        (difusion ? '<p style="margin:0 0 8px;color:var(--txt2);font-size:.88rem">Escoge a quién enviarle este mensaje (hasta ' + max + '). Cada persona lo recibe en su chat contigo, como un mensaje normal.</p>' : '') +
        '<input type="text" id="fw-q" placeholder="Buscar chat" style="margin-bottom:6px">' +
        (difusion ? '<label class="fsel" style="font-weight:700!important"><input type="checkbox" id="fw-todos"><div class="tx">Seleccionar todos</div></label>' : '') +
        '<div id="fw-l">' + lista.map(function (c) { return '<label class="fsel" data-n="' + esc(sinTilde(c.nombre)) + '"><input type="checkbox" class="fw-c" value="' + c.id + '">' + avatar(c, 'p', c.tipo === 'grupo') + '<div class="tx">' + esc(c.nombre) + '</div></label>'; }).join('') + '</div>',
        '<span id="fw-n" style="margin-right:auto;color:var(--txt2);align-self:center;font-size:.88rem">0 de ' + max + '</span><button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="fw-ok">Enviar</button>');
    var cuenta = function () {
        var ch = document.querySelectorAll('.fw-c:checked');
        document.querySelectorAll('.fw-c:not(:checked)').forEach(function (x) { x.disabled = ch.length >= max; });
        $('fw-n').textContent = ch.length + ' de ' + max;
    };
    $('fw-l').onchange = cuenta;
    $('fw-q').oninput = function () { var q = sinTilde(this.value.trim()); document.querySelectorAll('#fw-l .fsel').forEach(function (f) { f.hidden = q && f.dataset.n.indexOf(q) < 0; }); };
    if ($('fw-todos')) $('fw-todos').onchange = function () { var on = this.checked, n = 0; document.querySelectorAll('#fw-l .fsel:not([hidden]) .fw-c').forEach(function (x) { x.disabled = false; x.checked = on && n++ < max; }); cuenta(); };
    $('fw-ok').onclick = function () {
        var ids = [].slice.call(document.querySelectorAll('.fw-c:checked')).map(function (x) { return +x.value; });
        if (!ids.length) return toast('Escoge al menos un chat.');
        this.disabled = true;
        post(urlM(id, 'reenviar'), { chats: ids, difusion: difusion ? 1 : 0 }).then(function (j) {
            if (!j.ok) { $('fw-ok').disabled = false; return toast(j.mensaje || 'No se pudo enviar.'); }
            cerrarVentana(); toast(difusion ? '📣 Difusión enviada a ' + j.enviados + ' contacto' + (j.enviados === 1 ? '' : 's') + '.' : 'Mensaje reenviado' + (j.enviados > 1 ? ' a ' + j.enviados + ' chats' : '') + '.');
            traer(false); cargarChats();
        }).catch(function () { $('fw-ok').disabled = false; toast('Sin conexión.'); });
    };
}
function verDestacados() {
    api(U + '/chat/' + st.abierto + '/destacados').then(function (j) {
        if (!j.ok) return toast('No se pudo.');
        ventana('★ Mensajes destacados', j.mensajes.length ? j.mensajes.map(function (x) { return '<div class="fila" data-dest="' + x.id + '" style="cursor:pointer"><div class="tx"><b>' + esc(x.autor) + '</b><small style="color:var(--txt);white-space:pre-wrap">' + esc(x.texto) + '</small></div><small>' + esc(x.hora) + '</small></div>'; }).join('')
            : '<p style="color:var(--txt2);text-align:center">No tienes mensajes destacados en este chat.<br>Abre el menú de un mensaje y toca <b>Destacar</b>.</p>');
        document.querySelectorAll('[data-dest]').forEach(function (f) { f.onclick = function () { cerrarVentana(); irAMsg(+f.dataset.dest); }; });
    });
}
function verNota() {
    api(U + '/chat/' + st.abierto + '/nota').then(function (j) {
        if (!j.ok) return toast('No se pudo.');
        ventana('📝 Nota del chat', '<p style="margin:0 0 8px;color:var(--txt2);font-size:.85rem">Solo tú la ves. Úsala para apuntar datos de este chat (pedidos, direcciones, acuerdos).</p><textarea id="nota-t" rows="9" maxlength="8000" style="width:100%">' + esc(j.nota || '') + '</textarea>',
            '<button class="bt sec" data-cerrar>Cerrar</button><button class="bt" id="nota-ok">Guardar</button>');
        $('nota-ok').onclick = function () { post(U + '/chat/' + st.abierto + '/nota', { nota: $('nota-t').value }).then(function (r) { if (!r.ok) return toast(r.mensaje || 'No se pudo guardar.'); cerrarVentana(); toast('Nota guardada.'); }); };
    });
}

/* ---------- Menú de cada chat en la lista (2026-11-26, como WhatsApp) ----------
   Computador: flechita al pasar el mouse por el chat (o clic derecho). Celular: mantener presionado el chat. */
function chatDe(id) {
    var c = null;
    ['mis', 'general', 'archivados'].forEach(function (s) { (st.chats[s] || []).forEach(function (x) { if (x.id === id) c = x; }); });
    return c;
}
function urlC(id, acc) { return U + '/chat/' + id + '/opcion/' + acc; }
function menuChat(id, ancla, sub) {
    cerrarMenuMsg(); cerrarMenu();
    var c = chatDe(id), it = $('p-' + (st.tab === 'general' ? 'general' : 'mis')).querySelector('.it[data-chat="' + id + '"]') || document.querySelector('.it[data-chat="' + id + '"]');
    if (!c || !it) return;
    it.classList.add('mon');
    var l = [];
    if (sub === 'silenciar') {
        l.push(['silenciar', '‹ Silenciar notificaciones', 'volver', false, 'sub']);
        l.push(['silenciar', 'Durante 8 horas', 'sil:8']); l.push(['silenciar', 'Durante 1 semana', 'sil:168']); l.push(['silenciar', 'Siempre', 'sil:-1']);
    } else {
        l.push(['archivar', c.archivado ? 'Desarchivar chat' : 'Archivar chat', 'archivar']);
        l.push(c.silenciado ? ['sonar', 'Activar notificaciones', 'sil:0'] : ['silenciar', 'Silenciar notificaciones  ›', 'silmenu']);
        l.push(['fijar', c.fijado ? 'Desfijar chat' : 'Fijar chat', 'fijar']);
        l.push(['noleido', c.sin_leer || c.no_leido ? 'Marcar como leído' : 'Marcar como no leído', 'noleido']);
        l.push(['favorito', c.favorito ? 'Quitar de Favoritos' : 'Añadir a Favoritos', 'favorito']);
        l.push(['lista', 'Añadir a la lista', 'lista']);
        if (c.tipo === 'grupo') l.push(['salir', 'Salir del grupo', 'salir', true]);
        else l.push(['bloq', 'Bloquear', 'bloquear', true]);
        l.push(['vaciar', 'Vaciar chat', 'vaciar']);
        if (c.tipo !== 'grupo') l.push(['eliminar', 'Eliminar chat', 'eliminar', false, 'rojo']);
    }
    var d = document.createElement('div'); d.className = 'mmenu'; d.id = 'mmenu';
    d.innerHTML = '<div class="mlist">' + l.map(function (x) { return (x[3] ? '<hr>' : '') + '<button type="button" data-cacc="' + x[2] + '"' + (x[4] ? ' class="' + x[4] + '"' : '') + '>' + ico(x[0]) + esc(x[1]) + '</button>'; }).join('') + '</div>';
    document.body.appendChild(d);
    var r = (ancla || it).getBoundingClientRect(), W = window.innerWidth, H = window.innerHeight, dw = d.offsetWidth, dh = d.offsetHeight;
    var left = ancla ? r.right - dw : Math.min(r.left + 60, W - dw - 8), top = ancla ? r.bottom + 4 : r.top + r.height / 2;
    left = Math.max(8, Math.min(left, W - dw - 8));
    if (top + dh > H - 8) top = Math.max(8, (ancla ? r.top : top) - dh - 4);
    d.style.left = left + 'px'; d.style.top = top + 'px';
    d.addEventListener('click', function (e) {
        e.stopPropagation();
        var b = e.target.closest('[data-cacc]'); if (!b) return;
        var a = b.dataset.cacc;
        if (a === 'silmenu') return menuChat(id, ancla, 'silenciar');
        if (a === 'volver') return menuChat(id, ancla);
        cerrarMenuMsg(); accionChat(id, a);
    });
}
function trasChat(j, id) {
    if (!j.ok) return toast(j.mensaje || 'No se pudo.');
    if (j.mensaje) toast(j.mensaje);
    cargarChats();
}
function accionChat(id, a) {
    var c = chatDe(id); if (!c) return;
    if (a.indexOf('sil:') === 0) return post(urlC(id, 'silenciar'), { horas: +a.slice(4) }).then(function (j) { trasChat(j, id); });
    if (['archivar', 'fijar', 'noleido', 'favorito'].indexOf(a) >= 0) {
        return post(urlC(id, a)).then(function (j) { trasChat(j, id); if (a === 'archivar' && st.abierto === id && !c.archivado) cerrarChat(); });
    }
    if (a === 'lista') return elegirListas(id);
    var conf = {
        bloquear: ['¿Bloquear a ' + c.nombre + '?', 'No podrán escribirse ni llamarse. Puedes desbloquearlo después desde el chat (⋮).', 'Bloquear'],
        salir: ['¿Salir de "' + c.nombre + '"?', 'Ya no recibirás mensajes de este grupo.', 'Salir'],
        vaciar: ['¿Vaciar este chat?', 'Se borran todos los mensajes de tu pantalla. El chat sigue en la lista y la otra persona no ve ningún cambio.', 'Vaciar chat'],
        eliminar: ['¿Eliminar este chat?', 'Se quita de tu lista con sus mensajes. Si te vuelve a escribir, aparece otra vez.', 'Eliminar chat']
    }[a];
    if (!conf) return;
    ventana(esc(conf[0]), '<p style="margin:0;color:var(--txt2)">' + esc(conf[1]) + '</p>', '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt rojo" id="cc-ok">' + conf[2] + '</button>');
    $('cc-ok').onclick = function () {
        cerrarVentana();
        var url = a === 'vaciar' ? urlC(id, 'vaciar') : U + '/chat/' + id + '/' + (a === 'bloquear' ? 'bloquear' : 'borrar');
        post(url).then(function (j) {
            if (!j.ok) return toast(j.mensaje || 'No se pudo.');
            toast({ bloquear: 'Bloqueado.', salir: 'Saliste del grupo.', vaciar: 'Chat vaciado.', eliminar: 'Chat eliminado.' }[a]);
            if (st.abierto === id) { if (a === 'vaciar') abrirChat(id); else if (a !== 'bloquear') cerrarChat(); else traer(false); }
            cargarChats();
        });
    };
}
/* Archivados */
function cargarArch() {
    return api(U + '/chats?tab=archivados').then(function (j) { if (!j.ok) return; st.chats.archivados = j.chats; st.nArch = j.archivados || 0; if (st.verArch) pintar('mis'); }).catch(function () {});
}
function pintarArch(box, q) {
    var l = (st.chats.archivados || []).filter(function (c) { return !q || (c.nombre || '').toLowerCase().indexOf(q) >= 0; });
    box.innerHTML = '<div class="it" data-salir-arch style="padding:12px 14px;gap:8px;color:var(--verde2);font-weight:600">← Archivados</div>' +
        '<p style="margin:4px 16px 10px;color:var(--txt2);font-size:.82rem">Los chats archivados no te avisan ni suman en los contadores. Siguen guardados aunque te escriban.</p>' +
        (l.length ? l.map(itemHtml).join('') : '<div class="vacio">No tienes chats archivados.</div>');
}
/* Listas */
function cargarListas() {
    return api(U + '/listas').then(function (j) {
        if (!j.ok) return;
        st.listas = j.listas;
        $('chips-listas').innerHTML = j.listas.map(function (x) { return '<button class="chip' + (st.filtro === 'lista:' + x.id ? ' on' : '') + '" data-f="lista:' + x.id + '">' + esc(x.nombre) + '</button>'; }).join('');
        if (st.filtro.indexOf('lista:') === 0 && !j.listas.some(function (x) { return 'lista:' + x.id === st.filtro; })) { st.filtro = 'todos'; document.querySelector('#p-mis .chip[data-f="todos"]').classList.add('on'); pintar('mis'); }
    }).catch(function () {});
}
function crearLista(alCrear) {
    ventana('Nueva lista', '<label>Nombre de la lista</label><input type="text" id="nl-n" maxlength="40" placeholder="Ej: Clientes, Familia, Proveedores">', '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="nl-ok">Crear</button>');
    $('nl-n').focus();
    $('nl-n').onkeydown = function (e) { if (e.key === 'Enter') $('nl-ok').click(); };
    $('nl-ok').onclick = function () {
        var n = $('nl-n').value.trim(); if (!n) return toast('Ponle un nombre.');
        post(U + '/listas', { nombre: n }).then(function (j) { if (!j.ok) return toast(j.mensaje || 'No se pudo.'); cargarListas(); if (alCrear) alCrear(j.id); else { cerrarVentana(); toast('Lista creada. Agrega chats con la flechita → Añadir a la lista.'); } });
    };
}
function elegirListas(id) {
    var c = chatDe(id); if (!c) return;
    cargarListas().then(function () {
        var dentro = (chatDe(id) || c).listas || [];
        ventana('Añadir a la lista', (st.listas.length ? st.listas.map(function (x) {
            return '<label class="fsel"><input type="checkbox" data-lst="' + x.id + '"' + (dentro.indexOf(x.id) >= 0 ? ' checked' : '') + '><div class="tx">' + esc(x.nombre) + '</div><button type="button" class="ib" data-lst-borrar="' + x.id + '" title="Borrar lista">🗑</button></label>';
        }).join('') : '<p style="color:var(--txt2);margin:0">Aún no tienes listas. Crea una para ordenar tus chats (clientes, familia, trabajo…).</p>'),
            '<button class="bt sec" id="lst-nueva">＋ Nueva lista</button><button class="bt" data-cerrar>Listo</button>');
        document.querySelectorAll('[data-lst]').forEach(function (b) {
            b.onchange = function () { post(U + '/listas/' + b.dataset.lst + '/chat', { chat_id: id }).then(function (j) { if (!j.ok) { b.checked = !b.checked; return toast(j.mensaje || 'No se pudo.'); } cargarChats(); }); };
        });
        document.querySelectorAll('[data-lst-borrar]').forEach(function (b) {
            b.onclick = function (e) { e.preventDefault(); post(U + '/listas/' + b.dataset.lstBorrar + '/borrar').then(function () { cargarListas().then(function () { elegirListas(id); }); cargarChats(); }); };
        });
        $('lst-nueva').onclick = function () {
            crearLista(function (nid) { post(U + '/listas/' + nid + '/chat', { chat_id: id }).then(function () { cargarChats(); cargarListas().then(function () { elegirListas(id); }); }); });
        };
    });
}
/* Clics de la lista (antes que abrir el chat) */
var lpc = { t: 0, x: 0, y: 0, hecho: 0 };
document.addEventListener('click', function (e) {
    var it = e.target.closest('.lado .it[data-chat]');
    if (it && Date.now() - lpc.hecho < 700) { e.stopPropagation(); e.preventDefault(); return; }
    var b = e.target.closest('[data-cfl]');
    if (b) { e.stopPropagation(); e.preventDefault(); var id = +b.closest('[data-chat]').dataset.chat; if ($('mmenu') && b.closest('.it.mon')) return cerrarMenuMsg(); return menuChat(id, b); }
    if (e.target.closest('[data-ver-arch]')) { e.stopPropagation(); st.verArch = true; pintar('mis'); cargarArch(); if (st.tab !== 'mis') irTab('mis'); return; }
    if (e.target.closest('[data-salir-arch]')) { e.stopPropagation(); st.verArch = false; pintar('mis'); return; }
    if (e.target.closest('[data-nueva-lista]')) { e.stopPropagation(); return crearLista(); }
    var ch = e.target.closest('#chips-listas .chip');
    if (ch) { e.stopPropagation(); st.filtro = ch.dataset.f; st.verArch = false; document.querySelectorAll('#p-mis .chip').forEach(function (x) { x.classList.toggle('on', x === ch); }); pintar('mis'); }
}, true);
document.addEventListener('contextmenu', function (e) {
    var it = e.target.closest('.lado .it[data-chat]'); if (!it) return;
    e.preventDefault();
    if (Date.now() - lpc.hecho < 700) return;
    menuChat(+it.dataset.chat);
});
document.addEventListener('touchstart', function (e) {
    var it = e.target.closest('.lado .it[data-chat]'); if (!it || e.touches.length > 1) return;
    lpc.x = e.touches[0].clientX; lpc.y = e.touches[0].clientY; clearTimeout(lpc.t);
    lpc.t = setTimeout(function () { lpc.hecho = Date.now(); if (navigator.vibrate) try { navigator.vibrate(15); } catch (x) {} menuChat(+it.dataset.chat); }, 480);
}, { passive: true });
document.addEventListener('touchmove', function (e) { if (Math.abs(e.touches[0].clientX - lpc.x) > 10 || Math.abs(e.touches[0].clientY - lpc.y) > 10) clearTimeout(lpc.t); }, { passive: true });
document.addEventListener('touchend', function () { clearTimeout(lpc.t); }, { passive: true });
cargarListas();

/* ---------- Emojis y stickers (2026-11-18) ---------- */
var EMOJIS = [
    ['😀', 'Caritas', '😀 😃 😄 😁 😆 😅 😂 🤣 🥲 😊 😇 🙂 🙃 😉 😌 😍 🥰 😘 😗 😙 😚 😋 😛 😝 😜 🤪 🤨 🧐 🤓 😎 🥸 🤩 🥳 😏 😒 😞 😔 😟 😕 🙁 ☹️ 😣 😖 😫 😩 🥺 😢 😭 😤 😠 😡 🤬 🤯 😳 🥵 🥶 😱 😨 😰 😥 😓 🤗 🤔 🫣 🤭 🫢 🤫 🤥 😶 😐 😑 😬 🙄 😯 😦 😧 😮 😲 🥱 😴 🤤 😪 😵 🤐 🥴 🤢 🤮 🤧 😷 🤒 🤕 🤑 🤠 😈 👿 👹 👺 🤡 💩 👻 💀 ☠️ 👽 🤖 🎃 😺 😸 😹 😻 😼 😽 🙀 😿 😾'],
    ['👋', 'Gestos', '👋 🤚 🖐️ ✋ 🖖 👌 🤌 🤏 ✌️ 🤞 🫰 🤟 🤘 🤙 👈 👉 👆 🖕 👇 ☝️ 👍 👎 ✊ 👊 🤛 🤜 👏 🙌 🫶 👐 🤲 🤝 🙏 ✍️ 💅 🤳 💪 🦵 🦶 👂 👃 🧠 👀 👁️ 👅 👄 💋 👶 🧒 👦 👧 🧑 👱 👨 🧔 👩 🧓 👴 👵 🙍 🙎 🙅 🙆 💁 🙋 🧏 🙇 🤦 🤷 👮 💂 👷 🤴 👸 👳 🧕 🤵 👰 🤰 🤱 👼 🎅 🤶 🦸 🦹 🧙 🧚 🧛 🧜 🧝 💃 🕺 👯 🧖 🧘 🏃 🚶 👫 👭 👬 💏 💑 👪'],
    ['❤️', 'Corazones', '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❤️‍🔥 ❣️ 💕 💞 💓 💗 💖 💘 💝 💟 ♥️ 💌 💯 💢 💥 💫 💦 💨 🕳️ 💬 💭 💤 ✨ ⭐ 🌟 🔥 🎉 🎊'],
    ['🐶', 'Animales y plantas', '🐶 🐱 🐭 🐹 🐰 🦊 🐻 🐼 🐨 🐯 🦁 🐮 🐷 🐸 🐵 🙈 🙉 🙊 🐔 🐧 🐦 🐤 🦆 🦅 🦉 🦇 🐺 🐗 🐴 🦄 🐝 🐛 🦋 🐌 🐞 🐜 🕷️ 🐢 🐍 🦎 🐙 🦑 🦐 🦀 🐡 🐠 🐟 🐬 🐳 🐋 🦈 🐊 🐅 🐆 🦓 🦍 🐘 🦛 🦏 🐪 🐫 🦒 🦘 🐃 🐂 🐄 🐎 🐖 🐏 🐑 🦙 🐐 🦌 🐕 🐩 🐈 🐓 🦃 🦚 🦜 🦢 🕊️ 🐇 🦝 🦨 🦡 🦦 🦥 🐁 🐀 🐿️ 🦔 🌵 🎄 🌲 🌳 🌴 🌱 🌿 ☘️ 🍀 🍁 🍂 🍃 🍄 🌾 💐 🌷 🌹 🥀 🌺 🌸 🌼 🌻 🌞 🌝 🌛 🌙 🌎 🪐 ⚡ ☀️ 🌤️ ⛅ 🌧️ ⛈️ 🌈 ☁️ ❄️ ⛄ 🌊'],
    ['🍔', 'Comida', '🍏 🍎 🍐 🍊 🍋 🍌 🍉 🍇 🍓 🫐 🍈 🍒 🍑 🥭 🍍 🥥 🥝 🍅 🍆 🥑 🥦 🥬 🥒 🌶️ 🌽 🥕 🧄 🧅 🥔 🍠 🥐 🥯 🍞 🥖 🧀 🥚 🍳 🥞 🧇 🥓 🥩 🍗 🍖 🌭 🍔 🍟 🍕 🥪 🌮 🌯 🥗 🥘 🍝 🍜 🍲 🍛 🍣 🍱 🥟 🍤 🍙 🍚 🍘 🍥 🥮 🍢 🍡 🍧 🍨 🍦 🥧 🧁 🍰 🎂 🍮 🍭 🍬 🍫 🍿 🍩 🍪 🥜 🍯 🥛 ☕ 🍵 🧃 🥤 🧋 🍶 🍺 🍻 🥂 🍷 🥃 🍸 🍹 🧉 🍾 🧊'],
    ['⚽', 'Actividades', '⚽ 🏀 🏈 ⚾ 🥎 🎾 🏐 🏉 🥏 🎱 🏓 🏸 🏒 🏑 🥍 🏏 🥅 ⛳ 🏹 🎣 🥊 🥋 🎽 🛹 🛼 ⛸️ 🥌 🎿 ⛷️ 🏂 🏋️ 🤼 🤸 ⛹️ 🤺 🤾 🏌️ 🏇 🧘 🏄 🏊 🤽 🚣 🧗 🚵 🚴 🏆 🥇 🥈 🥉 🏅 🎖️ 🎗️ 🎫 🎟️ 🎪 🤹 🎭 🎨 🎬 🎤 🎧 🎼 🎹 🥁 🎷 🎺 🎸 🪕 🎻 🎲 ♟️ 🎯 🎳 🎮 🎰 🧩'],
    ['🚗', 'Viajes', '🚗 🚕 🚙 🚌 🚎 🏎️ 🚓 🚑 🚒 🚐 🛻 🚚 🚛 🚜 🛵 🏍️ 🛺 🚲 🛴 🚨 🚔 🚍 🚘 🚖 🚡 🚠 🚟 🚃 🚋 🚞 🚝 🚄 🚅 🚈 🚂 🚆 🚇 🚊 🚉 ✈️ 🛫 🛬 🛩️ 💺 🚀 🛸 🚁 🛶 ⛵ 🚤 🛥️ 🛳️ ⛴️ 🚢 ⚓ ⛽ 🚧 🚦 🚥 🗺️ 🗿 🗽 🗼 🏰 🏯 🏟️ 🎡 🎢 🎠 ⛲ ⛱️ 🏖️ 🏝️ 🏜️ 🌋 ⛰️ 🏔️ 🗻 🏕️ ⛺ 🏠 🏡 🏘️ 🏚️ 🏗️ 🏭 🏢 🏬 🏣 🏤 🏥 🏦 🏨 🏪 🏫 🏩 💒 🏛️ ⛪ 🕌 🕍 🛕 🌅 🌄 🌠 🎇 🎆 🌇 🌆 🏙️ 🌃 🌌 🌉 🌁'],
    ['💡', 'Objetos', '⌚ 📱 💻 ⌨️ 🖥️ 🖨️ 🖱️ 🕹️ 💽 💾 💿 📀 📷 📸 📹 🎥 📞 ☎️ 📺 📻 🎙️ ⏰ ⌛ ⏳ 📡 🔋 🔌 💡 🔦 🕯️ 🧯 💸 💵 💴 💶 💷 🪙 💰 💳 💎 ⚖️ 🧰 🔧 🔨 🛠️ ⛏️ 🔩 ⚙️ 🧱 ⛓️ 🧲 🔫 💣 🧨 🪓 🔪 🗡️ 🛡️ 🚬 ⚰️ 🏺 🔮 📿 🧿 💈 ⚗️ 🔭 🔬 💊 💉 🩸 🧬 🦠 🧫 🧪 🌡️ 🧹 🧺 🧻 🚽 🚰 🚿 🛁 🧼 🪥 🪒 🧽 🧴 🛎️ 🔑 🗝️ 🚪 🪑 🛋️ 🛏️ 🧸 🖼️ 🛍️ 🛒 🎁 🎈 🎏 🎀 🪄 🪅 🎊 🎉 🎎 🏮 🎐 🧧 ✉️ 📩 📨 📧 💌 📥 📤 📦 🏷️ 📪 📫 📬 📭 📮 📯 📜 📃 📄 📑 🧾 📊 📈 📉 🗒️ 🗓️ 📆 📅 🗑️ 📇 🗃️ 🗳️ 🗄️ 📋 📁 📂 🗂️ 🗞️ 📰 📓 📔 📒 📕 📗 📘 📙 📚 📖 🔖 🧷 🔗 📎 🖇️ 📐 📏 🧮 📌 📍 ✂️ 🖊️ 🖋️ ✒️ 🖌️ 🖍️ 📝 ✏️ 🔍 🔎 🔏 🔐 🔒 🔓'],
    ['✅', 'Símbolos', '✅ ☑️ ✔️ ❌ ❎ ➕ ➖ ➗ ✖️ ♾️ ‼️ ⁉️ ❓ ❔ ❕ ❗ 〰️ 💲 ⚠️ 🚸 ⛔ 🚫 🚳 🚭 🚯 🚱 🚷 📵 🔞 ☢️ ☣️ ⬆️ ↗️ ➡️ ↘️ ⬇️ ↙️ ⬅️ ↖️ ↕️ ↔️ ↩️ ↪️ ⤴️ ⤵️ 🔃 🔄 🔙 🔚 🔛 🔜 🔝 🛐 ⚛️ ☮️ ✝️ ☯️ ☪️ 🔯 🕎 ♈ ♉ ♊ ♋ ♌ ♍ ♎ ♏ ♐ ♑ ♒ ♓ ⛎ 🔀 🔁 🔂 ▶️ ⏩ ⏭️ ⏯️ ◀️ ⏪ ⏮️ 🔼 ⏫ 🔽 ⏬ ⏸️ ⏹️ ⏺️ 🎦 🔅 🔆 📶 📳 📴 ♻️ 🔱 📛 🔰 ⭕ 🆗 🆕 🆓 🆙 🆒 🆘 🔴 🟠 🟡 🟢 🔵 🟣 🟤 ⚫ ⚪ 🟥 🟧 🟨 🟩 🟦 🟪 🟫 ⬛ ⬜ 🔶 🔷 🔸 🔹 🔺 🔻 💠 🔘 🔳 🔲 🏁 🚩 🎌 🏴 🏳️ 🏳️‍🌈 🇨🇴']
];
var emo = { modo: 'e', cat: 0, sts: null };
function emoRecientes() { try { return JSON.parse(localStorage.getItem('lpchat_emo') || '[]'); } catch (e) { return []; } }
function emoUsar(x) { try { var r = emoRecientes().filter(function (y) { return y !== x; }); r.unshift(x); localStorage.setItem('lpchat_emo', JSON.stringify(r.slice(0, 32))); } catch (e) {} }
function soloEmoji(t) {
    t = String(t || '').trim();
    if (!t || t.length > 24) return false;
    try { return /^(?:\p{Extended_Pictographic}|\p{Emoji_Component}|️|‍|\s)+$/u.test(t) && !/^[\d#*\s]+$/.test(t); } catch (e) { return false; }
}
function emoAbrir(si) {
    var abrir = si === undefined ? $('emo').hidden : si;
    $('emo').hidden = !abrir; $('b-emo').classList.toggle('on', abrir);
    if (abrir) emoPintar();
}
function emoPintar() {
    document.querySelectorAll('[data-emo-modo]').forEach(function (b) { b.classList.toggle('on', b.dataset.emoModo === emo.modo); });
    var t = $('emo-t'), b = $('emo-b');
    if (emo.modo === 'e') {
        t.hidden = false;
        t.innerHTML = '<button type="button" data-ecat="-1" title="Recientes">🕘</button>' + EMOJIS.map(function (c, i) { return '<button type="button" data-ecat="' + i + '" title="' + c[1] + '">' + c[0] + '</button>'; }).join('');
        var rec = emoRecientes();
        b.innerHTML = (rec.length ? '<div class="ecat" id="ecat--1">Recientes</div><div class="eg">' + rec.map(function (x) { return '<button type="button" data-e="' + x + '">' + x + '</button>'; }).join('') + '</div>' : '') +
            EMOJIS.map(function (c, i) { return '<div class="ecat" id="ecat-' + i + '">' + c[1] + '</div><div class="eg">' + c[2].split(' ').map(function (x) { return '<button type="button" data-e="' + x + '">' + x + '</button>'; }).join('') + '</div>'; }).join('');
        return;
    }
    t.hidden = true;
    if (!emo.sts) { b.innerHTML = '<div class="vacio">Cargando…</div>'; return api(U + '/stickers').then(function (j) { emo.sts = j.stickers || []; if (emo.modo === 's' && !$('emo').hidden) emoPintar(); }); }
    b.innerHTML = '<div class="sg"><div class="s cr" data-st-crear><b>+</b>Crear</div>' + emo.sts.map(function (x) {
        return '<div class="s" data-sid="' + x.id + '" title="Toca para enviar · mantén para más" style="background-image:url(\'' + esc(x.url) + '\')">' + (x.fav ? '<span class="fv">⭐</span>' : '') + '</div>';
    }).join('') + '</div>' + (emo.sts.length ? '' : '<div class="vacio" style="font-size:.85rem">Crea tus stickers con cualquier foto. También puedes guardar los que te envíen tocándolos.</div>');
}
$('b-emo').onclick = function () { emoAbrir(); };
document.querySelectorAll('[data-emo-modo]').forEach(function (b) { b.onclick = function () { emo.modo = b.dataset.emoModo; emoPintar(); }; });
$('emo-t').addEventListener('click', function (e) {
    var c = e.target.closest('[data-ecat]'); if (!c) return;
    var d = $('ecat-' + c.dataset.ecat); if (d) $('emo-b').scrollTop = d.offsetTop - $('emo-b').offsetTop - 4;
    $('emo-t').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === c); });
});
var stPres = null;
$('emo-b').addEventListener('click', function (e) {
    var x = e.target.closest('[data-e]');
    if (x) {
        var t = $('txt'), v = x.dataset.e, a = t.selectionStart != null ? t.selectionStart : t.value.length, z = t.selectionEnd != null ? t.selectionEnd : a;
        t.value = t.value.slice(0, a) + v + t.value.slice(z);
        t.selectionStart = t.selectionEnd = a + v.length;
        emoUsar(v); autoAlto(); actualizarBotones();
        if (window.innerWidth > 760) t.focus();
        return;
    }
    if (e.target.closest('[data-st-crear]')) return $('f-st').click();
    var s = e.target.closest('[data-sid]');
    if (s && !stPres) enviarSticker(s.dataset.sid);
    stPres = null;
});
/* Mantener presionado (o clic derecho) un sticker: favorito o borrar */
function stMenu(id) {
    var x = (emo.sts || []).filter(function (y) { return String(y.id) === String(id); })[0]; if (!x) return;
    ventana('🧩 Sticker', '<div style="text-align:center"><img src="' + esc(x.url) + '" style="width:150px;height:150px;object-fit:contain"></div>', '<button class="bt sec" id="st-fav">' + (x.fav ? 'Quitar de favoritos' : '⭐ Favorito') + '</button><button class="bt rojo" id="st-del">Borrar</button>');
    $('st-fav').onclick = function () { post(U + '/stickers/' + id + '/favorito').then(function () { cerrarVentana(); emo.sts = null; emoPintar(); }); };
    $('st-del').onclick = function () { post(U + '/stickers/' + id + '/borrar').then(function () { cerrarVentana(); emo.sts = null; emoPintar(); toast('Sticker borrado.'); }); };
}
$('emo-b').addEventListener('contextmenu', function (e) { var s = e.target.closest('[data-sid]'); if (s) { e.preventDefault(); stMenu(s.dataset.sid); } });
var stTimer;
$('emo-b').addEventListener('touchstart', function (e) { var s = e.target.closest('[data-sid]'); if (!s) return; stTimer = setTimeout(function () { stPres = 1; stMenu(s.dataset.sid); }, 550); }, { passive: true });
['touchend', 'touchmove'].forEach(function (ev) { $('emo-b').addEventListener(ev, function () { clearTimeout(stTimer); }, { passive: true }); });
function enviarSticker(id) {
    if (!st.abierto) return;
    var fd = new FormData(); fd.append('sticker_id', id);
    api(U + '/chat/' + st.abierto + '/enviar', { method: 'POST', body: fd }).then(function (j) {
        if (!j.ok) return toast(j.mensaje || 'No se envió.');
        var x = (emo.sts || []).filter(function (y) { return String(y.id) === String(id); })[0];
        if (x && !x.fav) { emo.sts.splice(emo.sts.indexOf(x), 1); var k = emo.sts.filter(function (y) { return y.fav; }).length; emo.sts.splice(k, 0, x); }
        traer(false); cargarChats();
    }).catch(function () { toast('Sin conexión. No se envió.'); });
}
/* Crear sticker: la foto se ajusta a un cuadro de 512 px con fondo transparente (webp; si el celular no puede, png). */
$('f-st').onchange = function () {
    var f = this.files[0]; this.value = '';
    if (!f) return;
    if (f.size > 15 * 1024 * 1024) return toast('La foto pesa mucho.');
    var img = new Image(), u = URL.createObjectURL(f);
    img.onload = function () {
        var n = Math.min(img.width, img.height);
        ventana('🧩 Crear sticker', '<div style="display:flex;gap:8px;justify-content:center;margin-bottom:10px"><button class="chip on" data-cr="cuadro">Recortar cuadrado</button><button class="chip" data-cr="todo">Foto completa</button></div><canvas id="st-cv" width="512" height="512" style="width:200px;height:200px;display:block;margin:0 auto;background:repeating-conic-gradient(#2a332e 0 25%,#1d2320 0 50%) 0 0/20px 20px;border-radius:12px"></canvas>', '<button class="bt" id="st-ok">Guardar sticker</button>');
        var cv = $('st-cv'), cx = cv.getContext('2d'), modo = 'cuadro';
        function dibujar() {
            cx.clearRect(0, 0, 512, 512);
            if (modo === 'cuadro') cx.drawImage(img, (img.width - n) / 2, (img.height - n) / 2, n, n, 0, 0, 512, 512);
            else { var r = Math.min(512 / img.width, 512 / img.height), w = img.width * r, h = img.height * r; cx.drawImage(img, (512 - w) / 2, (512 - h) / 2, w, h); }
        }
        dibujar();
        document.querySelectorAll('[data-cr]').forEach(function (b) { b.onclick = function () { modo = b.dataset.cr; document.querySelectorAll('[data-cr]').forEach(function (x) { x.classList.toggle('on', x === b); }); dibujar(); }; });
        $('st-ok').onclick = function () {
            var boton = this; boton.disabled = true; boton.textContent = 'Guardando…';
            cv.toBlob(function (bl) {
                var listo = function (bb, ext) {
                    var fd = new FormData(); fd.append('imagen', bb, 'sticker.' + ext);
                    api(U + '/stickers', { method: 'POST', body: fd }).then(function (j) {
                        if (!j.ok) { boton.disabled = false; boton.textContent = 'Guardar sticker'; return toast(j.mensaje || 'No se pudo guardar.'); }
                        cerrarVentana(); toast('Sticker creado ✨'); emo.sts = null; emo.modo = 's'; emoAbrir(true);
                    }).catch(function () { boton.disabled = false; boton.textContent = 'Guardar sticker'; toast('Sin conexión.'); });
                };
                if (bl && bl.type === 'image/webp' && bl.size < 1000000) listo(bl, 'webp');
                else cv.toBlob(function (p) { listo(p, 'png'); }, 'image/png');
            }, 'image/webp', 0.85);
        };
        URL.revokeObjectURL(u);
    };
    img.onerror = function () { toast('No se pudo leer esa foto.'); };
    img.src = u;
};
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
    m.style.top = Math.min(r.bottom + 4, window.innerHeight - 40 * items.length - 20) + 'px'; if (r.left < 90) m.style.left = (r.right + 6) + 'px'; else m.style.right = Math.max(8, window.innerWidth - r.right) + 'px'; // 2026-11-25: desde la barra lateral abre hacia la derecha
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
    if (!YO.empresa) it.push(['⭐ Plan Pro', function () { planPro(); }]);
    if (SUPER) it.push(['Comprobantes del Plan Pro', function () { location.href = '/chat/pro/admin'; }]);
    if (EMPRESA) it.push(['Usar como empresa: ' + EMPRESA, function () { usarComo(true); }]);
    if (YO.empresa) it.push(['Volver a mi cuenta', function () { usarComo(false); }]);
    if ('Notification' in window && Notification.permission !== 'granted') it.push(['🔔 Activar avisos de mensajes', activarAvisos]);
    it.push(['Condiciones de uso', function () { window.open('/chat/terminos', '_blank'); }]);
    if (!yaInstalada()) it.push(['📲 Instalar app', instalarApp]);
    it.push(['Cerrar sesión', salirForm]);
    menu(this, it);
};
$('b-cmenu').onclick = function (e) {
    e.stopPropagation();
    var i = st.info; if (!i) return;
    var it = [['★ Mensajes destacados', verDestacados], ['📝 Nota del chat', verNota]];
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
    if (e.target.id === 'pro-otra') { planPro(true); return; }
    if (e.target.closest('[data-pro]')) { planPro(); return; }
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
// 2026-11-18 (Jhoset): "que me la instale de una desde el menú". En Android se usa el instalador del navegador
// (atrapado en _head). Si todavía no ha llegado, se espera hasta 3 s (el toque del menú sigue valiendo) y se abre solo.
var instalar = window.__lpInst;
function hayInstalador() { return instalar || window.__lpInst; }
document.addEventListener('lp-instalable', function () { instalar = window.__lpInst; if ($('b-instalar')) $('b-instalar').hidden = false; });
if (instalar && $('b-instalar')) $('b-instalar').hidden = false;
function yaInstalada() { return (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true; }
function abrirInstalador() {
    var ev = hayInstalador(); if (!ev) return false;
    ev.prompt();
    ev.userChoice.then(function (r) { if (r && r.outcome === 'accepted') toast('¡Listo! LP Chat quedó instalada 🎉 Búscala en tu pantalla de inicio.'); })
        .finally(function () { instalar = null; window.__lpInst = null; if ($('b-instalar')) $('b-instalar').hidden = true; });
    return true;
}
function instalarApp() {
    if (abrirInstalador()) return;
    var ua = navigator.userAgent || '';
    var ios = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var android = /android/i.test(ua);
    if (android) {
        // Esperar un momento a que el navegador avise que se puede instalar
        toast('Preparando el instalador…');
        var listo = false;
        var al = function () { if (listo) return; listo = true; abrirInstalador(); };
        document.addEventListener('lp-instalable', al, { once: true });
        setTimeout(function () { if (listo) return; listo = true; document.removeEventListener('lp-instalable', al); pasosInstalar(ios, android, ua); }, 3000);
        return;
    }
    pasosInstalar(ios, android, ua);
}
function pasosInstalar(ios, android, ua) {
    // Dentro de WhatsApp/Facebook/Instagram/TikTok (o un navegador que no es Chrome) no se puede instalar: se abre en Chrome
    var enApp = /FBAN|FBAV|Instagram|TikTok|musical_ly|WhatsApp|Line\/|; wv\)/i.test(ua);
    var noChrome = android && (!/Chrome\//.test(ua) || /SamsungBrowser|MiuiBrowser|OPR\/|EdgA|Firefox/i.test(ua));
    if (android && (enApp || noChrome)) {
        var intent = 'intent://' + location.host + '/chat#Intent;scheme=https;package=com.android.chrome;end';
        return ventana('📲 Instalar LP Chat', '<p style="margin-top:0">Este navegador no deja instalar apps. Ábrela en <b>Chrome</b> y desde ahí toca otra vez <b>📲 Instalar app</b> en el menú.</p>', '<a class="bt" href="' + intent + '">Abrir en Chrome</a>');
    }
    var pasos = ios
        ? ['Toca el botón <b>Compartir</b> ⬆️ (abajo en Safari; arriba en Chrome)', 'Baja y toca <b>Agregar a inicio</b> ➕', 'Toca <b>Agregar</b>. LP Chat queda en tu pantalla de inicio como una app']
        : ['Toca el menú <b>⋮</b> del navegador (arriba a la derecha)', 'Toca <b>Instalar aplicación</b> o <b>Agregar a pantalla principal</b>', 'Confirma con <b>Instalar</b>. LP Chat queda con su ícono como una app'];
    ventana('📲 Instalar LP Chat', '<ol style="margin:0;padding-left:20px;line-height:1.6">' + pasos.map(function (x) { return '<li style="margin-bottom:8px">' + x + '</li>'; }).join('') + '</ol><p style="color:var(--txt2);font-size:.88rem;margin-bottom:0">' + (android ? 'Si en el menú ⋮ no sale "Instalar aplicación" sino "Abrir LocalPro", es porque en este celular ya está instalada la app de LocalProo y Chrome abre LP Chat dentro de ella. Si la borraste o le diste "Cancelar" al aviso, Chrome no vuelve a ofrecer el botón por un tiempo: usa estos pasos.' : 'Ábrela desde el ícono y funciona a pantalla completa.') + '</p>', '<button class="bt" data-cerrar>Entendido</button>');
}
$('b-instalar').onclick = instalarApp;
if (/iphone|ipad/i.test(navigator.userAgent) && !navigator.standalone) {
    try { if (!localStorage.getItem('lpchat_ios')) { setTimeout(function () { toast('Para instalar LP Chat: toca Compartir ⬆️ y luego "Agregar a inicio".'); }, 2500); localStorage.setItem('lpchat_ios', 1); } } catch (e) {}
}
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(function () {});

/* ===== 2026-11-18: avisos al celular aunque LP Chat esté cerrado (Web Push) ===== */
function b64aBytes(b) { var p = '='.repeat((4 - b.length % 4) % 4), r = atob((b + p).replace(/-/g, '+').replace(/_/g, '/')), o = new Uint8Array(r.length); for (var i = 0; i < r.length; i++) o[i] = r.charCodeAt(i); return o; }
function suscribirAvisos() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window) || Notification.permission !== 'granted') return Promise.resolve(false);
    return navigator.serviceWorker.register('/sw.js').then(function () { return navigator.serviceWorker.ready; }).then(function (reg) {
        return reg.pushManager.getSubscription().then(function (sub) {
            if (sub) return sub;
            return api(U + '/push/clave').then(function (j) { return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64aBytes(j.clave) }); });
        });
    }).then(function (sub) { return post(U + '/push', { endpoint: sub.endpoint }); }).then(function () { return true; }).catch(function () { return false; });
}
function activarAvisos() {
    if (!('Notification' in window)) { toast('Este navegador no deja mandar avisos. En iPhone, instala LP Chat (Compartir → Agregar a inicio) y ábrela desde el ícono.'); return; }
    if (Notification.permission === 'denied') { toast('Los avisos están bloqueados: actívalos en los ajustes del navegador para localproo.com.'); return; }
    Notification.requestPermission().then(function (r) {
        var b = document.getElementById('aviso-push'); if (b) b.remove();
        if (r === 'granted') suscribirAvisos().then(function (ok) { toast(ok ? '🔔 Listo: te avisamos cuando te escriban.' : 'No se pudieron activar los avisos en este equipo.'); });
    });
}
if ('Notification' in window && Notification.permission === 'granted') suscribirAvisos();
else if ('Notification' in window && 'PushManager' in window && Notification.permission === 'default') {
    var bp = document.createElement('div'); bp.id = 'aviso-push';
    /* left+right fijos (no left:50% + translate): así el aviso usa todo el ancho y el texto no se parte palabra por palabra */
    bp.style.cssText = 'position:fixed;left:12px;right:12px;margin:0 auto;max-width:460px;box-sizing:border-box;bottom:84px;z-index:60;background:#0b0d0c;color:#fff;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:10px 12px;display:flex;gap:10px;align-items:center;box-shadow:0 8px 24px rgba(0,0,0,.35);font-size:.9rem;line-height:1.3';
    bp.innerHTML = '<span style="flex:1;min-width:0">🔔 Activa los avisos para saber cuando te escriban</span><button type="button" style="flex:none;white-space:nowrap;background:#a6f34a;color:#0b0d0c;border:0;border-radius:10px;padding:8px 14px;font-weight:800">Activar</button><button type="button" aria-label="Cerrar" style="flex:none;background:none;border:0;color:#fff;font-size:1.1rem;padding:4px">✕</button>';
    bp.children[1].onclick = activarAvisos; bp.children[2].onclick = function () { bp.remove(); };
    document.body.appendChild(bp);
}

/* ================= Arranque ================= */
var pChats = cargarChats();
if (pChats && pChats.then) pChats.then(quitarSplash, quitarSplash); else quitarSplash();
var abrir = {{ (int) $abrir }};
if (abrir) { history.replaceState({ chat: abrir }, '', urlChat(abrir)); abrirChat(abrir); }
setInterval(function () { if (!document.hidden) cargarChats(); }, 6000);
setInterval(function () { if (st.abierto && !document.hidden) traer(false); }, 2500);
document.addEventListener('visibilitychange', function () { if (!document.hidden) { cargarChats(); traer(false); } });
/* 2026-11-25 (Jhoset): barra lateral de computador (igual a WhatsApp Web). Usa las mismas funciones de abajo. */
(function () {
    var rail = $('rail'); if (!rail) return;
    function marcar() {
        var r = st.nav !== 'mensajes' ? st.nav : (st.tab === 'conocer' || st.tab === 'empresas' ? st.tab : 'chats');
        rail.querySelectorAll('button[data-r]').forEach(function (b) { b.classList.toggle('on', b.dataset.r === r); });
        var a = $('pt-msj'), e = $('pt-est');
        if (a) { $('rb-msj').hidden = a.hidden; $('rb-msj').textContent = a.textContent; }
        if (e) $('rb-est').hidden = e.hidden;
    }
    var nav0 = irNav, tab0 = irTab;
    irNav = function (n) { nav0(n); marcar(); };
    irTab = function (t) { tab0(t); marcar(); };
    rail.querySelectorAll('button[data-r]').forEach(function (b) {
        b.onclick = function (ev) {
            var r = b.dataset.r;
            if (r === 'chats') irTab(st.tab === 'conocer' || st.tab === 'empresas' ? 'mis' : st.tab);
            else if (r === 'estados' || r === 'tienda') irNav(r);
            else if (r === 'conocer' || r === 'empresas') irTab(r);
            else if (r === 'ajustes') { ev.stopPropagation(); $('b-menu').onclick.call(b, ev); }
            else if (r === 'perfil') { if (YO.empresa) { ev.stopPropagation(); $('b-menu').onclick.call(b, ev); } else miPerfil(); }
        };
    });
    var av = $('rail-av');
    if (YO.foto) av.style.backgroundImage = "url('" + String(YO.foto).replace(/'/g, '%27') + "')"; else av.textContent = (YO.nombre || '?').trim().charAt(0).toUpperCase();
    $('b-nuevo').onclick = function () { nuevoChat(); };
    marcar(); setInterval(marcar, 2000);
})();
})();
</script>
</body>
</html>
