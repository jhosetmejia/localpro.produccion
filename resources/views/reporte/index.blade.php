@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
@php $max = max(1, collect($dias)->max('total')); $q = request()->only(['periodo', 'desde', 'hasta']); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Reportes</small><h1>¿Cómo va su <span>negocio</span>?</h1><span class="text-muted fw-semibold">Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</span></div>
        <div class="lp-quick">
            <div class="dropdown">
                <button class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-file-excel me-1"></i>Exportar a Excel</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('reportes.exportar', ['tipo' => 'ventas'] + $q) }}">Ventas del periodo</a></li>
                    <li><a class="dropdown-item" href="{{ route('reportes.exportar', ['tipo' => 'productos'] + $q) }}">Productos vendidos</a></li>
                    <li><a class="dropdown-item" href="{{ route('reportes.exportar', ['tipo' => 'inventario'] + $q) }}">Inventario actual</a></li>
                </ul>
            </div>
        </div>
    </div>

    <form class="lp-box mb-3 d-flex flex-wrap gap-2 align-items-end" method="get">
        @foreach (['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'mes_pasado' => 'Mes pasado'] as $k => $txt)
            <button name="periodo" value="{{ $k }}" class="btn {{ $periodo === $k ? 'btn-primary' : 'btn-info' }}">{{ $txt }}</button>
        @endforeach
        <div class="d-flex gap-2 align-items-end ms-md-auto flex-wrap">
            <div><label class="form-label mb-1">Desde</label><input type="date" name="desde" class="form-control" value="{{ $desde->format('Y-m-d') }}"></div>
            <div><label class="form-label mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $hasta->format('Y-m-d') }}"></div>
            <button name="periodo" value="rango" class="btn btn-dark">Ver</button>
        </div>
    </form>

    <div class="lp-kpis" style="grid-template-columns:repeat(4,1fr)">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Ventas netas</div><div class="val">@pesos($resumen['neto'])</div><div class="sub">{{ $resumen['num'] }} ventas · promedio @pesos($resumen['promedio'])</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-chart-line"></i></div><div class="lbl">Ganancia</div><div class="val" style="color:#16b34a">@pesos($resumen['ganancia'])</div><div class="sub">Margen {{ number_format($resumen['margen'], 1, ',', '.') }}%</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-tags"></i></div><div class="lbl">Descuentos</div><div class="val">@pesos($resumen['descuentos'])</div><div class="sub">Costo de lo vendido @pesos($resumen['costo'])</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-rotate-left"></i></div><div class="lbl">Devoluciones</div><div class="val">@pesos($resumen['devuelto'])</div><div class="sub">Hora pico: {{ $horaPico !== null ? \Carbon\Carbon::createFromTime($horaPico)->format('g a') : '—' }}</div></div>
    </div>
    @if ($resumen['costo'] == 0 && $resumen['num'] > 0)
        <div class="alert border-0 mb-3" style="background:#fff5d6;color:#8a6400"><i class="fa-solid fa-lightbulb me-1"></i> Para ver la ganancia real, ponga el <b>precio de costo</b> en sus productos o registre sus <b>entradas de mercancía</b>.</div>
    @endif

    <div class="lp-box mb-3">
        <h3>Ventas por día</h3>
        <div class="lp-bars" style="height:220px;gap:4px">
            @foreach ($dias as $d)
            <div class="lp-bar {{ $loop->last ? 'today' : '' }}">
                <i style="height:{{ max(2, round($d['total'] / $max * 100)) }}%" data-v="{{ dinero($d['total']) }} · {{ $d['n'] }} vtas"></i>
                @if (count($dias) <= 16 || $loop->index % max(1, intdiv(count($dias), 10)) === 0)<span>{{ $d['label'] }}</span>@else<span>&nbsp;</span>@endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Lo más vendido</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Producto</th><th class="text-end">Unid.</th><th class="text-end">Vendido</th><th class="text-end">Ganancia</th></tr></thead>
                    <tbody>
                    @forelse ($top as $p)
                    <tr><td><b>{{ $p->descripcion }}</b> <small class="text-muted">{{ trim(($p->talla ?? '') . ' ' . ($p->color ?? '')) }}</small></td><td class="text-end">{{ $p->unidades }}</td><td class="text-end">@pesos($p->monto)</td><td class="text-end" style="color:#16b34a">@pesos($p->monto - $p->costo)</td></tr>
                    @empty
                    <tr><td colspan="4" class="lp-empty">Sin ventas en este periodo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <div class="lp-box mb-3">
                <h3>Por vendedor</h3>
                <ul class="lp-list">
                    @forelse ($porVendedor as $v)
                    <li><div class="av"><i class="fa-solid fa-user"></i></div><div class="t"><b>{{ $v->name }}</b><small>{{ $v->n }} ventas</small></div><span class="m">@pesos($v->t)</span></li>
                    @empty
                    <li class="lp-empty">Sin ventas.</li>
                    @endforelse
                </ul>
            </div>
            <div class="lp-box">
                <h3>Por método de pago</h3>
                <ul class="lp-list">
                    @forelse ($porMetodo as $m)
                    <li><div class="av"><i class="fa-solid fa-wallet"></i></div><div class="t"><b>{{ $m->nombre }}</b></div><span class="m">@pesos($m->t)</span></li>
                    @empty
                    <li class="lp-empty">Sin pagos.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
