@php
    $M = moneda();
    $wa = numero_whatsapp($tienda->telefono);
    $lugar = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo')</title>
<meta name="description" content="@yield('descripcion')">
@php $canonico = str_replace('://www.', '://', trim($__env->yieldContent('canonical'))); @endphp
<link rel="canonical" href="{{ $canonico }}">
<meta name="robots" content="{{ $tienda->en_google ? 'index, follow, max-image-preview:large' : 'noindex, follow' }}">
<meta property="og:type" content="@yield('og_tipo', 'website')">
<meta property="og:site_name" content="{{ $tienda->nombre }}">
<meta property="og:title" content="@yield('titulo')">
<meta property="og:description" content="@yield('descripcion')">
<meta property="og:url" content="{{ $canonico }}">
@hasSection('imagen')<meta property="og:image" content="@yield('imagen')">@elseif ($tienda->logo_url)<meta property="og:image" content="{{ $tienda->logo_url }}">@endif
@if (str_contains($__env->yieldContent('imagen'), '/compartir.jpg'))<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:type" content="image/jpeg">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0b0d0c">
@if ($tienda->logo_url)<link rel="icon" href="{{ $tienda->logo_url }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
@stack('jsonld')
<style>
/* El texto alternativo de una foto que no carga nunca se ve gigante */
img{color:transparent;font-size:0}
.foto-rota{display:grid;place-items:center;width:100%;height:100%;font-weight:800}

:root{--g:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);--g2:#16b34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--wa:#25d366}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;scroll-padding-top:130px}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;min-height:100vh;display:flex;flex-direction:column}
html{background:var(--k)}
a{color:inherit;text-decoration:none}
button{font:inherit;border:0;background:none;cursor:pointer;color:inherit}
.wrap{width:min(1180px,100% - 24px);margin:0 auto}
/* Barra superior */
.top{position:sticky;top:0;z-index:30;background:rgba(11,13,12,.94);backdrop-filter:blur(10px);color:#fff}
.top .wrap{display:flex;align-items:center;gap:12px;height:64px}
.top .marca{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
.top .lg{width:40px;height:40px;border-radius:12px;background:#fff;display:grid;place-items:center;overflow:hidden;flex:none;font-weight:800;color:var(--g2)}
.top .lg img{width:100%;height:100%;object-fit:cover}
.top b{font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top .acc{display:flex;gap:8px}
.top .ib{height:40px;border-radius:12px;background:rgba(255,255,255,.1);display:flex;align-items:center;gap:6px;padding:0 12px;font-weight:700;font-size:.88rem;color:#fff;position:relative}
.top .ib.wa{background:var(--wa)}
.top .ib.promo{background:rgba(225,29,72,.18);border:1px solid rgba(225,29,72,.5);color:#ffd5dd}
.top .ib.promo:hover{background:rgba(225,29,72,.3)}
.top .ib .n{position:absolute;top:-5px;right:-5px;background:#a6f34a;color:var(--k);border-radius:999px;font-size:.7rem;font-weight:800;min-width:20px;height:20px;display:none;place-items:center;padding:0 5px}
.top .ib .n.on{display:grid}
@media(max-width:520px){.top .d-sm{display:none}}
/* Tarjetas de producto */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
@media(max-width:520px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}}
.subir-top{position:fixed;right:14px;bottom:calc(90px + env(safe-area-inset-bottom));z-index:35;width:48px;height:48px;border-radius:50%;border:0;background:var(--k);color:#a6f34a;font-size:1.3rem;font-weight:900;box-shadow:0 10px 24px -8px rgba(0,0,0,.55);cursor:pointer;opacity:0;transform:translateY(12px);pointer-events:none;transition:opacity .2s,transform .2s}
.subir-top.ver{opacity:1;transform:none;pointer-events:auto}
.card{background:#fff;border:1.5px solid var(--line);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;min-width:0}
.card:hover{transform:translateY(-3px);box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.card.off{opacity:.55}
.card .ph{aspect-ratio:1/1;background:var(--bg);display:grid;place-items:center;font-size:2.6rem;font-weight:800;color:var(--g2);overflow:hidden;position:relative}
.card .ph img{width:100%;height:100%;object-fit:contain;object-position:center;background:#fff;transition:transform .3s}
.card:hover .ph img{transform:scale(1.04)}
.moda .card .ph{aspect-ratio:3/4}
.card .tag{position:absolute;left:10px;top:10px;background:#fff;border-radius:999px;padding:.2rem .6rem;font-size:.7rem;font-weight:800;box-shadow:0 4px 10px -6px rgba(0,0,0,.4)}
.card .tag.agot{background:#b0206a;color:#fff}
.card .tag-promo{position:absolute;right:10px;top:10px;background:#e11d48;color:#fff;border-radius:999px;padding:.22rem .6rem;font-size:.75rem;font-weight:900;box-shadow:0 4px 10px -6px rgba(0,0,0,.5)}
/* Lápiz para editar (solo lo ve el dueño de la tienda con sesión iniciada) */
.card .b.con-edit{position:relative}
.lp-edit{position:absolute;right:10px;top:-46px;z-index:3;width:36px;height:36px;border-radius:50%;background:var(--k);color:#fff;display:grid;place-items:center;font-size:.95rem;box-shadow:0 6px 14px -6px rgba(0,0,0,.6);text-decoration:none}
.lp-edit:hover{background:#16b34a;color:#fff}
.lp-edit-pp{display:inline-flex;align-items:center;gap:6px;background:var(--k);color:#fff;border-radius:999px;padding:.25rem .7rem;font-weight:800;font-size:.75rem;margin-top:6px;text-decoration:none}
.lp-edit-pp:hover{background:#16b34a;color:#fff}
.lp-dueno{position:fixed;left:14px;bottom:14px;z-index:60;background:var(--k);color:#fff;border-radius:999px;padding:.5rem .6rem .5rem 1rem;font-size:.82rem;font-weight:700;display:flex;gap:10px;align-items:center;box-shadow:0 12px 30px -12px rgba(0,0,0,.6);white-space:nowrap}
.lp-dueno a{background:#16b34a;color:#fff;border-radius:999px;padding:.3rem .8rem;text-decoration:none;font-weight:800}
.antes{color:var(--mut);text-decoration:line-through;font-weight:600;font-size:.8em;margin-right:4px}
.pr .ahora-promo{color:#e11d48}
.card .b{padding:10px 12px 12px;display:flex;flex-direction:column;gap:4px;flex:1}
.card h3{font-size:.95rem;font-weight:700;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card .pr{font-weight:800;font-size:1.08rem}
.card .pr small{font-weight:700;color:var(--mut);font-size:.72rem;margin-right:3px}
.card .op{color:var(--mut);font-size:.75rem;font-weight:600}
.card .bt{display:flex;gap:6px;margin-top:auto;padding-top:8px}
.btn-c{flex:1;height:42px;border-radius:13px;background:var(--wa);color:#fff;font-weight:800;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-c:hover{filter:brightness(1.05)}
.btn-car{width:42px;height:42px;border-radius:13px;background:var(--bg);font-size:1.15rem;font-weight:800;flex:none;display:grid;place-items:center}
.btn-car:hover{background:#e3efe5}
/* Carrito */
.ov{position:fixed;inset:0;background:rgba(11,13,12,.55);display:none;align-items:flex-end;justify-content:center;z-index:40}
.ov.on{display:flex}
.sheet{background:#fff;width:min(560px,100%);max-height:88vh;overflow:auto;border-radius:26px 26px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));animation:up .25s cubic-bezier(.2,.8,.2,1)}
@keyframes up{from{transform:translateY(40px);opacity:0}}
@media(min-width:700px){.ov{align-items:center}.sheet{border-radius:26px}}
.sh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}
.sh h3{font-size:1.25rem;font-weight:800}
.x{width:38px;height:38px;border-radius:12px;background:var(--bg);font-size:1.2rem;flex:none}
.it{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px dashed var(--line)}
.it .t{flex:1;min-width:0}.it .t b{display:block;font-size:.92rem}.it .t small{color:var(--mut);font-weight:600}
.it .q{display:flex;align-items:center;background:var(--bg);border-radius:12px}.it .q button{width:32px;height:32px;font-weight:800}
.tot{display:flex;justify-content:space-between;font-weight:800;font-size:1.2rem;margin-top:12px}
.in{width:100%;border:2px solid var(--line);border-radius:14px;padding:.75rem .9rem;font:inherit;font-weight:600;margin-top:8px}
.btn{width:100%;height:56px;border-radius:18px;font-weight:800;font-size:1.05rem;margin-top:14px;display:flex;align-items:center;justify-content:center;gap:.5rem}
.btn-wa{background:var(--wa);color:#fff}.btn-g{background:var(--g);color:var(--k)}.btn-l{background:var(--bg);color:var(--k)}
.btn:disabled{opacity:.4}
.bar{position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));height:64px;border-radius:20px;background:var(--k);color:#fff;display:none;align-items:center;justify-content:space-between;padding:0 18px;font-weight:800;z-index:30;box-shadow:0 18px 40px -16px rgba(0,0,0,.6);max-width:640px;margin:0 auto}
.bar.on{display:flex}
.bar .n{background:var(--g);color:var(--k);border-radius:999px;padding:.15rem .6rem;margin-right:.5rem}
.toast{position:fixed;left:50%;top:76px;transform:translate(-50%,-10px);background:var(--k);color:#fff;border-radius:14px;padding:.7rem 1rem;font-weight:700;opacity:0;transition:all .25s;z-index:50;pointer-events:none}
.toast.on{opacity:1;transform:translate(-50%,0)}
/* Pie */
.pie{background:var(--k);color:#a9b5ad;margin-top:auto;padding:30px 0 110px;font-size:.88rem}
main,.pie-sep{flex:none}
.pie-sep{height:40px}
.comp{width:100%;background:linear-gradient(135deg,#16211a,#0f1411);border:1px solid rgba(166,243,74,.18);border-radius:22px;padding:18px 18px 16px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:6px}
.comp .t{flex:1 1 220px;min-width:0}
.comp .t b{color:#fff;font-size:1.15rem;margin:0}
.comp .t span{display:block;color:#a9b5ad;font-size:.85rem;margin-top:2px}
.comp .r{display:flex;gap:8px;flex-wrap:wrap}
.comp a,.comp button{display:inline-flex;align-items:center;gap:7px;height:44px;padding:0 15px;border-radius:999px;border:0;font:inherit;font-weight:800;font-size:.86rem;cursor:pointer;text-decoration:none;color:#0b0d0c;background:#fff;transition:transform .12s}
.comp a:hover,.comp button:hover{transform:translateY(-2px)}
.comp svg{width:18px;height:18px;flex:none}
.comp .c-main{background:var(--g);color:var(--k)}
.comp .c-wa{background:#25d366;color:#fff}.comp .c-fb{background:#1877f2;color:#fff}.comp .c-x{background:#fff;color:#000}.comp .c-tg{background:#29a9eb;color:#fff}
.comp .c-cp{background:#1d2520;color:#a6f34a;border:1px solid rgba(166,243,74,.35)}
@media (max-width:560px){.comp .r{width:100%}.comp a,.comp button{flex:1 1 auto;justify-content:center}.comp .c-main{width:100%}}
.pie .wrap{display:flex;gap:24px;flex-wrap:wrap;justify-content:space-between}
.pie b{color:#fff;display:block;margin-bottom:6px;font-size:1rem}
.pie a{color:#a6f34a;font-weight:700}
.pie .lp{width:100%;text-align:center;border-top:1px solid rgba(255,255,255,.1);padding-top:18px;margin-top:6px;font-size:.78rem}
.pie .lp b{color:#a6f34a}
.pie .lp{display:flex;flex-direction:column;align-items:center;gap:10px}
.pie .lp .hecha{display:block;margin-bottom:2px}
.pie .lp a.crear{display:block;width:100%;max-width:380px;background:#a6f34a;color:#0b0d0c;border-radius:16px;padding:.65rem 1rem;font-weight:800;line-height:1.3;text-decoration:none}
.pie .lp a.crear:hover{background:#fff}
.pie .lp .visitas{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:4px;color:rgba(255,255,255,.42);font-size:.72rem;font-weight:600;letter-spacing:.02em}
.pie .lp .visitas i{width:6px;height:6px;border-radius:50%;background:#a6f34a;box-shadow:0 0 0 3px rgba(166,243,74,.15);animation:lpLatido 2.4s ease-in-out infinite}
@keyframes lpLatido{50%{box-shadow:0 0 0 6px rgba(166,243,74,0)}}
@stack('css')
</style>
<script>
// Foto que no carga (perdida en el servidor o mala conexión): en vez del texto gigante sale la inicial del producto
document.addEventListener('error', function (e) {
    var i = e.target;
    if (!i || i.tagName !== 'IMG' || i.dataset.rota) return;
    i.dataset.rota = 1;
    var t = (i.getAttribute('alt') || '').trim();
    var s = document.createElement('span');
    s.className = 'foto-rota';
    s.textContent = t ? t.charAt(0).toUpperCase() : '🛍️';
    i.replaceWith(s);
}, true);
</script>
</head>
{{-- Tiendas de ropa y calzado: fotos verticales 3:4 (como las tiendas de moda); los demás negocios en cuadrado --}}
<body class="{{ ($tienda->usa_tallas ?? false) ? 'moda' : '' }}">
<header class="top">
    <div class="wrap">
        <a class="marca" href="{{ $tienda->url_catalogo }}">
            <span class="lg">@if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="{{ $tienda->nombre }}">@else{{ mb_substr($tienda->nombre, 0, 1) }}@endif</span>
            <b>{{ $tienda->nombre }}</b>
        </a>
        <div class="acc">
            @php $nPromo = \App\Models\Producto::where('estado', 1)->whereNotNull('precio_antes')->whereColumn('precio_antes', '>', 'precio_venta')->count(); @endphp
            @if ($nPromo)<a class="ib promo" id="btnPromos" href="{{ $tienda->url_catalogo }}?ver=promos" aria-label="Promociones">🔥 <span class="d-sm">Promociones</span></a>@endif
            @if ($tienda->usa_carta)<a class="ib" href="{{ $tienda->url_carta }}" aria-label="Ver la carta">🍽️ <span class="d-sm">Carta</span></a>@endif
            @if ($wa)<a class="ib wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="WhatsApp">💬 <span class="d-sm">WhatsApp</span></a>@endif
            <button class="ib" id="btnCarrito" aria-label="Carrito">🛒<span class="n" id="topN">0</span></button>
        </div>
    </div>
</header>

@yield('contenido')

<div class="pie-sep"></div>
<footer class="pie">
    <div class="wrap">
        @php
            $urlT = $tienda->url_catalogo;
            $txtT = '¡Mira la tienda de ' . $tienda->nombre . '! Vea los productos y compre por WhatsApp:';
            // Datos para la imagen tipo estado de la tienda: hasta 4 fotos de productos disponibles
            $listaT = collect($productos ?? $relacionados ?? []);
            $compartirTienda = [
                'nombre' => $tienda->nombre,
                'lugar' => trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null]))),
                'logo' => $tienda->logo_url,
                'fotos' => $listaT->filter(fn ($x) => ($x['disponible'] ?? true) && !empty($x['img']))->pluck('img')->unique()->take(4)->values()->all(),
                'cuantos' => isset($productos) ? $productos->count() : 0,
            ];
        @endphp
        <div class="comp">
            <div class="t"><b>📣 Comparte esta tienda</b><span>Ayúdanos a llegar a más personas: envíala a tus amigos y grupos.</span></div>
            <div class="r">
                <button type="button" class="c-main" id="cShare"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>Comparte tu tienda</button>
                <a class="c-wa" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($txtT . ' ' . $urlT) }}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.3 5.2 4.6 2.6 1 3.1.8 3.7.8.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.2-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.3c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.3 8.3 0 1 1 12 20.3z"/></svg>WhatsApp</a>
                <a class="c-fb" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($urlT) }}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.3C16.7 2.2 15.6 2 14.4 2 11.9 2 10.2 3.5 10.2 6.3v2.2H7.5V12h2.7v10H14V12h2.7l.4-3.5H14z"/></svg>Facebook</a>
                <a class="c-x" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text={{ rawurlencode($txtT) }}&url={{ rawurlencode($urlT) }}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.8 2.5h3.3l-7.2 8.2 8.5 10.8h-6.6l-5.2-6.6-6 6.6H1.3l7.7-8.8L.9 2.5h6.8l4.7 6.1 5.4-6.1zm-1.2 17h1.8L7.1 4.3H5.2l11.4 15.2z"/></svg>X</a>
                <a class="c-tg" target="_blank" rel="noopener" href="https://t.me/share/url?url={{ rawurlencode($urlT) }}&text={{ rawurlencode($txtT) }}"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2l-11 6.9-4.7-1.5c-1-.3-1-1 .2-1.5L20.5 3c.9-.3 1.6.2 1.4 1.3z"/></svg>Telegram</a>
                <button type="button" class="c-cp" id="cCopy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Copiar enlace</button>
            </div>
        </div>
        <div><b>{{ $tienda->nombre }}</b>{{ $tienda->descripcion ?: $tienda->tipo_nombre }}</div>
        <div>
            @if ($tienda->direccion || $lugar)<div>📍 {{ trim($tienda->direccion . ($tienda->direccion && $lugar ? ' · ' : '') . $lugar) }}</div>@endif
            @if ($wa)<div>💬 <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">Escríbanos por WhatsApp</a></div>@endif
        </div>
        @php $urlCrear = (app()->environment('production') && config('app.dominio') ? 'https://' . config('app.dominio') . '/registro' : route('registro')) . '?desde=' . rawurlencode('tienda:' . \Illuminate\Support\Str::limit($tienda->slug, 70, '')) . ($tienda->tipo_negocio ? '&tipo=' . rawurlencode($tienda->tipo_negocio) : ''); @endphp
        <div class="lp"><span class="hecha">Tienda en línea creada con <b>LocalPro</b></span>
            <a class="crear" href="{{ $urlCrear }}">¿Tiene un negocio? Cree su tienda aquí →</a>
            <a class="crear" href="{{ route('marketplace', $tienda->ciudad ? ['ciudad' => $tienda->ciudad] : []) }}">🛍️ Compre en nuestro Marketplace: productos impulsados por LocalProo</a>
            @if ((int) $tienda->catalogo_visitas > 0)<span class="visitas" title="Visitas a esta tienda en línea"><i></i>{{ number_format((int) $tienda->catalogo_visitas, 0, ',', '.') }} {{ (int) $tienda->catalogo_visitas === 1 ? 'visita' : 'visitas' }}</span>@endif
        </div>
    </div>
</footer>

<button class="bar" id="bar"><span><span class="n" id="barN">0</span>Ver mi pedido</span><span id="barT"></span></button>
<div class="toast" id="toast"></div>
<script>
(function () {
    var url = @json($tienda->url_catalogo), txt = @json('¡Mira la tienda de ' . $tienda->nombre . '! Vea los productos y compre por WhatsApp');
    function aviso(m) { var t = document.getElementById('toast'); if (!t) return; t.textContent = m; t.classList.add('on'); setTimeout(function () { t.classList.remove('on'); }, 2200); }
    function copiar() {
        var viejo = function () { var i = document.createElement('input'); i.value = url; document.body.appendChild(i); i.select(); try { document.execCommand('copy'); aviso('✅ Enlace copiado'); } catch (e) { aviso(url); } i.remove(); };
        if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { aviso('✅ Enlace copiado'); }, viejo); else viejo();
    }
    var s = document.getElementById('cShare'), c = document.getElementById('cCopy');
    if (c) c.addEventListener('click', copiar);
    // ===== Compartir la tienda con imagen tipo estado (1080x1920): logo, nombre, fotos de productos + link en el texto =====
    var T = @json($compartirTienda), texto = txt + ':\n👉 ' + url, archivo = null, preparando = null;
    function cargar(src) {
        if (!src) return Promise.resolve(null);
        var u; try { u = new URL(src, location.href); } catch (e) { return Promise.resolve(null); }
        return fetch(u.pathname + u.search).then(function (r) { if (!r.ok) throw 0; return r.blob(); })
            .then(function (b) { return new Promise(function (ok) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = URL.createObjectURL(b); }); })
            .catch(function () { return null; });
    }
    function redondo(x, a, b, w, h, r) { x.beginPath(); x.moveTo(a + r, b); x.arcTo(a + w, b, a + w, b + h, r); x.arcTo(a + w, b + h, a, b + h, r); x.arcTo(a, b + h, a, b, r); x.arcTo(a, b, a + w, b, r); x.closePath(); }
    function encajar(x, img, a, b, w, h, pad) { var k = Math.min((w - pad * 2) / img.naturalWidth, (h - pad * 2) / img.naturalHeight), dw = img.naturalWidth * k, dh = img.naturalHeight * k; x.drawImage(img, a + (w - dw) / 2, b + (h - dh) / 2, dw, dh); }
    var F = "'Plus Jakarta Sans', system-ui, sans-serif";
    // Fondo oscuro con brillo verde LocalProo
    function fondo(x, W, H) {
        x.fillStyle = '#0b0f0c'; x.fillRect(0, 0, W, H);
        var r1 = x.createRadialGradient(W * .15, H * .1, 0, W * .15, H * .1, W * .9); r1.addColorStop(0, 'rgba(47,209,90,.35)'); r1.addColorStop(1, 'rgba(47,209,90,0)'); x.fillStyle = r1; x.fillRect(0, 0, W, H);
        var r2 = x.createRadialGradient(W * .9, H * .75, 0, W * .9, H * .75, W * .8); r2.addColorStop(0, 'rgba(166,243,74,.22)'); r2.addColorStop(1, 'rgba(166,243,74,0)'); x.fillStyle = r2; x.fillRect(0, 0, W, H);
    }
    // Foto que llena su espacio (recorta lo que sobra), con esquinas redondas
    function cubrir(x, im, a, b, w, h, r) {
        var k = Math.max(w / im.naturalWidth, h / im.naturalHeight), dw = im.naturalWidth * k, dh = im.naturalHeight * k;
        x.save(); redondo(x, a, b, w, h, r); x.clip(); x.drawImage(im, a + (w - dw) / 2, b + (h - dh) / 2, dw, dh); x.restore();
    }
    function linea(x, t, ancho) { if (x.measureText(t).width <= ancho) return t; while (t.length > 1 && x.measureText(t + '…').width > ancho) t = t.slice(0, -1); return t + '…'; }
    function preparar() {
        if (archivo) return Promise.resolve(archivo);
        if (preparando) return preparando;
        preparando = Promise.all([cargar(T.logo)].concat(T.fotos.map(cargar)).concat([document.fonts ? document.fonts.ready : null])).then(function (r) {
            var logo = r[0], fotos = r.slice(1, 1 + T.fotos.length).filter(Boolean), W = 1080, H = 1920, cv = document.createElement('canvas'); cv.width = W; cv.height = H;
            var x = cv.getContext('2d');
            fondo(x, W, H);
            // Marca arriba (debajo de la barra de WhatsApp)
            x.textAlign = 'right'; x.fillStyle = 'rgba(255,255,255,.55)'; x.font = '700 30px ' + F; x.fillText('localproo.com', W - 70, 205);
            x.textAlign = 'left'; x.fillStyle = '#a6f34a'; x.font = '800 30px ' + F; x.fillText('TIENDA EN LÍNEA', 70, 205);
            // Collage de productos a sangre (sin fondo)
            var n = Math.min(4, fotos.length), X0 = 70, Y0 = 250, AW = W - 140, AH = 920, g = 18, half = (AW - g) / 2, hh = (AH - g) / 2;
            var cajas = n === 1 ? [[0, 0, AW, AH]] : n === 2 ? [[0, 0, half, AH], [half + g, 0, half, AH]]
                : n === 3 ? [[0, 0, half, AH], [half + g, 0, half, hh], [half + g, hh + g, half, hh]]
                : [[0, 0, half, hh], [half + g, 0, half, hh], [0, hh + g, half, hh], [half + g, hh + g, half, hh]];
            if (n) fotos.slice(0, 4).forEach(function (im, i) { var c = cajas[i]; cubrir(x, im, X0 + c[0], Y0 + c[1], c[2], c[3], 44); });
            else { x.fillStyle = 'rgba(166,243,74,.12)'; redondo(x, X0, Y0, AW, AH, 44); x.fill(); }
            // Logo sobre el collage
            var lg = 190, lx = W / 2 - lg / 2, ly = Y0 + AH - lg / 2;
            x.save(); x.shadowColor = 'rgba(0,0,0,.45)'; x.shadowBlur = 40; x.shadowOffsetY = 12; redondo(x, lx, ly, lg, lg, 56); x.fillStyle = '#fff'; x.fill(); x.restore();
            x.save(); redondo(x, lx, ly, lg, lg, 56); x.clip();
            if (logo) encajar(x, logo, lx, ly, lg, lg, 14);
            else { var gl = x.createLinearGradient(lx, ly, lx + lg, ly + lg); gl.addColorStop(0, '#a6f34a'); gl.addColorStop(1, '#16b34a'); x.fillStyle = gl; x.fillRect(lx, ly, lg, lg); x.fillStyle = '#0b0d0c'; x.font = '800 110px ' + F; x.textAlign = 'center'; x.fillText(T.nombre.charAt(0).toUpperCase(), W / 2, ly + 132); }
            x.restore();
            // Nombre grande, ciudad y chip
            x.textAlign = 'center'; x.fillStyle = '#ffffff';
            var yN = ly + lg + 92, tam = 74;
            x.font = '800 ' + tam + 'px ' + F;
            while (x.measureText(T.nombre).width > W - 120 && tam > 56) { tam -= 4; x.font = '800 ' + tam + 'px ' + F; }
            if (x.measureText(T.nombre).width <= W - 120) x.fillText(T.nombre, W / 2, yN);
            else { var pal = T.nombre.split(/\s+/), l1 = '', k = 0; for (; k < pal.length; k++) { var t1 = l1 ? l1 + ' ' + pal[k] : pal[k]; if (x.measureText(t1).width > W - 120 && l1) break; l1 = t1; } x.fillText(l1, W / 2, yN); yN += tam + 6; x.fillText(linea(x, pal.slice(k).join(' '), W - 120), W / 2, yN); }
            if (T.lugar) { x.fillStyle = 'rgba(255,255,255,.7)'; x.font = '600 38px ' + F; x.fillText(linea(x, T.lugar, W - 160), W / 2, yN + 58); yN += 58; }
            var chip = (T.cuantos > 1 ? T.cuantos + ' productos · ' : '') + 'Compre por WhatsApp';
            x.font = '800 36px ' + F; var cw2 = x.measureText(chip).width + 80;
            var gc = x.createLinearGradient(W / 2 - cw2 / 2, 0, W / 2 + cw2 / 2, 0); gc.addColorStop(0, '#a6f34a'); gc.addColorStop(1, '#2fd15a');
            x.fillStyle = gc; redondo(x, W / 2 - cw2 / 2, yN + 34, cw2, 76, 38); x.fill();
            x.fillStyle = '#0b0d0c'; x.fillText(chip, W / 2, yN + 85);
            x.textAlign = 'left';
            return new Promise(function (ok) { cv.toBlob(ok, 'image/jpeg', 0.9); });
        }).then(function (b) {
            if (!b) throw 0;
            archivo = new File([b], 'tienda-' + (T.nombre || 'tienda').toLowerCase().normalize('NFD').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 40) + '.jpg', { type: 'image/jpeg' });
            return archivo;
        }).finally(function () { preparando = null; });
        return preparando;
    }
    function puede(f) { try { return !!(navigator.canShare && navigator.share && navigator.canShare({ files: [f] })); } catch (e) { return false; } }
    function soloTexto() {
        if (navigator.share) navigator.share({ title: document.title, text: txt, url: url }).catch(function () {});
        else window.open('https://wa.me/?text=' + encodeURIComponent(txt + ': ' + url), '_blank');
    }
    if (s) {
        var html0 = s.innerHTML;
        s.addEventListener('click', function () {
            function ir(f) {
                if (!puede(f)) return soloTexto();
                navigator.share({ files: [f], text: texto }).catch(function (e) { if (e && e.name !== 'AbortError') s.innerHTML = html0.replace('Comparte tu tienda', 'Listo: toque de nuevo'); });
            }
            if (archivo) { s.innerHTML = html0; ir(archivo); return; }
            s.disabled = true; s.innerHTML = html0.replace('Comparte tu tienda', 'Preparando imagen…');
            preparar().then(function (f) { s.disabled = false; s.innerHTML = html0; ir(f); }).catch(function () { s.disabled = false; s.innerHTML = html0; soloTexto(); });
        });
        // Lista apenas carga (el iPhone exige compartir enseguida del toque)
        setTimeout(function () { preparar().catch(function () {}); }, 2500);
    }
})();
</script>

<div class="ov" id="ovC"><div class="sheet">
    <div class="sh"><h3>Mi pedido</h3><button class="x" data-close>&times;</button></div>
    <div id="cItems"></div>
    <div class="tot"><span>Total</span><span id="cTot"></span></div>
    <input class="in" id="cNom" placeholder="Su nombre" maxlength="60">
    <input class="in" id="cNota" placeholder="Dirección de entrega o nota (opcional)" maxlength="200">
    @if ($wa)
    <button class="btn btn-wa" id="cEnviar">💬 Hacer el pedido por WhatsApp</button>
    @else
    <p style="color:#b0206a;font-weight:700;margin-top:12px">Esta tienda aún no tiene WhatsApp configurado.</p>
    @endif
</div></div>

<script src="{{ asset('js/wa-limpio.js') }}?v=1"></script>
<script>
// Carrito y botón Comprar (WhatsApp con el producto exacto). Compartido entre la tienda y cada producto.
window.LPC = (function () {
    // Así el negocio sabe que el cliente llegó por la tienda en línea
    // Sin emojis: algunos WhatsApp (computador) los muestran como "�"
    var FIRMA = '\n\n_Visto en LocalProo Marketplace_';
    var TASA_BS = {{ tasa_bs() }};
    var bsTxt = function (n) { if (!(TASA_BS > 0)) return ''; var r = Math.round(n * TASA_BS * 100) / 100, t = r.toFixed(2).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.'); return 'Bs ' + t.join(','); };
    var M = @json($M), WA = @json($wa), TIENDA = @json($tienda->nombre), KEY = 'lp_pedido_' + @json($tienda->slug);
    var $ = function (id) { return document.getElementById(id); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    var cart = []; try { cart = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { cart = []; }
    var guardar = function () { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} };
    function total() { return cart.reduce(function (a, i) { return a + i.precio * i.cant; }, 0); }
    function toast(t) { var x = $('toast'); x.textContent = t; x.classList.add('on'); clearTimeout(x._t); x._t = setTimeout(function () { x.classList.remove('on'); }, 1800); }
    function barra() {
        var n = cart.reduce(function (a, i) { return a + i.cant; }, 0);
        $('bar').classList.toggle('on', n > 0); $('barN').textContent = n; $('barT').textContent = fmt(total());
        $('topN').textContent = n; $('topN').classList.toggle('on', n > 0);
    }
    function pintar() {
        $('cItems').innerHTML = cart.length ? cart.map(function (i, k) {
            return '<div class="it"><div class="t"><b>' + esc(i.nombre) + '</b><small>' + (i.codigo ? 'Cód. ' + esc(i.codigo) + ' · ' : '') + esc(i.op) + (i.op ? ' · ' : '') + fmt(i.precio) + '</small></div>' +
                '<div class="q"><button data-k="' + k + '" data-d="-1">−</button><span style="min-width:22px;text-align:center;font-weight:800">' + i.cant + '</span><button data-k="' + k + '" data-d="1">+</button></div></div>';
        }).join('') : '<p style="color:var(--mut)">Su pedido está vacío.</p>';
        $('cTot').textContent = fmt(total()) + (TASA_BS > 0 ? ' · ' + bsTxt(total()) : '');
        if ($('cEnviar')) $('cEnviar').disabled = !cart.length;
    }
    function abrir() { pintar(); $('ovC').classList.add('on'); }
    $('cItems').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var i = cart[+b.dataset.k]; i.cant += +b.dataset.d; if (i.cant <= 0) cart.splice(+b.dataset.k, 1);
        guardar(); barra(); pintar();
    });
    $('bar').addEventListener('click', abrir);
    $('btnCarrito').addEventListener('click', abrir);
    document.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('.ov').classList.remove('on'); }); });
    document.querySelectorAll('.ov').forEach(function (o) { o.addEventListener('click', function (e) { if (e.target === o) o.classList.remove('on'); }); });
    if ($('cEnviar')) $('cEnviar').addEventListener('click', function () {
        var nom = $('cNom').value.trim(), nota = $('cNota').value.trim();
        var msg = '¡Hola ' + TIENDA + '! Quiero hacer este pedido:\n\n' +
            cart.map(function (i) { return '- ' + i.cant + ' × ' + i.nombre + (i.op ? ' (' + i.op + ')' : '') + (i.codigo ? ' [Cód. ' + i.codigo + ']' : '') + ' — ' + fmt(i.precio * i.cant); }).join('\n') +
            '\n\n*Total: ' + fmt(total()) + (TASA_BS > 0 ? ' (' + bsTxt(total()) + ')' : '') + '*' + (nom ? '\nNombre: ' + nom : '') + (nota ? '\nEntrega / nota: ' + nota : '') + FIRMA;
        window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
    });
    barra();
    return {
        fmt: fmt, esc: esc,
        agregar: function (p, v, cant) {
            cant = cant || 1;
            var ex = cart.find(function (x) { return x.id === v.id; });
            if (ex) ex.cant += cant; else cart.push({ id: v.id, nombre: p.nombre, codigo: p.codigo, op: p.variantes.length > 1 ? v.etiqueta : '', precio: v.precio, cant: cant });
            guardar(); barra(); toast('✓ ' + p.nombre + ' agregado al pedido');
        },
        // "Comprar": abre WhatsApp diciendo exactamente qué producto quiere
        comprar: function (p, v, cant) {
            if (!WA) { toast('Esta tienda aún no tiene WhatsApp'); return; }
            cant = cant || 1;
            var msg = '¡Hola ' + TIENDA + '! Quiero este producto:\n\n' +
                '*' + p.nombre + '*\n' +
                (p.codigo ? 'Código: ' + p.codigo + '\n' : '') +
                (v && p.variantes.length > 1 ? 'Opción: ' + v.etiqueta + '\n' : '') +
                'Cantidad: ' + cant + (p.unidad ? ' ' + p.unidad : '') + '\n' +
                'Precio: ' + fmt((v ? v.precio : p.precio) * cant) + '\n\n' + p.url + FIRMA;
            window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(msg), '_blank');
        }
    };
})();
</script>
<button type="button" class="subir-top" id="subirTop" aria-label="Subir al inicio" title="Subir">↑</button>
<script>(function(){var b=document.getElementById('subirTop');if(!b)return;var f=function(){b.classList.toggle('ver',window.scrollY>700)};window.addEventListener('scroll',f,{passive:true});f();b.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})});})();</script>
@stack('js')
@php $edUrl = \App\Http\Controllers\catalogoController::puedeEditar($tienda) ? url('/productos/__ID__/edit') : null; @endphp
<script>
// Si quien mira es el dueño (o alguien de la tienda) con sesión iniciada, sale un lápiz para editar cada producto
(function () {
    function pintar(ed, panel) {
        document.querySelectorAll('.card[data-id] .b').forEach(function (b) {
            if (b.querySelector('.lp-edit')) return;
            var a = document.createElement('a');
            a.className = 'lp-edit'; a.href = ed.replace('__ID__', b.closest('.card').dataset.id);
            a.title = 'Editar producto'; a.setAttribute('aria-label', 'Editar producto'); a.textContent = '✏️';
            b.classList.add('con-edit'); b.appendChild(a);
        });
        var h = document.querySelector('[data-editar-id]');
        if (h && !document.querySelector('.lp-edit-pp')) {
            var e = document.createElement('a');
            e.className = 'lp-edit-pp'; e.href = ed.replace('__ID__', h.dataset.editarId); e.textContent = '✏️ Editar este producto';
            h.insertAdjacentElement('afterend', e);
        }
        if (panel && !document.querySelector('.lp-dueno')) {
            var d = document.createElement('div');
            d.className = 'lp-dueno';
            d.innerHTML = '👀 Así ven su tienda sus clientes <a href="' + panel + '">Ir al panel</a>';
            document.body.appendChild(d);
        }
    }
    var ed = @json($edUrl), panel = @json($edUrl ? route('productos.index') : null);
    if (ed) return pintar(ed, panel);
    // Abierta con www: se pregunta al dominio principal, donde está la sesión
    if (/^www\./.test(location.hostname)) {
        fetch(@json('https://' . config('app.dominio') . '/t/' . $tienda->slug . '/yo'), { credentials: 'include' })
            .then(function (r) { return r.ok ? r.json() : {}; })
            .then(function (d) { if (d && d.editar) pintar(d.editar, d.panel); })
            .catch(function () {});
    }
})();
</script>
</body>
</html>
