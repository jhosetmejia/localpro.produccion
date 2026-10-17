@extends('layouts.app')

@section('title', $paciente->nombre_completo)

@push('css')
<style>
.fp-cab{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
.fp-av{width:64px;height:64px;border-radius:20px;display:grid;place-items:center;background:var(--lp-grad);font-weight:900;font-size:1.4rem;color:#0b0d0c}
.fp-datos{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:1rem}
.fp-datos div{background:#f8faf9;border-radius:14px;padding:.55rem .75rem}
.fp-datos small{display:block;color:var(--lp-muted);font-weight:700;font-size:.7rem}
.fp-datos b{font-size:.9rem;word-break:break-word}
.fp-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-top:14px;align-items:start}
.fp-at{display:flex;gap:.8rem;padding:.8rem 0;border-bottom:1px solid #eef3ef;text-decoration:none;color:inherit}
.fp-at:last-child{border-bottom:0}
.fp-at:hover b{color:var(--lp-green-2)}
.fp-at .ic{flex:none;width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:#e7f6ec;color:#11702f}
.fp-at .ic.abierta{background:#fff6d6;color:#9a6a00}
.fp-at .tx{flex:1;min-width:0}.fp-at small{color:var(--lp-muted);font-weight:600;display:block}
.fp-ant dt{font-size:.78rem;color:var(--lp-muted);font-weight:800;margin-top:.5rem}.fp-ant dd{margin:0;white-space:pre-line}
.fp-cita{display:flex;justify-content:space-between;gap:.5rem;padding:.45rem 0;border-bottom:1px dashed #edf2ee;font-size:.88rem}
.fp-cita:last-child{border-bottom:0}
.fp-log{font-size:.78rem;color:var(--lp-muted);display:flex;justify-content:space-between;gap:.5rem;padding:.25rem 0}
@media (max-width:991px){.fp-datos{grid-template-columns:repeat(2,1fr)}.fp-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $p = $paciente;
    $accNombres = ['ver' => 'vio', 'crear' => 'creó', 'editar' => 'editó', 'cerrar' => 'cerró una atención', 'nota' => 'agregó nota', 'imprimir' => 'imprimió'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.pacientes') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Pacientes</a></small></div>
    </div>
    @include('consultorio._nav')

    <div class="cs-card">
        <div class="fp-cab">
            <div class="fp-av">{{ mb_strtoupper(mb_substr($p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1)) }}</div>
            <div class="flex-fill">
                <h2 class="m-0 fw-bold" style="font-size:1.5rem">{{ $p->nombre_completo }}</h2>
                <div class="text-muted fw-semibold">{{ trim($p->tipo_documento . ' ' . $p->documento) ?: 'Sin documento' }}{{ $p->edad ? ' · ' . $p->edad : '' }}{{ $p->sexo ? ' · ' . ['F' => 'Femenino', 'M' => 'Masculino', 'I' => 'Intersexual'][$p->sexo] : '' }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if ($clinico)
                <form method="post" action="{{ route('consultorio.atencion.nueva', $p) }}">@csrf<button class="cs-btn prim"><i class="fa-solid fa-notes-medical"></i>Nueva atención</button></form>
                @endif
                <a href="{{ route('consultorio.agenda', ['paciente' => $p->id]) }}#nueva" class="cs-btn"><i class="fa-solid fa-calendar-plus"></i>Agendar cita</a>
                @if ($p->whatsapp)<a href="https://wa.me/{{ $p->whatsapp }}" target="_blank" rel="noopener" class="cs-btn"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>WhatsApp</a>@endif
                <a href="{{ route('consultorio.pacientes.edit', $p) }}" class="cs-btn"><i class="fa-solid fa-pen"></i>Editar</a>
            </div>
        </div>
        @if ($clinico && $p->alergias)
        <div class="cs-alerta mt-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>Alergias: {{ $p->alergias }}</div>
        @endif
        <div class="fp-datos">
            <div><small>Celular</small><b>{{ $p->telefono ?: '—' }}</b></div>
            <div><small>Correo</small><b>{{ $p->correo ?: '—' }}</b></div>
            <div><small>EPS / prepagada</small><b>{{ $p->aseguradora ?: '—' }}</b></div>
            <div><small>Ocupación</small><b>{{ $p->ocupacion ?: '—' }}</b></div>
            <div><small>Dirección</small><b>{{ trim($p->direccion . ($p->ciudad ? ', ' . $p->ciudad : ''), ', ') ?: '—' }}</b></div>
            <div><small>Contacto de emergencia</small><b>{{ $p->acompanante ?: '—' }}</b></div>
            <div><small>Fecha de nacimiento</small><b>{{ $p->fecha_nacimiento?->format('d/m/Y') ?: '—' }}</b></div>
            <div><small>Datos personales</small><b>{{ $p->autoriza_datos_at ? 'Autorizó ' . $p->autoriza_datos_at->format('d/m/Y') : 'Sin autorización' }}</b></div>
        </div>
    </div>

    <div class="fp-grid">
        <div class="cs-card">
            @if ($clinico)
            <h3>Historia clínica <small>{{ $atenciones->count() }} atención(es)</small>
                @if ($atenciones->whereNotNull('cerrada_at')->count())<a href="{{ route('consultorio.historia', $p) }}" target="_blank" class="cs-btn sm"><i class="fa-solid fa-print"></i>Historia completa</a>@endif</h3>
            @forelse ($atenciones as $a)
            @php $dx = collect($a->diagnosticos ?? [])->first(); @endphp
            <a href="{{ route('consultorio.atencion', $a) }}" class="fp-at">
                <div class="ic {{ $a->cerrada ? '' : 'abierta' }}"><i class="fa-solid {{ $a->cerrada ? 'fa-file-signature' : 'fa-pen-to-square' }}"></i></div>
                <div class="tx">
                    <b>{{ $a->fecha->format('d/m/Y h:i a') }} · {{ \App\Support\Consultorio::plantilla($a->plantilla)['nombre'] }}</b>
                    <small>{{ \Illuminate\Support\Str::limit($a->contenido['motivo'] ?? 'Sin motivo', 90) }}</small>
                    <small>{{ $dx ? trim(($dx['codigo'] ?? '') . ' ' . ($dx['texto'] ?? '')) . ' · ' : '' }}{{ $a->profesional_nombre ?: $a->profesional?->name }}{{ $a->cerrada ? '' : ' · SIN CERRAR' }}</small>
                </div>
            </a>
            @empty
            <div class="lp-empty">Sin atenciones todavía. Toque <b>Nueva atención</b> para empezar la historia.</div>
            @endforelse
            @else
            <h3>Historia clínica</h3>
            <div class="lp-empty"><i class="fa-solid fa-lock me-1"></i>La historia clínica es reservada: solo la ven los profesionales de salud.</div>
            @endif
        </div>

        <div class="d-flex flex-column gap-3">
            @if ($clinico)
            <div class="cs-card">
                <h3>Antecedentes <a href="{{ route('consultorio.pacientes.edit', $p) }}" class="small fw-bold text-decoration-none" style="color:var(--lp-green-2)">Editar</a></h3>
                @php $ant = array_filter((array) $p->antecedentes); @endphp
                @if ($ant || $p->notas)
                <dl class="fp-ant m-0">
                    @foreach ($ant as $k => $v)<dt>{{ \App\Models\Paciente::ANTECEDENTES[$k] ?? $k }}</dt><dd>{{ $v }}</dd>@endforeach
                    @if ($p->notas)<dt>Notas</dt><dd>{{ $p->notas }}</dd>@endif
                </dl>
                @else<div class="text-muted small">Sin antecedentes registrados.</div>@endif
            </div>
            @endif
            <div class="cs-card">
                <h3>Citas</h3>
                @forelse ($citas as $c)
                <div class="fp-cita"><span>{{ $c->fecha->format('d/m/Y') }} · {{ $c->hora_corta }}<br><small class="text-muted">{{ $c->motivo ?: 'Consulta' }}</small></span><span class="cs-est" style="background:{{ \App\Models\Cita::ESTADOS[$c->estado][1] }};align-self:center">{{ \App\Models\Cita::ESTADOS[$c->estado][0] }}</span></div>
                @empty<div class="text-muted small">Sin citas.</div>@endforelse
            </div>
            @if ($accesos->count())
            <div class="cs-card">
                <h3>Quién ha consultado esta historia <small>solo lo ve el dueño</small></h3>
                @foreach ($accesos as $l)
                <div class="fp-log"><span>{{ $l->user?->name ?? '—' }} {{ $accNombres[$l->accion] ?? $l->accion }}</span><span>{{ $l->created_at?->format('d/m/Y h:i a') }}</span></div>
                @endforeach
            </div>
            @endif
            @can('editar-negocio')
            @if (!$atenciones->count() || !$clinico)
            <form method="post" action="{{ route('consultorio.pacientes.destroy', $p) }}" data-confirm="¿Eliminar este paciente?" data-confirm-texto="Se borran sus datos y sus citas. Si ya tiene historia clínica no se puede borrar." data-confirm-ok="Sí, eliminar" data-peligro>
                @csrf @method('DELETE')<button class="btn btn-link text-danger fw-bold p-0 small">Eliminar paciente</button></form>
            @endif
            @endcan
            <div class="cs-privado"><i class="fa-solid fa-lock"></i>Datos de salud reservados (Res. 1995/1999 · Ley 1581/2012).</div>
        </div>
    </div>
</div>
@endsection
