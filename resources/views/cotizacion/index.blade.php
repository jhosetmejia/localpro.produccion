@extends('layouts.app')

@section('title', 'Cotizaciones')

@push('css')
<style>
.ct-fil{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem}
.ct-fil a{border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.85rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.ct-fil a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.ct-card{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.9rem 1rem;margin-bottom:.6rem;text-decoration:none;color:inherit}
.ct-card:hover{border-color:#2fd15a}
.ct-card .t{flex:1 1 240px;min-width:0}
.ct-card .t b{display:block;font-size:1rem}
.ct-card .num{font-weight:900;font-size:.72rem;background:#f1f5f2;border-radius:8px;padding:.1rem .45rem;margin-right:.3rem}
.ct-card .t small{display:block;color:#6b7570;font-weight:600;margin-top:.15rem}
.ct-card .m{text-align:right;min-width:130px}
.ct-card .m b{display:block;font-size:1.1rem}
.ct-est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;white-space:nowrap;display:inline-block}
.ct-disp{display:flex;gap:.5rem;flex-wrap:wrap;align-items:end}
.ct-disp label{font-size:.75rem;font-weight:800;color:#6b7570;display:block}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-signature me-1"></i>Ventas</small><h1>Cotiza<span>ciones</span></h1>
            <span class="text-muted fw-semibold">Arme la cotización con sus productos, cambie el precio si quiere y envíela en PDF con su logo.</span></div>
        <div class="lp-quick"><a href="{{ route('cotizaciones.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Nueva cotización</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-hourglass-half"></i></div><div class="lbl">Pendientes de respuesta</div><div class="val">{{ $kpi['pendientes'] }}</div><div class="sub">Por {{ dinero($kpi['valorPendientes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-circle-check"></i></div><div class="lbl">Aceptadas este mes</div><div class="val">{{ $kpi['aceptadasMes'] }}</div><div class="sub">Por {{ dinero($kpi['valorAceptadasMes']) }}</div></div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-calendar-check"></i></div><div class="lbl">¿Qué tengo libre?</div>
            <form method="get" action="{{ route('cotizaciones.calendario') }}" class="ct-disp mt-1">
                <div><label>Desde</label><input type="date" name="desde" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
                <div><label>Hasta</label><input type="date" name="hasta" class="form-control form-control-sm"></div>
                <button class="btn btn-sm btn-dark">Ver</button>
            </form>
        </div>
    </div>

    <div class="lp-box">
        <form method="get" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="ver" value="{{ $ver }}">
            <input name="q" value="{{ $q }}" class="form-control" style="max-width:320px" placeholder="Buscar: cliente, celular, asunto o COT-0001…" data-libre>
            <button class="btn btn-dark">Buscar</button>
        </form>
        <div class="ct-fil">
            @foreach (['todas' => 'Todas', 'pendientes' => 'Pendientes', 'aceptadas' => 'Aceptadas', 'vencidas' => 'Vencidas', 'rechazadas' => 'No aceptadas'] as $k => $txt)
                <a href="{{ route('cotizaciones.index', array_filter(['ver' => $k, 'q' => $q])) }}" class="{{ $ver === $k ? 'on' : '' }}">{{ $txt }}</a>
            @endforeach
        </div>

        @forelse ($lista as $c)
        @php [$etq, $col, $fondo] = $c->vencida ? ['Vencida', '#6b7570', '#eef1ef'] : (\App\Models\Cotizacion::ESTADOS[$c->estado] ?? \App\Models\Cotizacion::ESTADOS['pendiente']); @endphp
        <a class="ct-card" href="{{ route('cotizaciones.show', $c) }}">
            <div class="t">
                <b><span class="num">{{ $c->numero }}</span>{{ $c->cliente_nombre }}</b>
                @if ($c->asunto)<small><i class="fa-solid fa-tag me-1"></i>{{ $c->asunto }}</small>@endif
                <small><i class="fa-regular fa-calendar me-1"></i>{{ $c->fecha->format('d/m/Y') }}@if ($c->evento_texto) · <i class="fa-solid fa-calendar-days ms-1 me-1"></i>Alquiler: {{ $c->evento_texto }}@if ($c->horario_texto), {{ $c->horario_texto }}@endif @endif @if ($c->vence) · válida hasta {{ $c->vence->format('d/m/Y') }}@endif</small>
            </div>
            <div class="m"><b>{{ dinero($c->total) }}</b><span class="ct-est" style="color:{{ $col }};background:{{ $fondo }}">{{ $etq }}</span></div>
        </a>
        @empty
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-file-signature fa-2x mb-2" style="color:#bfeccb"></i>
            <p class="mb-2 fw-semibold">{{ $q !== '' || $ver !== 'todas' ? 'No hay cotizaciones con ese filtro.' : 'Todavía no ha hecho cotizaciones.' }}</p>
            <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Hacer la primera</a>
        </div>
        @endforelse
        {{ $lista->links() }}
    </div>
</div>
@endsection
