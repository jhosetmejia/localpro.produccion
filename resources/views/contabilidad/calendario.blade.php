@extends('layouts.app')

@section('title', 'Calendario tributario')

@section('content')
@php
    $fechaTxt = fn ($d) => ucfirst($d->copy()->locale('es')->isoFormat('dddd D [de] MMMM'));
    $dig = fn ($nit) => \App\Support\CalendarioTributario::digito($nit);
@endphp
<style>
.ct-dia{border-left:4px solid #dfe5e1;padding-left:12px;margin-bottom:18px}
.ct-dia.danger{border-color:#d93025}.ct-dia.warning{border-color:#f5b544}.ct-dia.ok{border-color:#2fd15a}
.ct-venc{display:flex;align-items:center;gap:12px;padding:8px 10px;border:1px solid #edf1ee;border-radius:12px;margin-bottom:6px;background:#fff}
.ct-venc.hecho{opacity:.55}
.ct-ico{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:#f4fcf6;color:#11702f;flex:0 0 36px}
.ct-tabla td,.ct-tabla th{text-align:center;padding:4px 6px}
</style>
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Contabilidad</small><h1>Calendario <span>tributario</span></h1>
            <p class="text-muted mb-0">Vencimientos de retención en la fuente (350) e IVA (300) de sus clientes, según el último dígito del NIT.</p></div>
        <div class="lp-quick">
            <a href="{{ route('contabilidad.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Clientes</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    @if (now()->addDays($dias)->gt($ultimoMes))
    <div class="alert alert-warning small"><i class="fa-solid fa-circle-info me-1"></i>Por ahora están cargadas las fechas hasta <b>{{ $ultimoMes->locale('es')->isoFormat('MMMM [de] YYYY') }}</b>. Cuando la DIAN publique el calendario del año siguiente (normalmente en diciembre), LocalProo lo agrega.</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="lp-box">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h3 class="mb-0">Próximos {{ $dias }} días @if ($pendientes)<span class="badge bg-danger ms-1">{{ $pendientes }} urgentes</span>@endif</h3>
                    <div class="btn-group btn-group-sm">
                        @foreach ([15, 30, 60, 90] as $d)<a href="{{ route('contabilidad.calendario', ['dias' => $d]) }}" class="btn {{ $dias === $d ? 'btn-dark' : 'btn-light' }}">{{ $d }} días</a>@endforeach
                    </div>
                </div>

                @forelse ($porFecha as $fecha => $items)
                @php
                    $f = $items->first()['fecha'];
                    $todos = $items->every(fn ($v) => $v['presentado']);
                    [$txt, $color] = \App\Support\CalendarioTributario::falta($items->first()['dias']);
                @endphp
                <div class="ct-dia {{ $todos ? 'ok' : $color }}">
                    <div class="d-flex justify-content-between align-items-baseline flex-wrap mb-2">
                        <b>{{ $fechaTxt($f) }}</b>
                        <span class="small {{ $todos ? 'text-success' : 'text-' . $color }} fw-bold">{{ $todos ? 'Todo presentado' : $txt }}</span>
                    </div>
                    @foreach ($items as $v)
                    <div class="ct-venc {{ $v['presentado'] ? 'hecho' : '' }}">
                        <div class="ct-ico"><i class="fa-solid {{ \App\Support\CalendarioTributario::TIPOS[$v['tipo']][2] }}"></i></div>
                        <div class="flex-grow-1 small">
                            <b>{{ $v['nombre'] }}</b> · {{ $v['periodo'] }}
                            <div class="text-muted">{{ $v['cliente']->nombre }} · NIT {{ $v['cliente']->nit }} (dígito {{ $dig($v['cliente']->nit) }}) · {{ $v['formulario'] }}</div>
                        </div>
                        @if ($v['presentado'])
                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Presentado</span>
                        @elseif ($v['periodo_id'])
                            <a href="{{ route('contabilidad.periodo', $v['periodo_id']) }}" class="btn btn-sm btn-dark">Abrir mes</a>
                        @else
                            <form method="post" action="{{ route('contabilidad.periodos.store', $v['cliente']) }}">@csrf
                                <input type="hidden" name="anio" value="{{ $v['anio'] }}"><input type="hidden" name="mes" value="{{ $v['mes'] }}">
                                <button class="btn btn-sm btn-light">Abrir mes</button></form>
                        @endif
                    </div>
                    @endforeach
                </div>
                @empty
                <p class="text-muted mb-0">No hay vencimientos en este rango para sus clientes activos. Revise que cada cliente tenga bien su NIT, si es agente de retención y su periodicidad de IVA.</p>
                @endforelse

                <small class="text-muted d-block mt-2">Un vencimiento queda como <b>Presentado</b> cuando marca el mes como "Presentado en la DIAN" en <b>Estado y notas</b>. Para el IVA se toma el último mes del bimestre o cuatrimestre.</small>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-envelope me-1"></i>Recordatorios</h3>
                <p class="small mb-0">LocalProo le envía un correo <b>3 días antes</b> y <b>el mismo día</b> de cada vencimiento que no esté marcado como presentado. Se apaga por cliente en sus datos (<i>Recordarme sus vencimientos por correo</i>).</p>
            </div>
            <div class="lp-box">
                <h3>Fechas por último dígito</h3>
                <p class="small text-muted">Día de vencimiento de la retención y del IVA de cada mes. El IVA bimestral vence en marzo, mayo, julio, septiembre, noviembre y enero; el cuatrimestral en mayo, septiembre y enero.</p>
                <div class="table-responsive">
                <table class="table table-sm ct-tabla small mb-2">
                    <thead><tr><th class="text-start">Vence en</th>@foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 0] as $d)<th>{{ $d }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach (\App\Support\CalendarioTributario::DIAS as $mes => $diasMes)
                    <tr class="{{ $mes === now()->format('Y-m') ? 'table-success' : '' }}"><td class="text-start text-nowrap">{{ ucfirst(\Illuminate\Support\Carbon::parse($mes . '-01')->locale('es')->isoFormat('MMM YYYY')) }}</td>@foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 0] as $d)<td>{{ $diasMes[$d] }}</td>@endforeach</tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                <small class="text-muted">Fuente: {{ \App\Support\CalendarioTributario::FUENTE }} Verifique siempre en el calendario oficial de la DIAN.</small>
            </div>
        </div>
    </div>
</div>
@endsection
