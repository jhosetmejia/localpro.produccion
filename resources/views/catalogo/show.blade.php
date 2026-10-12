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
.cats{position:sticky;top:64px;z-index:20;background:var(--bg);display:flex;gap:8px;overflow-x:auto;padding:12px 2px;scrollbar-width:none}
.cats a,.cats button{white-space:nowrap;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:.5rem .95rem;font-weight:700;font-size:.88rem}
.cats .on{background:var(--k);color:#fff;border-color:var(--k)}
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
    @if ($categorias->count() > 1)
    <nav class="cats" id="cats" aria-label="Categorías">
        <button class="on" data-c="">Todo</button>
        @foreach ($categorias as $c)<a href="#cat-{{ \Illuminate\Support\Str::slug($c) }}" data-c="{{ $c }}">{{ $c }}</a>@endforeach
    </nav>
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
                </a>
                <div class="b">
                    <a href="{{ $p['url'] }}"><h3>{{ $p['nombre'] }}</h3></a>
                    <div class="pr">@if ($p['desde'])<small>Desde</small>@pesos($p['desde'])@else @pesos($p['precio'])@endif</div>
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
    var q = document.getElementById('q'), cat = '';
    function filtrar() {
        var t = q.value.trim().toLowerCase(), hay = 0;
        document.querySelectorAll('.sec').forEach(function (s) {
            var n = 0;
            s.querySelectorAll('.card').forEach(function (c) {
                var p = P[c.dataset.id], ok = (!t || c.dataset.q.indexOf(t) !== -1) && (!cat || p.categorias.indexOf(cat) !== -1 || (!p.categorias.length && cat === 'General'));
                c.style.display = ok ? '' : 'none'; if (ok) n++;
            });
            s.style.display = n ? '' : 'none'; hay += n;
        });
        document.getElementById('nada').style.display = hay ? 'none' : '';
    }
    q.addEventListener('input', filtrar);
    var cats = document.getElementById('cats');
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
