@extends('eventos.publico._layout')

@php
    $desc = $evento->subtitulo ?: \Illuminate\Support\Str::limit(strip_tags((string) $evento->descripcion), 150);
    $fechaCorta = $evento->fecha_evento?->locale('es')->isoFormat('D [de] MMMM');
@endphp

@section('meta')
<meta name="description" content="{{ $evento->nombre }}{{ $fechaCorta ? ' · ' . $fechaCorta : '' }}. {{ $desc }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $evento->nombre }}{{ $evento->subtitulo ? ' · ' . $evento->subtitulo : '' }}">
<meta property="og:description" content="{{ $fechaCorta ? ucfirst($fechaCorta) . '. ' : '' }}¡Compra tu entrada aquí!">
<meta property="og:url" content="{{ $evento->url }}">
@if ($evento->imagen_url)<meta property="og:image" content="{{ $evento->imagen_url }}">@endif
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="{{ $evento->url }}">
<meta name="robots" content="{{ $evento->estado === 'borrador' ? 'noindex, nofollow' : 'index, follow' }}">
@endsection

@section('css')
.hero{position:relative;margin:0 -16px;overflow:hidden;background:#000}
.hero .fondo{position:absolute;inset:-30px;background-size:cover;background-position:center;filter:blur(28px) brightness(.45);transform:scale(1.1)}
.hero img{position:relative;display:block;width:100%;max-height:82vh;object-fit:contain;margin:0 auto}
.hero .sin{position:relative;height:280px;display:grid;place-items:center;background:radial-gradient(circle at 30% 20%,var(--c),#0b0a10 70%)}
.hero .sin i{font-size:4rem;opacity:.7}
.tit{margin:22px 0 4px;font-size:2rem;line-height:1.1;font-weight:800;letter-spacing:-.02em}
.sub{color:var(--c);font-weight:800;letter-spacing:.18em;text-transform:uppercase;font-size:.85rem}
.datos{display:grid;gap:.7rem;margin:18px 0}
.dato{display:flex;gap:.8rem;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:.85rem 1rem}
.dato i{width:40px;height:40px;flex:0 0 40px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 22%,transparent);color:var(--c)}
.dato b{display:block}.dato small{color:var(--mu)}
.vistas{display:flex;align-items:center;justify-content:center;gap:.45rem;margin:14px auto 0;width:max-content;max-width:100%;padding:.4rem .9rem;border-radius:999px;background:color-mix(in srgb,var(--c) 16%,transparent);border:1px solid color-mix(in srgb,var(--c) 40%,transparent);font-size:.88rem;font-weight:700}
.vistas .pt{width:8px;height:8px;border-radius:50%;background:var(--c);box-shadow:0 0 0 0 var(--c);animation:latir 1.8s infinite}
@keyframes latir{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--c) 70%,transparent)}100%{box-shadow:0 0 0 10px transparent}}
@media (prefers-reduced-motion:reduce){.vistas .pt{animation:none}}
.precio{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.precio .v{font-size:2.1rem;font-weight:800;line-height:1}
.desc{white-space:pre-line;color:#dcd6e8}
.redes{display:flex;gap:.6rem;justify-content:center;margin-top:10px}
.redes a{width:48px;height:48px;border-radius:50%;display:grid;place-items:center;border:1px solid var(--line);font-size:1.2rem;text-decoration:none}
.fijo{position:fixed;left:0;right:0;bottom:0;z-index:30;padding:12px 16px calc(12px + env(safe-area-inset-bottom));background:linear-gradient(transparent,rgba(11,10,16,.95) 30%)}
.fijo .btn{max-width:528px;margin:0 auto}
.borr{background:#fde68a;color:#5a3d00;text-align:center;font-weight:700;padding:.5rem;margin:0 -16px;font-size:.85rem}
.afiche2{width:100%;border-radius:18px;display:block}
.logo-ev{margin:18px auto 0;text-align:center}
.logo-ev img{max-width:min(92%,440px);max-height:220px;filter:drop-shadow(0 10px 24px rgba(0,0,0,.7))}
.visually-hidden{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
@endsection

@section('content')
@if ($evento->estado === 'borrador')<div class="borr"><i class="fa-solid fa-eye"></i> Vista previa: el público aún no ve esta página (está en borrador).</div>@endif
<div class="hero">
    @if ($evento->imagen_url)
        <div class="fondo" style="background-image:url('{{ $evento->imagen_url }}')"></div>
        <img src="{{ $evento->imagen_url }}" alt="{{ $evento->nombre }}" fetchpriority="high">
    @else
        <div class="sin"><i class="fa-solid fa-ticket"></i></div>
    @endif
</div>

@if ($evento->logo_url)
<div class="logo-ev"><img src="{{ $evento->logo_url }}" alt="{{ $evento->nombre }}"></div>
@if ($evento->subtitulo)<div class="sub" style="margin-top:6px;text-align:center">{{ $evento->subtitulo }}</div>@endif
<h1 class="tit visually-hidden">{{ $evento->nombre }}</h1>
@else
@if ($evento->subtitulo)<div class="sub" style="margin-top:22px">{{ $evento->subtitulo }}</div>@endif
<h1 class="tit" @if ($evento->subtitulo) style="margin-top:6px" @endif>{{ $evento->nombre }}</h1>
@endif

@if ($evento->mostrar_visitas && $evento->visitas > 0)
<div class="vistas"><span class="pt"></span><i class="fa-regular fa-eye"></i> {{ number_format($evento->visitas, 0, ',', '.') }} {{ $evento->visitas === 1 ? 'persona ha visto' : 'personas han visto' }} este evento</div>
@endif

<div class="datos">
    @if ($evento->fecha_evento)
    <div class="dato"><i class="fa-regular fa-calendar"></i><div><b>{{ ucfirst($evento->fecha_evento->locale('es')->isoFormat('dddd D [de] MMMM')) }}</b><small>{{ $evento->fecha_evento->format('g:i a') }}</small></div></div>
    @endif
    @if ($evento->lugar || $evento->ciudad)
    <div class="dato"><i class="fa-solid fa-location-dot"></i><div><b>{{ $evento->lugar ?: $evento->ciudad }}</b>@if ($evento->lugar && $evento->ciudad)<small>{{ $evento->ciudad }}</small>@endif</div></div>
    @endif
</div>

<div class="card">
    <div class="precio">
        <div><small class="mu">{{ $evento->etapa ?: 'Entrada' }}</small><div class="v">{{ $evento->precio_texto }}</div></div>
        @if ($evento->venta_hasta && !$motivo)<span class="badge b-wait"><i class="fa-regular fa-clock"></i> Hasta el {{ $evento->venta_hasta->locale('es')->isoFormat('D [de] MMMM') }}</span>@endif
    </div>
    @if ($evento->quedan !== null && !$motivo && $evento->quedan <= 50)<p class="mu" style="margin-top:.6rem"><i class="fa-solid fa-fire" style="color:var(--warn)"></i> {{ $evento->quedan === 1 ? '¡Queda 1 entrada!' : '¡Quedan ' . $evento->quedan . ' entradas!' }}</p>@endif
    @if (!$motivo && ($promosL = $evento->promosVigentes()))
    <div style="display:grid;gap:.5rem;margin-top:.9rem">
        @foreach ($promosL as $pr)<div style="background:color-mix(in srgb,var(--c) 14%,transparent);border:1px dashed color-mix(in srgb,var(--c) 60%,transparent);border-radius:14px;padding:.6rem .8rem;font-weight:700"><i class="fa-solid fa-tags" style="color:var(--c)"></i> ¡Promo! {{ $pr['texto'] }}@if ($pr['hasta']) <small class="mu" style="font-weight:600">· hasta el {{ \Illuminate\Support\Carbon::parse($pr['hasta'])->locale('es')->isoFormat('D [de] MMMM') }}</small>@endif</div>@endforeach
    </div>
    @endif
    @if ($motivo)<div class="aviso" style="margin-top:14px">{{ $motivo }}</div>@endif
    <p class="mu" style="margin-top:.8rem;font-size:.9rem"><i class="fa-solid fa-shield-halved"></i> Te registras, pagas por {{ $evento->pago_metodo }} y cuando confirmamos tu pago recibes tu entrada con QR.</p>
</div>

@if ($evento->descripcion)
<div class="card"><h2>Sobre el evento</h2><p class="desc">{{ $evento->descripcion }}</p></div>
@endif

@if ($evento->imagen2_url)
<div style="margin:16px 0"><img class="afiche2" src="{{ $evento->imagen2_url }}" alt="Información de {{ $evento->nombre }}" loading="lazy"></div>
@endif

@if ($evento->condiciones)
<div class="card"><h2>Condiciones</h2><p class="desc mu" style="font-size:.9rem">{{ $evento->condiciones }}</p></div>
@endif

@if ($evento->instagram || $evento->tiktok || $evento->whatsapp_link)
<div class="redes">
    @if ($evento->instagram)<a href="{{ $evento->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>@endif
    @if ($evento->tiktok)<a href="{{ $evento->tiktok_url }}" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>@endif
    @if ($evento->whatsapp_link)<a href="{{ $evento->whatsapp_link }}?text={{ rawurlencode('Hola, quiero información sobre ' . $evento->nombre) }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>@endif
</div>
@endif

<div class="fijo">
    @if ($entrada && $entrada->estado === 'aprobada')
        <a class="btn btn-c" href="{{ route('mis-entradas.ver', $entrada->uuid) }}"><i class="fa-solid fa-qrcode"></i> Ver mi entrada</a>
    @elseif ($entrada && $entrada->estado === 'pendiente')
        <a class="btn btn-c" href="{{ route('evento.pago', $evento->slug) }}"><i class="fa-solid fa-hourglass-half"></i> Ver mi pago pendiente</a>
    @elseif ($motivo)
        <button class="btn btn-l" disabled>{{ $evento->estado === 'publicado' && !$evento->quedan && $evento->cupo ? 'Agotado' : 'Venta no disponible' }}</button>
    @else
        <a class="btn btn-c" href="{{ route('evento.comprar', $evento->slug) }}"><i class="fa-solid fa-ticket"></i> {{ (int) $evento->max_por_compra > 1 ? 'Comprar entradas' : 'Comprar entrada' }}</a>
    @endif
</div>
@endsection
