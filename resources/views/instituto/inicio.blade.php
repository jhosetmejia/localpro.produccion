@extends('layouts.app')

@section('title', 'Resumen del instituto')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-school me-1"></i>Instituto</small><h1>{{ $inst->nombre }}</h1>
            <span class="text-muted fw-semibold">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }} · periodo {{ \App\Models\Instituto\Matricula::periodoActual() }}</span></div>
        <div class="lp-quick">
            <a href="{{ route('instituto.inscripcion') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>Nueva inscripción</a>
            <a href="{{ route('instituto.estudiantes') }}" class="btn btn-dark"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Registrar pago</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    @if ($kpi['programas'] === 0)
    <div class="lp-box mb-3" style="background:#f7fdf8;border-color:#bfeccb">
        <h3>Primeros pasos</h3>
        <ol class="mb-0 fw-semibold">
            <li><a href="{{ route('instituto.programas') }}">Cree los programas</a> que ofrece (o cargue los de ejemplo) con el valor del semestre y de la inscripción.</li>
            <li>Haga la <a href="{{ route('instituto.inscripcion') }}">inscripción</a> de cada aspirante (el mismo formulario de papel, pero en el sistema).</li>
            <li>Matricúlelo de contado o a crédito: el sistema arma las cuotas mensuales y le avisa quién está en mora.</li>
        </ol>
    </div>
    @endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Recaudado este mes</div><div class="val">{{ dinero($kpi['recaudoMes']) }}</div><div class="sub">{{ $kpi['pagosMes'] }} pago(s) registrados</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-user-graduate"></i></div><div class="lbl">Estudiantes matriculados</div><div class="val">{{ $kpi['matriculados'] }}</div><div class="sub">{{ $kpi['matriculasPeriodo'] }} matrículas en este periodo · {{ $kpi['aspirantes'] }} aspirante(s)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="lbl">Cartera vencida</div><div class="val">{{ dinero($kpi['vencido']) }}</div><div class="sub">{{ $kpi['enMora'] }} estudiante(s) en mora · por cobrar {{ dinero($kpi['cartera']) }}</div></div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-calendar-day me-1"></i> Cuotas que vencen esta semana</span><a href="{{ route('instituto.cartera', ['vencido' => 0]) }}" class="small">Ver todo</a></h3>
            <ul class="lp-list">
                @forelse ($proximas as $c)
                <li><div class="av"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="t"><b><a href="{{ route('instituto.estudiante', $c->estudiante_id) }}" class="text-decoration-none" style="color:inherit">{{ $c->estudiante->nombre ?? '' }}</a> · {{ dinero($c->saldo) }}</b><small>{{ $c->concepto }} · vence {{ $c->vence->locale('es')->isoFormat('ddd D MMM') }}</small></div></li>
                @empty
                <li class="lp-empty">No hay cuotas por vencer en los próximos 7 días.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-receipt me-1"></i> Últimos pagos</span><a href="{{ route('instituto.pagos') }}" class="small">Ver todos</a></h3>
            <ul class="lp-list">
                @forelse ($ultimosPagos as $p)
                <li><div class="av"><i class="fa-solid fa-receipt"></i></div><div class="t"><b><a href="{{ route('instituto.recibo', $p) }}" class="text-decoration-none" style="color:inherit">{{ dinero($p->valor) }} · {{ $p->estudiante->nombre ?? '' }}</a></b><small>{{ $p->numero }} · {{ $p->fecha->format('d/m/Y') }} · {{ $p->metodo }}</small></div></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-user-clock me-1"></i> Aspirantes por matricular</span><a href="{{ route('instituto.estudiantes', ['estado' => 'aspirante']) }}" class="small">Ver todos</a></h3>
        <ul class="lp-list">
            @forelse ($aspirantes as $a)
            <li><div class="av"><i class="fa-solid fa-user"></i></div>
                <div class="t"><b><a href="{{ route('instituto.estudiante', $a) }}" class="text-decoration-none" style="color:inherit">{{ $a->nombre }}</a></b><small>{{ $a->programa->nombre ?? 'Sin programa' }} · inscrito {{ $a->fecha_inscripcion?->format('d/m/Y') }}</small></div>
                <a class="btn btn-sm btn-primary" href="{{ route('instituto.matricula', $a) }}">Matricular</a></li>
            @empty
            <li class="lp-empty">No hay aspirantes pendientes.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
