@extends('layouts.app')

@section('title', 'Consultorio')

@push('css')
<style>
.ci-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:1rem}
.ci-k{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem 1.1rem}
.ci-k small{display:block;color:var(--lp-muted);font-weight:700;font-size:.78rem}
.ci-k b{display:block;font-size:1.6rem;font-weight:900;line-height:1.1}
.ci-k.dark{background:var(--lp-black);border-color:var(--lp-black);color:#fff}.ci-k.dark small{color:#a8b3ab}.ci-k.dark b{color:var(--lp-lime)}
.ci-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:14px}
.ci-cita{display:grid;grid-template-columns:74px 1fr auto;gap:.8rem;align-items:center;padding:.75rem 0;border-bottom:1px solid #eef3ef}
.ci-cita:last-child{border-bottom:0}
.ci-hora{font-weight:900;font-size:1rem;text-align:center;background:#f4f8f5;border-radius:14px;padding:.45rem .3rem}
.ci-hora small{display:block;font-weight:700;color:var(--lp-muted);font-size:.68rem}
.ci-cita .tx b{display:block}.ci-cita .tx small{color:var(--lp-muted);font-weight:600}
.ci-acc{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.ci-aviso{display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap;background:#fff8e1;border:1.5px solid #ffe08a;border-radius:20px;padding:.9rem 1.1rem;margin-bottom:1rem}
@media (max-width:991px){.ci-kpis{grid-template-columns:repeat(2,1fr)}.ci-grid{grid-template-columns:1fr}}
@media (max-width:575px){.ci-cita{grid-template-columns:62px 1fr}.ci-acc{grid-column:1/-1;justify-content:flex-start}}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-user-doctor me-1"></i>Consultorio</small><h1>Su día en <span>consulta</span></h1>
            <span class="text-muted fw-semibold">{{ ucfirst(now()->translatedFormat('l j \d\e F')) }}</span></div>
        <div class="lp-quick d-flex gap-2 flex-wrap">
            <a href="{{ route('consultorio.pacientes.create') }}" class="cs-btn"><i class="fa-solid fa-user-plus"></i>Nuevo paciente</a>
            <a href="{{ route('consultorio.agenda') }}#nueva" class="cs-btn prim"><i class="fa-solid fa-calendar-plus"></i>Agendar cita</a>
        </div>
    </div>
    @include('consultorio._nav')

    @if ($sinPerfil)
    <div class="ci-aviso">
        <div><b>Complete su perfil profesional</b><div class="small text-muted">Su número de registro (tarjeta profesional) y su firma salen en la historia, las fórmulas y las incapacidades.</div></div>
        <a href="{{ route('consultorio.configuracion') }}" class="cs-btn dark sm"><i class="fa-solid fa-signature"></i>Completar ahora</a>
    </div>
    @endif

    <div class="ci-kpis">
        <div class="ci-k dark"><small>Citas pendientes hoy</small><b>{{ $kpi['citas_hoy'] }}</b></div>
        <div class="ci-k"><small>Atendidos hoy</small><b>{{ $kpi['atendidas_hoy'] }}</b></div>
        <div class="ci-k"><small>Citas próximos 7 días</small><b>{{ $kpi['semana'] }}</b></div>
        <div class="ci-k"><small>Pacientes</small><b>{{ $kpi['pacientes'] }}</b></div>
    </div>

    <div class="ci-grid">
        <div class="cs-card">
            <h3>Agenda de hoy <a href="{{ route('consultorio.agenda') }}" class="small fw-bold text-decoration-none" style="color:var(--lp-green-2)">Ver la semana →</a></h3>
            @forelse ($hoy as $c)
            @php [$en, $col] = \App\Models\Cita::ESTADOS[$c->estado]; @endphp
            <div class="ci-cita">
                <div class="ci-hora">{{ \Carbon\Carbon::parse($c->hora)->format('h:i') }}<small>{{ \Carbon\Carbon::parse($c->hora)->format('a') }} · {{ $c->duracion }}′</small></div>
                <div class="tx">
                    <b><a href="{{ route('consultorio.paciente', $c->paciente_id) }}" class="text-decoration-none" style="color:inherit">{{ $c->paciente?->nombre_completo }}</a></b>
                    <small>{{ $c->motivo ?: 'Consulta' }}{{ $c->profesional ? ' · ' . $c->profesional->name : '' }}</small>
                    <div><span class="cs-est" style="background:{{ $col }}">{{ $en }}</span></div>
                </div>
                <div class="ci-acc">
                    @if ($c->activa)
                    @if (auth()->user()->can('historia-clinica') || auth()->user()->es_superadmin)
                    <form method="post" action="{{ route('consultorio.atencion.nueva', $c->paciente_id) }}">@csrf<input type="hidden" name="cita" value="{{ $c->id }}"><button class="cs-btn prim sm"><i class="fa-solid fa-notes-medical"></i>Atender</button></form>
                    @endif
                    @if ($c->estado === 'programada')
                    <form method="post" action="{{ route('consultorio.citas.estado', $c) }}">@csrf<input type="hidden" name="estado" value="confirmada"><button class="cs-btn sm">Confirmó</button></form>
                    @endif
                    <form method="post" action="{{ route('consultorio.citas.estado', $c) }}">@csrf<input type="hidden" name="estado" value="no_asistio"><button class="cs-btn sm">No vino</button></form>
                    @elseif ($c->atencion_id && (auth()->user()->can('historia-clinica') || auth()->user()->es_superadmin))
                    <a href="{{ route('consultorio.atencion', $c->atencion_id) }}" class="cs-btn sm"><i class="fa-solid fa-file-medical"></i>Ver atención</a>
                    @endif
                </div>
            </div>
            @empty
            <div class="lp-empty">No hay citas para hoy. <a href="{{ route('consultorio.agenda') }}">Agende una</a>.</div>
            @endforelse
        </div>

        <div class="d-flex flex-column gap-3">
            @if ($kpi['abiertas'])
            <div class="cs-alerta"><i class="fa-solid fa-pen-to-square me-1"></i>Tiene {{ $kpi['abiertas'] }} atención(es) sin cerrar. Ciérrelas para que queden firmadas en la historia.</div>
            @endif
            <div class="cs-card">
                <h3>Pacientes recientes <a href="{{ route('consultorio.pacientes') }}" class="small fw-bold text-decoration-none" style="color:var(--lp-green-2)">Todos →</a></h3>
                @forelse ($recientes as $p)
                <a href="{{ route('consultorio.paciente', $p) }}" class="cs-pac">
                    <div class="cs-av">{{ mb_strtoupper(mb_substr($p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1)) }}</div>
                    <div class="tx"><b>{{ $p->nombre_completo }}</b><small>{{ trim($p->tipo_documento . ' ' . $p->documento) }}{{ $p->edad ? ' · ' . $p->edad : '' }}</small></div>
                </a>
                @empty
                <div class="lp-empty">Aún no hay pacientes. <a href="{{ route('consultorio.pacientes.create') }}">Cree el primero</a>.</div>
                @endforelse
            </div>
            <div class="cs-card">
                <h3>Este mes</h3>
                <div class="d-flex justify-content-between"><span class="text-muted fw-semibold">Atenciones registradas</span><b>{{ $kpi['atenciones_mes'] }}</b></div>
                @can('usar-pos')<a href="{{ route('pos.index') }}" class="cs-btn w-100 justify-content-center mt-3"><i class="fa-solid fa-cash-register"></i>Cobrar una consulta en la caja</a>@endcan
            </div>
            <div class="cs-privado"><i class="fa-solid fa-lock"></i>La historia clínica se guarda cifrada y cada acceso queda registrado.</div>
        </div>
    </div>
</div>
@endsection
