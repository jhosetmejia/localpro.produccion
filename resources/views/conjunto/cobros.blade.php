@extends('layouts.app')

@section('title', 'Cobros de administración')

@section('content')
@php
    $mes = \Carbon\Carbon::createFromFormat('Y-m-d', $periodo . '-01');
    $tot = $cargos->sum('valor'); $pag = $cargos->sum('pagado');
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Cobros de <span>administración</span></h1>
            <span class="text-muted fw-semibold">{{ ucfirst($mes->locale('es')->isoFormat('MMMM YYYY')) }}: facturado {{ dinero($tot) }} · recaudado {{ dinero($pag) }} · pendiente {{ dinero($tot - $pag) }}</span></div>
        <form method="get" class="lp-quick"><input type="month" name="periodo" class="form-control" value="{{ $periodo }}" onchange="this.form.submit()"></form>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-calendar-plus me-1"></i> Cobro mensual de administración</span></h3>
            <p class="small text-muted">Crea el cobro del mes a todas las unidades activas con su cuota. Si alguna ya lo tiene, no se repite. Si tienen saldo a favor, se descuenta solo.</p>
            <form method="post" action="{{ route('conjunto.cobros.mes') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-6"><label class="form-label">Mes</label><input type="month" name="periodo" class="form-control" value="{{ $periodo }}" required></div>
                <div class="col-6"><label class="form-label">Vence</label><input type="date" name="vence" class="form-control" value="{{ $mes->copy()->day(min(28, (int) $conj->conj_dia_vence))->format('Y-m-d') }}"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Generar cobro de {{ $mes->locale('es')->isoFormat('MMMM') }}</button></div>
            </form>
            @if ((float) $conj->conj_interes_mora > 0)
            <form method="post" action="{{ route('conjunto.cobros.intereses') }}" class="mt-2" data-confirm="¿Generar intereses de mora?" data-confirm-texto="Se cobra el {{ $conj->conj_interes_mora }}% sobre lo vencido de cada unidad en mora (una vez por mes)." data-confirm-ok="Sí, generar">@csrf<button class="btn btn-light w-100"><i class="fa-solid fa-percent me-1"></i>Generar intereses de mora ({{ $conj->conj_interes_mora }}%)</button></form>
            @endif
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-plus me-1"></i> Cuota extraordinaria, multa u otro cobro</span></h3>
            <form method="post" action="{{ route('conjunto.cobros.especial') }}" class="row g-2">
                @csrf
                <div class="col-6"><select name="tipo" class="form-select">@foreach (['extraordinaria' => 'Cuota extraordinaria', 'multa' => 'Multa / sanción', 'otro' => 'Otro cobro'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><input name="concepto" class="form-control" maxlength="120" required placeholder="Concepto (ej. Pintura fachada)"></div>
                <div class="col-6"><select name="modo" class="form-select" onchange="document.getElementById('uniSel').style.display=this.value==='unidad'?'':'none';document.getElementById('lblValor').textContent=this.value==='coeficiente'?'Valor TOTAL a repartir':'Valor por unidad'">
                    <option value="igual">Igual para todas</option><option value="coeficiente">Repartir por coeficiente</option><option value="unidad">Solo a una unidad</option></select></div>
                <div class="col-6"><input data-dinero type="number" name="valor" class="form-control" min="1" required placeholder="Valor"><small class="text-muted" id="lblValor">Valor por unidad</small></div>
                <div class="col-12" id="uniSel" style="display:none"><select name="unidad_id" class="form-select"><option value="">Elija la unidad…</option>@foreach ($unidades as $u)<option value="{{ $u->id }}">{{ $u->nombre }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small mb-0">Vence</label><input type="date" name="vence" class="form-control" value="{{ today()->addDays(15)->format('Y-m-d') }}"></div>
                <div class="col-6 d-grid align-items-end"><button class="btn btn-dark mt-3">Crear cobro</button></div>
            </form>
        </div>
    </div>

    <div class="lp-box mb-3">
        <h3>Cobros de {{ $mes->locale('es')->isoFormat('MMMM YYYY') }} ({{ $cargos->count() }})</h3>
        <ul class="lp-list">
            @forelse ($cargos as $c)
            <li><div class="av" style="{{ $c->vencido ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid {{ $c->estado === 'pagado' ? 'fa-circle-check' : 'fa-file-invoice' }}"></i></div>
                <div class="t"><b><a href="{{ route('conjunto.unidad', $c->unidad_id) }}" class="text-decoration-none" style="color:inherit">{{ $c->unidad->nombre ?? '' }}</a> · {{ dinero($c->valor) }}</b><small>{{ $c->concepto }}{{ $c->vence ? ' · vence ' . $c->vence->format('d/m') : '' }}{{ $c->estado === 'parcial' ? ' · abonó ' . dinero($c->pagado) : '' }}</small></div>
                <span class="badge {{ $c->estado === 'pagado' ? 'bg-success' : ($c->vencido ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $c->estado === 'pagado' ? 'Pagado' : ($c->vencido ? 'Vencido' : ucfirst($c->estado)) }}</span></li>
            @empty
            <li class="lp-empty">No hay cobros en este mes.</li>
            @endforelse
        </ul>
    </div>

    <details class="lp-box">
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-gear me-1" style="color:#16b34a"></i> Configuración de cobros y zonas comunes</summary>
        <form method="post" action="{{ route('conjunto.config') }}" class="row g-2 mt-1">
            @csrf
            <div class="col-6 col-md-3"><label class="form-label">Día de vencimiento</label><input type="number" name="conj_dia_vence" class="form-control" min="1" max="28" value="{{ $conj->conj_dia_vence }}"></div>
            <div class="col-6 col-md-3"><label class="form-label">Interés de mora (% mensual)</label><input type="number" step="0.01" name="conj_interes_mora" class="form-control" min="0" max="5" value="{{ (float) $conj->conj_interes_mora }}"></div>
            <div class="col-md-6"><label class="form-label">Zonas comunes reservables (separadas por coma)</label><input name="conj_zonas" class="form-control" value="{{ $conj->conj_zonas }}" placeholder="Salón social, BBQ, Piscina, Cancha"></div>
            <div class="col-12"><button class="btn btn-dark">Guardar configuración</button></div>
        </form>
    </details>
</div>
@endsection
