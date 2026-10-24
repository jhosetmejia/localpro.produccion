@extends('eventos.publico._layout')

@section('titulo', 'Mis entradas')
@section('meta')<meta name="robots" content="noindex, nofollow">@endsection

@section('css')
.ent{display:flex;gap:.9rem;align-items:center}
.ent img,.ent .ph{width:70px;height:94px;border-radius:12px;object-fit:cover;flex:0 0 70px;background:#0f0e14;display:grid;place-items:center;color:var(--mu)}
.ent .tx{flex:1;min-width:0}
.ent b{display:block;font-size:1.05rem}
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;gap:1rem">
    <div><small class="mu">Hola</small><h1 style="font-size:1.4rem">{{ $yo->nombre }}</h1></div>
    <form method="post" action="{{ route('mis-entradas.salir') }}">@csrf<button class="badge" style="background:transparent;border:1px solid var(--line);color:var(--mu);cursor:pointer;padding:.5rem .9rem">Salir</button></form>
</div>

@forelse ($entradas as $e)
@php $ev = $e->evento; @endphp
<div class="card">
    <div class="ent">
        @if ($ev?->imagen_url)<img src="{{ $ev->imagen_url }}" alt="">@else<span class="ph"><i class="fa-solid fa-ticket"></i></span>@endif
        <div class="tx">
            <b>{{ $ev?->nombre }}</b>
            @if ($ev?->fecha_evento)<small class="mu">{{ ucfirst($ev->fecha_evento->locale('es')->isoFormat('ddd D [de] MMMM · h:mm a')) }}</small>@endif
            <div style="margin-top:.4rem">
                @if ($e->estado === 'aprobada' && $e->ingreso_at)<span class="badge b-ok"><i class="fa-solid fa-check-double"></i> Ya ingresaste</span>
                @elseif ($e->estado === 'aprobada')<span class="badge b-ok"><i class="fa-solid fa-check"></i> Pago aprobado</span>
                @elseif ($e->estado === 'pendiente')<span class="badge b-wait"><i class="fa-solid fa-hourglass-half"></i> Pago por aprobar</span>
                @else<span class="badge b-bad">{{ $e->estado_texto }}</span>@endif
            </div>
        </div>
    </div>
    @if ($e->estado === 'aprobada')
    <a class="btn btn-c" style="margin-top:14px" href="{{ route('mis-entradas.ver', $e->uuid) }}"><i class="fa-solid fa-qrcode"></i> Ver QR</a>
    @elseif (in_array($e->estado, ['pendiente', 'rechazada'], true) && $ev)
    <a class="btn btn-l" style="margin-top:14px" href="{{ route('evento.pago', $ev->slug) }}"><i class="fa-solid fa-money-bill-transfer"></i> Ver cómo pagar</a>
    @endif
</div>
@empty
<div class="card"><p class="mu">Todavía no tienes entradas.</p></div>
@endforelse
@endsection
