@extends('layouts.app')

@section('title', $e->nombre)

@section('content')
@php
    $pend = $cargos->whereIn('estado', ['pendiente', 'parcial']);
    $vencido = $pend->filter->vencido->sum->saldo;
    $tel = numero_whatsapp($e->contacto);
    $txt = "Hola " . $e->nombres . ", le compartimos su estado de cuenta en " . $inst->nombre . ":\n\n";
    foreach ($pend as $c) { $txt .= "• " . $c->concepto . ": " . dinero($c->saldo) . ($c->vence ? " (vence " . $c->vence->format('d/m/Y') . ")" : '') . "\n"; }
    $txt .= "\nTotal: " . dinero($saldo) . "\nRecuerde que las cuotas se pagan del 1 al " . ($inst->inst_dia_pago ?: 5) . " de cada mes. ¡Gracias!";
    $estados = \App\Models\Instituto\Estudiante::ESTADOS;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('instituto.estudiantes') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Estudiantes</a></small><h1>{{ $e->nombre }}</h1>
            <span class="text-muted fw-semibold">{{ $e->tipo_doc }} {{ $e->documento ?: '—' }} · {{ $e->programa->nombre ?? 'Sin programa' }}{{ $e->jornada ? ' · ' . (\App\Models\Instituto\Estudiante::JORNADAS[$e->jornada] ?? '') : '' }} ·
                <span class="badge {{ $e->estado === 'matriculado' ? 'bg-success' : ($e->estado === 'aspirante' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $estados[$e->estado] ?? $e->estado }}</span></span></div>
        <div class="lp-quick">
            <a class="btn btn-primary" href="{{ route('instituto.matricula', $e) }}"><i class="fa-solid fa-file-signature me-1"></i>Matricular</a>
            @if ($tel)<a class="btn btn-success" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($saldo > 0 ? $txt : 'Hola ' . $e->nombres . ', está al día con ' . $inst->nombre . '. ¡Gracias!') }}"><i class="fa-brands fa-whatsapp me-1"></i>Estado de cuenta</a>@endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-scale-balanced"></i></div><div class="lbl">Saldo pendiente</div><div class="val">{{ $saldo > 0 ? dinero($saldo) : 'Al día ✅' }}</div><div class="sub">{{ $vencido > 0 ? 'Vencido ' . dinero($vencido) . ' · ' : '' }}{{ $pend->count() }} cobro(s){{ $favor > 0 ? ' · saldo a favor ' . dinero($favor) : '' }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-address-card"></i></div><div class="lbl">Contacto</div><div class="val" style="font-size:1.05rem">{{ $e->celular ?: ($e->telefono ?: '—') }}</div><div class="sub">{{ $e->email ?: 'Sin correo' }}{{ $e->acu_nombre ? ' · acudiente: ' . $e->acu_nombre . ($e->acu_telefono ? ' ' . $e->acu_telefono : '') : '' }}</div></div>
        <div class="lp-kpi">
            <div class="lbl mb-2">Registrar pago</div>
            <form method="post" action="{{ route('instituto.pago.store', $e) }}" class="row g-2">
                @csrf
                <div class="col-6"><input data-dinero type="number" name="valor" class="form-control fw-bold" min="1" required placeholder="Valor" value="{{ old('valor', $vencido > 0 ? (int) $vencido : (($p1 = $pend->first()) ? (int) $p1->saldo : '')) }}"></div>
                <div class="col-6"><input type="date" name="fecha" class="form-control" value="{{ old('fecha', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"></div>
                <div class="col-6"><select name="metodo" class="form-select">@foreach ($metodos as $m)<option>{{ $m }}</option>@endforeach</select></div>
                <div class="col-6"><input name="referencia" class="form-control" maxlength="60" placeholder="Referencia"></div>
                <div class="col-12 d-grid"><button class="btn btn-dark">Registrar y generar recibo</button></div>
            </form>
        </div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3>Estado de cuenta</h3>
            <ul class="lp-list">
                @forelse ($cargos as $c)
                <li>
                    <div class="av" style="{{ $c->vencido ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid {{ $c->estado === 'pagado' ? 'fa-circle-check' : 'fa-file-invoice' }}"></i></div>
                    <div class="t"><b>{{ $c->concepto }}</b><small>{{ dinero($c->valor) }}{{ $c->pagado > 0 && $c->estado !== 'pagado' ? ' · abonado ' . dinero($c->pagado) : '' }}{{ $c->vence ? ' · vence ' . $c->vence->format('d/m/Y') : '' }}</small></div>
                    <span class="badge {{ $c->estado === 'pagado' ? 'bg-success' : ($c->vencido ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $c->estado === 'pagado' ? 'Pagado' : ($c->vencido ? 'Vencido' : ucfirst($c->estado)) }}</span>
                    @if ($admin && $c->pagado <= 0 && $c->estado !== 'pagado')
                    <form method="post" action="{{ route('instituto.cargo.anular', $c) }}" data-confirm="¿Anular este cobro?" data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-sm btn-light" title="Anular"><i class="fa-solid fa-xmark"></i></button></form>
                    @endif
                </li>
                @empty
                <li class="lp-empty">Sin cobros todavía. Al matricularlo se crean solos.</li>
                @endforelse
            </ul>
            <details class="mt-2">
                <summary class="small fw-bold" style="cursor:pointer;color:#16b34a"><i class="fa-solid fa-plus me-1"></i>Agregar otro cobro (carné, uniforme, certificado…)</summary>
                <form method="post" action="{{ route('instituto.cargo.store', $e) }}" class="row g-2 mt-1">
                    @csrf
                    <div class="col-md-6"><input name="concepto" class="form-control" maxlength="150" required placeholder="Concepto"></div>
                    <div class="col-6 col-md-3"><input data-dinero type="number" name="valor" class="form-control" min="1" required placeholder="Valor"></div>
                    <div class="col-6 col-md-3"><input type="date" name="vence" class="form-control" value="{{ today()->format('Y-m-d') }}"></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary">Agregar cobro</button></div>
                </form>
            </details>
        </div>
        <div class="lp-box">
            <h3>Pagos</h3>
            <ul class="lp-list">
                @forelse ($pagos as $p)
                <li style="{{ $p->anulado ? 'opacity:.5' : '' }}"><div class="av"><i class="fa-solid fa-receipt"></i></div>
                    <div class="t"><b><a href="{{ route('instituto.recibo', $p) }}" class="text-decoration-none" style="color:inherit">{{ dinero($p->valor) }} · {{ $p->numero }}</a></b><small>{{ $p->fecha->format('d/m/Y') }} · {{ $p->metodo }}{{ $p->anulado ? ' · ANULADO' : '' }}</small></div></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-box mb-3">
        <h3><span>Matrículas</span><a class="small" href="{{ route('instituto.formato.inscripcion', $e) }}" target="_blank"><i class="fa-solid fa-print me-1"></i>Formulario de inscripción No. {{ $e->consecutivo }}</a></h3>
        <ul class="lp-list">
            @forelse ($matriculas as $m)
            <li style="flex-wrap:wrap;{{ $m->estado === 'activa' ? '' : 'opacity:.55' }}">
                <div class="av"><i class="fa-solid fa-file-signature"></i></div>
                <div class="t"><b>{{ $m->programa_nombre }} · semestre {{ $m->semestre }}</b>
                    <small>Periodo {{ $m->periodo_texto }} · {{ \App\Models\Instituto\Estudiante::JORNADAS[$m->jornada] ?? '' }} ·
                        {{ $m->modalidad === 'credito' ? 'Crédito: inicial ' . dinero($m->cuota_inicial) . ' + ' . $m->num_cuotas . ' cuotas de ' . dinero($m->valor_cuota) : 'Contado ' . dinero($m->valor_semestre) }}{{ $m->estado !== 'activa' ? ' · ' . strtoupper($m->estado) : '' }}</small></div>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-light" target="_blank" href="{{ route('instituto.formato.matricula', $m) }}" title="Imprimir formato de matrícula"><i class="fa-solid fa-print"></i></a>
                    @if ($admin && $m->estado === 'activa')
                    <form method="post" action="{{ route('instituto.matricula.anular', $m) }}" data-confirm="¿Anular esta matrícula?" data-confirm-texto="Se anulan también sus cuotas pendientes (solo si no tienen pagos)." data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-sm btn-light" title="Anular"><i class="fa-solid fa-xmark"></i></button></form>
                    @endif
                </div>
            </li>
            @empty
            <li class="lp-empty">Aún no está matriculado. <a href="{{ route('instituto.matricula', $e) }}">Matricularlo ahora</a>.</li>
            @endforelse
        </ul>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3><span>Datos del estudiante</span><a class="small" href="{{ route('instituto.estudiante.editar', $e) }}"><i class="fa-solid fa-pen me-1"></i>Editar</a></h3>
            <dl class="row small mb-0">
                <dt class="col-5">Nacimiento</dt><dd class="col-7">{{ $e->fecha_nacimiento?->format('d/m/Y') ?: '—' }}{{ $e->edad !== null ? ' (' . $e->edad . ' años)' : '' }}{{ $e->lugar_nacimiento ? ' · ' . $e->lugar_nacimiento : '' }}</dd>
                <dt class="col-5">Dirección</dt><dd class="col-7">{{ trim($e->direccion . ', ' . $e->ciudad, ', ') ?: '—' }}</dd>
                <dt class="col-5">EPS / estrato</dt><dd class="col-7">{{ $e->eps ?: '—' }}{{ $e->estrato ? ' · estrato ' . $e->estrato : '' }}</dd>
                <dt class="col-5">Enfermedad</dt><dd class="col-7">{{ $e->enfermedad ?: 'No' }}</dd>
                <dt class="col-5">Estudios</dt><dd class="col-7">{{ $e->bach_titulo ?: ($e->noveno_institucion ? 'Grado noveno · ' . $e->noveno_institucion : '—') }}{{ $e->bach_institucion ? ' · ' . $e->bach_institucion : '' }}</dd>
                <dt class="col-5">Trabajo</dt><dd class="col-7">{{ $e->lab_entidad ? $e->lab_entidad . ($e->lab_cargo ? ' · ' . $e->lab_cargo : '') : '—' }}</dd>
                <dt class="col-5">Autorizaciones</dt><dd class="col-7">{{ $e->autoriza_datos ? 'Datos ✔' : 'Datos ✘' }} · {{ $e->autoriza_imagen ? 'Imagen ✔' : 'Imagen ✘' }}</dd>
                <dt class="col-5">Inscripción</dt><dd class="col-7">No. {{ $e->consecutivo }} · {{ $e->fecha_inscripcion?->format('d/m/Y') }}</dd>
            </dl>
        </div>
        @if ($admin)
        <div class="lp-box">
            <h3>Estado del estudiante</h3>
            <form method="post" action="{{ route('instituto.estudiante.estado', $e) }}" class="d-flex gap-2">
                @csrf
                <select name="estado" class="form-select">@foreach ($estados as $k => $n)<option value="{{ $k }}" @selected($e->estado === $k)>{{ $n }}</option>@endforeach</select>
                <button class="btn btn-dark">Cambiar</button>
            </form>
            <p class="small text-muted mt-2 mb-0">Al matricularlo queda como "Matriculado" solo. Use Retirado o Graduado cuando corresponda.</p>
        </div>
        @endif
    </div>
</div>
@endsection
