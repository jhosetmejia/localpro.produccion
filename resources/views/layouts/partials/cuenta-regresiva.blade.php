{{-- Cuenta regresiva del plan: se muestra al dueño cuando faltan 30 días o menos para que venza (prueba gratis o plan pagado) --}}
@php
    $tCR = \App\Support\TiendaActual::tienda();
    $dCR = $tCR?->plan_vence && !$tCR->plan_vencido ? $tCR->dias_para_vencer : null;
@endphp
@if ($dCR !== null && $dCR <= 30 && auth()->user()?->can('editar-negocio'))
@php
    $gratisCR = !$tCR->plan || (float) $tCR->plan->precio <= 0;
    $urgenteCR = $dCR <= 7;
    $finCR = $tCR->plan_vence->copy()->endOfDay();
@endphp
<div class="lp-cr {{ $urgenteCR ? 'urgente' : '' }}">
    <div class="lp-cr-txt">
        <small>{{ $gratisCR ? '⏳ Su prueba gratis se está acabando' : '⏳ Su plan está por vencer' }}</small>
        <b>{{ $dCR === 0 ? '¡Hoy es el último día!' : 'Le quedan ' . $dCR . ' ' . ($dCR === 1 ? 'día' : 'días') }}</b>
        <p>Realice su pago antes del <b>{{ $finCR->locale('es')->isoFormat('D [de] MMMM') }}</b>. Si no, su cuenta se bloquea y no podrá vender ni ver sus productos, ventas ni clientes hasta activar un plan. <b>¡No pierda lo que ya construyó!</b></p>
    </div>
    <div class="lp-cr-reloj" data-fin="{{ $finCR->timestamp * 1000 }}">
        <div><span data-u="d">{{ $dCR }}</span><small>días</small></div>
        <div><span data-u="h">00</span><small>horas</small></div>
        <div><span data-u="m">00</span><small>min</small></div>
        <div><span data-u="s">00</span><small>seg</small></div>
    </div>
    <a href="{{ route('plan.index') }}" class="lp-cr-btn"><i class="fa-solid fa-bolt me-1"></i>Pagar ahora</a>
</div>
@once
<style>
.lp-cr{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;background:#0f1411;color:#fff;border-radius:24px;padding:1.1rem 1.3rem;margin-bottom:1rem;position:relative;overflow:hidden}
.lp-cr::after{content:"";position:absolute;width:180px;height:180px;border-radius:50%;border:30px solid rgba(47,209,90,.14);right:-60px;top:-70px;pointer-events:none}
.lp-cr.urgente{background:linear-gradient(135deg,#1a0d12,#2a0f1c);box-shadow:0 0 0 2px #d61f69 inset}
.lp-cr.urgente::after{border-color:rgba(214,31,105,.18)}
.lp-cr-txt{flex:1 1 260px;min-width:0;position:relative;z-index:1}
.lp-cr-txt small{color:#9fe3b0;font-weight:700;font-size:.82rem}
.lp-cr.urgente .lp-cr-txt small{color:#ff9cc4}
.lp-cr-txt>b{display:block;font-size:1.45rem;font-weight:900;line-height:1.15;margin:.15rem 0 .3rem}
.lp-cr-txt p{margin:0;color:#c9d3cc;font-size:.88rem;line-height:1.4}
.lp-cr-txt p b{color:#fff}
.lp-cr-reloj{display:flex;gap:.45rem;position:relative;z-index:1}
.lp-cr-reloj div{background:#1d2520;border-radius:14px;min-width:58px;padding:.45rem .3rem;text-align:center}
.lp-cr.urgente .lp-cr-reloj div{background:#3a1424}
.lp-cr-reloj span{display:block;font-size:1.5rem;font-weight:900;line-height:1;color:#2fd15a;font-variant-numeric:tabular-nums}
.lp-cr.urgente .lp-cr-reloj span{color:#ff5c9a}
.lp-cr-reloj small{font-size:.66rem;color:#9aa69e;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.lp-cr-btn{position:relative;z-index:1;background:#2fd15a;color:#0b0d0c;font-weight:900;border-radius:999px;padding:.8rem 1.4rem;text-decoration:none;white-space:nowrap}
.lp-cr-btn:hover{background:#a6f34a;color:#0b0d0c}
.lp-cr.urgente .lp-cr-btn{background:#ff5c9a;color:#fff}
@media (max-width:640px){.lp-cr-reloj{width:100%;justify-content:space-between}.lp-cr-reloj div{flex:1;min-width:0}.lp-cr-btn{width:100%;text-align:center}}
</style>
<script>
(function () {
    function pintar() {
        document.querySelectorAll('.lp-cr-reloj[data-fin]').forEach(function (r) {
            var t = Math.max(0, Math.floor((+r.dataset.fin - Date.now()) / 1000));
            var v = { d: Math.floor(t / 86400), h: Math.floor(t % 86400 / 3600), m: Math.floor(t % 3600 / 60), s: t % 60 };
            Object.keys(v).forEach(function (k) { var e = r.querySelector('[data-u=' + k + ']'); if (e) e.textContent = k === 'd' ? v[k] : String(v[k]).padStart(2, '0'); });
        });
    }
    pintar(); setInterval(pintar, 1000);
})();
</script>
@endonce
@endif
