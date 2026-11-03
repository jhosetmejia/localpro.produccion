@extends('layouts.app')

@section('title', 'Tratamientos y saldos')

@push('css')
<style>
.tr-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:1rem}
.tr-k{background:#fff;border:1.5px solid #d6eef3;border-radius:20px;padding:1rem 1.1rem}
.tr-k small{display:block;color:var(--lp-muted);font-weight:700;font-size:.78rem}
.tr-k b{display:block;font-size:1.5rem;font-weight:900;line-height:1.15}
.tr-k.dark{background:linear-gradient(135deg,#0e7490,#155e75);border:0;color:#fff}.tr-k.dark small{color:#a5f3fc}
.tr-fil{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:.8rem}
.tr-fil a{border:1.5px solid var(--lp-line);border-radius:999px;padding:.35rem .85rem;font-weight:800;font-size:.82rem;text-decoration:none;color:var(--lp-ink);background:#fff}
.tr-fil a.on{background:#0e7490;border-color:#0e7490;color:#fff}
.tr-fila{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr auto;gap:.8rem;align-items:center;padding:.75rem 0;border-bottom:1px solid #eef5f7;text-decoration:none;color:inherit}
.tr-fila:hover b{color:#0e7490}
.tr-fila small{display:block;color:var(--lp-muted);font-weight:600}
.tr-bar{height:6px;border-radius:9px;background:#e2f3f6;overflow:hidden;margin-top:.3rem}.tr-bar i{display:block;height:100%;background:linear-gradient(90deg,#22d3ee,#0891b2)}
@media (max-width:991px){.tr-kpis{grid-template-columns:repeat(2,1fr)}.tr-fila{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-tooth me-1"></i>Consultorio odontológico</small><h1>Tratamientos y <span>saldos</span></h1>
            <span class="text-muted fw-semibold">Presupuestos, tratamientos en curso y lo que le deben sus pacientes.</span></div>
    </div>
    @include('consultorio._nav')

    <div class="tr-kpis">
        <div class="tr-k dark"><small>Por cobrar (en tratamiento)</small><b>{{ dinero($kpi['por_cobrar']) }}</b></div>
        <div class="tr-k"><small>Tratamientos en curso</small><b>{{ $kpi['en_curso'] }}</b></div>
        <div class="tr-k"><small>Presupuestos sin aprobar</small><b>{{ $kpi['presupuestos'] }}</b></div>
        <div class="tr-k"><small>Abonos de este mes</small><b>{{ dinero($kpi['abonos_mes']) }}</b></div>
    </div>

    <div class="cs-card">
        <div class="tr-fil">
            @foreach (['activos' => 'Activos', 'propuesto' => 'Presupuestos', 'aprobado' => 'En tratamiento', 'terminado' => 'Terminados', 'cancelado' => 'Cancelados', 'todos' => 'Todos'] as $k => $txt)
            <a href="{{ route('odonto.tratamientos', ['estado' => $k, 'q' => $q ?: null]) }}" class="{{ $estado === $k ? 'on' : '' }}">{{ $txt }}</a>
            @endforeach
            <form class="ms-auto d-flex gap-2" method="get"><input type="hidden" name="estado" value="{{ $estado }}">
                <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Buscar paciente o documento" style="border-radius:999px;min-width:220px"></form>
        </div>
        @forelse ($planes as $pl)
        @php [$en, $ec] = \App\Models\TratamientoPlan::ESTADOS[$pl->estado]; @endphp
        <a href="{{ route('odonto.plan', $pl) }}" class="tr-fila">
            <div><b>{{ $pl->paciente?->nombre_completo }}</b><small>{{ $pl->numero }} · {{ $pl->nombre }}</small><div class="tr-bar"><i style="width:{{ $pl->avance }}%"></i></div></div>
            <div><small>Total</small><b>{{ dinero($pl->total) }}</b></div>
            <div><small>Abonado</small><b style="color:#16a34a">{{ dinero($pl->abonado) }}</b></div>
            <div><small>Saldo</small><b style="color:{{ $pl->saldo > 0 ? '#e11d48' : '#16a34a' }}">{{ dinero($pl->saldo) }}</b></div>
            <span class="cs-est" style="background:{{ $ec }}">{{ $en }}</span>
        </a>
        @empty
        <div class="lp-empty">No hay planes en esta lista. Desde la ficha del paciente toque <b>Nuevo plan</b>, o haga el odontograma y toque <b>Pasar al plan de tratamiento</b>.</div>
        @endforelse
        <div class="mt-3">{{ $planes->links() }}</div>
    </div>
</div>
@endsection
