<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Pedidos · {{ $tienda->nombre }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}html,body{margin:0;height:100%;background:#0b0d0c;color:#fff;font-family:Inter,system-ui,sans-serif;overflow:hidden}
.w{display:grid;grid-template-columns:1.4fr 1fr;height:100vh}
.col{padding:3vh 3vw;display:flex;flex-direction:column;gap:2vh;overflow:hidden}
.col h2{margin:0;font-size:4vh;font-weight:900;letter-spacing:-.02em}
.listos{background:linear-gradient(160deg,#16351f,#0b0d0c)}
.listos h2{color:#a6f34a}
.prep h2{color:#b8c4bc}
.ls{display:flex;flex-direction:column;gap:1.4vh}
.it{display:flex;align-items:center;gap:2vw;background:rgba(255,255,255,.06);border-radius:2vh;padding:1.6vh 2vw;font-weight:900}
.listos .it{font-size:6vh;background:rgba(166,243,74,.12);animation:entra .6s ease}
.listos .it.nuevo{animation:entra .6s ease,brilla 1.2s ease 3}
.prep .it{font-size:3.6vh;color:#dfe7e1}
.it span{font-size:.55em;color:#a6f34a;min-width:3.2em}
.prep .it span{color:#8a968e}
.pie{position:fixed;bottom:1.5vh;left:3vw;font-size:2vh;color:#6b7570;font-weight:700}
@keyframes entra{from{transform:translateY(20px);opacity:0}}
@keyframes brilla{50%{background:rgba(166,243,74,.4)}}
.vacio{color:#6b7570;font-size:3vh;font-weight:800}
</style>
</head>
<body>
<div class="w">
    <div class="col listos"><h2>✓ Su pedido está listo</h2><div class="ls" id="L"></div></div>
    <div class="col prep"><h2>Preparando…</h2><div class="ls" id="P"></div></div>
</div>
<div class="pie">{{ $tienda->nombre }} · con LocalProo</div>
<script>
(function () {
    var antes = null, esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var ctx = null; document.addEventListener('click', function () { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {} var d = document.documentElement; if (!document.fullscreenElement && d.requestFullscreen) d.requestFullscreen().catch(function () {}); });
    function sonar() { if (!ctx) return; [660, 990].forEach(function (f, j) { var o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + j * .2; o.frequency.value = f; o.connect(g); g.connect(ctx.destination); g.gain.setValueAtTime(.0001, t); g.gain.exponentialRampToValueAtTime(.4, t + .02); g.gain.exponentialRampToValueAtTime(.0001, t + .3); o.start(t); o.stop(t + .32); }); }
    function cargar() {
        fetch(@json(route('pedidos.clientes.datos', $token)), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            var ids = d.listos.map(function (x) { return x.n; }), nuevos = antes ? ids.filter(function (n) { return antes.indexOf(n) < 0; }) : [];
            document.getElementById('L').innerHTML = d.listos.length ? d.listos.map(function (x) { return '<div class="it' + (nuevos.indexOf(x.n) >= 0 ? ' nuevo' : '') + '"><span>#' + x.n + '</span>' + esc(x.t) + '</div>'; }).join('') : '<div class="vacio">—</div>';
            document.getElementById('P').innerHTML = d.preparando.length ? d.preparando.map(function (x) { return '<div class="it"><span>#' + x.n + '</span>' + esc(x.t) + '</div>'; }).join('') : '<div class="vacio">—</div>';
            if (nuevos.length) sonar();
            antes = ids;
        }).catch(function () {});
    }
    cargar(); setInterval(cargar, 5000);
    if (navigator.wakeLock) navigator.wakeLock.request('screen').catch(function () {});
    setTimeout(function () { location.reload(); }, 6 * 3600 * 1000);
})();
</script>
</body>
</html>
