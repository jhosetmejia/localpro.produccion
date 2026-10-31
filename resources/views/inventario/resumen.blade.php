@extends('layouts.app')

@section('title', 'Resumen de inventario')

@push('css')
<style>
.inv-top{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:1rem}
.inv-kpi{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem 1.1rem}
.inv-kpi small{display:block;color:var(--lp-muted);font-weight:700;font-size:.78rem}
.inv-kpi b{display:block;font-size:1.45rem;font-weight:900;line-height:1.15;margin-top:.15rem}
.inv-kpi.dark{background:var(--lp-black);border-color:var(--lp-black);color:#fff}
.inv-kpi.dark small{color:#a8b3ab}.inv-kpi.dark b{color:var(--lp-lime)}
.inv-filtro{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem}
.inv-filtro select{max-width:320px;border-radius:999px;font-weight:700;border:1.5px solid var(--lp-line)}
.inv-filtro .cat-on{background:var(--lp-black);color:#fff;border-radius:999px;padding:.4rem .9rem;font-weight:800;font-size:.85rem}
.inv-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.inv-grid .lp-box.full{grid-column:1/-1}
.inv-row{display:grid;grid-template-columns:minmax(90px,170px) 1fr auto;gap:.8rem;align-items:center;padding:.45rem 0;border-bottom:1px dashed #edf2ee;color:inherit;text-decoration:none}
.inv-row:last-child{border-bottom:0}
a.inv-row:hover .inv-nom{color:var(--lp-green-2)}
.inv-nom{font-weight:800;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.inv-nom small{display:block;font-weight:600;color:var(--lp-muted);font-size:.72rem}
.inv-bar{height:12px;border-radius:999px;background:#eef3ef;overflow:hidden}
.inv-bar i{display:block;height:100%;border-radius:999px;background:var(--lp-grad)}
.inv-num{text-align:right;font-weight:900;white-space:nowrap}
.inv-num small{display:block;font-weight:600;color:var(--lp-muted);font-size:.72rem}
.inv-tallas{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:10px}
.inv-talla{border:1.5px solid var(--lp-line);border-radius:18px;padding:.7rem .5rem;text-align:center;background:#fafcfb}
.inv-talla .t{font-size:1.05rem;font-weight:900}
.inv-talla .u{font-size:1.5rem;font-weight:900;color:var(--lp-green-2);line-height:1.1}
.inv-talla small{display:block;color:var(--lp-muted);font-weight:700;font-size:.7rem}
.inv-talla.cero{opacity:.55}.inv-talla.cero .u{color:#b0173f}
a.inv-talla{display:block;color:inherit;text-decoration:none;cursor:pointer;transition:.15s}
a.inv-talla:hover{border-color:#bfeccb;background:#fff;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.06)}
a.inv-talla .ver{display:block;margin-top:.25rem;font-size:.68rem;font-weight:800;color:var(--lp-green-2)}
a.inv-talla.on{background:var(--lp-black);border-color:var(--lp-black);color:#fff;opacity:1}
a.inv-talla.on .u{color:var(--lp-lime)}a.inv-talla.on small{color:#a8b3ab}a.inv-talla.on .ver{color:var(--lp-lime)}
.inv-det{margin-top:1rem;border-top:1.5px solid var(--lp-line);padding-top:1rem}
.inv-det-head{display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.4rem}
.inv-det-head b{font-size:1rem;font-weight:900}
.inv-prod{display:grid;grid-template-columns:64px 1fr auto;gap:.8rem;align-items:center;padding:.6rem 0;border-bottom:1px dashed #edf2ee}
.inv-prod:last-child{border-bottom:0}
.inv-prod a{color:inherit;text-decoration:none;font-weight:800;font-size:.92rem}
.inv-prod a:hover{color:var(--lp-green-2)}
.inv-chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:.3rem}
.inv-chips span{background:#f1f6f2;border-radius:999px;padding:.12rem .55rem;font-size:.72rem;font-weight:700;color:var(--lp-ink)}
.inv-chips span.neg{background:#fde8ec;color:#b0173f}
.inv-foto{width:64px;height:64px;border-radius:14px;overflow:hidden;background:#eef3ef;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.3rem;color:var(--lp-muted);border:0;padding:0}
.inv-foto img{width:100%;height:100%;object-fit:cover}
button.inv-foto{cursor:zoom-in}
.inv-prod .nota{display:block;font-size:.7rem;color:var(--lp-muted);font-weight:600}
.inv-zoom{position:fixed;inset:0;background:rgba(5,8,6,.88);z-index:2000;display:none;align-items:center;justify-content:center;padding:16px;cursor:zoom-out}
.inv-zoom.on{display:flex}
.inv-zoom img{max-width:100%;max-height:85vh;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.5)}
.inv-zoom b{position:absolute;bottom:18px;left:0;right:0;text-align:center;color:#fff;font-weight:800}
.inv-zoom .x{position:absolute;top:14px;right:16px;width:42px;height:42px;border-radius:50%;border:0;background:#fff;color:#111;font-size:1.2rem;font-weight:900}
.inv-cta{display:flex;align-items:center;gap:1rem;justify-content:space-between;flex-wrap:wrap;background:linear-gradient(135deg,#0b0d0c,#1d2a21);color:#fff;border-radius:24px;padding:1.1rem 1.3rem;margin-bottom:1rem}
.inv-cta b{font-size:1.05rem}.inv-cta p{margin:.15rem 0 0;color:#b8c4bc;font-size:.88rem}
@media (max-width:991px){.inv-top{grid-template-columns:repeat(2,1fr)}.inv-top .inv-kpi.dark{grid-column:1/-1}.inv-grid{grid-template-columns:1fr}}
@media print{.lp-side,.sb-topnav,.inv-tabs,.inv-cta,.inv-filtro form{display:none!important}}
</style>
@endpush

@section('content')
@include('layouts.partials.alert')
@php
    $catNombre = $catId ? $categorias->firstWhere('id', $catId)['nombre'] : null;
    $idCat = $categorias->pluck('id', 'nombre');
    $maxT = max(1, collect($porTalla)->max('unidades'));
    $maxC = max(1, collect($porCategoria)->max('unidades'));
    $maxCo = max(1, collect($porColor)->max('unidades'));
    $u = fn ($n) => cantidad($n);
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Inventario</small><h1>¿Cuánto <span>tenemos</span>?</h1></div>
        <div class="lp-quick">
            <button type="button" class="btn btn-light border" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Imprimir</button>
        </div>
    </div>
    @include('inventario._tabs')

    <div class="inv-cta">
        @if ($abierto)
        <div><b>Tiene un conteo físico abierto: {{ $abierto->nombre }}</b><p>Siga contando donde lo dejó y al final ajuste el inventario con un botón.</p></div>
        <a href="{{ route('inventario.conteos.show', $abierto) }}" class="btn btn-primary fw-bold"><i class="fa-solid fa-clipboard-check me-1"></i>Seguir contando</a>
        @else
        <div><b>¿Lo del sistema es lo que hay en la bodega?</b><p>Haga un conteo físico: escanee las prendas con el celular y el sistema le muestra qué falta y qué sobra.</p></div>
        <a href="{{ route('inventario.conteos.index') }}" class="btn btn-primary fw-bold"><i class="fa-solid fa-clipboard-check me-1"></i>Hacer conteo físico</a>
        @endif
    </div>

    <div class="inv-filtro">
        <form method="get" class="d-flex gap-2 align-items-center">
            <select name="categoria" class="form-select" onchange="this.form.submit()" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $c)<option value="{{ $c['id'] }}" @selected($catId == $c['id'])>{{ $c['nombre'] }}</option>@endforeach
            </select>
        </form>
        @if ($catNombre)<span class="cat-on">{{ $catNombre }}</span><a href="{{ route('inventario.resumen') }}" class="text-danger fw-bold text-decoration-none small">✕ Ver todo</a>@endif
    </div>

    <div class="inv-top">
        <div class="inv-kpi dark"><small>Prendas en inventario{{ $catNombre ? ' · ' . $catNombre : '' }}</small><b>{{ $u($total['unidades']) }}</b></div>
        <div class="inv-kpi"><small>Productos</small><b>{{ $total['productos'] }}</b></div>
        <div class="inv-kpi"><small>Tallas / colores</small><b>{{ $total['refs'] }}</b></div>
        <div class="inv-kpi"><small>Valor a precio de venta</small><b>{{ dinero($total['venta']) }}</b></div>
        <div class="inv-kpi"><small>Valor a costo</small><b>{{ dinero($total['costo']) }}</b></div>
    </div>
    @if ($total['negativos'])
    <div class="alert alert-warning py-2 small fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $total['negativos'] }} talla(s)/color(es) tienen existencias en negativo (se vendió más de lo que había en el sistema). Un conteo físico las corrige.</div>
    @endif

    <div class="inv-grid">
        <div class="lp-box full">
            <h3 id="tallas">Por talla <small class="text-muted fw-bold" style="font-size:.78rem">{{ $catNombre ?: 'todas las categorías' }} · toque una para ver cuáles son</small></h3>
            @if (count($porTalla))
            <div class="inv-tallas">
                @foreach ($porTalla as $t => $x)
                @php $on = $talla === $t; @endphp
                <a href="{{ $on ? route('inventario.resumen', array_filter(['categoria' => $catId])) : route('inventario.resumen', array_filter(['categoria' => $catId, 'talla' => $t])) }}#tallas" class="inv-talla {{ $x['unidades'] <= 0 ? 'cero' : '' }} {{ $on ? 'on' : '' }}">
                    <div class="t">{{ $t === 'SIN TALLA' ? 'Sin talla' : $t }}</div>
                    <div class="u">{{ $u($x['unidades']) }}</div>
                    <small>{{ $x['productos'] }} producto{{ $x['productos'] == 1 ? '' : 's' }}</small>
                    <span class="ver">{{ $on ? 'Ocultar ▲' : 'Ver cuáles ▼' }}</span>
                </a>
                @endforeach
            </div>

            @if ($talla)
            <div class="inv-det">
                <div class="inv-det-head">
                    <b>Talla {{ $talla === 'SIN TALLA' ? 'sin talla' : $talla }} · {{ $u($porTalla[$talla]['unidades']) }} prenda{{ $porTalla[$talla]['unidades'] == 1 ? '' : 's' }} en {{ $detalleTalla->count() }} producto{{ $detalleTalla->count() == 1 ? '' : 's' }}</b>
                    <a href="{{ route('inventario.resumen', array_filter(['categoria' => $catId])) }}#tallas" class="text-danger fw-bold text-decoration-none small">✕ Cerrar</a>
                </div>
                @foreach ($detalleTalla as $p)
                <div class="inv-prod">
                    @if ($p['foto'])
                    <button type="button" class="inv-foto" data-zoom="{{ $p['foto'] }}" data-nombre="{{ $p['nombre'] }}" title="Ver foto grande"><img src="{{ $p['foto'] }}" alt="{{ $p['nombre'] }}" loading="lazy"></button>
                    @else
                    <a href="{{ route('productos.edit', ['producto' => $p['id']]) }}" class="inv-foto" title="Súbale una foto"><i class="fa-solid fa-camera" style="font-size:1rem"></i></a>
                    @endif
                    <div>
                        @if ($p['publica'])
                        <a href="{{ $p['publica'] }}" target="_blank" rel="noopener">{{ $p['nombre'] }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.7rem"></i></a>
                        @else
                        <a href="{{ route('productos.edit', ['producto' => $p['id']]) }}">{{ $p['nombre'] }}</a><span class="nota">No sale en la tienda en línea (toque para editarlo)</span>
                        @endif
                        <div class="inv-chips">
                            @foreach ($p['colores'] as $c)
                            <span class="{{ $c['stock'] < 0 ? 'neg' : '' }}">{{ $c['color'] }}: {{ $u($c['stock']) }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="inv-num">{{ $u($p['unidades']) }}<small>{{ dinero($p['venta']) }}</small></div>
                </div>
                @endforeach
            </div>
            <div class="inv-zoom" id="invZoom"><button type="button" class="x" aria-label="Cerrar">✕</button><img src="" alt=""><b></b></div>
            <script>
            (function () {
                var z = document.getElementById('invZoom'), img = z.querySelector('img'), tit = z.querySelector('b');
                document.querySelectorAll('[data-zoom]').forEach(function (b) {
                    b.addEventListener('click', function () { img.src = b.dataset.zoom; tit.textContent = b.dataset.nombre || ''; z.classList.add('on'); });
                });
                z.addEventListener('click', function () { z.classList.remove('on'); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') z.classList.remove('on'); });
            })();
            </script>
            @endif
            @else<div class="lp-empty">No hay prendas con inventario.</div>@endif
        </div>

        @if (!$catNombre)
        <div class="lp-box">
            <h3>Por categoría <small class="text-muted fw-bold" style="font-size:.72rem">toque una para ver sus tallas</small></h3>
            @forelse ($porCategoria as $n => $x)
            @php $id = $idCat[$n] ?? null; @endphp
            <{{ $id ? 'a' : 'div' }} class="inv-row" @if ($id) href="{{ route('inventario.resumen', ['categoria' => $id]) }}" @endif>
                <div class="inv-nom">{{ $n }}<small>{{ $x['productos'] }} productos · {{ $x['refs'] }} tallas/colores</small></div>
                <div class="inv-bar"><i style="width:{{ round($x['unidades'] / $maxC * 100, 1) }}%"></i></div>
                <div class="inv-num">{{ $u($x['unidades']) }}<small>{{ dinero($x['venta']) }}</small></div>
            </{{ $id ? 'a' : 'div' }}>
            @empty<div class="lp-empty">Sin datos.</div>@endforelse
            @if (count($porCategoria) > 1)<small class="text-muted d-block mt-2">Si un producto está en dos categorías, se cuenta en las dos.</small>@endif
        </div>
        @endif

        <div class="lp-box {{ $catNombre ? 'full' : '' }}">
            <h3>Por color</h3>
            @forelse ($porColor as $n => $x)
            <div class="inv-row">
                <div class="inv-nom">{{ $n === 'SIN COLOR' ? 'Sin color' : mb_convert_case($n, MB_CASE_TITLE) }}<small>{{ $x['productos'] }} productos</small></div>
                <div class="inv-bar"><i style="width:{{ round($x['unidades'] / $maxCo * 100, 1) }}%"></i></div>
                <div class="inv-num">{{ $u($x['unidades']) }}<small>{{ dinero($x['venta']) }}</small></div>
            </div>
            @empty<div class="lp-empty">Sin datos.</div>@endforelse
        </div>
    </div>
</div>
@endsection
