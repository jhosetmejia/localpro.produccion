@extends('layouts.app')

@section('title', $p->nombre)

@section('content')
@php
    [$etxt, $ecol, $ebg] = \App\Models\NominaPeriodo::ESTADOS[$p->estado];
    $grupos = ['dias' => 'Días', 'horas' => 'Horas extra y recargos', 'devengos' => 'Otros pagos', 'deducciones' => 'Descuentos'];
    $nombresAporte = ['salud' => 'Salud', 'pension' => 'Pensión', 'arl' => 'ARL', 'caja' => 'Caja de compensación', 'icbf' => 'ICBF', 'sena' => 'SENA'];
    $nombresProv = ['cesantias' => 'Cesantías', 'intereses' => 'Intereses a cesantías', 'prima' => 'Prima', 'vacaciones' => 'Vacaciones'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('nomina.index') }}">Nómina</a> · {{ $p->fecha_inicio->format('d/m/Y') }} a {{ $p->fecha_fin->format('d/m/Y') }} · pago {{ $p->fecha_pago->format('d/m/Y') }}</small>
            <h1>{{ $p->nombre }} <span class="badge fs-6 align-middle" style="color:{{ $ecol }};background:{{ $ebg }}">{{ $etxt }}</span></h1></div>
        <div class="lp-quick">
            <a href="{{ route('nomina.desprendible', $p) }}" target="_blank" class="btn btn-dark"><i class="fa-solid fa-print me-1"></i>Desprendibles</a>
            @if ($p->estado === 'borrador')
                <form method="post" action="{{ route('nomina.periodo.estado', $p) }}" class="d-inline" data-confirm="¿Liquidar {{ $p->nombre }}?" data-confirm-texto="Quedará cerrada para cambios (puede reabrirla)." data-confirm-ok="Sí, liquidar">@csrf<input type="hidden" name="estado" value="liquidada"><button class="btn btn-primary"><i class="fa-solid fa-lock me-1"></i>Liquidar</button></form>
                <form method="post" action="{{ route('nomina.periodo.destroy', $p) }}" class="d-inline" data-confirm="¿Eliminar este periodo?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-danger"><i class="fa-solid fa-trash"></i></button></form>
            @else
                @if ($p->estado === 'liquidada')
                <form method="post" action="{{ route('nomina.periodo.estado', $p) }}" class="d-inline">@csrf<input type="hidden" name="estado" value="pagada"><button class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Marcar pagada</button></form>
                @endif
                <form method="post" action="{{ route('nomina.periodo.estado', $p) }}" class="d-inline" data-confirm="¿Reabrir el periodo?" data-confirm-ok="Sí, reabrir">@csrf<input type="hidden" name="estado" value="borrador"><button class="btn btn-light"><i class="fa-solid fa-lock-open me-1"></i>Reabrir</button></form>
            @endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-hand-holding-dollar"></i></div><div class="lbl">Neto a pagar</div><div class="val">@pesos($p->total_neto)</div><div class="sub">{{ $p->liquidaciones->count() }} empleado(s)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Devengado</div><div class="val">@pesos($p->total_devengado)</div><div class="sub">Descuentos @pesos($p->total_deducido)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-building-shield"></i></div><div class="lbl">Seguridad social y parafiscales</div><div class="val">@pesos($p->total_aportes)</div><div class="sub">A cargo del empleador (PILA)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-piggy-bank"></i></div><div class="lbl">Provisión prestaciones</div><div class="val">@pesos($p->total_provisiones)</div><div class="sub">Costo total @pesos($p->total_devengado + $p->total_aportes + $p->total_provisiones)</div></div>
    </div>

    <form method="post" action="{{ route('nomina.periodo.update', $p) }}">
        @csrf @method('PUT')
        @foreach ($p->liquidaciones as $l)
        @php $r = $l->resultado ?? []; $e = $l->empleado; $nov = $l->novedades ?? []; @endphp
        <details class="lp-box mb-3" @if ($loop->first && $p->liquidaciones->count() <= 3) open @endif>
            <summary class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="cursor:pointer;list-style:none">
                <div><b>{{ $e->nombre }}</b> <small class="text-muted">· {{ $e->cargo ?: 'Sin cargo' }} · {{ $r['dias_periodo'] ?? 0 }} días</small>
                    @if ($e->personalizado)<span class="badge ms-1" style="background:#fef3c7;color:#92600a">Personalizada</span>@endif
                    @if (!empty($r['bajo_minimo']))<span class="badge ms-1" style="background:#fee4e2;color:#b42318">Menos del mínimo</span>@endif
                </div>
                <div class="text-end"><small class="text-muted">Neto</small> <b class="fs-5">@pesos($l->neto)</b> <a href="{{ route('nomina.desprendible', [$p, $l]) }}" target="_blank" class="btn btn-sm btn-light ms-2" onclick="event.stopPropagation()"><i class="fa-solid fa-receipt"></i></a></div>
            </summary>
            <hr>
            @if ($p->editable)
            <div class="row g-2 mb-2">
                @if ($e->personalizado)
                <div class="col-md-3"><label class="form-label small fw-bold">Salario de este periodo (mensual)</label><input type="number" min="0" name="liq[{{ $l->id }}][salario]" class="form-control" value="{{ (float) $l->salario }}"></div>
                @endif
                @foreach ($grupos as $g => $tit)
                <div class="col-12"><small class="fw-bold text-uppercase text-muted">{{ $tit }}</small></div>
                @foreach (\App\Support\Nomina\Liquidador::NOVEDADES[$g] as $k => $lbl)
                <div class="col-6 col-md-3"><label class="form-label small mb-0">{{ $lbl }}</label><input type="number" min="0" step="any" name="liq[{{ $l->id }}][{{ $k }}]" class="form-control form-control-sm" value="{{ $nov[$k] ?? '' }}" placeholder="0"></div>
                @endforeach
                @endforeach
                <div class="col-12"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="liq[{{ $l->id }}][liquidar_prima]" value="1" id="pr{{ $l->id }}" @checked(!empty($nov['liquidar_prima']))><label class="form-check-label" for="pr{{ $l->id }}">Pagar prima de servicios del semestre (junio / diciembre)</label></div></div>
            </div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <small class="fw-bold text-uppercase text-muted">Devengado</small>
                    <table class="table table-sm mb-0">
                        @foreach ($r['devengos'] ?? [] as $d)<tr><td>{{ $d['concepto'] }} @if($d['cantidad'])<small class="text-muted">({{ $d['cantidad'] + 0 }})</small>@endif</td><td class="text-end">@pesos($d['valor'])</td></tr>@endforeach
                        <tr class="fw-bold"><td>Total devengado</td><td class="text-end">@pesos($l->total_devengado)</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <small class="fw-bold text-uppercase text-muted">Descuentos</small>
                    <table class="table table-sm mb-0">
                        @forelse ($r['deducciones'] ?? [] as $d)<tr><td>{{ $d['concepto'] }} @if($d['porcentaje'])<small class="text-muted">({{ $d['porcentaje'] + 0 }}%)</small>@endif</td><td class="text-end">-@pesos($d['valor'])</td></tr>@empty<tr><td class="text-muted" colspan="2">Sin descuentos</td></tr>@endforelse
                        <tr class="fw-bold"><td>Total descuentos</td><td class="text-end">-@pesos($l->total_deducido)</td></tr>
                    </table>
                </div>
                <div class="col-md-6"><small class="text-muted">
                    <b>Empleador:</b> IBC @pesos($l->ibc) ·
                    @foreach ($r['aportes'] ?? [] as $k => $v) {{ $nombresAporte[$k] ?? $k }} @pesos($v)@if(!$loop->last) · @endif @endforeach
                    @if (!empty($r['exonerado'])) <span class="d-block">Exonerado de salud, SENA e ICBF.</span>@endif
                </small></div>
                <div class="col-md-6"><small class="text-muted">
                    <b>Provisión:</b>
                    @forelse ($r['provisiones'] ?? [] as $k => $v) {{ $nombresProv[$k] ?? $k }} @pesos($v)@if(!$loop->last) · @endif @empty No aplica @endforelse
                </small></div>
            </div>
        </details>
        @endforeach
        @if ($p->editable)
        <div class="position-sticky bottom-0 py-2" style="z-index:5"><button class="btn btn-dark btn-lg w-100 shadow"><i class="fa-solid fa-calculator me-1"></i>Guardar novedades y recalcular</button></div>
        @endif
    </form>
</div>
@endsection
