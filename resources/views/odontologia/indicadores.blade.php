@extends('layouts.app')

@section('title', 'Indicadores del consultorio')

@push('css')
<style>
.in-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.in-k{background:#fff;border:1.5px solid #d6eef3;border-radius:20px;padding:.9rem 1rem}
.in-k small{display:block;color:var(--lp-muted);font-weight:700;font-size:.76rem}
.in-k b{display:block;font-size:1.45rem;font-weight:900;line-height:1.2}
.in-k span{font-size:.75rem;color:var(--lp-muted);font-weight:600}
.in-k.dark{background:linear-gradient(135deg,#0e7490,#155e75);border:0;color:#fff}.in-k.dark small,.in-k.dark span{color:#a5f3fc}
.in-bars{display:flex;align-items:flex-end;gap:12px;height:170px;padding-top:10px}
.in-bar{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px}
.in-bar i{display:block;width:100%;max-width:56px;border-radius:10px 10px 4px 4px;background:#a5f3fc;min-height:3px}
.in-bar.on i{background:#0891b2}
.in-bar small{font-weight:800;color:var(--lp-muted);font-size:.72rem}
.in-bar em{font-style:normal;font-size:.68rem;font-weight:800;color:#0e7490;white-space:nowrap}
.in-row{display:flex;justify-content:space-between;gap:10px;padding:.5rem 0;border-bottom:1px solid #eef5f7;align-items:center;font-size:.9rem}
.in-pbar{height:6px;background:#e2f3f6;border-radius:6px;overflow:hidden;margin-top:4px}.in-pbar i{display:block;height:100%;background:#22d3ee}
@media (max-width:991px){.in-kpis{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
@php $max = max(1, $serie->max('valor')); $maxProd = max(1, $produccion->max('valor') ?? 1); $t = \App\Support\TiendaActual::tienda(); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-chart-line me-1"></i>Consultorio odontológico</small><h1>Indicadores del <span>consultorio</span></h1>
            <span class="text-muted fw-semibold">{{ ucfirst($mes->translatedFormat('F Y')) }}</span></div>
        <form method="get"><input type="month" name="mes" value="{{ $mes->format('Y-m') }}" class="form-control" onchange="this.form.submit()" style="border-radius:999px"></form>
    </div>
    @include('consultorio._nav')
    @include('odontologia._sub')

    <div class="in-kpis">
        <div class="in-k dark"><small>Ingresos del mes</small><b>{{ dinero($kpi['ingresos']) }}</b><span>Abonos {{ dinero($kpi['abonos']) }} · Caja {{ dinero($kpi['ventas']) }}</span></div>
        <div class="in-k"><small>Pacientes nuevos</small><b>{{ $kpi['nuevos'] }}</b><span>{{ $kpi['en_linea'] }} cita(s) pedidas en línea</span></div>
        <div class="in-k"><small>Citas</small><b>{{ $kpi['atendidas'] }} <span>de {{ $kpi['citas'] }} atendidas</span></b><span style="color:{{ $kpi['no_asistio'] >= 15 ? '#e11d48' : 'inherit' }}">{{ $kpi['no_asistio'] }} % no asistió</span></div>
        <div class="in-k"><small>Presupuestos aprobados</small><b>{{ $kpi['aprobados'] }} <span>de {{ $kpi['presupuestos'] }}</span></b><span>{{ $kpi['presupuestos'] ? round($kpi['aprobados'] * 100 / $kpi['presupuestos']) : 0 }} % de aceptación</span></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="cs-card mb-3">
                <h3>Ingresos de los últimos 6 meses</h3>
                <div class="in-bars" role="img" aria-label="Ingresos por mes">
                    @foreach ($serie as $i => $s)
                    <div class="in-bar {{ $loop->last ? 'on' : '' }}"><em>{{ $s['valor'] ? dinero($s['valor']) : '' }}</em><i style="height:{{ max(2, round($s['valor'] * 100 / $max)) }}%"></i><small>{{ $s['mes'] }}</small></div>
                    @endforeach
                </div>
            </div>
            <div class="cs-card">
                <h3>Procedimientos que más producen <small>este mes</small></h3>
                @forelse ($produccion as $pr)
                <div class="in-row" style="display:block"><div class="d-flex justify-content-between"><span>{{ $pr['nombre'] }} <small class="text-muted">× {{ $pr['n'] }}</small></span><b>{{ dinero($pr['valor']) }}</b></div>
                    <div class="in-pbar"><i style="width:{{ round($pr['valor'] * 100 / $maxProd) }}%"></i></div></div>
                @empty<div class="text-muted small">Sin procedimientos hechos este mes.</div>@endforelse
            </div>
        </div>
        <div class="col-lg-5">
            <div class="cs-card mb-3">
                <h3>Por cobrar <small>tratamientos en curso</small></h3>
                <div style="font-size:1.6rem;font-weight:900;color:#e11d48">{{ dinero($kpi['por_cobrar']) }}</div>
                <a href="{{ route('odonto.tratamientos', ['estado' => 'aprobado']) }}" class="small fw-bold" style="color:#0e7490">Ver tratamientos y saldos</a>
            </div>
            <div class="cs-card mb-3">
                <h3>Ortodoncia con cuotas atrasadas <small>{{ $ortoAtrasados->count() }}</small></h3>
                @forelse ($ortoAtrasados as $pl)
                <div class="in-row"><a href="{{ route('odonto.plan', $pl) }}" class="text-decoration-none" style="color:inherit"><b>{{ $pl->paciente?->nombre_completo }}</b><br><small class="text-muted">{{ $pl->cuotas_atrasadas }} cuota(s) · {{ dinero($pl->atraso) }}</small></a>
                    @if ($pl->paciente?->whatsapp)<a class="cs-btn sm" target="_blank" rel="noopener" href="https://wa.me/{{ $pl->paciente->whatsapp }}?text={{ rawurlencode('Hola ' . $pl->paciente->nombres . ', le recordamos que tiene ' . $pl->cuotas_atrasadas . ' cuota(s) pendiente(s) de su ortodoncia por ' . dinero($pl->atraso) . '. ¡Gracias!') }}"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i></a>@endif</div>
                @empty<div class="text-muted small">Todos al día.</div>@endforelse
            </div>
            <div class="cs-card">
                <h3>Es hora de su control <small>{{ $recall->count() }}</small></h3>
                <form method="get" class="d-flex gap-2 align-items-center mb-2 small"><input type="hidden" name="mes" value="{{ $mes->format('Y-m') }}">Sin venir hace más de
                    <select name="meses" class="form-select form-select-sm" data-nativo style="width:auto;padding-right:2rem" onchange="this.form.submit()">@foreach ([3, 6, 9, 12] as $m)<option value="{{ $m }}" @selected($meses === $m)>{{ $m }} meses</option>@endforeach</select> y sin cita próxima</form>
                @forelse ($recall as $r)
                @php $msg = 'Hola ' . $r['p']->nombres . ', en ' . $t->nombre . ' le recordamos que ya es hora de su control y limpieza (su última cita fue el ' . $r['ultima']->format('d/m/Y') . ').' . (\App\Support\AgendaEnLinea::config($t)['activa'] ? ' Pida su cita aquí: ' . route('citas.publica', $t->slug) : ' Escríbanos para agendarla.') . ' ¡Lo esperamos!'; @endphp
                <div class="in-row"><a href="{{ route('consultorio.paciente', $r['p']) }}" class="text-decoration-none" style="color:inherit"><b>{{ $r['p']->nombre_completo }}</b><br><small class="text-muted">Última cita {{ $r['ultima']->format('d/m/Y') }}</small></a>
                    @if ($r['p']->whatsapp)<a class="cs-btn sm" target="_blank" rel="noopener" href="https://wa.me/{{ $r['p']->whatsapp }}?text={{ rawurlencode($msg) }}"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>Invitar</a>@endif</div>
                @empty<div class="text-muted small">Nadie pendiente de control.</div>@endforelse
            </div>
        </div>
    </div>
</div>
@endsection
