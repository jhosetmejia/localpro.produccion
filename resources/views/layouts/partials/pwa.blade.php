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
    // App instalada: si el celular la cerró al cambiar de aplicación, vuelve a la última pantalla (no al inicio)
    try {
        var K = 'lp_ultima', ahora = Date.now();
        if (app && location.pathname === '/' && /(^|[?&])app=1/.test(location.search)) {
            var u = JSON.parse(localStorage.getItem(K) || 'null');
            if (u && u.url && ahora - u.t < 4 * 3600 * 1000 && u.url !== '/' && u.url.indexOf('/?') !== 0) { location.replace(u.url); return; }
        }
        if (!/^\/(login|logout|registro|recuperar|password)/.test(location.pathname)) {
            var guardar = function () { try { localStorage.setItem(K, JSON.stringify({ url: location.pathname + location.search + location.hash, t: Date.now() })); } catch (x) {} };
            guardar(); document.addEventListener('visibilitychange', function () { if (document.hidden) guardar(); });
        }
    } catch (x) {}
    // Enlaces con data-app-href ("Ver todas las tiendas", "Ver mi tienda"): se abren en la misma ventana.
    // Así, si está en el navegador se queda en el navegador (Chrome manda las pestañas nuevas a la app instalada)
    // y si está en la app se queda en la app. Con Ctrl/clic central sí se abre en otra pestaña.
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-app-href]');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;
        e.preventDefault();
        location.href = a.getAttribute('data-app-href');
    }, true);
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
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-title" content="LocalPro">
<link rel="icon" href="/favicon.svg?v=3" type="image/svg+xml">
<link rel="icon" href="/favicon-32.png?v=3" sizes="32x32" type="image/png">
<link rel="icon" href="/favicon-16.png?v=3" sizes="16x16" type="image/png">
<link rel="shortcut icon" href="/favicon.ico?v=3">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png?v=3">
<script>if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js?v=3').catch(function () {}); }); }</script>
<style>
/* Ya instalada como app: se esconden las opciones de "instalar" */
@media (display-mode: standalone){.lp-solo-navegador{display:none!important}}
#lpGuia{position:fixed;inset:0;z-index:100000;background:rgba(11,13,12,.55);display:flex;align-items:flex-end;justify-content:center;font-family:inherit}
#lpGuia .hoja{background:#fff;color:#151a17;width:100%;max-width:440px;border-radius:24px 24px 0 0;padding:22px 20px calc(18px + env(safe-area-inset-bottom));box-shadow:0 -20px 40px -20px rgba(0,0,0,.4);animation:lpSube .25s ease-out}
#lpGuia h4{font-weight:900;font-size:1.15rem;margin:0 0 4px;display:flex;align-items:center;gap:10px}
#lpGuia h4 img{width:40px;height:40px;border-radius:10px}
#lpGuia p{color:#6b7570;font-weight:600;margin:0 0 12px;font-size:.92rem}
#lpGuia ol{list-style:none;padding:0;margin:0 0 14px;counter-reset:p}
#lpGuia li{counter-increment:p;display:flex;gap:10px;align-items:center;padding:9px 0;border-top:1px solid #eef1ef;font-weight:700;font-size:.95rem}
#lpGuia li::before{content:counter(p);flex:0 0 28px;height:28px;border-radius:50%;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-weight:900;font-size:.85rem}
#lpGuia li svg{width:20px;height:20px;vertical-align:-4px;color:#007aff}
#lpGuia .ok{width:100%;border:0;border-radius:999px;padding:13px;font-weight:900;font-family:inherit;background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c}
@keyframes lpSube{from{transform:translateY(100%)}to{transform:none}}
</style>
<script>
(function () {
    var ua = navigator.userAgent || '';
    var iOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var instalada = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    var K = 'lp_guia_ios';
    var compartir = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>';
    var mas = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg>';

    function guia(pasos, titulo, nota) {
        if (document.getElementById('lpGuia')) return;
        var d = document.createElement('div');
        d.id = 'lpGuia';
        d.innerHTML = '<div class="hoja"><h4><img src="/icons/icon-192.png" alt="">' + titulo + '</h4><p>' + nota + '</p><ol>' + pasos.map(function (x) { return '<li><span>' + x + '</span></li>'; }).join('') + '</ol><button type="button" class="ok">Entendido</button></div>';
        var cerrar = function () { d.remove(); try { localStorage.setItem(K, String(Date.now())); } catch (x) {} };
        d.addEventListener('click', function (e) { if (e.target === d || e.target.classList.contains('ok')) cerrar(); });
        document.body.appendChild(d);
    }

    function guiaIOS() {
        guia([
            'Toque el botón <b>Compartir</b> ' + compartir + ' (abajo en Safari; arriba en Chrome)',
            'Baje y toque <b>Agregar a inicio</b> ' + mas,
            'Toque <b>Agregar</b>. LocalPro queda en su pantalla de inicio como una app'
        ], 'Instale LocalPro en su iPhone', 'Ábrala desde el ícono y funciona a pantalla completa, como una app.');
    }

    // Para el menú "Instalar la app en el celular": Android abre el instalador; iPhone muestra los pasos
    window.lpInstalarApp = function () {
        if (instalada) return;
        if (window.__lpInstalar) { var p = window.__lpInstalar; p.prompt(); p.userChoice.finally(function () { window.__lpInstalar = null; var b = document.getElementById('lpInstalar'); if (b) b.remove(); }); return; }
        if (iOS) return guiaIOS();
        guia([
            'Toque el menú <b>⋮</b> del navegador (arriba a la derecha)',
            'Toque <b>Instalar aplicación</b> o <b>Agregar a pantalla principal</b>',
            'Confirme con <b>Instalar</b>. LocalPro queda con su ícono como una app'
        ], 'Instale LocalPro en su celular', 'Funciona en Android con Chrome, Samsung Internet o Edge.');
    };

    // Android: botón "Instalar LocalPro" cuando el navegador lo permite
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.__lpInstalar = e;
        var mostrar = function () {
            if (document.getElementById('lpInstalar')) return;
            var b = document.createElement('button');
            b.id = 'lpInstalar'; b.type = 'button';
            b.innerHTML = '📲 Instalar LocalPro';
            b.style.cssText = 'position:fixed;right:14px;bottom:calc(14px + env(safe-area-inset-bottom));z-index:999;border:0;border-radius:999px;padding:12px 18px;font-weight:800;font-family:inherit;background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c;box-shadow:0 14px 30px -12px rgba(47,209,90,.9);cursor:pointer';
            b.onclick = window.lpInstalarApp;
            document.body.appendChild(b);
            setTimeout(function () { if (b.parentNode) b.remove(); }, 20000);
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mostrar); else mostrar();
    });
    window.addEventListener('appinstalled', function () { window.__lpInstalar = null; var b = document.getElementById('lpInstalar'); if (b) b.remove(); });

    // iPhone: Safari no tiene botón de instalar, así que se muestran los pasos (una vez por semana, solo con sesión iniciada)
    if (iOS && !instalada) {
        var visto = 0;
        try { visto = parseInt(localStorage.getItem(K) || '0', 10); } catch (x) {}
        var enPanel = !/^\/($|login|registro|recuperar|password|unete|catalogo|tienda|mi-separado|certificado)/.test(location.pathname);
        if (enPanel && Date.now() - visto > 7 * 86400 * 1000) {
            window.addEventListener('load', function () { setTimeout(guiaIOS, 2500); });
        }
    }
})();
</script>

@include('layouts.partials.ver-clave')
