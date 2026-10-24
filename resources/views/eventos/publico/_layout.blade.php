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
body{background:#070203 radial-gradient(ellipse 120% 70% at 50% -10%,#5c0610 0%,rgba(40,3,8,.6) 35%,transparent 70%) fixed;}
body::before{content:"";position:fixed;inset:0;pointer-events:none;z-index:0;
  background:radial-gradient(circle at 12% 78%,rgba(140,0,12,.35),transparent 28%),radial-gradient(circle at 88% 60%,rgba(120,0,10,.3),transparent 26%),radial-gradient(ellipse at 50% 120%,rgba(160,0,15,.45),transparent 55%);}
body::after{content:"";position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.18;mix-blend-mode:screen;
  background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.012' numOctaves='3'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='.9'/%3E%3C/svg%3E");animation:niebla 40s linear infinite}
@keyframes niebla{from{background-position:0 0}to{background-position:600px 0}}
main,header,footer{position:relative;z-index:1}
.top{background:rgba(7,2,3,.88);border-bottom:1px solid #3a0b10}
.gotas{position:absolute;top:56px;left:0;right:0;height:64px;z-index:25;pointer-events:none}
.gotas svg{width:100%;height:100%;display:block}
.gota{position:absolute;top:56px;width:7px;border-radius:0 0 7px 7px;background:linear-gradient(#8a0010,#e3061b);z-index:24;pointer-events:none;animation:cae 6s ease-in infinite;opacity:.9}
@keyframes cae{0%{height:0;opacity:.95}70%{height:var(--h);opacity:.95}100%{height:var(--h);transform:translateY(100vh);opacity:0}}
.tit,.sub,.card h2{font-family:'Creepster','Plus Jakarta Sans',cursive;letter-spacing:.04em}
.tit{font-weight:400;font-size:2.6rem;color:#f2f2f2;text-shadow:0 0 18px rgba(227,6,27,.6),0 3px 0 #5c0610}
.sub{font-size:1.15rem;letter-spacing:.12em;text-shadow:0 0 12px rgba(227,6,27,.7)}
.card{box-shadow:0 0 0 1px rgba(227,6,27,.12),0 20px 50px -25px rgba(227,6,27,.55);backdrop-filter:blur(4px)}
.card h2{font-weight:400;font-size:1.5rem;color:#ff2a3a}
.btn-c{background:linear-gradient(180deg,#ff1f33,#9d0010);box-shadow:0 0 0 1px #ff4b5a inset,0 14px 34px -10px #e3061b;text-transform:uppercase;letter-spacing:.06em}
.dato i{background:rgba(227,6,27,.18);color:#ff3344}
.hero{box-shadow:0 30px 60px -30px #e3061b}
.hero .fondo{filter:blur(28px) brightness(.35) saturate(1.6) hue-rotate(-10deg)}
@endif
@yield('css')
</style>
@if ($terror)<link href="https://fonts.googleapis.com/css2?family=Creepster&display=swap" rel="stylesheet">@endif
</head>
<body>
@if ($terror)
{{-- Sangre que escurre desde arriba --}}
<div class="gotas" aria-hidden="true"><svg viewBox="0 0 1200 110" preserveAspectRatio="none"><defs><linearGradient id="sg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5c0610"/><stop offset=".6" stop-color="#b3000f"/><stop offset="1" stop-color="#e3061b"/></linearGradient></defs>
<path fill="url(#sg)" d="M0 0H1200V22c-18 0-22 10-30 10s-10-8-24-8-16 52-26 52-8-46-22-46-18 14-34 14-14-12-30-12-12 30-24 30-6-26-22-26-20 8-36 8-14 64-26 64-10-58-24-58-22 16-40 16-18-10-30-10-14 24-26 24-8-20-22-20-24 10-40 10-16 40-28 40-10-36-24-36-20 6-34 6-12 22-26 22-12-18-26-18-26 12-42 12-14 70-26 70-12-64-26-64-18 10-34 10-16-12-30-12-12 34-26 34-10-30-24-30-22 8-38 8-14 26-28 26-10-22-26-22-20 6-34 6-16 48-28 48-12-44-26-44-18 12-34 12-14-10-28-10-14 28-26 28-12-24-26-24-22 8-38 8-12 58-26 58-14-54-28-54-18 8-34 8-14-12-28-12-12 20-26 20-10-16-24-16-20 10-36 10-18 36-30 36-12-32-26-32-18 4-30 4Z"/></svg></div>
@foreach ([[7,'14%',2.1,140],[18,'31%',4.6,210],[27,'47%',1.2,120],[41,'63%',3.4,260],[52,'79%',5.2,170],[66,'92%',2.8,230],[79,'22%',6.1,190],[88,'71%',0.6,150]] as [$x, $l, $d, $h])
<span class="gota" aria-hidden="true" style="left:{{ $x }}%;--h:{{ $h }}px;animation-delay:{{ $d }}s;animation-duration:{{ 5 + ($x % 4) }}s"></span>
@endforeach
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
</footer>
@yield('js')
</body>
</html>
