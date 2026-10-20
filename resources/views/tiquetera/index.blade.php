@extends('layouts.app')

@section('title', 'Tiquetera')

@push('css')
<style>
.tq-kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-bottom:1rem}
@media (max-width:760px){.tq-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}}
.tq-kpi div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem}
.tq-kpi div.v{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.tq-kpi b{display:block;font-size:1.5rem;font-weight:900;line-height:1.1}
.tq-kpi .v b{color:#a6f34a}
.tq-kpi small{font-weight:700;color:#6b7570}.tq-kpi .v small{color:#b8c4bc}
.tq-fil{display:flex;gap:.4rem;flex-wrap:wrap;margin:.8rem 0}
.tq-fil a{border-radius:999px;padding:.4rem .9rem;font-weight:800;font-size:.85rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.tq-fil a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.tq-row{display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.8rem 1rem;margin-bottom:.55rem}
.tq-row .t{flex:1 1 220px;min-width:0}
.tq-row .t a{color:inherit;text-decoration:none}
.tq-row .t b{display:block;font-size:1.02rem}
.tq-row .t small{color:#6b7570;font-weight:600}
.tq-dots{display:flex;flex-wrap:wrap;gap:3px;margin-top:.35rem;max-width:340px}
.tq-dots i{width:12px;height:12px;border-radius:4px;background:#dcfce7;border:1.5px solid #16b34a}
.tq-dots i.u{background:#e8eee9;border-color:#cfd8d2}
.tq-q{min-width:92px;text-align:center}
.tq-q b{display:block;font-size:1.5rem;font-weight:900;color:#11702f;line-height:1}
.tq-q small{font-weight:700;color:#6b7570}
.tq-usar{border:0;border-radius:999px;padding:.65rem 1.1rem;font-weight:900;background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;white-space:nowrap}
.tq-usar:disabled{background:#eef1ef;color:#a3aca7}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-ticket me-1"></i>Restaurante</small><h1>Tique<span>tera</span></h1>
            <span class="text-muted fw-semibold">Almuerzos o servicios pagados por adelantado. Busque al cliente y toque <b>Marcar almuerzo</b>: no se le cobra de nuevo.</span></div>
        <div class="lp-quick"><a href="{{ route('tiqueteras.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Vender tiquetera</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="tq-kpi">
        <div class="v"><b>{{ $kpi['activas'] }}</b><small>tiqueteras activas</small></div>
        <div><b>{{ number_format($kpi['pendientes'], 0, ',', '.') }}</b><small>servicios por consumir</small></div>
        <div><b>{{ $kpi['hoy'] }}</b><small>consumidos hoy</small></div>
        <div><b>{{ dinero($kpi['mes']) }}</b><small>vendido este mes</small></div>
    </div>

    <form method="get" class="d-flex gap-2">
        <input type="hidden" name="ver" value="{{ $ver }}">
        <input type="search" name="q" value="{{ $q }}" class="form-control form-control-lg" placeholder="Buscar cliente por nombre, celular o código (TQ-…)" autofocus>
        <button class="btn btn-dark btn-lg"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div class="tq-fil">
        @foreach (['activas' => 'Activas', 'agotadas' => 'Gastadas', 'todas' => 'Todas'] as $k => $n)<a href="{{ route('tiqueteras.index', ['ver' => $k, 'q' => $q]) }}" class="{{ $ver === $k ? 'on' : '' }}">{{ $n }}</a>@endforeach
    </div>

    @forelse ($lista as $t)
    <div class="tq-row">
        <div class="t"><a href="{{ route('tiqueteras.show', $t) }}"><b>{{ $t->cliente_nombre }}</b>
            <small>{{ $t->codigo }} · {{ $t->nombre }}{{ $t->cliente_telefono ? ' · ' . $t->cliente_telefono : '' }}@if ($t->vence) · {!! $t->vencida ? '<span class="text-danger">venció ' . $t->vence->format('d/m/Y') . '</span>' : 'vence ' . $t->vence->format('d/m/Y') !!}@endif</small></a>
            @if ($t->total <= 60)<div class="tq-dots">@for ($i = 1; $i <= $t->total; $i++)<i class="{{ $i <= $t->usados ? 'u' : '' }}"></i>@endfor</div>@endif
        </div>
        <div class="tq-q"><b>{{ $t->quedan }}</b><small>de {{ $t->total }}</small></div>
        <form method="post" action="{{ route('tiqueteras.usar', $t) }}">@csrf<input type="hidden" name="volver" value="lista"><input type="hidden" name="q" value="{{ $q }}">
            <button class="tq-usar" @disabled(!$t->quedan || $t->vencida)><i class="fa-solid fa-utensils me-1"></i>Marcar almuerzo</button></form>
    </div>
    @empty
    <div class="lp-box text-center text-muted fw-semibold">
        @if ($q !== '')No encontramos tiqueteras con "{{ $q }}".@else Aún no hay tiqueteras. Toque <b>Vender tiquetera</b>: el cliente paga por ejemplo 20 almuerzos y cada día se le descuenta uno.@endif
    </div>
    @endforelse
    {{ $lista->links('pagination::bootstrap-5') }}
</div>
@endsection
