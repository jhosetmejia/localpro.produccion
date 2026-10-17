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
.filtros{background:var(--bg);padding:12px 0 2px}
@media (min-width:768px){.filtros{position:sticky;top:64px;z-index:20}}
.fbar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;padding-bottom:10px}
@media (min-width:768px){.fbar{grid-template-columns:repeat(var(--n,4),minmax(0,1fr)) auto}}
.fsel{position:relative;min-width:0}
.fsel label{position:absolute;left:14px;top:6px;font-size:.66rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--mut);pointer-events:none}
.fsel select{width:100%;appearance:none;-webkit-appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%230b0d0c' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;border:1.5px solid var(--line);border-radius:16px;padding:1.35rem 2.2rem .45rem .85rem;font:inherit;font-weight:800;font-size:.92rem;color:var(--k);cursor:pointer;text-overflow:ellipsis}
.fsel select:focus{outline:none;border-color:#2fd15a;box-shadow:0 0 0 3px rgba(47,209,90,.18)}
.fsel.on select{background-color:var(--k);color:#fff;border-color:var(--k);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23a6f34a' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E")}
.fsel.on label{color:#a6f34a}
.fsel.grande select{pointer-events:none}
.fsel.grande{cursor:pointer}
.fpop-fondo{position:fixed;inset:0;background:rgba(11,13,12,.45);z-index:90;display:none}
.fpop{position:fixed;z-index:91;background:#fff;display:none;flex-direction:column;box-shadow:0 24px 50px -20px rgba(0,0,0,.45);overflow:hidden}
.fpop.ver,.fpop-fondo.ver{display:flex}
.fpop-fondo.ver{display:block}
@media (max-width:767px){.fpop{left:0;right:0;bottom:0;max-height:78vh;border-radius:24px 24px 0 0}}
@media (min-width:768px){.fpop{width:340px;max-height:420px;border-radius:18px;border:1.5px solid var(--line)}}
.fpop-cab{padding:12px 12px 8px;border-bottom:1px solid var(--line)}
.fpop-cab b{display:block;font-size:.95rem;margin:0 4px 8px}
.fpop-cab input{width:100%;border:1.5px solid var(--line);border-radius:14px;padding:.7rem .9rem;font:inherit;font-size:16px;font-weight:600;outline:none}
.fpop-cab input:focus{border-color:#2fd15a}
.fpop-lista{overflow-y:auto;padding:6px;-webkit-overflow-scrolling:touch}
.fpop-lista button{display:block;width:100%;text-align:left;border:0;background:none;border-radius:12px;padding:.65rem .8rem;font:inherit;font-weight:700;font-size:.95rem;color:var(--k);cursor:pointer}
.fpop-lista button:hover,.fpop-lista button.act{background:#f1f7f2}
.fpop-lista button.sel{background:var(--k);color:#fff}
.fpop-nada{padding:1rem;text-align:center;color:var(--mut);font-weight:600}
.flimpiar{display:none;align-self:stretch;border:0;background:none;color:#e11d48;font:inherit;font-weight:800;font-size:.85rem;cursor:pointer;padding:0 .6rem;white-space:nowrap}
.flimpiar.ver{display:block}
@media (max-width:767px){.flimpiar{grid-column:1/-1;padding:.2rem}}
.sec{margin-top:18px}
.sec h2{font-size:1.25rem;font-weight:800;margin:6px 2px 12px;display:flex;align-items:baseline;gap:8px}
.sec h2 small{color:var(--mut);font-size:.8rem;font-weight:700}
.vacio{text-align:center;color:var(--mut);padding:50px 10px;font-weight:600}
/* Inicio: filas por categoría que se deslizan + "Más productos" en una cuadrícula; al elegir categoría o buscar, la cuadrícula normal */
body:not(.filtrando) .filtro-solo,body.filtrando .inicio-solo{display:none!important}
.sec h2 .ver-todo{margin-left:auto;background:none;border:0;color:#11702f;font-weight:800;font-size:.85rem;cursor:pointer;padding:.2rem .1rem;font-family:inherit}
.sec h2 .ver-todo:hover{text-decoration:underline}
.carril{position:relative}
.riel{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:2px 2px 8px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.riel::-webkit-scrollbar{display:none}
.riel>.card{flex:0 0 clamp(150px,42vw,215px);scroll-snap-align:start}
.mas-card{align-items:center;justify-content:center;gap:4px;border-style:dashed;color:#11702f;font-weight:800;font-family:inherit;cursor:pointer;min-height:220px;background:#f7fdf8}
.mas-card b{font-size:1.6rem}
.flecha{display:none;position:absolute;top:38%;z-index:3;width:38px;height:38px;border-radius:50%;border:1.5px solid var(--line);background:#fff;box-shadow:0 8px 20px -10px rgba(0,0,0,.35);font-size:1.4rem;line-height:1;cursor:pointer;color:var(--k)}
.flecha.izq{left:-14px}.flecha.der{right:-14px}
@media (hover:hover) and (min-width:768px){.carril:hover .flecha{display:grid;place-items:center}}
.flecha[disabled]{opacity:0;pointer-events:none}
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
    @php
        // Tallas y colores que tienen existencias (para los filtros)
        $tallasF = $productos->pluck('variantes')->flatten(1)->pluck('talla')->filter()->unique()->values();
        $coloresF = $productos->pluck('variantes')->flatten(1)->pluck('color')->filter()->map(fn ($c) => mb_strtoupper(trim($c)))->unique()->sort()->values();
        $nFiltros = ($publicos->isNotEmpty() ? 1 : 0) + ($categorias->count() > 1 || $enPromo ? 1 : 0) + ($tallasF->count() > 1 ? 1 : 0) + ($coloresF->count() > 1 ? 1 : 0);
    @endphp
    @if ($nFiltros)
    <div class="filtros">
        <div class="fbar" style="--n:{{ $nFiltros }}">
            @if ($publicos->isNotEmpty())
            <div class="fsel"><label for="fPara">Para</label><select id="fPara" aria-label="¿Para quién?"><option value="">Todos</option>@foreach ($publicos as $k => [$ico, $nom])<option value="{{ $k }}">{{ $ico }} {{ $nom }}</option>@endforeach</select></div>
            @endif
            @if ($categorias->count() > 1 || $enPromo)
            <div class="fsel"><label for="fCat">Categoría</label><select id="fCat" aria-label="Categoría">
                <option value="">Todas</option>
                @if ($enPromo)<option value="__promo">🔥 Promociones ({{ $enPromo }})</option>@endif
                @foreach ($categorias as $c)<option value="{{ $c }}" data-para="{{ $catPara[$c] ?? 'todos' }}">{{ $c }}</option>@endforeach
            </select></div>
            @endif
            @if ($tallasF->count() > 1)
            <div class="fsel"><label for="fTalla">Talla</label><select id="fTalla" aria-label="Talla"><option value="">Todas</option></select></div>
            @endif
            @if ($coloresF->count() > 1)
            <div class="fsel"><label for="fColor">Color</label><select id="fColor" aria-label="Color"><option value="">Todos</option></select></div>
            @endif
            <button type="button" class="flimpiar" id="fLimpiar">✕ Quitar filtros</button>
        </div>
    </div>
    @endif

    @php
        // Inicio de la tienda: las categorías con más productos van en filas que se deslizan a los lados
        // (máximo 5) y los demás productos quedan juntos en una sola cuadrícula, sin huecos.
        $filas = collect($secciones)->filter(fn ($i) => count($i) >= 3)->take(5);
        $resto = collect($secciones)->except($filas->keys())->flatten(1)->unique('id')->values();
    @endphp
    @if ($categorias->count() > 1)
    @foreach ($filas as $cat => $items)
    <section class="sec fila inicio-solo" data-fila="{{ $cat }}">
        <h2>{{ $cat }} <small>{{ count($items) }}</small>
            <button type="button" class="ver-todo" data-ver="{{ $cat }}">Ver todo →</button></h2>
        <div class="carril">
            <button type="button" class="flecha izq" data-mover="-1" aria-label="Anteriores">‹</button>
            <div class="riel">
                @foreach (collect($items)->take(12) as $p)@include('catalogo._card')@endforeach
                @if (count($items) > 12)<button type="button" class="card mas-card" data-ver="{{ $cat }}"><b>+{{ count($items) - 12 }}</b>Ver todos</button>@endif
            </div>
            <button type="button" class="flecha der" data-mover="1" aria-label="Siguientes">›</button>
        </div>
    </section>
    @endforeach
    @if ($resto->isNotEmpty())
    <section class="sec inicio-solo" data-resto>
        <h2>{{ $filas->isEmpty() ? 'Todos los productos' : 'Más productos' }} <small>{{ $resto->count() }}</small></h2>
        <div class="grid">@foreach ($resto as $p)@include('catalogo._card')@endforeach</div>
    </section>
    @endif
    @endif

    @forelse ($secciones as $cat => $items)
    <section class="sec {{ $categorias->count() > 1 ? 'filtro-solo' : '' }}" id="cat-{{ \Illuminate\Support\Str::slug($cat) }}" data-sec="{{ $cat }}">
        @if ($categorias->count() > 1)<h2>{{ $cat }} <small>{{ count($items) }}</small></h2>@endif
        <div class="grid">
            @foreach ($items as $p)@include('catalogo._card')@endforeach
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
    var q = document.getElementById('q'), cat = '', para = '', talla = '', color = '';
    var fPara = document.getElementById('fPara'), fCat = document.getElementById('fCat'), fTalla = document.getElementById('fTalla'),
        fColor = document.getElementById('fColor'), fLimpiar = document.getElementById('fLimpiar');
    var conFilas = !!document.querySelector('.inicio-solo');
    var ORDEN = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '2XL', '3XL', '4XL', 'U', 'UNICA', 'ÚNICA'];
    function up(v) { return (v || '').toString().trim().toUpperCase(); }
    function enCat(p) { return !cat || (cat === '__promo' ? p.descuento > 0 : (p.categorias.indexOf(cat) !== -1 || (!p.categorias.length && cat === 'General'))); }
    function enPara(p) { return !para || !p.para || p.para === para; }
    // ¿Tiene una opción (variante) con esa talla y ese color al mismo tiempo?
    function enVariante(p, t, c) {
        if (!t && !c) return true;
        return p.variantes.some(function (v) { return (!t || up(v.talla) === t) && (!c || up(v.color) === c); });
    }
    function ordenTalla(a, b) {
        var ia = ORDEN.indexOf(a), ib = ORDEN.indexOf(b), na = parseFloat(a), nb = parseFloat(b);
        if (ia !== -1 || ib !== -1) return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib);
        if (!isNaN(na) && !isNaN(nb)) return na - nb;
        return a.localeCompare(b);
    }
    // Las tallas y colores que se ofrecen dependen de lo ya elegido (para quién y categoría)
    function llenar(sel, valores, actual, ordenar, todos) {
        if (!sel) return actual;
        var lista = Object.keys(valores); lista.sort(ordenar);
        if (actual && lista.indexOf(actual) === -1) actual = '';
        sel.innerHTML = '<option value="">' + todos + '</option>' + lista.map(function (v) {
            return '<option value="' + v.replace(/"/g, '&quot;') + '"' + (v === actual ? ' selected' : '') + '>' + v.charAt(0) + v.slice(1).toLowerCase() + '</option>';
        }).join('');
        sel.parentNode.style.display = lista.length > 1 || actual ? '' : 'none';
        return actual;
    }
    function opciones() {
        var ts = {}, cs = {};
        Object.keys(P).forEach(function (id) {
            var p = P[id]; if (!p.disponible || !enPara(p) || !enCat(p)) return;
            p.variantes.forEach(function (v) {
                if (v.talla && (!color || up(v.color) === color)) ts[up(v.talla)] = 1;
                if (v.color && (!talla || up(v.talla) === talla)) cs[up(v.color)] = 1;
            });
        });
        talla = llenar(fTalla, ts, talla, ordenTalla, 'Todas');
        color = llenar(fColor, cs, color, function (a, b) { return a.localeCompare(b); }, 'Todos');
    }
    function marcar() {
        [[fPara, para], [fCat, cat], [fTalla, talla], [fColor, color]].forEach(function (x) { if (x[0]) x[0].parentNode.classList.toggle('on', !!x[1]); });
        if (fLimpiar) fLimpiar.classList.toggle('ver', !!(para || cat || talla || color));
    }
    function filtrar() {
        var t = q.value.trim().toLowerCase(), hay = 0;
        // Sin búsqueda ni filtros se ve el inicio (filas que se deslizan); si no, la cuadrícula normal
        var filtrando = !conFilas || !!t || !!cat || !!talla || !!color;
        document.body.classList.toggle('filtrando', filtrando);
        document.querySelectorAll(filtrando ? '.sec:not(.inicio-solo)' : '.sec.inicio-solo').forEach(function (s) {
            var n = 0;
            s.querySelectorAll('.card[data-id]').forEach(function (c) {
                var p = P[c.dataset.id], ok = (!t || c.dataset.q.indexOf(t) !== -1) && enCat(p) && enPara(p) && enVariante(p, talla, color);
                c.style.display = ok ? '' : 'none'; if (ok) n++;
            });
            s.style.display = n ? '' : 'none'; hay += n;
        });
        document.getElementById('nada').style.display = hay ? 'none' : '';
        marcar();
        flechas();
    }
    function subir() { window.scrollTo({ top: document.querySelector('.panel').offsetTop - 70, behavior: 'smooth' }); }
    function urlPara() { try { var u = new URL(location.href); para ? u.searchParams.set('para', para) : u.searchParams.delete('para'); history.replaceState(null, '', u); } catch (e) {} }
    function elegirPara(v) {
        para = v; if (fPara) fPara.value = v;
        // Solo quedan las categorías de ese grupo
        if (fCat) {
            Array.prototype.forEach.call(fCat.options, function (o) {
                if (!o.dataset.para) return;
                var g = o.dataset.para.split(' '), ok = !v || g.indexOf(v) !== -1 || g.indexOf('todos') !== -1;
                o.hidden = !ok; o.disabled = !ok;
            });
            if (fCat.selectedOptions[0] && fCat.selectedOptions[0].disabled) { cat = ''; fCat.value = ''; }
        }
        urlPara(); opciones(); filtrar();
    }
    function elegirCat(v) { cat = v; if (fCat) fCat.value = v; if (q.value) q.value = ''; opciones(); filtrar(); }

    if (fPara) fPara.addEventListener('change', function () { elegirPara(fPara.value); subir(); });
    if (fCat) fCat.addEventListener('change', function () { elegirCat(fCat.value); subir(); });
    if (fTalla) fTalla.addEventListener('change', function () { talla = fTalla.value; opciones(); filtrar(); subir(); });
    if (fColor) fColor.addEventListener('change', function () { color = fColor.value; opciones(); filtrar(); subir(); });
    if (fLimpiar) fLimpiar.addEventListener('click', function () { talla = ''; color = ''; elegirCat(''); elegirPara(''); });
    q.addEventListener('input', filtrar);

    function flechas() {
        document.querySelectorAll('.carril').forEach(function (c) {
            var r = c.querySelector('.riel');
            c.querySelector('.izq').disabled = r.scrollLeft < 5;
            c.querySelector('.der').disabled = r.scrollLeft + r.clientWidth >= r.scrollWidth - 5;
        });
    }
    document.querySelectorAll('.riel').forEach(function (r) { r.addEventListener('scroll', flechas, { passive: true }); });
    window.addEventListener('resize', flechas);
    document.addEventListener('click', function (e) {
        var m = e.target.closest('[data-mover]'), v = e.target.closest('[data-ver]');
        if (m) { var r = m.parentNode.querySelector('.riel'); r.scrollBy({ left: m.dataset.mover * r.clientWidth * 0.85 }); }
        // "Ver todo": abre la categoría completa, igual que elegirla en el filtro
        if (v) { elegirCat(v.dataset.ver); subir(); }
    });
    // Botón "Promociones" de arriba: en esta página filtra sin recargar
    var vp = document.getElementById('btnPromos'), hayPromo = fCat && fCat.querySelector('option[value="__promo"]');
    if (vp && hayPromo) vp.addEventListener('click', function (e) { e.preventDefault(); elegirCat('__promo'); subir(); });

    // Listas largas (más de 8 opciones): se abren en un panel con buscador
    var fondo = document.createElement('div'), pop = document.createElement('div'), popSel = null;
    fondo.className = 'fpop-fondo'; pop.className = 'fpop';
    pop.innerHTML = '<div class="fpop-cab"><b></b><input type="search" placeholder="Buscar…" autocomplete="off"></div><div class="fpop-lista"></div>';
    document.body.appendChild(fondo); document.body.appendChild(pop);
    var popIn = pop.querySelector('input'), popLista = pop.querySelector('.fpop-lista');
    function sinTildes(t) { return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
    function visibles(sel) { return Array.prototype.filter.call(sel.options, function (o) { return !o.disabled && !o.hidden; }); }
    function pintarLista() {
        var t = sinTildes(popIn.value.trim()), html = '';
        visibles(popSel).forEach(function (o) {
            if (t && sinTildes(o.text).indexOf(t) === -1) return;
            html += '<button type="button" data-v="' + o.value.replace(/"/g, '&quot;') + '" class="' + (o.value === popSel.value ? 'sel' : '') + '">' + o.text.replace(/</g, '&lt;') + '</button>';
        });
        popLista.innerHTML = html || '<div class="fpop-nada">No hay opciones con "' + popIn.value.replace(/</g, '&lt;') + '"</div>';
    }
    function abrirPop(sel) {
        popSel = sel; popIn.value = '';
        pop.querySelector('b').textContent = sel.parentNode.querySelector('label').textContent;
        pintarLista();
        if (window.innerWidth >= 768) {
            var r = sel.getBoundingClientRect();
            pop.style.top = Math.min(r.bottom + 6, window.innerHeight - 430) + 'px';
            pop.style.left = Math.max(10, Math.min(r.left, window.innerWidth - 350)) + 'px';
        } else { pop.style.top = ''; pop.style.left = ''; }
        fondo.classList.add('ver'); pop.classList.add('ver');
        if (window.innerWidth >= 768) setTimeout(function () { popIn.focus(); }, 30);
    }
    function cerrarPop() { fondo.classList.remove('ver'); pop.classList.remove('ver'); popSel = null; }
    fondo.addEventListener('click', cerrarPop);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && popSel) cerrarPop(); });
    popIn.addEventListener('input', pintarLista);
    popIn.addEventListener('keydown', function (e) { if (e.key === 'Enter') { var b = popLista.querySelector('button'); if (b) b.click(); } });
    popLista.addEventListener('click', function (e) {
        var b = e.target.closest('[data-v]'); if (!b || !popSel) return;
        var sel = popSel; cerrarPop();
        sel.value = b.dataset.v; sel.dispatchEvent(new Event('change'));
    });
    // Revisa qué listas son largas (cambia según lo elegido) y las marca para abrir el panel
    function revisarGrandes() {
        [fPara, fCat, fTalla, fColor].forEach(function (sel) {
            if (sel) sel.parentNode.classList.toggle('grande', visibles(sel).length > 8);
        });
    }
    [fPara, fCat, fTalla, fColor].forEach(function (sel) {
        if (!sel) return;
        sel.parentNode.addEventListener('click', function () { if (sel.parentNode.classList.contains('grande')) abrirPop(sel); });
    });
    var marcarAntes = marcar;
    marcar = function () { marcarAntes(); revisarGrandes(); };

    opciones(); flechas(); marcar();
    // Enlaces compartidos: ?para=mujer o ?ver=promos abren ya filtrados
    var qs = new URLSearchParams(location.search);
    if (qs.get('para') && fPara && fPara.querySelector('option[value="' + qs.get('para') + '"]')) elegirPara(qs.get('para'));
    if (hayPromo && qs.get('ver') === 'promos') elegirCat('__promo');

    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-comprar]'), a = e.target.closest('[data-agregar]');
        if (c) { var p = P[c.dataset.comprar]; LPC.comprar(p, p.variantes[0], 1); }
        if (a) { var p2 = P[a.dataset.agregar]; LPC.agregar(p2, p2.variantes[0], 1); }
    });
})();
</script>
@endpush
