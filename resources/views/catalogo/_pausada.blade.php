{{-- Tienda con el plan vencido: se sigue viendo, pero sus botones de WhatsApp muestran "en mantenimiento".
     Los botones para compartir (wa.me/?text=…, sin número) siguen funcionando. Recibe $tienda. --}}
@if ($tienda->plan_vencido)
<style>
.lp-pausa{position:fixed;inset:0;z-index:9999;background:rgba(11,13,12,.55);display:none;align-items:center;justify-content:center;padding:18px;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
.lp-pausa.on{display:flex}
.lp-pausa .c{background:#fff;color:#151a17;border-radius:24px;max-width:400px;width:100%;padding:24px 22px 20px;text-align:center;box-shadow:0 30px 80px -30px rgba(0,0,0,.6)}
.lp-pausa .i{width:64px;height:64px;border-radius:20px;background:#fff5d6;display:grid;place-items:center;font-size:2rem;margin:0 auto 12px}
.lp-pausa h3{margin:0 0 8px;font-size:1.25rem;font-weight:800;line-height:1.2}
.lp-pausa p{margin:0 0 16px;color:#5b665e;line-height:1.5;font-size:.95rem}
.lp-pausa button,.lp-pausa a{display:block;width:100%;border:0;border-radius:14px;padding:.85rem;font:800 1rem inherit;font-family:inherit;cursor:pointer;text-decoration:none}
.lp-pausa button{background:#0b0d0c;color:#fff}
.lp-pausa a{color:#11702f;margin-top:8px;font-size:.92rem}
</style>
<div class="lp-pausa" id="lpPausa" role="dialog" aria-modal="true" aria-labelledby="lpPausaT">
    <div class="c">
        <div class="i" aria-hidden="true">🛠️</div>
        <h3 id="lpPausaT">Esta tienda está en mantenimiento</h3>
        <p>Por el momento no recibe mensajes de WhatsApp. Puede seguir viendo sus productos; vuelva a intentarlo más tarde.</p>
        <button type="button" id="lpPausaOk">Entendido</button>
        <a href="{{ route('marketplace') }}">Ver otras tiendas en LocalProo</a>
    </div>
</div>
<script>
(function () {
    var m = document.getElementById('lpPausa');
    function abrir() { m.classList.add('on'); var b = document.getElementById('lpPausaOk'); if (b) b.focus(); }
    function cerrar() { m.classList.remove('on'); }
    document.getElementById('lpPausaOk').addEventListener('click', cerrar);
    m.addEventListener('click', function (e) { if (e.target === m) cerrar(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });
    // WhatsApp al número de la tienda (no los enlaces para compartir, que no llevan número)
    function esChat(u) { return /wa\.me\/\+?\d|api\.whatsapp\.com\/send\?[^#]*phone=\d|whatsapp:\/\/send\?[^#]*phone=\d/i.test(String(u || '')); }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        var b = e.target.closest && e.target.closest('#ppComprar, #cEnviar');
        if ((a && esChat(a.getAttribute('href'))) || b) { e.preventDefault(); e.stopImmediatePropagation(); abrir(); }
    }, true);
    var abrirVentana = window.open;
    window.open = function (u) { if (esChat(u)) { abrir(); return null; } return abrirVentana.apply(window, arguments); };
})();
</script>
@endif
