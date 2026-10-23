@php
    [$temaNombre, $fondo, $texto, $btnBg, $btnTx, $btnBd] = $tema;
    $radio = ['pildora' => '999px', 'redondeado' => '16px', 'cuadrado' => '4px', 'contorno' => '999px'][$boton] ?? '999px';
    $contorno = $boton === 'contorno';
    $descripcion = $tienda->enlaces_bio ?: ('Todos los enlaces de ' . $tienda->nombre . ' en un solo lugar.');
    $inicial = mb_strtoupper(mb_substr($tienda->nombre, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $tienda->nombre }} · Enlaces</title>
<meta name="description" content="{{ $descripcion }}">
<link rel="canonical" href="{{ $url }}">
<meta name="robots" content="{{ $tienda->en_google && !$borrador ? 'index, follow' : 'noindex, follow' }}">
<meta property="og:type" content="profile">
<meta property="og:site_name" content="{{ $tienda->nombre }}">
<meta property="og:title" content="{{ $tienda->nombre }}">
<meta property="og:description" content="{{ $descripcion }}">
<meta property="og:url" content="{{ $url }}">
@if ($foto)<meta property="og:image" content="{{ $foto }}"><link rel="icon" href="{{ $foto }}">@endif
<meta name="twitter:card" content="summary">
<meta name="theme-color" content="{{ str_starts_with($fondo, '#') ? $fondo : '#0b0d0c' }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
:root{--fondo:{{ $fondo }};--tx:{{ $texto }};--bbg:{{ $contorno ? 'transparent' : $btnBg }};--btx:{{ $contorno ? $texto : $btnTx }};--bbd:{{ $contorno ? $texto : $btnBd }};--r:{{ $radio }}}
*{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100%}
body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--fondo);background-attachment:fixed;color:var(--tx);-webkit-font-smoothing:antialiased}
.pg{max-width:580px;margin:0 auto;padding:28px 18px calc(40px + env(safe-area-inset-bottom));display:flex;flex-direction:column;align-items:center;min-height:100vh}
.top{width:100%;display:flex;justify-content:flex-end;margin-bottom:6px}
.ico-btn{width:42px;height:42px;border-radius:50%;border:0;display:grid;place-items:center;background:rgba(127,127,127,.18);background:color-mix(in srgb,var(--tx) 14%,transparent);color:var(--tx);font-size:1rem;cursor:pointer;backdrop-filter:blur(6px)}
.ico-btn:hover{background:color-mix(in srgb,var(--tx) 24%,transparent)}
.av{width:104px;height:104px;border-radius:50%;object-fit:cover;box-shadow:0 0 0 4px rgba(255,255,255,.35),0 14px 30px -12px rgba(0,0,0,.45);background:#fff}
.av.ini{display:grid;place-items:center;font-size:2.6rem;font-weight:800;color:#0b3d1f;background:linear-gradient(135deg,#a6f34a,#2fd15a)}
h1{font-size:1.35rem;font-weight:800;margin-top:16px;text-align:center;letter-spacing:-.01em}
.bio{margin-top:6px;text-align:center;opacity:.85;font-weight:500;line-height:1.5;max-width:440px;white-space:pre-line}
.redes{display:flex;flex-wrap:wrap;justify-content:center;gap:6px;margin-top:16px}
.redes a{width:44px;height:44px;display:grid;place-items:center;color:var(--tx);font-size:1.35rem;border-radius:50%;text-decoration:none;transition:transform .15s}
.redes a:hover{transform:translateY(-2px) scale(1.08)}
.lista{width:100%;display:flex;flex-direction:column;gap:14px;margin-top:24px}
.bt{position:relative;display:flex;align-items:center;justify-content:center;min-height:58px;padding:14px 56px;border-radius:var(--r);background:var(--bbg);color:var(--btx);border:2px solid var(--bbd);text-decoration:none;font-weight:700;font-size:1rem;text-align:center;line-height:1.3;box-shadow:0 8px 22px -14px rgba(0,0,0,.55);transition:transform .15s,box-shadow .15s}
.bt:hover{transform:translateY(-2px) scale(1.01);box-shadow:0 14px 26px -14px rgba(0,0,0,.6)}
.bt:active{transform:scale(.98)}
.bt .i{position:absolute;left:18px;top:50%;transform:translateY(-50%);width:26px;text-align:center;font-size:1.2rem}
.bt .t{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-width:0;max-width:100%;overflow-wrap:anywhere;word-break:break-word}
@media (max-width:380px){.bt{padding:12px 46px;font-size:.95rem}.bt .i{left:14px}}
.bt.dest{animation:menear 3.2s ease-in-out infinite}
@keyframes menear{0%,82%,100%{transform:rotate(0)}85%{transform:rotate(-2deg) scale(1.02)}88%{transform:rotate(2deg) scale(1.02)}91%{transform:rotate(-1.5deg)}94%{transform:rotate(1deg)}}
@media (prefers-reduced-motion:reduce){.bt.dest{animation:none}.bt,.redes a{transition:none}}
.sep{font-weight:800;text-align:center;margin-top:10px;font-size:1.02rem;opacity:.95}
.borr{width:100%;background:#fef3c7;color:#92600a;border-radius:14px;padding:10px 14px;font-weight:700;font-size:.9rem;text-align:center;margin-bottom:12px}
.vacio{opacity:.8;text-align:center;margin-top:24px}
.pie{margin-top:auto;padding-top:40px}
.marca{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.92);color:#0b3d1f;padding:9px 16px;border-radius:999px;text-decoration:none;font-weight:800;font-size:.86rem;box-shadow:0 8px 20px -12px rgba(0,0,0,.5)}
.marca b{display:grid;place-items:center;width:22px;height:22px;border-radius:7px;background:linear-gradient(135deg,#a6f34a,#2fd15a);color:#0b0d0c;font-size:.75rem}
.toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%) translateY(30px);background:#0b0d0c;color:#fff;padding:10px 18px;border-radius:999px;font-weight:700;font-size:.9rem;opacity:0;transition:.25s;pointer-events:none}
.toast.on{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>
<main class="pg">
    @if ($borrador)
    <div class="borr"><i class="fa-solid fa-eye-slash"></i> Vista previa: su página aún no está publicada. Actívela en "Mis enlaces".</div>
    @endif
    <div class="top"><button class="ico-btn" id="compartir" type="button" aria-label="Compartir"><i class="fa-solid fa-arrow-up-from-bracket"></i></button></div>

    @if ($foto)
    <img class="av" src="{{ $foto }}" alt="{{ $tienda->nombre }}" width="104" height="104">
    @else
    <div class="av ini" aria-hidden="true">{{ $inicial }}</div>
    @endif
    <h1>{{ $tienda->nombre }}</h1>
    @if ($tienda->enlaces_bio)<p class="bio">{{ $tienda->enlaces_bio }}</p>@endif

    @if ($redes->isNotEmpty())
    <nav class="redes" aria-label="Redes sociales">
        @foreach ($redes as $r)
        <a href="{{ $r['url'] }}" target="_blank" rel="noopener" aria-label="{{ $r['nombre'] }}" title="{{ $r['nombre'] }}"><i class="{{ $r['icono'] }}"></i></a>
        @endforeach
    </nav>
    @endif

    <div class="lista">
        @foreach ($enlaces as $e)
            @if ($e->tipo === 'titulo')
            <div class="sep">{{ $e->titulo }}</div>
            @else
            <a class="bt {{ $e->destacado ? 'dest' : '' }}" href="{{ route('enlaces.ir', ['slug' => $tienda->slug, 'enlace' => $e->id]) }}" target="_blank" rel="noopener">
                <span class="i"><i class="{{ $e->icono_final }}"></i></span><span class="t">{{ $e->titulo_mostrar }}</span>
            </a>
            @endif
        @endforeach
        @if ($catalogo)
        <a class="bt" href="{{ $catalogo }}"><span class="i"><i class="fa-solid fa-store"></i></span><span class="t">Ver nuestra tienda en línea</span></a>
        @endif
        @if ($enlaces->isEmpty() && !$catalogo)
        <p class="vacio">Pronto encontrará aquí nuestros enlaces.</p>
        @endif
    </div>

    <div class="pie">
        <a class="marca" href="{{ route('registro') }}"><b>L</b>Cree su página gratis con LocalProo</a>
    </div>
</main>
<div class="toast" id="toast">Enlace copiado</div>
<script>
document.getElementById('compartir').addEventListener('click', async function () {
    var d = { title: @json($tienda->nombre), text: @json($descripcion), url: @json($url) };
    try {
        if (navigator.share) { await navigator.share(d); return; }
        await navigator.clipboard.writeText(d.url);
        var t = document.getElementById('toast'); t.classList.add('on'); setTimeout(function () { t.classList.remove('on'); }, 1800);
    } catch (e) {}
});
</script>
</body>
</html>
