@extends('layouts.app')

@section('title', 'Pagos y recibos')

@section('content')
@php $ok = $pagos->where('anulado', false); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Pagos y <span>recibos</span></h1>
            <span class="text-muted fw-semibold">Recaudado en el mes: <b>{{ dinero($ok->sum('valor')) }}</b> · {{ $ok->count() }} pagos</span></div>
        <form method="get" class="lp-quick"><input type="month" name="mes" class="form-control" value="{{ $mes }}" onchange="this.form.submit()"></form>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <p class="small text-muted">Para registrar un pago, entre a la unidad (o búsquela en <a href="{{ route('conjunto.cartera') }}">Cartera</a>) y use "Registrar pago".</p>
        <ul class="lp-list">
            @forelse ($pagos as $p)
            <li style="{{ $p->anulado ? 'opacity:.5' : '' }}"><div class="av"><i class="fa-solid fa-receipt"></i></div>
                <div class="t"><b><a href="{{ route('conjunto.recibo', $p) }}" class="text-decoration-none" style="color:inherit">{{ dinero($p->valor) }} · {{ $p->unidad->nombre ?? '' }}</a></b><small>{{ $p->numero }} · {{ $p->fecha->format('d/m/Y') }} · {{ $p->metodo }}{{ $p->referencia ? ' · ' . $p->referencia : '' }}{{ $p->anulado ? ' · ANULADO' : '' }}</small></div></li>
            @empty
            <li class="lp-empty">Sin pagos en este mes.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
