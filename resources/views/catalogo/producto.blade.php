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

@php
    $compartir = [
        'precio' => $precioTxt,
        'antes' => $p['antes'] ? dinero($p['antes']) : null,
        'tienda' => $tienda->nombre,
        'ciudad' => $tienda->ciudad,
        'logo' => $tienda->logo_url,
    ];
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
        $cat ? ['@type' => 'ListItem', 'position' => 2, 'name' => $cat, 'item' => $tienda->url_catalogo . '?categoria=' . \Illuminate\Support\Str::slug($cat)] : null,
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
    <nav class="migas" aria-label="Ruta"><a href="{{ $tienda->url_catalogo }}">{{ $tienda->nombre }}</a>@if ($cat)<span>›</span><a href="{{ $tienda->url_catalogo }}?categoria={{ \Illuminate\Support\Str::slug($cat) }}" title="Ver todo en {{ $cat }}">{{ $cat }}</a>@endif<span>›</span><span>{{ \Illuminate\Support\Str::limit($p['nombre'], 40) }}</span></nav>

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
            @if (!empty($p['alquiler']))<div style="display:inline-block;background:#14753a;color:#fff;border-radius:999px;padding:.25rem .75rem;font-size:.8rem;font-weight:800;margin-bottom:6px">📅 Para alquilar</div>@endif
            <h1 data-editar-id="{{ $p['id'] }}">{{ $p['nombre'] }}</h1>
            @if ($p['codigo'])<div class="cod">Código: {{ $p['codigo'] }}</div>@endif
            @if ($p['antes'] && $p['disponible'])<div class="promo-pp"><span class="antes">{{ dinero($p['antes']) }}</span><b>-{{ $p['descuento'] }}% 🔥 Promoción</b></div>@endif
            <div class="precio {{ $p['antes'] ? 'ahora-promo' : '' }}" id="ppPrecio">@if ($p['desde'])<small>Desde</small>@endif{{ $precioTxt }}@if ($p['unidad'])<small> / {{ $p['unidad'] }}</small>@endif @if (!empty($p['alquiler']))<small> · alquiler</small>@endif</div>
            @if ($bsPp = precio_bs($p['desde'] ?? $p['precio']))<div style="font-weight:800;color:#5b665e;margin:-4px 0 6px">{{ $bsPp }} <small style="font-weight:600">· tasa del día</small></div>@endif
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
                    <button class="btn btn-wa" id="ppComprar">💬 {{ !empty($p['alquiler']) ? 'Alquilar' : 'Comprar' }} por WhatsApp</button>
                </div>
                <button class="agregar" id="ppAgregar">🛒 Agregar al pedido</button>
            @else
                @if (numero_whatsapp($tienda->telefono))
                <a class="btn btn-wa" style="max-width:460px" target="_blank" rel="noopener" href="https://wa.me/{{ numero_whatsapp($tienda->telefono) }}?text={{ rawurlencode('Hola ' . $tienda->nombre . ', ¿cuándo les llega ' . $p['nombre'] . ($p['codigo'] ? ' (código ' . $p['codigo'] . ')' : '') . '? ' . $p['url'] . "\n\n_Visto en LocalProo Marketplace_") }}">💬 Preguntar cuándo llega</a>
                @endif
            @endif
            <button class="compartir" id="ppCompartir">📲 Compartir en WhatsApp o estados (foto + link)</button>

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
    // ===== Compartir: imagen tipo estado (1080x1920) con foto, nombre, precio y tienda + el link en el texto =====
    var sh = @json($compartir);
    var texto = p.nombre + ' · ' + sh.precio + (sh.antes ? ' (antes ' + sh.antes + ')' : '') + '\n' + sh.tienda + (sh.ciudad ? ', ' + sh.ciudad : '') + '\n👉 ' + p.url;
    var archivo = null, preparando = null, boton = $('ppCompartir'), txtBoton = boton.textContent;
    // La foto se pide por la ruta del mismo dominio (localproo.com o www) para poder dibujarla
    function cargar(src) {
        if (!src) return Promise.resolve(null);
        var u; try { u = new URL(src, location.href); } catch (e) { return Promise.resolve(null); }
        return fetch(u.pathname + u.search).then(function (r) { if (!r.ok) throw 0; return r.blob(); })
            .then(function (b) { return new Promise(function (ok) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = URL.createObjectURL(b); }); })
            .catch(function () { return null; });
    }
    function lineas(ctx, t, ancho, max) {
        var pal = t.split(/\s+/), out = [], l = '';
        pal.forEach(function (w) { var n = l ? l + ' ' + w : w; if (ctx.measureText(n).width > ancho && l) { out.push(l); l = w; } else l = n; });
        if (l) out.push(l);
        if (out.length > max) { out = out.slice(0, max); out[max - 1] = out[max - 1].replace(/\s*\S*$/, '') + '…'; }
        return out;
    }
    function redondo(ctx, x, y, w, h, r) { ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r); ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath(); }
    function preparar() {
        if (archivo) return Promise.resolve(archivo);
        if (preparando) return preparando;
        var fuente = "'Plus Jakarta Sans', system-ui, sans-serif";
        preparando = Promise.all([cargar($('ppImg') ? $('ppImg').getAttribute('src') : fotoBase), cargar(sh.logo), document.fonts ? document.fonts.ready : null]).then(function (r) {
            var foto = r[0], logo = r[1], W = 1080, H = 1920, c = document.createElement('canvas'); c.width = W; c.height = H;
            var x = c.getContext('2d');
            // Fondo oscuro con brillo verde LocalProo
            x.fillStyle = '#0b0f0c'; x.fillRect(0, 0, W, H);
            var r1 = x.createRadialGradient(W * .15, H * .1, 0, W * .15, H * .1, W * .9); r1.addColorStop(0, 'rgba(47,209,90,.35)'); r1.addColorStop(1, 'rgba(47,209,90,0)'); x.fillStyle = r1; x.fillRect(0, 0, W, H);
            var r2 = x.createRadialGradient(W * .9, H * .75, 0, W * .9, H * .75, W * .8); r2.addColorStop(0, 'rgba(166,243,74,.22)'); r2.addColorStop(1, 'rgba(166,243,74,0)'); x.fillStyle = r2; x.fillRect(0, 0, W, H);
            // Tienda arriba (debajo de la barra de WhatsApp)
            var ty = 180;
            if (logo) { x.save(); redondo(x, 70, ty, 84, 84, 24); x.fillStyle = '#fff'; x.fill(); x.clip(); var k = Math.min(70 / logo.naturalWidth, 70 / logo.naturalHeight); x.drawImage(logo, 70 + (84 - logo.naturalWidth * k) / 2, ty + (84 - logo.naturalHeight * k) / 2, logo.naturalWidth * k, logo.naturalHeight * k); x.restore(); }
            var tx = logo ? 176 : 70;
            x.textAlign = 'left'; x.fillStyle = '#fff'; x.font = '800 40px ' + fuente; x.fillText(lineas(x, sh.tienda, W - tx - 70, 1)[0], tx, ty + 38);
            x.fillStyle = 'rgba(255,255,255,.65)'; x.font = '600 30px ' + fuente; x.fillText(sh.ciudad ? sh.ciudad + ' · localproo.com' : 'localproo.com', tx, ty + 78);
            // Foto a sangre (sin fondo), esquinas redondas
            var fx = 70, fy = 300, fw = W - 140, fh = 930;
            x.save(); redondo(x, fx, fy, fw, fh, 48); x.clip();
            if (foto) { var k2 = Math.max(fw / foto.naturalWidth, fh / foto.naturalHeight), dw = foto.naturalWidth * k2, dh = foto.naturalHeight * k2; x.drawImage(foto, fx + (fw - dw) / 2, fy + (fh - dh) / 2, dw, dh); }
            else { var gl = x.createLinearGradient(fx, fy, fx + fw, fy + fh); gl.addColorStop(0, '#a6f34a'); gl.addColorStop(1, '#16b34a'); x.fillStyle = gl; x.fillRect(fx, fy, fw, fh); x.fillStyle = '#0b0d0c'; x.font = '800 340px ' + fuente; x.textAlign = 'center'; x.fillText(p.nombre.charAt(0).toUpperCase(), W / 2, fy + fh / 2 + 120); x.textAlign = 'left'; }
            // Degradado abajo de la foto para que el precio se lea encima
            var gd = x.createLinearGradient(0, fy + fh - 420, 0, fy + fh); gd.addColorStop(0, 'rgba(11,15,12,0)'); gd.addColorStop(1, 'rgba(11,15,12,.92)'); x.fillStyle = gd; x.fillRect(fx, fy + fh - 420, fw, 420);
            x.restore();
            if (p.descuento) { x.fillStyle = '#ff2d55'; redondo(x, fx + 30, fy + 30, 200, 88, 44); x.fill(); x.fillStyle = '#fff'; x.font = '800 48px ' + fuente; x.textAlign = 'center'; x.fillText('-' + p.descuento + '%', fx + 130, fy + 90); x.textAlign = 'left'; }
            // Precio sobre la foto
            var pre = (p.desde ? 'Desde ' : '') + sh.precio, py = fy + fh - 60;
            x.font = '800 104px ' + fuente; var pw = x.measureText(pre).width;
            var gp = x.createLinearGradient(fx + 50, 0, fx + 50 + pw, 0); gp.addColorStop(0, '#a6f34a'); gp.addColorStop(1, '#2fd15a'); x.fillStyle = gp; x.fillText(pre, fx + 50, py);
            if (sh.antes) { x.fillStyle = 'rgba(255,255,255,.7)'; x.font = '600 46px ' + fuente; var ax = fx + 50 + pw + 28, aw = x.measureText(sh.antes).width; if (ax + aw < fx + fw - 30) { x.fillText(sh.antes, ax, py - 8); x.fillRect(ax, py - 24, aw, 4); } }
            // Nombre y llamado
            var y = fy + fh + 100;
            x.fillStyle = '#fff'; x.font = '800 62px ' + fuente;
            lineas(x, p.nombre, W - 140, 2).forEach(function (l) { x.fillText(l, 70, y); y += 76; });
            var chip = 'Pídalo por WhatsApp';
            x.font = '800 38px ' + fuente; var cw = x.measureText(chip).width + 80;
            x.fillStyle = '#25d366'; redondo(x, 70, y - 10, cw, 80, 40); x.fill();
            x.fillStyle = '#fff'; x.fillText(chip, 110, y + 43);
            return new Promise(function (ok) { c.toBlob(function (b) { ok(b); }, 'image/jpeg', 0.9); });
        }).then(function (b) {
            if (!b) throw 0;
            archivo = new File([b], (p.nombre || 'producto').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50) + '.jpg', { type: 'image/jpeg' });
            return archivo;
        }).finally(function () { preparando = null; });
        return preparando;
    }
    function puede(f) { try { return !!(navigator.canShare && navigator.share && navigator.canShare({ files: [f] })); } catch (e) { return false; } }
    function soloTexto() {
        if (navigator.share) navigator.share({ title: p.nombre, text: texto }).catch(function () {});
        else window.open('https://wa.me/?text=' + encodeURIComponent(texto), '_blank');
    }
    function compartir(f) {
        if (!puede(f)) return soloTexto();
        navigator.share({ files: [f], text: texto }).catch(function (e) {
            if (e && e.name === 'AbortError') return;
            // Se pasó el tiempo del toque mientras se creaba la imagen: pedir otro toque
            boton.textContent = '📲 Listo: toque de nuevo para compartir';
        });
    }
    boton.addEventListener('click', function () {
        if (archivo) { boton.textContent = txtBoton; compartir(archivo); return; }
        boton.disabled = true; boton.textContent = '⏳ Preparando imagen…';
        preparar().then(function (f) { boton.disabled = false; boton.textContent = txtBoton; compartir(f); })
            .catch(function () { boton.disabled = false; boton.textContent = txtBoton; soloTexto(); });
    });
    // Se prepara apenas carga la página para que al tocar se comparta de una (el iPhone lo exige)
    setTimeout(function () { preparar().catch(function () {}); }, 1500);
    // Si cambian la foto (color), se rehace la imagen
    if ($('ppImg')) new MutationObserver(function () { archivo = null; setTimeout(function () { preparar().catch(function () {}); }, 400); }).observe($('ppImg'), { attributes: true, attributeFilter: ['src'] });
})();
</script>
@endpush
