@extends('catalogo._layout')

@php
    $lugarT = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
    $cat = $p['categorias'][0] ?? null;
    $precioTxt = dinero($p['desde'] ?? $p['precio']);
    $tallas = collect($p['variantes'])->pluck('talla')->filter()->unique()->values();
    $colores = collect($p['variantes'])->pluck('color')->filter()->unique()->map(fn ($c) => \App\Support\Textos::titulo($c))->values();
    // SEO: lo que la gente busca en Google = producto + ciudad. Ej: "Chaqueta rompe vientos hombre | Abril Store, Pasto"
    $ciudad = $tienda->ciudad;
    $catEnNombre = $cat && str_contains(\App\Support\Textos::clave($p['nombre']), \App\Support\Textos::clave($cat));
    $tituloSeo = $p['nombre'] . ($cat && !$catEnNombre && mb_strlen($p['nombre']) < 40 ? ' · ' . $cat : '') . ' | ' . $tienda->nombre . ($ciudad ? ', ' . $ciudad : '');
    $desc = \Illuminate\Support\Str::limit(
        $p['nombre'] . ($p['desde'] ? ' desde ' : ' por ') . $precioTxt
        . ($p['descuento'] ? ' (-' . $p['descuento'] . '% en promoción)' : '')
        . ' en ' . $tienda->nombre . ($ciudad ? ', ' . $ciudad : '') . '.'
        . ($tallas->isNotEmpty() ? ' Tallas: ' . $tallas->take(8)->implode(', ') . '.' : '')
        . ($colores->isNotEmpty() ? ' Colores: ' . $colores->take(5)->implode(', ') . '.' : '')
        . ($p['descripcion'] ? ' ' . $p['descripcion'] : '')
        . ' Compre fácil por WhatsApp.', 158);
@endphp

@section('titulo', $tituloSeo)
@section('descripcion', $desc)
@section('canonical', $p['url'])
@section('og_tipo', 'product')
@if ($p['img'])@section('imagen', $p['img'])@endif

@push('jsonld')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $p['nombre'],
    'description' => $p['descripcion'] ?: $desc,
    'color' => $colores->isNotEmpty() ? $colores->implode(', ') : null,
    'size' => $tallas->isNotEmpty() ? $tallas->implode(', ') : null,
    'image' => $p['img'] ? [$p['img']] : null,
    'sku' => $p['codigo'],
    'category' => $cat,
    'brand' => ['@type' => 'Brand', 'name' => $tienda->nombre],
    'offers' => [
        '@type' => 'Offer',
        'url' => $p['url'],
        'priceCurrency' => moneda()['moneda'],
        'price' => $p['desde'] ?? $p['precio'],
        'priceValidUntil' => now()->addMonths(3)->toDateString(),
        'availability' => $p['disponible'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller' => ['@type' => 'Organization', 'name' => $tienda->nombre],
    ],
], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
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
.foto{width:100%;max-width:calc(100vh - 170px);margin-inline:auto}
.moda .foto{aspect-ratio:3/4;max-width:calc((100vh - 170px) * .75)}
@media(max-width:800px){.foto{position:static;max-width:none}.moda .foto{max-width:none}}
/* En computador, al lado del producto se ven otros para seguir mirando sin bajar */
.rel-lado{display:none;margin-top:22px;padding-top:16px;border-top:1.5px solid var(--line)}
.rel-lado h2{font-size:1rem;font-weight:800;margin-bottom:10px;display:flex;align-items:baseline;justify-content:space-between}
.rel-lado h2 a{font-size:.8rem;color:var(--g2)}
.mini{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.mini a{display:block;background:#fff;border:1.5px solid var(--line);border-radius:14px;overflow:hidden;transition:transform .15s,box-shadow .15s}
.mini a:hover{transform:translateY(-2px);box-shadow:0 10px 20px -14px rgba(0,0,0,.4)}
.mini .mf{aspect-ratio:1/1;background:#fff;display:grid;place-items:center;font-weight:800;font-size:1.6rem;color:var(--g2);position:relative}
.moda .mini .mf{aspect-ratio:1/1}
.mini .mf img{width:100%;height:100%;object-fit:contain}
.mini .mf .tag-promo{top:6px;right:6px;font-size:.65rem;padding:.1rem .4rem}
.mini .mt{padding:6px 8px 8px;font-size:.75rem;font-weight:700;line-height:1.2}
.mini .mt span{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mini .mt b{display:block;margin-top:3px;font-size:.82rem}
.mini .mt b.ahora-promo{color:#e11d48}
.rel .t-d{display:none}
@media(min-width:1000px){.rel-lado{display:block}.rel.con-lado{margin-top:28px}.rel .t-m{display:none}.rel .t-d{display:inline}}
.foto img{width:100%;height:100%;object-fit:contain;object-position:center;background:#fff;transition:opacity .2s}
.foto-col{position:sticky;top:80px}
.foto-col .foto{position:static}
@media(max-width:800px){.foto-col{position:static}}
/* Galería: las miniaturas van al lado de la foto (computador) o encima de la foto abajo (celular), así no empujan nada hacia abajo */
.thumbs{display:flex;gap:8px;scrollbar-width:none}
.thumbs::-webkit-scrollbar{display:none}
.thumbs button{flex:none;width:58px;height:70px;border-radius:12px;border:2px solid var(--line);background:#fff;overflow:hidden;padding:0;cursor:pointer}
.thumbs button.on{border-color:var(--k)}
.thumbs img{width:100%;height:100%;object-fit:contain}
.foto-col.con-thumbs{display:flex;gap:10px;align-items:flex-start}
.foto-col.con-thumbs .foto{flex:1 1 auto;min-width:0}
.foto-col.con-thumbs .thumbs{order:-1;flex-direction:column;max-height:calc(100vh - 170px);overflow-y:auto;padding:2px}
@media(max-width:800px){
  .foto-col.con-thumbs{display:block;position:relative}
  .foto-col.con-thumbs .thumbs{position:absolute;left:10px;right:10px;bottom:10px;flex-direction:row;justify-content:center;max-height:none;overflow-x:auto;overflow-y:visible}
  .foto-col.con-thumbs .thumbs button{width:46px;height:56px;border-radius:10px;box-shadow:0 6px 14px -6px rgba(0,0,0,.45)}
  .foto-col.con-thumbs .thumbs button.on{border-color:#2fd15a}
}
.info h1{font-size:clamp(1.5rem,4vw,2.2rem);font-weight:800;letter-spacing:-.02em;line-height:1.15}
.info .cod{color:var(--mut);font-weight:700;font-size:.85rem;margin-top:6px}
.info .precio{font-size:2rem;font-weight:800;margin-top:12px}
.info .precio small{font-size:.9rem;color:var(--mut);margin-right:6px}
.info .precio.ahora-promo{color:#e11d48;margin-top:4px}
.promo-pp{margin-top:12px;display:flex;align-items:center;gap:10px;font-size:1.05rem}
.promo-pp b{background:#e11d48;color:#fff;border-radius:999px;padding:.2rem .7rem;font-size:.85rem}
.info .disp{display:inline-flex;align-items:center;gap:6px;font-weight:800;font-size:.85rem;margin-top:6px;color:#14753a}
.info .disp.no{color:#b0206a}
.info .desc{color:var(--mut);margin-top:14px;line-height:1.6;white-space:pre-line}
.lbl{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);margin:18px 0 8px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:2px solid var(--line);border-radius:14px;padding:.6rem .95rem;font-weight:800;background:#fff}
.chip.on{background:var(--k);border-color:var(--k);color:#fff}
.chip{padding:.45rem .8rem;font-size:.9rem;border-radius:12px}
.chip-c{display:inline-flex;align-items:center;gap:6px}
.chip-c i{width:16px;height:16px;border-radius:50%;border:1px solid rgba(0,0,0,.2);flex:none}
.chip.no{opacity:.35;text-decoration:line-through}
.lbl{margin:14px 0 6px}
.lbl .elegido{text-transform:none;letter-spacing:0;color:var(--k);margin-left:4px}
.fila-compra{display:flex;gap:10px;align-items:center;margin-top:16px;max-width:520px}
.fila-compra .btn{margin-top:0;flex:1;height:52px}
.agregar{margin-top:8px;font-weight:800;font-size:.92rem;color:var(--k);background:none;padding:.3rem 0}
.agregar:hover{color:var(--g2)}
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
        <div class="foto-col {{ count($p['fotos']) + ($p['img'] ? 1 : 0) > 1 ? 'con-thumbs' : '' }}">
            <div class="foto" id="ppFoto">@if ($p['img'] ?: (array_values($p['fotos'])[0] ?? null))<img src="{{ $p['img'] ?: array_values($p['fotos'])[0] }}" alt="{{ $p['nombre'] }}" id="ppImg">@else{{ mb_strtoupper(mb_substr($p['nombre'], 0, 1)) }}@endif</div>
            @if (count($p['fotos']) + ($p['img'] ? 1 : 0) > 1)
            <div class="thumbs" id="ppThumbs">
                @if ($p['img'])<button type="button" class="on" data-src="{{ $p['img'] }}" data-color=""><img src="{{ $p['img'] }}" alt="" loading="lazy"></button>@endif
                @foreach ($p['fotos'] as $col => $url)<button type="button" data-src="{{ $url }}" data-color="{{ $col }}" title="{{ ucfirst(mb_strtolower($col)) }}"><img src="{{ $url }}" alt="{{ $p['nombre'] }} {{ mb_strtolower($col) }}" loading="lazy"></button>@endforeach
            </div>
            @endif
        </div>
        <div class="info">
            <h1 data-editar-id="{{ $p['id'] }}">{{ $p['nombre'] }}</h1>
            @if ($p['codigo'])<div class="cod">Código: {{ $p['codigo'] }}</div>@endif
            @if ($p['antes'] && $p['disponible'])<div class="promo-pp"><span class="antes">{{ dinero($p['antes']) }}</span><b>-{{ $p['descuento'] }}% 🔥 Promoción</b></div>@endif
            <div class="precio {{ $p['antes'] ? 'ahora-promo' : '' }}" id="ppPrecio">@if ($p['desde'])<small>Desde</small>@endif{{ $precioTxt }}@if ($p['unidad'])<small> / {{ $p['unidad'] }}</small>@endif</div>
            <div class="disp {{ $p['disponible'] ? '' : 'no' }}">{{ $p['disponible'] ? '● Disponible' : '● Agotado por ahora' }}</div>
            @if ($p['descripcion'])<p class="desc">{{ $p['descripcion'] }}</p>@endif

            @if ($p['disponible'])
                @php
                    $vs = collect($p['variantes']);
                    $colsV = $vs->pluck('color')->filter()->unique()->values();
                    $talV = $vs->pluck('talla')->filter()->unique()->values();
                    // Color y talla por separado (más compacto y al tocar el color cambia la foto)
                    $separado = $vs->count() > 1 && ($colsV->isNotEmpty() || $talV->isNotEmpty()) && $vs->every(fn ($v) => $v['talla'] || $v['color']);
                @endphp
                @if ($vs->count() > 1)
                <div id="ppVars">
                    @if ($separado)
                        @if ($colsV->isNotEmpty())
                        <div class="lbl">Color <span class="elegido" id="ppColTxt"></span></div>
                        <div class="chips">
                            @foreach ($colsV as $c)<button type="button" class="chip chip-c" data-color="{{ $c }}" title="{{ ucfirst(mb_strtolower($c)) }}"><i style="background:{{ \App\Support\Colores::PALETA[\App\Support\Colores::normalizar($c)] ?? '#ddd' }}"></i>{{ ucfirst(mb_strtolower($c)) }}</button>@endforeach
                        </div>
                        @endif
                        @if ($talV->isNotEmpty())
                        <div class="lbl">Talla</div>
                        <div class="chips">
                            @foreach ($talV as $t)<button type="button" class="chip chip-t" data-talla="{{ $t }}">{{ $t === 'ÚNICA' ? 'Única' : $t }}</button>@endforeach
                        </div>
                        @endif
                    @else
                        <div class="lbl">Elija una opción</div>
                        <div class="chips">
                            @foreach ($p['variantes'] as $i => $v)<button type="button" class="chip" data-v="{{ $i }}">{{ $v['etiqueta'] }}</button>@endforeach
                        </div>
                    @endif
                </div>
                @endif
                <div class="fila-compra">
                    <div class="qty"><button id="qMenos" aria-label="Menos">−</button><span id="qN">1</span><button id="qMas" aria-label="Más">+</button></div>
                    <button class="btn btn-wa" id="ppComprar">💬 Comprar por WhatsApp</button>
                </div>
                <button class="agregar" id="ppAgregar">🛒 Agregar al pedido</button>
            @else
                @if (numero_whatsapp($tienda->telefono))
                <a class="btn btn-wa" style="max-width:460px" target="_blank" rel="noopener" href="https://wa.me/{{ numero_whatsapp($tienda->telefono) }}?text={{ rawurlencode('Hola ' . $tienda->nombre . ', ¿cuándo les llega ' . $p['nombre'] . ($p['codigo'] ? ' (código ' . $p['codigo'] . ')' : '') . '? ' . $p['url'] . "\n\n_Visto en LocalProo Marketplace_") }}">💬 Preguntar cuándo llega</a>
                @endif
            @endif
            <button class="compartir" id="ppCompartir">↗ Compartir este producto</button>

            @if ($relacionados->isNotEmpty())
            <div class="rel-lado">
                <h2>También le puede gustar @if ($relacionados->count() > 4)<a href="#rel">Ver más ↓</a>@endif</h2>
                <div class="mini">
                    @foreach ($relacionados->take(4) as $r)
                    <a href="{{ $r['url'] }}">
                        <div class="mf">@if ($r['img'])<img src="{{ $r['img'] }}" alt="{{ $r['nombre'] }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($r['nombre'], 0, 1)) }}@endif @if ($r['descuento'])<span class="tag-promo">-{{ $r['descuento'] }}%</span>@endif</div>
                        <div class="mt"><span>{{ $r['nombre'] }}</span><b class="{{ $r['antes'] ? 'ahora-promo' : '' }}">{{ dinero($r['desde'] ?? $r['precio']) }}</b></div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @if ($relacionados->isNotEmpty())
    <section class="rel con-lado" id="rel">
        <h2><span class="t-m">También le puede gustar</span><span class="t-d">Más productos de {{ $tienda->nombre }}</span></h2>
        <div class="grid">
            @foreach ($relacionados as $r)
            <article class="card">
                <a href="{{ $r['url'] }}" class="ph">@if ($r['img'])<img src="{{ $r['img'] }}" alt="{{ $r['nombre'] }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($r['nombre'], 0, 1)) }}@endif @if ($r['descuento'])<span class="tag-promo">-{{ $r['descuento'] }}%</span>@endif</a>
                <div class="b"><a href="{{ $r['url'] }}"><h3>{{ $r['nombre'] }}</h3></a><div class="pr">@if ($r['antes'])<span class="antes">@pesos($r['antes'])</span>@endif<span class="{{ $r['antes'] ? 'ahora-promo' : '' }}">@if ($r['desde'])<small>Desde</small>@pesos($r['desde'])@else @pesos($r['precio'])@endif</span></div>
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
    var fotoBase = p.img || null;
    function verFoto(src, color) {
        var img = $('ppImg'); if (!img || !src || img.getAttribute('src') === src) { marcarThumb(color); return; }
        img.style.opacity = .3;
        var n = new Image(); n.onload = function () { img.src = src; img.style.opacity = 1; }; n.src = src;
        marcarThumb(color);
    }
    function marcarThumb(color) {
        document.querySelectorAll('#ppThumbs button').forEach(function (b) { b.classList.toggle('on', b.dataset.color === (color || '')); });
    }
    if ($('ppThumbs')) $('ppThumbs').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        verFoto(b.dataset.src, b.dataset.color);
    });
    function pintar() {
        // Al elegir un color, se muestra la foto de ese color (si el negocio la subió)
        if (sel && sel.color && p.fotos && p.fotos[sel.color]) verFoto(p.fotos[sel.color], sel.color);
        if ($('qN')) $('qN').textContent = cant;
        if (sel) $('ppPrecio').textContent = LPC.fmt(sel.precio * cant);
        document.querySelectorAll('#ppVars .chip[data-v]').forEach(function (c) { c.classList.toggle('on', p.variantes[+c.dataset.v] === sel); });
        document.querySelectorAll('#ppVars .chip-c').forEach(function (c) { c.classList.toggle('on', c.dataset.color === colSel); });
        document.querySelectorAll('#ppVars .chip-t').forEach(function (c) { c.classList.toggle('on', c.dataset.talla === talSel); });
    }
    function falta() {
        if (sel) return false;
        var v = $('ppVars'); v.scrollIntoView({ behavior: 'smooth', block: 'center' });
        v.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-6px)' }, { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }], { duration: 300 });
        return true;
    }
    // Color y talla por separado: con los dos elegidos queda la opción exacta
    var colSel = null, talSel = null;
    var hayCol = !!document.querySelector('#ppVars .chip-c'), hayTal = !!document.querySelector('#ppVars .chip-t');
    function buscar() {
        sel = p.variantes.find(function (v) { return (!hayCol || v.color === colSel) && (!hayTal || v.talla === talSel); }) || null;
        // Marca las combinaciones que no existen
        document.querySelectorAll('#ppVars .chip-t').forEach(function (b) { b.classList.toggle('no', !!colSel && !p.variantes.some(function (v) { return v.talla === b.dataset.talla && v.color === colSel; })); });
        document.querySelectorAll('#ppVars .chip-c').forEach(function (b) { b.classList.toggle('no', !!talSel && !p.variantes.some(function (v) { return v.color === b.dataset.color && v.talla === talSel; })); });
        if ($('ppColTxt')) $('ppColTxt').textContent = colSel ? '· ' + colSel.charAt(0) + colSel.slice(1).toLowerCase() : '';
    }
    if ($('ppVars')) $('ppVars').addEventListener('click', function (e) {
        var b = e.target.closest('.chip'); if (!b) return;
        if (b.dataset.color) {
            colSel = b.dataset.color;
            if (p.fotos && p.fotos[colSel]) verFoto(p.fotos[colSel], colSel);
            if (talSel && !p.variantes.some(function (v) { return v.color === colSel && v.talla === talSel; })) talSel = null;
            buscar();
        } else if (b.dataset.talla) {
            talSel = b.dataset.talla;
            if (colSel && !p.variantes.some(function (v) { return v.color === colSel && v.talla === talSel; })) colSel = null;
            buscar();
        } else sel = p.variantes[+b.dataset.v];
        pintar();
    });
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
