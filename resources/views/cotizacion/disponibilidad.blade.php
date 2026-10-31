@extends('layouts.app')

@section('title', 'Disponibilidad')

@push('css')
<style>
.dp-row{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.75rem 1rem;margin-bottom:.5rem}
.dp-row .t{flex:1 1 220px;min-width:0}.dp-row .t b{display:block}
.dp-row .t small{color:#6b7570;font-weight:600}
.dp-num{display:flex;gap:.4rem;flex-wrap:wrap}
.dp-num span{border-radius:12px;padding:.3rem .7rem;font-weight:800;font-size:.85rem;text-align:center;min-width:86px}
.dp-num small{display:block;font-size:.66rem;text-transform:uppercase;font-weight:800;opacity:.75}
.dp-bar{height:8px;border-radius:99px;background:#e3f9e8;overflow:hidden;width:100%}
.dp-bar i{display:block;height:100%;background:#f59e0b}
</style>
@endpush

@section('content')
@php $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-calendar-check me-1"></i>Cotizaciones</small><h1>¿Qué tengo <span>libre?</span></h1>
            <span class="text-muted fw-semibold">Del {{ $desde->format('d/m/Y') }}@if ($hasta->gt($desde)) al {{ $hasta->format('d/m/Y') }}@endif: lo que tiene, lo ocupado por cotizaciones aceptadas y lo que queda libre.</span></div>
        <div class="lp-quick d-flex flex-wrap gap-2"><a href="{{ route('productos.create', ['alquiler' => 1]) }}" class="btn btn-outline-success btn-lg"><i class="fa-solid fa-calendar-plus me-1"></i>Nuevo producto para alquilar</a><a href="{{ route('cotizaciones.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Nueva cotización</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-box mb-3">
        <form method="get" class="d-flex gap-2 flex-wrap align-items-end">
            <div><label class="form-label fw-bold mb-1">Desde</label><input type="date" name="desde" class="form-control" value="{{ $desde->toDateString() }}" required></div>
            <div><label class="form-label fw-bold mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $hasta->toDateString() }}"></div>
            <div class="flex-grow-1" style="min-width:200px"><label class="form-label fw-bold mb-1">Producto</label><input name="q" class="form-control" value="{{ $q }}" placeholder="Buscar por nombre (opcional)" data-libre></div>
            <button class="btn btn-dark">Ver disponibilidad</button>
        </form>
    </div>

    @if ($alquileres->isNotEmpty())
    <div class="lp-box mb-3" style="border-color:#c9efd2;background:#f4fcf6">
        <b><i class="fa-solid fa-calendar-days me-1"></i>Alquileres registrados para esas fechas</b>
        <ul class="mb-0 mt-1">
            @foreach ($alquileres as $a)
            <li><a href="{{ route('alquileres.edit', $a->id) }}">{{ $a->numero }}</a> · {{ $a->fecha->format('d/m') }} · {{ $a->que_alquila }}@if ($a->lugar) ({{ $a->lugar }})@endif @if ($a->entrega === 'alquilado')<span class="badge rounded-pill bg-light text-dark">en manos del cliente</span>@endif</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="lp-box">
        @forelse ($disp as $id => $d)
        @php $pct = $d['tiene'] > 0 ? min(100, $d['ocupado'] / $d['tiene'] * 100) : ($d['ocupado'] > 0 ? 100 : 0); $agotado = $d['libre'] <= 0; @endphp
        <div class="dp-row">
            <div class="t"><b>{{ $d['nombre'] }}</b>
                @if ($d['cotizaciones'])<small>Ocupado por: @foreach ($d['cotizaciones'] as $x)<span class="me-2">{{ $x['numero'] }} {{ $x['cliente'] }} ({{ $n($x['cantidad']) }}, {{ $x['fechas'] }})</span>@endforeach</small>@else<small>Nada ocupado en esas fechas</small>@endif
                <div class="dp-bar mt-1"><i style="width:{{ $pct }}%"></i></div>
            </div>
            <div class="dp-num">
                <span style="background:#f1f5f2"><small>Tiene</small>{{ $n($d['tiene']) }}</span>
                <span style="background:#fff5d6;color:#8a6400"><small>Ocupado</small>{{ $n($d['ocupado']) }}</span>
                <span style="background:{{ $agotado ? '#fde8e8' : '#e3f9e8' }};color:{{ $agotado ? '#9b1c1c' : '#11702f' }}"><small>Libre</small>{{ $n($d['libre']) }}</span>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-4">
            <p class="fw-semibold mb-1">Todavía no tiene productos para alquilar.</p>
            <small>Aquí solo salen los productos marcados como <b>"Este producto es para alquilar"</b> (lo que solo vende no sale). Cree el primero con <a href="{{ route('productos.create', ['alquiler' => 1]) }}">Nuevo producto para alquilar</a> o marque los que ya tiene en <a href="{{ route('productos.index') }}">Productos</a>.</small>
        </div>
        @endforelse
    </div>
</div>
@endsection
