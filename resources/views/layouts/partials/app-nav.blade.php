{{-- App instalada (sin barra del navegador): botón para volver atrás y para compartir la página, sutiles arriba en las esquinas --}}
<style>
.lp-nav-app{display:none}
@media (display-mode: standalone){.lp-nav-app{display:grid}}
html.lp-standalone .lp-nav-app{display:grid}
.lp-nav-app{width:38px;height:38px;place-items:center;border-radius:50%;border:1px solid rgba(11,13,12,.08);background:rgba(255,255,255,.82);
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);color:#151a17;box-shadow:0 4px 14px -6px rgba(0,0,0,.25);cursor:pointer;padding:0;flex:0 0 auto}
.lp-nav-app svg{width:19px;height:19px}
.lp-nav-app:active{transform:scale(.92)}
.lp-nav-fijo{position:fixed;top:calc(env(safe-area-inset-top,0px) + 10px);z-index:9990}
.lp-nav-fijo.izq{left:10px}.lp-nav-fijo.der{right:10px}
.lp-nav-aviso{position:fixed;left:50%;top:calc(env(safe-area-inset-top,0px) + 56px);transform:translateX(-50%);z-index:9991;background:#0b0d0c;color:#fff;font-weight:700;font-size:.85rem;padding:.55rem .9rem;border-radius:999px;box-shadow:0 10px 24px -10px rgba(0,0,0,.5)}
</style>
<script>
(function () {
    var app = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
    if (!app) return;
    document.documentElement.classList.add('lp-standalone');
    var atras = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>';
    var comp = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="M8.3 10.8l7.4-4.4M8.3 13.2l7.4 4.4"/></svg>';
    function boton(html, titulo, fn) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'lp-nav-app'; b.innerHTML = html; b.setAttribute('aria-label', titulo); b.title = titulo;
        b.addEventListener('click', fn);
        return b;
    }
    function aviso(t) {
        var d = document.createElement('div'); d.className = 'lp-nav-aviso'; d.textContent = t; document.body.appendChild(d);
        setTimeout(function () { d.remove(); }, 2200);
    }
    function volver() {
        // Si vino de otra página de LocalPro, vuelve a esa; si no, al inicio
        var deAqui = document.referrer && document.referrer.indexOf(location.origin) === 0;
        if (history.length > 1 && deAqui) history.back(); else location.href = '/';
    }
    function compartir() {
        var datos = { title: document.title, url: location.href.replace(/[?&]app=1\b/, '') };
        if (navigator.share) { navigator.share(datos).catch(function () {}); return; }
        if (navigator.clipboard) navigator.clipboard.writeText(datos.url).then(function () { aviso('Enlace copiado ✓'); }, function () {});
    }
    function poner() {
        var bA = location.pathname !== '/' ? boton(atras, 'Volver', volver) : null;
        var bC = boton(comp, 'Compartir', compartir);
        var barra = document.querySelector('header.nav .wrap');
        if (barra) {
            // Página con barra de arriba: "volver" va al lado del logo y "compartir" al final, sin tapar nada
            var grupo = function (el, antes) {
                var g = document.createElement('div');
                g.style.cssText = 'display:flex;align-items:center;gap:8px;min-width:0';
                barra.insertBefore(g, el); g.appendChild(el);
                if (antes) g.insertBefore(antes, el);
                return g;
            };
            var primero = barra.firstElementChild, ultimo = barra.lastElementChild;
            if (bA && primero) grupo(primero, bA);
            if (ultimo && ultimo !== primero) grupo(ultimo).appendChild(bC); else barra.appendChild(bC);
        } else {
            if (bA) { bA.classList.add('lp-nav-fijo', 'izq'); document.body.appendChild(bA); }
            bC.classList.add('lp-nav-fijo', 'der'); document.body.appendChild(bC);
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', poner); else poner();
})();
</script>
