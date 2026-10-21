@extends('layouts.app')

@section('title', 'Cierre de pedidos')

@push('css')
<style>
.cr-kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-bottom:1rem}
@media (max-width:760px){.cr-kpi{grid-template-columns:1fr 1fr}}
.cr-kpi div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem}
.cr-kpi div.v{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.cr-kpi b{display:block;font-size:1.45rem;font-weight:900;line-height:1.1}.cr-kpi .v b{color:#a6f34a}
.cr-kpi small{font-weight:700;color:#6b7570}.cr-kpi .v small{color:#b8c4bc}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-scale-balanced me-1"></i>Pedidos</small><h1>Cierre del <span>día</span></h1>
            <span class="text-muted fw-semibold">{{ ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM YYYY')) }}. El dinero se cuadra en <a href="{{ route('cajas.index') }}">Cierres de caja</a>; aquí ve cómo trabajó el equipo.</span></div>
        <form class="lp-quick" method="get"><input type="date" name="fecha" class="form-control" value="{{ $fecha->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()"></form>
    </div>
    @include('pedidos._nav')

    <div class="cr-kpi">
        <div class="v"><b>{{ dinero($cobrado) }}</b><small>cobrado en pedidos</small></div>
        <div><b>{{ $pedidos->where('estado', '<>', 'anulado')->count() }}</b><small>pedidos</small></div>
        <div><b>{{ $sinCobrar->count() }}</b><small>sin cobrar ({{ dinero($sinCobrar->sum('total')) }})</small></div>
        <div><b>{{ $anulados }}</b><small>anulados</small></div>
    </div>
    @if ($sinCobrar->count())
    <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>Quedan pedidos sin cobrar: {{ $sinCobrar->map(fn ($p) => $p->titulo)->join(', ') }}. Cóbrelos o anúlelos antes de cerrar la caja.</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6"><div class="lp-box h-100">
            <h3><i class="fa-solid fa-bell-concierge me-1"></i>Por mesero</h3>
            <table class="table mb-0"><thead><tr><th>Mesero</th><th class="text-end">Pedidos</th><th class="text-end">Valor</th></tr></thead><tbody>
                @forelse ($porMesero as $n => $x)<tr><td>{{ $n }}</td><td class="text-end">{{ $x['n'] }}</td><td class="text-end fw-bold">{{ dinero($x['t']) }}</td></tr>
                @empty<tr><td colspan="3" class="text-muted">Sin pedidos.</td></tr>@endforelse
            </tbody></table></div></div>
        <div class="col-lg-6"><div class="lp-box h-100">
            <h3><i class="fa-solid fa-fire-burner me-1"></i>Por estación</h3>
            <table class="table mb-0"><thead><tr><th>Estación</th><th class="text-end">Productos</th><th class="text-end">Tiempo promedio</th><th class="text-end">Más demorado</th></tr></thead><tbody>
                @forelse ($porEstacion as $n => $x)<tr><td>{{ $n }}</td><td class="text-end">{{ cantidad($x['n']) }}</td><td class="text-end fw-bold">{{ $x['prom'] !== null ? str_replace('.', ',', $x['prom']) . ' min' : '—' }}</td><td class="text-end">{{ $x['max'] !== null ? str_replace('.', ',', $x['max']) . ' min' : '—' }}</td></tr>
                @empty<tr><td colspan="4" class="text-muted">Sin pedidos.</td></tr>@endforelse
            </tbody></table></div></div>
        <div class="col-12"><div class="lp-box">
            <h3><i class="fa-solid fa-ranking-star me-1"></i>Lo más pedido</h3>
            <table class="table mb-0"><thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Valor</th></tr></thead><tbody>
                @forelse ($top as $n => $x)<tr><td>{{ $n }}</td><td class="text-end">{{ cantidad($x['c']) }}</td><td class="text-end">{{ dinero($x['t']) }}</td></tr>
                @empty<tr><td colspan="3" class="text-muted">Sin pedidos.</td></tr>@endforelse
            </tbody></table></div></div>
    </div>
</div>
@endsection
