{{-- Sonido de aviso (sin archivos): Pd.sonar() --}}
<script>
window.Pd = window.Pd || {};
(function () {
    var ctx = null;
    function activar() { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); if (ctx.state === 'suspended') ctx.resume(); } catch (e) {} }
    document.addEventListener('click', activar, { once: false });
    Pd.sonar = function (veces) {
        activar(); if (!ctx) return;
        for (var k = 0; k < (veces || 2); k++) {
            [880, 1320].forEach(function (f, j) {
                var o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + k * 0.45 + j * 0.16;
                o.type = 'sine'; o.frequency.value = f; o.connect(g); g.connect(ctx.destination);
                g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.35, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.15);
                o.start(t); o.stop(t + 0.16);
            });
        }
        if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    };
    Pd.esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    Pd.cant = function (n) { n = Math.round((+n || 0) * 1000) / 1000; return String(n).replace('.', ','); };
})();
</script>
