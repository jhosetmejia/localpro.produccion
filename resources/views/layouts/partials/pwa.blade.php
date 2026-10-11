<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b0d0c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="LocalPro">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); }); }</script>
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
