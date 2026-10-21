@once
<style>
.lp-promo{margin-top:.45rem}
.lp-promo-tag{display:inline-flex;align-items:center;gap:.35rem;background:linear-gradient(90deg,#ff6a00,#ff2d55);color:#fff;font-weight:800;font-size:.78rem;padding:.3rem .7rem;border-radius:999px;box-shadow:0 4px 14px rgba(255,60,60,.3);animation:lpPulse 2s infinite}
.lp-promo-antes{margin-top:.45rem;font-size:.95rem;color:#8a948c;font-weight:600}
.lp-promo-antes s{text-decoration-color:#ff2d55;text-decoration-thickness:2px;color:#9aa39c}
.lp-promo-antes span{background:#ffe8ec;color:#e0213f;font-weight:800;font-size:.75rem;padding:.1rem .45rem;border-radius:6px;margin-left:.25rem}
.lp-promo-hoy{color:#11702f;font-weight:800;font-size:.85rem;text-transform:uppercase;letter-spacing:.04em;margin-top:.2rem}
.lp-promo-fin{display:flex;gap:.6rem;align-items:flex-start;background:#fff7e6;border:1px dashed #ffb020;color:#7a4b00;border-radius:12px;padding:.6rem .75rem;font-size:.82rem;margin:.2rem 0 .8rem;text-align:left}
.lp-promo-fin i{color:#ff8a00;margin-top:.15rem}
@keyframes lpPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}
</style>
@endonce
@php
    $antesTx = $p->precioAntesTexto($tienda);
    $promo = $antesTx && \App\Models\Plan::promoVigente();
    $diasPromo = \App\Models\Plan::promoDiasRestantes();
    $hastaPromo = \Carbon\Carbon::parse(\App\Models\Plan::PROMO_HASTA)->locale('es');
@endphp
@if ($antesTx)
<div class="lp-promo">
    @if ($promo)<div class="lp-promo-tag"><i class="fa-solid fa-fire"></i> Oferta de lanzamiento · Ahorra {{ $p->ahorroTexto($tienda) }}</div>@endif
    <div class="lp-promo-antes">Antes <s>{{ $antesTx }}</s> <span>-{{ round(100 - $p->precio * 100 / $p->precio_antes) }}%</span></div>
    @if ($promo)<div class="lp-promo-hoy">¡Ahora solo!</div>@endif
</div>
@endif
