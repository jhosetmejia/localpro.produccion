@extends('layouts.app')

@section('title', 'Tiquetera ' . $t->codigo)

@section('content')
@php
    $msg = '¡Hola ' . \Illuminate\Support\Str::of($t->cliente_nombre)->explode(' ')->first() . '! 🍽️ Su tiquetera ' . $t->codigo . ' (' . $t->nombre . ') en ' . (\App\Support\TiendaActual::tienda()->nombre ?? '') . ': ' . ($t->quedan ? 'le quedan *' . $t->quedan . ' de ' . $t->total . '*.' : 'ya se gastó toda. ¿Le renovamos otra?') . ($t->vence && $t->quedan ? ' Vence el ' . $t->vence->format('d/m/Y') . '.' : '') . ' ¡Lo esperamos! 💚';
@endphp
<div class="lp-dash" style="max-width:900px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('tiqueteras.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiquetera</a></small><h1>{{ $t->cliente_nombre }}</h1>
            <span class="text-muted fw-semibold">{{ $t->codigo }} · {{ $t->nombre }} · {{ dinero($t->valor) }}{{ $t->venta ? ' · venta ' . $t->venta->numero : '' }} · desde el {{ $t->created_at->format('d/m/Y') }}</span></div>
        <div class="lp-quick">@if ($t->whatsapp_numero)<a href="https://wa.me/{{ $t->whatsapp_numero }}?text={{ rawurlencode($msg) }}" target="_blank" class="btn btn-success btn-lg"><i class="fa-brands fa-whatsapp me-1"></i>Enviarle su saldo</a>@endif
            <a href="{{ route('tiqueteras.create') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-plus me-1"></i>Nueva tiquetera</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="lp-box mb-3 text-center">
        <div style="font-size:3.4rem;font-weight:900;color:#11702f;line-height:1">{{ $t->quedan }}</div>
        <div class="fw-bold text-muted">le quedan de {{ $t->total }}@if ($t->vence) · {{ $t->vencida ? 'venció' : 'vence' }} el {{ $t->vence->format('d/m/Y') }}@endif</div>
        <div class="d-flex flex-wrap justify-content-center gap-1 my-3" style="max-width:560px;margin:0 auto">
            @for ($i = 1; $i <= min($t->total, 120); $i++)<span style="width:22px;height:22px;border-radius:6px;{{ $i <= $t->usados ? 'background:#e8eee9;border:1.5px solid #cfd8d2' : 'background:#dcfce7;border:1.5px solid #16b34a' }}"></span>@endfor
        </div>
        <form method="post" action="{{ route('tiqueteras.usar', $t) }}" class="d-flex justify-content-center gap-2 flex-wrap">
            @csrf
            <input type="number" name="cantidad" class="form-control form-control-lg" style="width:90px" min="1" max="{{ max(1, min(20, $t->quedan)) }}" value="1">
            <button class="btn btn-primary btn-lg rounded-pill fw-bold" @disabled(!$t->quedan || $t->vencida)><i class="fa-solid fa-utensils me-1"></i>Marcar consumo</button>
        </form>
        @if ($t->nota)<div class="small text-muted mt-2">Nota: {{ $t->nota }}</div>@endif
    </div>

    <div class="lp-box">
        <h3>Consumos</h3>
        @forelse ($t->usos as $u)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom {{ $u->anulado ? 'opacity-50' : '' }}">
            <span><b>{{ $u->created_at->format('d/m/Y h:i a') }}</b> · {{ $u->cantidad }} · {{ $u->user->name ?? '—' }}@if ($u->anulado) <span class="badge bg-secondary">Quitado</span>@endif</span>
            @if (!$u->anulado && ($esAdmin || ((int) $u->user_id === (int) auth()->id() && $u->created_at->gt(now()->subMinutes(15)))))
            <form method="post" action="{{ route('tiqueteras.uso.anular', $u) }}" data-confirm="¿Quitar este consumo?" data-confirm-texto="Se le devuelve a la tiquetera." data-confirm-ok="Sí, quitar">@csrf<button class="btn btn-sm btn-light border">↩ Quitar</button></form>
            @endif
        </div>
        @empty
        <p class="text-muted fw-semibold mb-0">Todavía no ha consumido.</p>
        @endforelse
    </div>
</div>
@endsection
