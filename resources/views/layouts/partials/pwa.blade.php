<style>
/* Pantalla de carga animada (app instalada y cambios de página) */
html.lp-navegando::after{content:"";position:fixed;left:0;top:0;height:3px;width:40%;z-index:99999;background:linear-gradient(90deg,#a6f34a,#16b34a);animation:lpBarra 1s ease-in-out infinite;border-radius:0 3px 3px 0}
html.lp-cargando::before{content:"";position:fixed;inset:0;z-index:99998;background:#0b0d0c}
html.lp-cargando::after{content:"";position:fixed;left:50%;top:50%;width:64px;height:64px;margin:-32px 0 0 -32px;z-index:99999;border-radius:18px;
  background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);animation:lpGira 1.1s cubic-bezier(.6,.05,.3,.95) infinite;box-shadow:0 0 0 0 rgba(166,243,74,.6)}
@keyframes lpGira{0%{transform:rotate(45deg) scale(.7);border-radius:14px;box-shadow:0 0 0 0 rgba(166,243,74,.55)}50%{transform:rotate(225deg) scale(1);border-radius:30px;box-shadow:0 0 0 22px rgba(166,243,74,0)}100%{transform:rotate(405deg) scale(.7);border-radius:14px;box-shadow:0 0 0 0 rgba(166,243,74,0)}}
@keyframes lpBarra{0%{transform:translateX(-100%)}100%{transform:translateX(260%)}}
</style>
<script>
(function () {
    var h = document.documentElement;
    var app = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    // En la app instalada: logo animado hasta que la página termine de cargar
    if (app) h.classList.add('lp-cargando');
    var listo = function () { h.classList.remove('lp-cargando', 'lp-navegando'); };
    document.addEventListener('DOMContentLoaded', function () { setTimeout(listo, 150); });
    window.addEventListener('pageshow', listo);
    setTimeout(listo, 12000);
    // Al cambiar de página: barra animada arriba (y el logo si tarda)
    var cargar = function () {
        h.classList.add('lp-navegando');
        clearTimeout(window.__lpT);
        if (app) window.__lpT = setTimeout(function () { if (h.classList.contains('lp-navegando')) h.classList.add('lp-cargando'); }, 900);
        clearTimeout(window.__lpF); window.__lpF = setTimeout(listo, 7000);
    };
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || a.target || a.hasAttribute('download')) return;
        var url = a.getAttribute('href') || '';
        if (url.charAt(0) === '#' || /^(javascript:|mailto:|tel:|https?:\/\/wa\.me)/i.test(url) || /exportar|imprimir/i.test(url)) return;
        if (a.origin && a.origin !== location.origin) return;
        cargar();
    });
    document.addEventListener('submit', function (e) {
        setTimeout(function () { if (!e.defaultPrevented && !(e.target && e.target.target)) cargar(); }, 0);
    });
})();
</script>
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b0d0c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="LocalPro">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js?v=2').catch(function () {}); }); }</script>
<script>
// Botón "Instalar app" cuando el navegador lo permite
window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    var aviso = window.__lpInstalar = e;
    document.addEventListener('DOMContentLoaded', mostrar); if (document.readyState !== 'loading') mostrar();
    function mostrar() {
        if (document.getElementById('lpInstalar')) return;
        var b = document.createElement('button');
        b.id = 'lpInstalar'; b.type = 'button';
        b.innerHTML = '📲 Instalar LocalPro';
        b.style.cssText = 'position:fixed;right:14px;bottom:14px;z-index:999;border:0;border-radius:999px;padding:12px 18px;font-weight:800;font-family:inherit;background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c;box-shadow:0 14px 30px -12px rgba(47,209,90,.9);cursor:pointer';
        b.onclick = function () { aviso.prompt(); aviso.userChoice.finally(function () { b.remove(); }); };
        document.body.appendChild(b);
        setTimeout(function () { if (b.parentNode) b.remove(); }, 20000);
    }
});
</script>
