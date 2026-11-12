{{-- Tema de Navidad para el marketplace y las tiendas en línea (lo elige el súper administrador en Admin → Marketplace).
     Cambia los colores (variables --g, --k, --bg...), pone luces en la barra de arriba, nieve suave y una cinta navideña.
     Va justo después de <body>. Uso: @include('partials.tema-navidad', ['cinta' => 'texto']) --}}
<style>
:root{--g:linear-gradient(135deg,#ffd166,#e63946 50%,#b5121b);--g2:#c1121f;--k:#0f2a1d;--bg:#fbf6ef;--line:#f0e1d2}
html{background:#0f2a1d}
.top{background:#0f2a1d!important}
.top::after{content:"";position:absolute;left:0;right:0;bottom:-9px;height:16px;pointer-events:none;z-index:1;
  background:
    radial-gradient(circle at 12px 9px,#ff4d5e 0 4px,transparent 5px),
    radial-gradient(circle at 36px 11px,#ffd166 0 4px,transparent 5px),
    radial-gradient(circle at 60px 9px,#3ddc84 0 4px,transparent 5px),
    radial-gradient(circle at 84px 11px,#5fb4ff 0 4px,transparent 5px),
    linear-gradient(transparent 4px,rgba(255,255,255,.18) 4px 5px,transparent 5px);
  background-size:96px 16px;animation:lpLuces 1.4s steps(2) infinite}
@keyframes lpLuces{to{background-position:48px 0}}
.marca small,.cifras b,.chip.on small,.pie b{color:#ffd166!important}
.marca i{color:#fff!important}
.cifras span{border-color:rgba(255,209,102,.3)!important}
.top .crear:hover,.top .ib .n{background:#ffd166!important;color:#0f2a1d!important}
@media(max-width:760px){.top .crear{background:#e63946!important;color:#fff!important}}
.busca button{color:#fff}
.subir-top{color:#ffd166!important}
.hero{background:radial-gradient(90% 120% at 90% 0%,rgba(230,57,70,.35),transparent 60%),linear-gradient(180deg,#0f2a1d,#173b29)!important;position:relative;overflow:hidden}
.hero::after{background:radial-gradient(circle,rgba(230,57,70,.5),transparent 70%)!important}
.hero::before{content:"🎄";position:absolute;right:max(14px,4vw);bottom:6px;font-size:clamp(3rem,9vw,6rem);opacity:.9;pointer-events:none;filter:drop-shadow(0 10px 18px rgba(0,0,0,.4))}
.hero .wrap{position:relative;z-index:1}
.card:hover,.tienda:hover{box-shadow:0 18px 30px -22px rgba(193,18,31,.85)!important}
.ok{background:#fde8e8!important;color:#a4161a!important}
.chip.on,.pag span.on{background:#0f2a1d!important;border-color:#0f2a1d!important}
.cta{background:linear-gradient(135deg,#7a0f1c,#0f2a1d)!important;border-color:rgba(255,209,102,.3)!important}
.lp-cinta-nav{background:repeating-linear-gradient(135deg,#b5121b 0 18px,#c8242f 18px 36px);color:#fff;text-align:center;font-weight:800;font-size:.86rem;padding:.6rem 12px .5rem;letter-spacing:.01em;box-shadow:inset 0 -3px 0 rgba(255,209,102,.6)}
.lp-cinta-nav b{color:#ffd166}
/* Nieve suave (no tapa nada: no recibe toques) */
.lp-nieve{position:fixed;inset:0;pointer-events:none;z-index:20;overflow:hidden}
.lp-nieve i{position:absolute;top:-14px;font-style:normal;color:#fff;text-shadow:0 0 3px rgba(90,120,150,.55);animation:lpCae linear infinite;will-change:transform}
@keyframes lpCae{to{transform:translate3d(var(--dx),105vh,0) rotate(220deg)}}
@media (prefers-reduced-motion:reduce){.lp-nieve{display:none}.top::after{animation:none}}
</style>
<div class="lp-nieve" aria-hidden="true">@for ($i = 0; $i < 22; $i++)<i style="left:{{ ($i * 37 + 11) % 100 }}%;font-size:{{ 9 + ($i * 7) % 12 }}px;opacity:{{ 0.45 + (($i * 13) % 50) / 100 }};animation-duration:{{ 9 + ($i * 5) % 11 }}s;animation-delay:-{{ ($i * 3) % 14 }}s;--dx:{{ (($i % 2) ? 1 : -1) * (20 + ($i * 9) % 40) }}px">❄</i>@endfor</div>
