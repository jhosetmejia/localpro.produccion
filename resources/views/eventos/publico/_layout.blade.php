@php
    $terror = (bool) (($evento ?? null)?->es_terror);
    $c = $terror ? '#e3061b' : (($evento ?? null)?->color ?: '#a855f7');
    $titulo = trim($__env->yieldContent('titulo')) ?: (($evento ?? null)?->nombre ?? 'Mis entradas');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $titulo }} · Entradas</title>
@yield('meta')
<meta name="theme-color" content="#0b0a10">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
:root{--c:{{ $c }};--bg:#0b0a10;--card:#16141d;--line:#2a2633;--tx:#f4f1fa;--mu:#a9a2b8;--ok:#22c55e;--warn:#f59e0b;--bad:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%}
body{background:var(--bg);color:var(--tx);font-family:'Plus Jakarta Sans',system-ui,sans-serif;line-height:1.5;min-height:100vh}
a{color:inherit}
.wrap{max-width:560px;margin:0 auto;padding:0 16px}
.top{position:sticky;top:0;z-index:20;background:rgba(11,10,16,.85);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.top .wrap{display:flex;align-items:center;gap:.6rem;height:56px}
.top .marca{font-weight:800;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
.top a.mi{font-size:.85rem;font-weight:700;text-decoration:none;border:1px solid var(--line);padding:.4rem .8rem;border-radius:999px;white-space:nowrap}
.card{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:20px;margin:16px 0}
.card h2{font-size:1.15rem;margin-bottom:.6rem}
.mu{color:var(--mu)}
.btn{display:flex;align-items:center;justify-content:center;gap:.55rem;width:100%;min-height:56px;border:0;border-radius:999px;font:inherit;font-weight:800;font-size:1.05rem;cursor:pointer;text-decoration:none;padding:.8rem 1.2rem;transition:transform .15s}
.btn:active{transform:scale(.98)}
.btn-c{background:var(--c);color:#fff;box-shadow:0 14px 34px -14px var(--c)}
.btn-wa{background:#25d366;color:#08240f}
.btn-l{background:transparent;color:var(--tx);border:1.5px solid var(--line)}
.btn[disabled]{opacity:.5;cursor:not-allowed}
label.lb{display:block;font-weight:700;font-size:.9rem;margin:.9rem 0 .35rem}
.in{width:100%;min-height:50px;background:#0f0e14;border:1.5px solid var(--line);border-radius:14px;color:var(--tx);font:inherit;padding:.7rem .9rem;outline:none}
.in:focus{border-color:var(--c)}
select.in{appearance:none;background-image:linear-gradient(45deg,transparent 50%,var(--mu) 50%),linear-gradient(135deg,var(--mu) 50%,transparent 50%);background-position:calc(100% - 20px) 22px,calc(100% - 15px) 22px;background-size:5px 5px;background-repeat:no-repeat}
input[type=date].in{color-scheme:dark}
.chk{display:flex;gap:.6rem;align-items:flex-start;margin:.9rem 0;font-size:.9rem;color:var(--mu)}
.chk input{width:22px;height:22px;flex:0 0 22px;accent-color:var(--c);margin-top:2px}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.4);color:#fecaca;border-radius:14px;padding:.8rem 1rem;margin:14px 0;font-weight:600;font-size:.92rem}
.okm{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.4);color:#bbf7d0;border-radius:14px;padding:.8rem 1rem;margin:14px 0;font-weight:600;font-size:.92rem}
.aviso{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);color:#fde68a;border-radius:16px;padding:.9rem 1rem;font-size:.92rem}
.pie{text-align:center;color:var(--mu);font-size:.8rem;padding:30px 16px 110px}
.pie a{color:var(--mu)}
.badge{display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:800;padding:.3rem .7rem;border-radius:999px}
.b-ok{background:rgba(34,197,94,.15);color:#86efac}.b-wait{background:rgba(245,158,11,.15);color:#fcd34d}.b-bad{background:rgba(239,68,68,.15);color:#fca5a5}
@if ($terror)
/* ===== Tema terror / Halloween ===== */
:root{--bg:#070203;--card:rgba(24,6,8,.88);--line:#4a1015;--tx:#f6eeee;--mu:#c9a3a6}
body{background:#050001}
/* Fondo: manos que se mueven */
.manos{position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden;background:#120002}
.manos i{position:absolute;inset:-10%;background:url("{{ asset('img/eventos/terror-manos.webp') }}") center/cover no-repeat;will-change:transform,opacity}
.manos .m1{filter:brightness(.8) saturate(1.15);animation:alcanzan 16s ease-in-out infinite alternate}
.manos .m2{opacity:0;mix-blend-mode:multiply;animation:aparecen 11s ease-in-out infinite}
.manos .luz{inset:0;background:radial-gradient(ellipse 70% 55% at 50% 40%,rgba(255,40,40,.28),transparent 70%);mix-blend-mode:screen;animation:parpadea 7s steps(1) infinite}
.manos::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.75) 0,rgba(0,0,0,.25) 22%,rgba(0,0,0,.35) 70%,rgba(0,0,0,.85) 100%),radial-gradient(ellipse 85% 60% at 50% 50%,rgba(0,0,0,.45),transparent 80%)}
@media (min-width:760px){
  .manos i{inset:-6%;background-size:auto 112%;background-repeat:repeat-x}
  .manos::after{background:linear-gradient(90deg,rgba(0,0,0,.1) 0,rgba(0,0,0,.82) 33%,rgba(0,0,0,.82) 67%,rgba(0,0,0,.1) 100%),linear-gradient(180deg,rgba(0,0,0,.6),transparent 25%,transparent 75%,rgba(0,0,0,.8))}
}
@keyframes alcanzan{0%{transform:scale(1.02) translate3d(0,0,0)}35%{transform:scale(1.09) translate3d(-1.6%,1.2%,0)}70%{transform:scale(1.05) translate3d(1.4%,-1%,0)}100%{transform:scale(1.11) translate3d(-.6%,-1.8%,0)}}
@keyframes aparecen{0%,100%{opacity:0;transform:scaleX(-1) scale(1.16) translate3d(0,3%,0)}50%{opacity:.55;transform:scaleX(-1) scale(1.04) translate3d(0,-2%,0)}}
@keyframes parpadea{0%,40%,58%,100%{opacity:1}42%{opacity:.25}44%{opacity:.9}47%{opacity:.15}52%{opacity:.8}}
@media (prefers-reduced-motion:reduce){.manos i{animation:none!important}}
main,header,footer{position:relative;z-index:1}
.top{background:rgba(7,2,3,.88);border-bottom:1px solid #3a0b10}
.tit,.sub,.card h2{font-family:'Creepster','Plus Jakarta Sans',cursive;letter-spacing:.04em}
.tit{font-weight:400;font-size:2.6rem;color:#f2f2f2;text-shadow:0 0 18px rgba(227,6,27,.6),0 3px 0 #5c0610}
.sub{font-size:1.15rem;letter-spacing:.12em;text-shadow:0 0 12px rgba(227,6,27,.7)}
.card{background:rgba(14,3,5,.9);box-shadow:0 0 0 1px rgba(227,6,27,.14),0 20px 50px -25px rgba(227,6,27,.55);backdrop-filter:blur(6px)}
.card h2{font-weight:400;font-size:1.5rem;color:#ff2a3a}
.btn-c{background:linear-gradient(180deg,#ff1f33,#9d0010);box-shadow:0 0 0 1px #ff4b5a inset,0 14px 34px -10px #e3061b;text-transform:uppercase;letter-spacing:.06em}
.dato i{background:rgba(227,6,27,.18);color:#ff3344}
.hero{box-shadow:0 30px 60px -30px #e3061b}
.hero .fondo{filter:blur(28px) brightness(.35) saturate(1.6) hue-rotate(-10deg)}
@endif
@yield('css')
</style>
@if ($terror)<link href="https://fonts.googleapis.com/css2?family=Creepster&display=swap" rel="stylesheet"><link rel="preload" as="image" href="{{ asset('img/eventos/terror-manos.webp') }}">@endif
</head>
<body>
@if ($terror)
<div class="manos" aria-hidden="true"><i class="m1"></i><i class="m2"></i><i class="luz"></i></div>
@endif
<header class="top"><div class="wrap">
    @if ($evento ?? null)
    <a class="marca" href="{{ route('evento.publico', $evento->slug) }}"><i class="fa-solid fa-ticket me-1" style="color:var(--c)"></i> {{ $evento->nombre }}</a>
    @else
    <a class="marca" href="{{ route('mis-entradas.index') }}"><i class="fa-solid fa-ticket" style="color:var(--c)"></i> Mis entradas</a>
    @endif
    @if (auth('asistente')->check())
    <a class="mi" href="{{ route('mis-entradas.index') }}"><i class="fa-solid fa-qrcode"></i> Mi entrada</a>
    @else
    <a class="mi" href="{{ route('mis-entradas.ingresar', ($evento ?? null) ? ['e' => $evento->slug] : []) }}"><i class="fa-regular fa-user"></i> Ingresar</a>
    @endif
</div></header>
<main class="wrap">
    @if (session('error'))<div class="err">{{ session('error') }}</div>@endif
    @if (session('success'))<div class="okm">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="err"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>@endif
    @yield('content')
</main>
<footer class="pie">
    @if (($tienda ?? null)?->nombre)Organiza: <b>{{ $tienda->nombre }}</b><br>@endif
    Entradas con <a href="{{ url('/') }}?desde=evento" rel="noopener">LocalProo</a>
    · <a href="{{ route('eventos.terminos-compra', ($evento ?? null)?->slug ? ['e' => $evento->slug] : []) }}">Términos y reembolsos</a>
    <br><small>LocalProo es la plataforma de venta; el evento es responsabilidad del organizador.</small>
</footer>
@yield('js')
</body>
</html>
