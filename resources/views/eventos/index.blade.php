@extends('layouts.app')

@section('title', 'Eventos')

@push('css')
<style>
.ev-card{display:flex;gap:1rem;align-items:center;background:#fff;border:1.5px solid #e3efe5;border-radius:20px;padding:.9rem;margin-bottom:.8rem;flex-wrap:wrap}
.ev-card.cerrado{opacity:.65}
.ev-img{width:70px;height:94px;border-radius:14px;object-fit:cover;flex:0 0 70px;background:#f1ecf9;display:grid;place-items:center;color:#a855f7;font-size:1.6rem;overflow:hidden}
.ev-img img{width:100%;height:100%;object-fit:cover}
.ev-card .tx{flex:1;min-width:200px}
.ev-card .tx b{font-size:1.1rem}
.ev-num{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.4rem}
.ev-num span{font-size:.78rem;font-weight:700;border-radius:999px;padding:.2rem .6rem;background:#f4f6f5}
.ev-num .p{background:#fff5d6;color:#8a6400}.ev-num .a{background:#e7f8ec;color:#167a3a}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>LocalProo Eventos</small><h1>Mis <span>eventos</span></h1>
            <p class="text-muted mb-0">Página del evento, compradores, aprobación de pagos y puerta con QR.</p></div>
        <div class="lp-quick">@if (\Illuminate\Support\Facades\Route::has('aprende.curso'))<a href="{{ route('aprende.curso', 'eventos') }}" class="btn btn-light"><i class="fa-solid fa-graduation-cap me-1"></i>Curso: cómo manejar Eventos</a>@endif @if ($admin)<a href="{{ route('eventos.create') }}" class="btn btn-dark"><i class="fa-solid fa-plus me-1"></i>Nuevo evento</a>@endif</div>
    </div>
    @include('layouts.partials.lp-flash')

    @forelse ($eventos as $ev)
    <div class="ev-card {{ $ev->estado }}">
        <div class="ev-img">@if ($ev->imagen_url)<img src="{{ $ev->imagen_url }}" alt="">@else<i class="fa-solid fa-ticket"></i>@endif</div>
        <div class="tx">
            <b>{{ $ev->nombre }}</b>
            <div class="text-muted small">{{ $ev->fecha_evento ? ucfirst($ev->fecha_evento->locale('es')->isoFormat('ddd D [de] MMMM [de] YYYY · h:mm a')) : 'Sin fecha' }} @if ($ev->lugar)· {{ $ev->lugar }}@endif</div>
            <div class="ev-num">
                <span>{{ \App\Models\Evento::ESTADOS[$ev->estado] ?? $ev->estado }}</span>
                @if ($ev->pendientes)<span class="p"><i class="fa-solid fa-hourglass-half me-1"></i>{{ $ev->pendientes }} por aprobar</span>@endif
                <span class="a"><i class="fa-solid fa-check me-1"></i>{{ $ev->aprobadas }} aprobadas</span>
                <span><i class="fa-solid fa-door-open me-1"></i>{{ $ev->ingresaron }} ingresaron</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($p['admin'] || $p['pagos'] || $p['info'])<a href="{{ route('eventos.show', $ev) }}" class="btn btn-primary"><i class="fa-solid fa-users me-1"></i>Compradores</a>@endif
            @if (!$p['admin'] && ($p['info'] || $p['diseno']))<a href="{{ route('eventos.edit', $ev) }}" class="btn btn-light"><i class="fa-solid fa-pen me-1"></i>{{ $p['info'] ? 'Editar evento' : 'Diseño de la página' }}</a>@endif
            @if ($p['admin'] || $p['puerta'])<a href="{{ route('eventos.puerta', $ev) }}" class="btn btn-dark"><i class="fa-solid fa-qrcode me-1"></i>Puerta</a>@endif
        </div>
    </div>
    @empty
    <div class="lp-box text-center">
        <i class="fa-solid fa-ticket fa-3x mb-3" style="color:#a855f7"></i>
        <h3>Cree su primer evento</h3>
        <p class="text-muted">Suba el afiche, ponga la fecha, el precio de la etapa y su Nequi. Luego comparta el enlace en Instagram, TikTok y WhatsApp.</p>
        @if ($admin)<a href="{{ route('eventos.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Nuevo evento</a>@endif
    </div>
    @endforelse
</div>
@endsection
