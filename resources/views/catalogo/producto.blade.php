@extends('catalogo._layout')

@php
    $lugarT = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
    $cat = $p['categorias'][0] ?? null;
    $precioTxt = dinero($p['desde'] ?? $p['precio']);
    $desc = \Illuminate\Support\Str::limit(($p['descripcion'] ? $p['descripcion'] . '. ' : '') . $p['nombre'] . ($p['desde'] ? ' desde ' : ' a ') . $precioTxt . ' en ' . $tienda->nombre . ($lugarT ? ', ' . $lugarT : '') . '. Cómprelo por WhatsApp.', 158);
    $tallas = collect($p['variantes'])->pluck('talla')->filter()->unique()->values();
@endphp

@section('titulo', $p['nombre'] . ' · ' . $tienda->nombre)
@section('descripcion', $desc)
@section('canonical', $p['url'])
@section('og_tipo', 'product')
@if ($p['img'])@section('imagen', $p['img'])@endif

@push('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $p['nombre'],
    'description' => $p['descripcion'] ?: $p['nombre'] . ' en ' . $tienda->nombre,
    'image' => $p['img'] ? [$p['img']] : null,
    'sku' => $p['codigo'],
    'category' => $cat,
    'brand' => ['@type' => 'Brand', 'name' => $tienda->nombre],
    'offers' => [
        '@type' => 'Offer',
        'url' => $p['url'],
        'priceCurrency' => moneda()['moneda'],
        'price' => $p['desde'] ?? $p['precio'],
        'availability' => $p['disponible'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller' => ['@type' => 'Organization', 'name' => $tienda->nombre],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => $tienda->nombre, 'item' => $tienda->url_catalogo],
        $cat ? ['@type' => 'ListItem', 'position' => 2, 'name' => $cat, 'item' => $tienda->url_catalogo . '#cat-' . \Illuminate\Support\Str::slug($cat)] : null,
        ['@type' => 'ListItem', 'position' => $cat ? 3 : 2, 'name' => $p['nombre'], 'item' => $p['url']],
    ])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@push('css')
.migas{display:flex;gap:6px;flex-wrap:wrap;font-size:.82rem;font-weight:700;color:var(--mut);padding:16px 0 10px}
.migas a{color:var(--g2)}
.pp{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:28px;align-items:start}
@media(max-width:800px){.pp{grid-template-columns:minmax(0,1fr);gap:16px}}
.foto{background:#fff;border:1.5px solid var(--line);border-radius:26px;aspect-ratio:1/1;overflow:hidden;display:grid;place-items:center;font-size:5rem;font-weight:800;color:var(--g2);position:sticky;top:80px}
@media(max-width:800px){.foto{position:static}}
.foto img{width:100%;height:100%;object-fit:cover}
.info h1{font-size:clamp(1.5rem,4vw,2.2rem);font-weight:800;letter-spacing:-.02em;line-height:1.15}
.info .cod{color:var(--mut);font-weight:700;font-size:.85rem;margin-top:6px}
.info .precio{font-size:2rem;font-weight:800;margin-top:12px}
.info .precio small{font-size:.9rem;color:var(--mut);margin-right:6px}
.info .disp{display:inline-flex;align-items:center;gap:6px;font-weight:800;font-size:.85rem;margin-top:6px;color:#14753a}
.info .disp.no{color:#b0206a}
.info .desc{color:var(--mut);margin-top:14px;line-height:1.6;white-space:pre-line}
.lbl{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);margin:18px 0 8px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:2px solid var(--line);border-radius:14px;padding:.6rem .95rem;font-weight:800;background:#fff}
.chip.on{background:var(--k);border-color:var(--k);color:#fff}
.qty{display:inline-flex;align-items:center;gap:10px;background:#fff;border:1.5px solid var(--line);border-radius:16px;padding:4px}
.qty button{width:42px;height:42px;border-radius:12px;background:var(--bg);font-size:1.3rem;font-weight:800}
.qty span{font-weight:800;font-size:1.2rem;min-width:30px;text-align:center}
.acc{display:grid;grid-template-columns:1fr;gap:0;max-width:460px}
.compartir{display:inline-flex;gap:6px;align-items:center;margin-top:14px;font-weight:700;color:var(--g2);font-size:.9rem}
.rel{margin-top:40px}
.rel h2{font-size:1.25rem;font-weight:800;margin-bottom:12px}
@endpush

@section('contenido')
<main class="wrap">
    <nav class="migas" aria-label="Ruta"><a href="{{ $tienda->url_catalogo }}">{{ $tienda->nombre }}</a>@if ($cat)<span>›</span><a href="{{ $tienda->url_catalogo }}#cat-{{ \Illuminate\Support\Str::slug($cat) }}">{{ $cat }}</a>@endif<span>›</span><span>{{ \Illuminate\Support\Str::limit($p['nombre'], 40) }}</span></nav>

    <div class="pp">
        <div class="foto">@if ($p['img'])<img src="{{ $p['img'] }}" alt="{{ $p['nombre'] }}">@else{{ mb_strtoupper(mb_substr($p['nombre'], 0, 1)) }}@endif</div>
        <div class="info">
            <h1>{{ $p['nombre'] }}</h1>
            @if ($p['codigo'])<div class="cod">Código: {{ $p['codigo'] }}</div>@endif
            <div class="precio" id="ppPrecio">@if ($p['desde'])<small>Desde</small>@endif{{ $precioTxt }}@if ($p['unidad'])<small> / {{ $p['unidad'] }}</small>@endif</div>
            <div class="disp {{ $p['disponible'] ? '' : 'no' }}">{{ $p['disponible'] ? '● Disponible' : '● Agotado por ahora' }}</div>
            @if ($p['descripcion'])<p class="desc">{{ $p['descripcion'] }}</p>@endif

            @if ($p['disponible'])
                @if (count($p['variantes']) > 1)
                <div class="lbl">{{ collect($p['variantes'])->contains(fn ($v) => $v['talla'] || $v['color']) ? 'Elija talla / color' : 'Elija una opción' }}</div>
                <div class="chips" id="ppVars">
                    @foreach ($p['variantes'] as $i => $v)<button class="chip" data-v="{{ $i }}">{{ $v['etiqueta'] }}</button>@endforeach
                </div>
                @endif
                <div class="lbl">Cantidad</div>
                <div class="qty"><button id="qMenos" aria-label="Menos">−</button><span id="qN">1</span><button id="qMas" aria-label="Más">+</button></div>
                <div class="acc">
                    <button class="btn btn-wa" id="ppComprar">💬 Comprar por WhatsApp</button>
                    <button class="btn btn-l" id="ppAgregar">🛒 Agregar al pedido</button>
                </div>
            @else
                @if (numero_whatsapp($tienda->telefono))
                <a class="btn btn-wa" style="max-width:460px" target="_blank" rel="noopener" href="https://wa.me/{{ numero_whatsapp($tienda->telefono) }}?text={{ rawurlencode('Hola ' . $tienda->nombre . ', ¿cuándo les llega ' . $p['nombre'] . ($p['codigo'] ? ' (código ' . $p['codigo'] . ')' : '') . '? ' . $p['url']) }}">💬 Preguntar cuándo llega</a>
                @endif
            @endif
            <button class="compartir" id="ppCompartir">↗ Compartir este producto</button>
        </div>
    </div>

    @if ($relacionados->isNotEmpty())
    <section class="rel">
        <h2>También le puede gustar</h2>
        <div class="grid">
            @foreach ($relacionados as $r)
            <article class="card">
                <a href="{{ $r['url'] }}" class="ph">@if ($r['img'])<img src="{{ $r['img'] }}" alt="{{ $r['nombre'] }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($r['nombre'], 0, 1)) }}@endif</a>
                <div class="b"><a href="{{ $r['url'] }}"><h3>{{ $r['nombre'] }}</h3></a><div class="pr">@if ($r['desde'])<small>Desde</small>@pesos($r['desde'])@else @pesos($r['precio'])@endif</div>
                    <div class="bt"><a class="btn-c" href="{{ $r['url'] }}">Ver</a></div></div>
            </article>
            @endforeach
        </div>
    </section>
    @endif
</main>
@endsection

@push('js')
<script>
(function () {
    var p = @json($p), sel = p.variantes.length === 1 ? p.variantes[0] : null, cant = 1;
    var $ = function (id) { return document.getElementById(id); };
    function pintar() {
        if ($('qN')) $('qN').textContent = cant;
        if (sel) $('ppPrecio').textContent = LPC.fmt(sel.precio * cant);
        document.querySelectorAll('#ppVars .chip').forEach(function (c) { c.classList.toggle('on', p.variantes[+c.dataset.v] === sel); });
    }
    function falta() {
        if (sel) return false;
        var v = $('ppVars'); v.scrollIntoView({ behavior: 'smooth', block: 'center' });
        v.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-6px)' }, { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }], { duration: 300 });
        return true;
    }
    if ($('ppVars')) $('ppVars').addEventListener('click', function (e) { var b = e.target.closest('.chip'); if (!b) return; sel = p.variantes[+b.dataset.v]; pintar(); });
    if ($('qMenos')) $('qMenos').addEventListener('click', function () { if (cant > 1) { cant--; pintar(); } });
    if ($('qMas')) $('qMas').addEventListener('click', function () { if (cant < 99) { cant++; pintar(); } });
    if ($('ppComprar')) $('ppComprar').addEventListener('click', function () { if (!falta()) LPC.comprar(p, sel, cant); });
    if ($('ppAgregar')) $('ppAgregar').addEventListener('click', function () { if (!falta()) LPC.agregar(p, sel, cant); });
    $('ppCompartir').addEventListener('click', function () {
        if (navigator.share) navigator.share({ title: p.nombre, url: p.url }).catch(function () {});
        else window.open('https://wa.me/?text=' + encodeURIComponent(p.nombre + ' ' + p.url), '_blank');
    });
})();
</script>
@endpush
