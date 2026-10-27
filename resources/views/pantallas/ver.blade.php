<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $pantalla->nombre }} · {{ $tienda->nombre }}</title>
    <style>
        html,body{margin:0;height:100%;background:#000;overflow:hidden;cursor:none}
        #escena{position:fixed;inset:0}
        #escena > *{position:absolute;inset:0;width:100%;height:100%;object-fit:{{ $pantalla->ajuste === 'llenar' ? 'cover' : 'contain' }};opacity:0;transition:opacity .8s ease;background:#000}
        #escena > .ver{opacity:1}
        .vacio{position:fixed;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2vh;color:#fff;font-family:system-ui,Segoe UI,Roboto,sans-serif;text-align:center;padding:4vh;background:radial-gradient(circle at 50% 35%,#1d2a22,#07100b 70%)}
        .vacio img{max-width:40vw;max-height:30vh;object-fit:contain}
        .vacio h1{margin:0;font-size:clamp(24px,5vh,56px)}
        .vacio p{margin:0;opacity:.75;font-size:clamp(14px,2.6vh,26px)}
        #pc{position:fixed;right:2vh;bottom:2vh;color:#fff;background:rgba(0,0,0,.55);font:600 14px system-ui,sans-serif;border-radius:10px;padding:.5em .8em;opacity:0;transition:opacity .4s}
        body.mouse{cursor:default}body.mouse #pc{opacity:1}
    </style>
</head>
<body>
@if ($items->isEmpty())
<div class="vacio">
    @if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="">@endif
    <h1>{{ $pantalla->activa ? $tienda->nombre : 'Pantalla apagada' }}</h1>
    <p>{{ $pantalla->activa ? 'Agregue imágenes o videos desde LocalProo → Pantallas → ' . $pantalla->nombre . '. Salen aquí solos.' : 'Enciéndala desde LocalProo → Pantallas.' }}</p>
</div>
@endif
<div id="escena"></div>
<div id="pc">Toque para pantalla completa</div>
<script>
(function () {
    var ITEMS = @json($items), VERSION = @json($version), HUELLA = @json($huella);
    var escena = document.getElementById('escena'), i = -1, timer = null, cambio = false;

    function crear(it) {
        var el;
        if (it.tipo === 'video') {
            el = document.createElement('video');
            el.muted = true; el.setAttribute('muted', ''); el.playsInline = true; el.preload = 'auto';
            if (ITEMS.length === 1) el.loop = true;
        } else {
            el = document.createElement('img'); el.alt = '';
        }
        el.src = it.url;
        return el;
    }
    function siguiente() {
        clearTimeout(timer);
        if (cambio) return location.reload();
        if (!ITEMS.length) return;
        i = (i + 1) % ITEMS.length;
        var it = ITEMS[i], el = crear(it), viejos = Array.prototype.slice.call(escena.children);
        escena.appendChild(el);
        function mostrar() {
            el.classList.add('ver');
            setTimeout(function () { viejos.forEach(function (v) { if (v.pause) v.pause(); v.remove(); }); }, 900);
        }
        if (it.tipo === 'video') {
            el.addEventListener('ended', siguiente);
            el.addEventListener('error', function () { timer = setTimeout(siguiente, 1000); });
            el.addEventListener('playing', mostrar, { once: true });
            var p = el.play(); if (p && p.catch) p.catch(function () { timer = setTimeout(siguiente, 3000); });
            // Por si el video se queda pegado: máximo 30 minutos
            if (ITEMS.length > 1) timer = setTimeout(siguiente, 30 * 60 * 1000);
        } else {
            if (el.complete) mostrar(); else { el.onload = mostrar; el.onerror = function () { timer = setTimeout(siguiente, 1000); }; }
            if (ITEMS.length > 1) timer = setTimeout(siguiente, it.seg * 1000);
            // Deja lista la siguiente imagen
            var sig = ITEMS[(i + 1) % ITEMS.length]; if (sig.tipo === 'imagen') { var pre = new Image(); pre.src = sig.url; }
        }
    }
    siguiente();

    // ¿Cambió algo en LocalProo? Se recarga al terminar lo que está mostrando (o ya si no hay nada o es uno solo)
    setInterval(function () {
        fetch(VERSION, { cache: 'no-store' }).then(function (r) { return r.ok ? r.json() : null; }).then(function (j) {
            if (j && j.v && j.v !== HUELLA) { cambio = true; if (ITEMS.length <= 1) location.reload(); }
        }).catch(function () {});
    }, 30000);
    // Se refresca cada 6 horas para que el televisor no se llene de memoria
    setTimeout(function () { cambio = true; if (ITEMS.length <= 1) location.reload(); }, 6 * 3600 * 1000);

    // Pantalla completa con un toque; que el TV no se apague
    document.addEventListener('click', function () {
        var d = document.documentElement;
        if (!document.fullscreenElement && d.requestFullscreen) d.requestFullscreen().catch(function () {});
    });
    var tm; document.addEventListener('mousemove', function () { document.body.classList.add('mouse'); clearTimeout(tm); tm = setTimeout(function () { document.body.classList.remove('mouse'); }, 2500); });
    function despierto() { if (navigator.wakeLock) navigator.wakeLock.request('screen').catch(function () {}); }
    despierto(); document.addEventListener('visibilitychange', function () { if (!document.hidden) despierto(); });
})();
</script>
</body>
</html>
