@extends('catalogo._layout')

@php
    $lugarT = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
    $desc = \Illuminate\Support\Str::limit(($tienda->descripcion ?: $tienda->tipo_nombre) . ($lugarT ? ' en ' . $lugarT : '') . '. ' . $productos->count() . ' productos: ' . $categorias->take(5)->implode(', ') . '. Compre por WhatsApp.', 158);
    $lista = $productos->where('disponible', true)->take(30)->values();
@endphp

@section('titulo', $tienda->nombre . ($lugarT ? ' · ' . $lugarT : '') . ' · Tienda en línea')
@section('descripcion', $desc)
@section('canonical', $tienda->url_catalogo)

@push('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Store',
    'name' => $tienda->nombre,
    'description' => $tienda->descripcion ?: $tienda->tipo_nombre,
    'url' => $tienda->url_catalogo,
    'image' => $tienda->logo_url ?: null,
    'telephone' => $tienda->telefono ?: null,
    'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $tienda->direccion, 'addressLocality' => $tienda->ciudad, 'addressRegion' => $tienda->departamento, 'addressCountry' => $tienda->pais]),
    'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Productos de ' . $tienda->nombre, 'itemListElement' => $lista->map(fn ($p, $i) => [
        '@type' => 'Offer', 'position' => $i + 1, 'url' => $p['url'], 'price' => $p['desde'] ?? $p['precio'], 'priceCurrency' => moneda()['moneda'],
        'itemOffered' => array_filter(['@type' => 'Product', 'name' => $p['nombre'], 'image' => $p['img'], 'sku' => $p['codigo']]),
    ])->all()],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@push('css')
.hero{background:var(--k);color:#fff;padding:26px 0 74px;position:relative;overflow:hidden}
.hero::after{content:"";position:absolute;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(47,209,90,.45),transparent 70%);right:-110px;top:-150px}
.hero .wrap{position:relative;z-index:1}
.hero h1{font-size:clamp(1.6rem,5vw,2.6rem);font-weight:800;letter-spacing:-.03em;line-height:1.1}
.hero p{color:#a9b5ad;margin-top:6px;max-width:640px}
.hero .chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.hero .chips span{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:999px;padding:.3rem .75rem;font-size:.8rem;font-weight:700}
.panel{margin-top:-50px;position:relative;z-index:2}
.search{background:#fff;border-radius:20px;box-shadow:0 20px 40px -24px rgba(0,0,0,.35);padding:10px;display:flex;gap:8px;align-items:center}
.search input{flex:1;border:0;outline:none;font:inherit;font-weight:600;font-size:1rem;padding:.6rem .7rem;min-width:0}
.filtros{position:sticky;top:64px;z-index:20;background:var(--bg);padding:12px 0 2px}
.pub{display:flex;gap:8px;margin-bottom:10px}
.pub button{flex:1;max-width:170px;background:#fff;border:1.5px solid var(--line);border-radius:16px;padding:.65rem .5rem;font-weight:800;font-size:.95rem;display:flex;align-items:center;justify-content:center;gap:6px;cursor:pointer}
.pub button.on{background:var(--k);color:#fff;border-color:var(--k)}
.cats{display:flex;gap:8px;overflow-x:auto;padding:0 2px 10px;scrollbar-width:none;-webkit-mask-image:linear-gradient(90deg,#000 85%,transparent);mask-image:linear-gradient(90deg,#000 85%,transparent)}
.cats::-webkit-scrollbar{display:none}
@media (min-width:768px){.cats{flex-wrap:wrap;overflow:visible;-webkit-mask-image:none;mask-image:none}}
.cats a,.cats button{white-space:nowrap;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:.5rem .95rem;font-weight:700;font-size:.88rem}
.cats .on{background:var(--k);color:#fff;border-color:var(--k)}
.cats .promo-chip{border-color:#fecdd3;color:#e11d48;background:#fff1f2}
.cats .promo-chip.on{background:#e11d48;border-color:#e11d48;color:#fff}
.sec{margin-top:18px}
.sec h2{font-size:1.25rem;font-weight:800;margin:6px 2px 12px;display:flex;align-items:baseline;gap:8px}
.sec h2 small{color:var(--mut);font-size:.8rem;font-weight:700}
.vacio{text-align:center;color:var(--mut);padding:50px 10px;font-weight:600}
@endpush

@section('contenido')
<section class="hero">
    <div class="wrap">
        <h1>{{ $tienda->nombre }}</h1>
        <p>{{ $tienda->descripcion ?: $tienda->tipo_nombre }}{{ $lugarT ? ' · ' . $lugarT : '' }}</p>
        <div class="chips"><span>🛍️ {{ $productos->count() }} productos</span><span>💬 Compra por WhatsApp</span>@if ($lugarT)<span>📍 {{ $lugarT }}</span>@endif</div>
    </div>
</section>

<main class="wrap panel">
    <div class="search"><span style="padding-left:6px">🔎</span><input id="q" type="search" placeholder="¿Qué está buscando?" autocomplete="off" aria-label="Buscar productos"></div>
    @if ($categorias->count() > 1 || $publicos->isNotEmpty() || $enPromo)
    <div class="filtros">
        @if ($publicos->isNotEmpty())
        <nav class="pub" id="pub" aria-label="¿Para quién?">
            <button class="on" data-p="">Todo</button>
            @foreach ($publicos as $k => [$ico, $nom])<button data-p="{{ $k }}">{{ $ico }} {{ $nom }}</button>@endforeach
        </nav>
        @endif
        @if ($categorias->count() > 1 || $enPromo)
        <nav class="cats" id="cats" aria-label="Categorías">
            <button class="on" data-c="">Todo</button>
            @if ($enPromo)<button data-c="__promo" class="promo-chip">🔥 Promociones ({{ $enPromo }})</button>@endif
            @foreach ($categorias as $c)<a href="#cat-{{ \Illuminate\Support\Str::slug($c) }}" data-c="{{ $c }}" data-para="{{ $catPara[$c] ?? 'todos' }}">{{ $c }}</a>@endforeach
        </nav>
        @endif
    </div>
    @endif

    @forelse ($secciones as $cat => $items)
    <section class="sec" id="cat-{{ \Illuminate\Support\Str::slug($cat) }}" data-sec="{{ $cat }}">
        @if ($categorias->count() > 1)<h2>{{ $cat }} <small>{{ count($items) }}</small></h2>@endif
        <div class="grid">
            @foreach ($items as $p)
            <article class="card {{ $p['disponible'] ? '' : 'off' }}" data-q="{{ mb_strtolower($p['nombre'] . ' ' . $p['codigo'] . ' ' . implode(' ', $p['categorias'])) }}" data-id="{{ $p['id'] }}">
                <a href="{{ $p['url'] }}" class="ph">
                    @if ($p['img'])<img src="{{ $p['img'] }}" alt="{{ $p['nombre'] }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($p['nombre'], 0, 1)) }}@endif
                    @if (!$p['disponible'])<span class="tag agot">Agotado</span>@elseif (count($p['variantes']) > 1)<span class="tag">{{ count($p['variantes']) }} opciones</span>@endif
                    @if ($p['descuento'] && $p['disponible'])<span class="tag-promo">-{{ $p['descuento'] }}%</span>@endif
                </a>
                <div class="b">
                    <a href="{{ $p['url'] }}"><h3>{{ $p['nombre'] }}</h3></a>
                    <div class="pr">@if ($p['antes'])<span class="antes">@pesos($p['antes'])</span>@endif<span class="{{ $p['antes'] ? 'ahora-promo' : '' }}">@if ($p['desde'])<small>Desde</small>@pesos($p['desde'])@else @pesos($p['precio'])@endif</span> @if ($p['unidad'])<small>/ {{ $p['unidad'] }}</small>@endif</div>
                    @if ($p['disponible'])
                    <div class="bt">
                        @if (count($p['variantes']) > 1)
                        <a class="btn-c" href="{{ $p['url'] }}">Comprar</a>
                        @else
                        <button class="btn-c" data-comprar="{{ $p['id'] }}">💬 Comprar</button>
                        <button class="btn-car" data-agregar="{{ $p['id'] }}" aria-label="Agregar al pedido">+</button>
                        @endif
                    </div>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @empty
    <p class="vacio">Esta tienda está preparando sus productos. ¡Vuelva pronto!</p>
    @endforelse
    <p class="vacio" id="nada" style="display:none">No encontramos productos con esa búsqueda.</p>
</main>
@endsection

@push('js')
<script>
(function () {
    var P = {}; @json($productos).forEach(function (p) { P[p.id] = p; });
    var q = document.getElementById('q'), cat = '', para = '';
    function filtrar() {
        var t = q.value.trim().toLowerCase(), hay = 0;
        document.querySelectorAll('.sec').forEach(function (s) {
            var n = 0;
            s.querySelectorAll('.card').forEach(function (c) {
                var p = P[c.dataset.id], ok = (!t || c.dataset.q.indexOf(t) !== -1) && (!cat || (cat === '__promo' ? p.descuento > 0 : (p.categorias.indexOf(cat) !== -1 || (!p.categorias.length && cat === 'General')))) && (!para || !p.para || p.para === para);
                c.style.display = ok ? '' : 'none'; if (ok) n++;
            });
            s.style.display = n ? '' : 'none'; hay += n;
        });
        document.getElementById('nada').style.display = hay ? 'none' : '';
    }
    q.addEventListener('input', filtrar);
    var cats = document.getElementById('cats'), pub = document.getElementById('pub');
    // Botón "Promociones" de arriba: en esta página filtra sin recargar
    var vp = document.getElementById('btnPromos'), chipPromo = cats && cats.querySelector('[data-c="__promo"]');
    if (vp && chipPromo) vp.addEventListener('click', function (e) { e.preventDefault(); chipPromo.click(); });
    if (chipPromo && new URLSearchParams(location.search).get('ver') === 'promos') setTimeout(function () { chipPromo.click(); }, 50);
    function elegirPara(v) {
        para = v;
        pub.querySelectorAll('[data-p]').forEach(function (x) { x.classList.toggle('on', x.dataset.p === v); });
        // Solo quedan las categorías de ese grupo
        if (cats) {
            cats.querySelectorAll('[data-para]').forEach(function (x) {
                var g = x.dataset.para.split(' ');
                x.style.display = !v || g.indexOf(v) !== -1 || g.indexOf('todos') !== -1 ? '' : 'none';
            });
            var actual = cats.querySelector('.on');
            if (actual && actual.style.display === 'none') { cat = ''; cats.querySelectorAll('[data-c]').forEach(function (x) { x.classList.toggle('on', x.dataset.c === ''); }); }
        }
        try { var u = new URL(location.href); v ? u.searchParams.set('para', v) : u.searchParams.delete('para'); history.replaceState(null, '', u); } catch (e) {}
        filtrar();
    }
    if (pub) {
        pub.addEventListener('click', function (e) {
            var b = e.target.closest('[data-p]'); if (!b) return;
            elegirPara(b.dataset.p);
            window.scrollTo({ top: document.querySelector('.panel').offsetTop - 70, behavior: 'smooth' });
        });
        // Enlace compartido con ?para=mujer abre ya filtrado
        var inicial = new URLSearchParams(location.search).get('para');
        if (inicial && pub.querySelector('[data-p="' + inicial + '"]')) elegirPara(inicial);
    }
    if (cats) cats.addEventListener('click', function (e) {
        var b = e.target.closest('[data-c]'); if (!b) return;
        if (q.value) { q.value = ''; }
        cat = b.dataset.c;
        if (cat) { e.preventDefault(); }
        cats.querySelectorAll('[data-c]').forEach(function (x) { x.classList.toggle('on', x === b); });
        filtrar();
        window.scrollTo({ top: document.querySelector('.panel').offsetTop - 70, behavior: 'smooth' });
    });
    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-comprar]'), a = e.target.closest('[data-agregar]');
        if (c) { var p = P[c.dataset.comprar]; LPC.comprar(p, p.variantes[0], 1); }
        if (a) { var p2 = P[a.dataset.agregar]; LPC.agregar(p2, p2.variantes[0], 1); }
    });
})();
</script>
@endpush
