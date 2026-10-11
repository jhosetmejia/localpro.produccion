@extends('layouts.app')

@section('title', 'Resumen del conjunto')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>{{ $conj->nombre }}</h1>
            <span class="text-muted fw-semibold">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span></div>
        <div class="lp-quick">
            <a href="{{ route('conjunto.cartera') }}" class="btn btn-dark"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Registrar pago</a>
            <a href="{{ route('conjunto.cobros') }}" class="btn btn-primary"><i class="fa-solid fa-file-invoice me-1"></i>Cobros</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    @if ($kpi['unidades'] === 0)
    <div class="lp-box mb-3" style="background:#f7fdf8;border-color:#bfeccb">
        <h3>Primeros pasos</h3>
        <ol class="mb-0 fw-semibold">
            <li><a href="{{ route('conjunto.unidades') }}">Cree las unidades</a> (puede crearlas en lote: torres, pisos y apartamentos por piso).</li>
            <li>Ponga la cuota de administración de cada una.</li>
            <li>En <a href="{{ route('conjunto.cobros') }}">Cobros</a> genere el cobro del mes y empiece a registrar pagos.</li>
        </ol>
    </div>
    @elseif (!$kpi['cobroMesGenerado'])
    <div class="lp-box mb-3" style="background:#fff5d6;border-color:#f3e0a6">
        <form method="post" action="{{ route('conjunto.cobros.mes') }}" class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            @csrf <input type="hidden" name="periodo" value="{{ now()->format('Y-m') }}">
            <b><i class="fa-solid fa-bell me-1"></i>Aún no ha generado el cobro de administración de {{ now()->locale('es')->isoFormat('MMMM') }}.</b>
            <button class="btn btn-dark">Generar cobro del mes</button>
        </form>
    </div>
    @endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Recaudado este mes</div><div class="val">{{ dinero($kpi['recaudoMes']) }}</div><div class="sub">Facturado del mes {{ dinero($kpi['facturadoMes']) }} · gastos {{ dinero($kpi['gastosMes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="lbl">Cartera por cobrar</div><div class="val">{{ dinero($kpi['cartera']) }}</div><div class="sub">{{ $kpi['enMora'] }} unidad(es) en mora</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-scale-balanced"></i></div><div class="lbl">Saldo del mes</div><div class="val">{{ dinero($kpi['recaudoMes'] - $kpi['gastosMes']) }}</div><div class="sub">{{ $kpi['unidades'] }} unidades · {{ $kpi['pqrsAbiertas'] }} PQRS abiertas</div></div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-triangle-exclamation me-1"></i> Unidades en mora</span><a href="{{ route('conjunto.cartera') }}" class="small">Ver cartera</a></h3>
            <ul class="lp-list">
                @forelse ($morosos as $m)
                <li><div class="av" style="background:#ffe9f3;color:#b0206a"><i class="fa-solid fa-door-closed"></i></div>
                    <div class="t"><b><a href="{{ route('conjunto.unidad', $m->unidad_id) }}" class="text-decoration-none" style="color:inherit">{{ $m->unidad->nombre ?? '' }}</a> · {{ dinero($m->deuda) }}</b><small>{{ $m->unidad->persona ?? 'Sin nombre' }} · desde {{ \Carbon\Carbon::parse($m->desde)->format('d/m/Y') }}</small></div></li>
                @empty
                <li class="lp-empty">Nadie en mora. ✅</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-receipt me-1"></i> Últimos pagos</span><a href="{{ route('conjunto.pagos') }}" class="small">Ver todos</a></h3>
            <ul class="lp-list">
                @forelse ($ultimosPagos as $p)
                <li><div class="av"><i class="fa-solid fa-receipt"></i></div><div class="t"><b><a href="{{ route('conjunto.recibo', $p) }}" class="text-decoration-none" style="color:inherit">{{ dinero($p->valor) }} · {{ $p->unidad->nombre ?? '' }}</a></b><small>{{ $p->numero }} · {{ $p->fecha->format('d/m/Y') }} · {{ $p->metodo }}</small></div></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-calendar-check me-1"></i> Próximas reservas</span><a href="{{ route('conjunto.reservas') }}" class="small">Ver todas</a></h3>
            <ul class="lp-list">
                @forelse ($reservas as $r)
                <li><div class="av"><i class="fa-solid fa-calendar-day"></i></div><div class="t"><b>{{ $r->zona }}</b><small>{{ $r->fecha->locale('es')->isoFormat('ddd D MMM') }} · {{ substr($r->hora_inicio, 0, 5) }}–{{ substr($r->hora_fin, 0, 5) }} · {{ $r->unidad->nombre ?? '' }}</small></div></li>
                @empty
                <li class="lp-empty">Sin reservas próximas.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-headset me-1"></i> PQRS abiertas</span><a href="{{ route('conjunto.pqrs') }}" class="small">Ver todas</a></h3>
            <ul class="lp-list">
                @forelse ($pqrs as $q)
                <li><div class="av"><i class="fa-solid fa-headset"></i></div><div class="t"><b>{{ $q->asunto }}</b><small>{{ \App\Models\Conjunto\Pqrs::TIPOS[$q->tipo] ?? '' }} · {{ $q->unidad->nombre ?? 'General' }} · {{ $q->created_at->locale('es')->diffForHumans() }}</small></div></li>
                @empty
                <li class="lp-empty">Sin solicitudes abiertas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
