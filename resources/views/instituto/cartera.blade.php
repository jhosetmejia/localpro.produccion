@extends('layouts.app')

@section('title', 'Cartera y morosos')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-school me-1"></i>Instituto</small><h1>Cartera y <span>morosos</span></h1>
            <span class="text-muted fw-semibold">{{ $soloVencido ? 'Vencido' : 'Por cobrar (vencido y por vencer)' }}: <b>{{ dinero($filas->sum('deuda')) }}</b> · {{ $filas->count() }} estudiante(s)</span></div>
        <div class="lp-quick btn-group">
            <a href="{{ route('instituto.cartera') }}" class="btn {{ $soloVencido ? 'btn-dark' : 'btn-light' }}">En mora</a>
            <a href="{{ route('instituto.cartera', ['vencido' => 0]) }}" class="btn {{ $soloVencido ? 'btn-light' : 'btn-dark' }}">Todo lo pendiente</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($filas as $f)
            @php
                $e = $f->estudiante;
                $tel = numero_whatsapp($e?->contacto);
                $vencida = $f->desde && \Carbon\Carbon::parse($f->desde)->lt(today());
                $dias = $vencida ? (int) \Carbon\Carbon::parse($f->desde)->diffInDays(today()) : 0;
                $msg = 'Hola ' . ($e?->nombres ?? '') . ', le recordamos que tiene un saldo ' . ($vencida ? 'vencido' : 'pendiente') . ' de ' . dinero($f->deuda) . ' con ' . $inst->nombre . '. Las cuotas se pagan del 1 al ' . ($inst->inst_dia_pago ?: 5) . ' de cada mes. Puede ponerse al día con el pago o comunicarse con nosotros. ¡Gracias!';
            @endphp
            <li style="flex-wrap:wrap">
                <div class="av" style="{{ $vencida ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="t"><b><a href="{{ route('instituto.estudiante', $f->estudiante_id) }}" class="text-decoration-none" style="color:inherit">{{ $e->nombre ?? '' }}</a> · {{ dinero($f->deuda) }}</b>
                    <small>{{ $e?->programa->nombre ?? 'Sin programa' }} · {{ $f->n }} cobro(s){{ $vencida ? ' · ' . $dias . ' días de mora' : ' · aún no vence' }}</small></div>
                <div class="d-flex gap-1">
                    @if ($tel)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($msg) }}" title="Recordar por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>@endif
                    <a class="btn btn-sm btn-dark" href="{{ route('instituto.estudiante', $f->estudiante_id) }}">Cobrar</a>
                </div>
            </li>
            @empty
            <li class="lp-empty">{{ $soloVencido ? 'Nadie en mora. ✅' : 'No hay cobros pendientes.' }}</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
