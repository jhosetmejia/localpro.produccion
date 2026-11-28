{{-- 2026-11-28 (Jhoset): Tema (oscuro / claro / automático) y fondo del chat, como WhatsApp. Se guarda en el celular (Menú → Tema y fondo). --}}
<style>
body.tema-claro{color-scheme:light;--fondo:#eef1f0;--panel:#ffffff;--panel2:#ffffff;--panel3:#f0f2f5;--borde:#e3e7e5;--txt:#111b21;--txt2:#5f6f68;--burbuja-el:#ffffff;--wall:#efeae2;--sel:#e9f5ee;--hover:#f3f5f4;--chip:rgba(22,184,78,.12);--chipt:#0f7a35;--punto:rgba(0,0,0,.05)}
body.tema-claro .sis{color:#54656f;background:#fff7d6;border-color:#f1e3a6}
body.tema-claro .dia{background:#fff;color:#54656f;box-shadow:0 1px 1px rgba(0,0,0,.08)}
body.tema-claro .m.el{border-color:transparent;box-shadow:0 1px 1px rgba(0,0,0,.1)}
body.tema-claro .ib:hover,body.tema-claro .menc .op:hover,body.tema-claro .menc .op.sel{background:rgba(0,0,0,.06)}
body.tema-claro .menu{box-shadow:0 10px 30px rgba(0,0,0,.18)}
body.tema-claro .menu button:hover{background:rgba(0,0,0,.05)}
body.tema-claro .m .cita{background:rgba(0,0,0,.05)}
body.tema-claro .reacbar .mas{background:rgba(0,0,0,.06)}
body.tema-claro .emo-todo button:hover{background:rgba(0,0,0,.06)}
.msgs{background-image:radial-gradient(var(--punto,rgba(166,243,74,.07)) 1px,transparent 1.4px)}
.tema-sw{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:8px}
.tema-sw button{height:64px;border-radius:12px;border:2px solid var(--borde);cursor:pointer;position:relative;font-size:.72rem;color:#fff;display:flex;align-items:flex-end;justify-content:center;padding-bottom:4px;text-shadow:0 1px 2px rgba(0,0,0,.6)}
.tema-sw button.on{border-color:var(--verde2);box-shadow:0 0 0 2px var(--verde2)}
.tema-op{display:flex;gap:8px;flex-wrap:wrap}
.tema-op button{flex:1;min-width:90px;padding:10px;border-radius:12px;border:2px solid var(--borde);background:var(--panel3);color:var(--txt);cursor:pointer;font-weight:600}
.tema-op button.on{border-color:var(--verde2)}
</style>
<script>
/* Se aplica antes de pintar la app (sin parpadeo). */
var LP_FONDOS = {
    oscuro: [['', 'Predeterminado'], ['#0b141a', 'Noche'], ['#1d2b33', 'Azul'], ['#1e2a22', 'Verde'], ['#2b1d2a', 'Vino'], ['#262626', 'Gris'], ['#1f1b2e', 'Morado'], ['#000000', 'Negro']],
    claro: [['', 'Predeterminado'], ['#d9fdd3', 'Menta'], ['#dbeafe', 'Cielo'], ['#fde2e4', 'Rosa'], ['#fff1c1', 'Arena'], ['#ede9fe', 'Lila'], ['#e5e7eb', 'Gris'], ['#ffffff', 'Blanco']]
};
function lpTemaActual() { var t = 'oscuro'; try { t = localStorage.getItem('lp-tema') || 'oscuro'; } catch (e) {} if (t === 'auto') t = window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'claro' : 'oscuro'; return t; }
function lpAplicarTema() {
    var t = lpTemaActual(), f = ''; try { f = localStorage.getItem('lp-fondo-' + t) || ''; } catch (e) {}
    document.body.classList.toggle('tema-claro', t === 'claro');
    if (f) document.body.style.setProperty('--wall', f); else document.body.style.removeProperty('--wall');
    var mc = document.querySelector('meta[name=theme-color]'); if (mc) mc.setAttribute('content', t === 'claro' ? '#ffffff' : '#0b0d0c');
}
lpAplicarTema();
if (window.matchMedia) try { matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () { try { if (localStorage.getItem('lp-tema') === 'auto') lpAplicarTema(); } catch (e) {} }); } catch (e) {}
</script>
