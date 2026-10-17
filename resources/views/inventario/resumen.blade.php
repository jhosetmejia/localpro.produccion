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
            <h3>Por talla <small class="text-muted fw-bold" style="font-size:.78rem">{{ $catNombre ?: 'todas las categorías' }}</small></h3>
            @if (count($porTalla))
            <div class="inv-tallas">
                @foreach ($porTalla as $t => $x)
                <div class="inv-talla {{ $x['unidades'] <= 0 ? 'cero' : '' }}">
                    <div class="t">{{ $t === 'SIN TALLA' ? 'Sin talla' : $t }}</div>
                    <div class="u">{{ $u($x['unidades']) }}</div>
                    <small>{{ $x['productos'] }} producto{{ $x['productos'] == 1 ? '' : 's' }}</small>
                </div>
                @endforeach
            </div>
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
