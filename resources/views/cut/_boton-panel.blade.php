{{-- Botón grande y animado del panel para abrir LocalProCut (editor de videos) --}}
@php $tC = \App\Support\TiendaActual::tienda(); @endphp
@if (!$tC || $tC->enMenu('cut'))
<a href="{{ route('cut') }}" class="lpcut-boton">
    <span class="lpcut-ic"><img src="/icons/cut-192.png" alt=""></span>
    <span class="lpcut-tx"><b>Editar videos <em>· LocalProCut</em></b><small>Subtítulos automáticos, transiciones, belleza y 4K. Gratis y sin marca de agua.</small></span>
    <span class="lpcut-ir">Abrir <i class="fa-solid fa-arrow-right"></i></span>
    <span class="lpcut-chip c1">✨ Subtítulos</span><span class="lpcut-chip c2">🎬 4K</span>
</a>
<style>
.lpcut-boton{position:relative;overflow:hidden;display:flex;align-items:center;gap:1rem;margin:0 0 16px;padding:1rem 1.2rem;border-radius:24px;text-decoration:none;color:#fff;
  background:linear-gradient(120deg,#0b0d0c 0%,#13261a 45%,#2b1748 100%);background-size:200% 200%;animation:lpcutFondo 8s ease infinite;box-shadow:0 14px 34px -14px rgba(22,184,78,.55)}
.lpcut-boton:hover{color:#fff;transform:translateY(-1px)}
.lpcut-boton::after{content:"";position:absolute;top:0;bottom:0;width:35%;left:-50%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.12),transparent);animation:lpcutBrillo 3.5s ease-in-out infinite}
.lpcut-ic{flex:0 0 auto;width:56px;height:56px;border-radius:16px;display:grid;place-items:center;background:rgba(166,243,74,.12);animation:lpcutSalto 2.6s cubic-bezier(.3,1.6,.5,1) infinite}
.lpcut-ic img{width:46px;height:46px;border-radius:13px;box-shadow:0 6px 18px rgba(22,184,78,.45)}
.lpcut-tx{display:flex;flex-direction:column;min-width:0;z-index:1}
.lpcut-tx b{font-size:1.15rem;font-weight:800}
.lpcut-tx em{font-style:normal;color:#a6f34a}
.lpcut-tx small{color:#c9d3cc;font-weight:600;font-size:.84rem}
.lpcut-ir{margin-left:auto;flex:0 0 auto;background:linear-gradient(90deg,#a6f34a,#16b84e);color:#0b0d0c;font-weight:800;border-radius:999px;padding:.55rem 1rem;z-index:1}
.lpcut-chip{position:absolute;font-size:.7rem;font-weight:800;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:999px;padding:.15rem .55rem;animation:lpcutFlota 3.4s ease-in-out infinite;pointer-events:none}
.lpcut-chip.c1{right:150px;top:8px}.lpcut-chip.c2{right:118px;bottom:8px;animation-delay:-1.5s}
@media (max-width:640px){.lpcut-chip{display:none}.lpcut-ir{display:none}.lpcut-tx b{font-size:1.02rem}.lpcut-boton{padding:.85rem 1rem}}
@keyframes lpcutFondo{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@keyframes lpcutBrillo{0%,50%{left:-50%}100%{left:130%}}
@keyframes lpcutSalto{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px) scale(1.04)}}
@keyframes lpcutFlota{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@media (prefers-reduced-motion:reduce){.lpcut-boton,.lpcut-boton *,.lpcut-boton::after{animation:none!important}}
</style>
@endif
