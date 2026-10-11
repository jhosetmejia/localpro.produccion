@extends('layouts.app')

@section('title', 'Cartera y morosos')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Cartera y <span>morosos</span></h1>
            <span class="text-muted fw-semibold">Por cobrar: <b>{{ dinero($filas->sum('deuda')) }}</b> · vencido {{ dinero($filas->sum('vencida')) }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($filas as $f)
            @php
                $u = $f->unidad;
                $tel = numero_whatsapp($u?->contacto);
                $dias = $f->vencida > 0 ? (int) \Carbon\Carbon::parse($f->desde)->diffInDays(today()) : 0;
                $msg = 'Hola ' . ($u?->persona ?: '') . ', le recordamos que ' . ($u?->nombre ?? '') . ' tiene un saldo pendiente de ' . dinero($f->deuda) . ' con la administración de ' . $conj->nombre . '. Puede ponerse al día con el pago o comunicarse con nosotros. ¡Gracias!';
            @endphp
            <li style="flex-wrap:wrap">
                <div class="av" style="{{ $f->vencida > 0 ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid fa-door-closed"></i></div>
                <div class="t"><b><a href="{{ route('conjunto.unidad', $f->unidad_id) }}" class="text-decoration-none" style="color:inherit">{{ $u->nombre ?? '' }}</a> · {{ dinero($f->deuda) }}</b>
                    <small>{{ $u?->persona ?: 'Sin nombre' }} · {{ $f->n }} cobro(s){{ $f->vencida > 0 ? ' · ' . $dias . ' días de mora' : ' · aún no vence' }}</small></div>
                <div class="d-flex gap-1">
                    @if ($tel)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($msg) }}" title="Recordar por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>@endif
                    <a class="btn btn-sm btn-dark" href="{{ route('conjunto.unidad', $f->unidad_id) }}">Cobrar</a>
                </div>
            </li>
            @empty
            <li class="lp-empty">Todas las unidades están al día. ✅</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
