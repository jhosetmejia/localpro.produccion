@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
<style>
.ae-row{display:grid;grid-template-columns:64px 1fr auto;gap:1rem;align-items:center}
.ae-img{width:64px;height:84px;border-radius:12px;overflow:hidden;background:#111;display:grid;place-items:center;color:#a855f7}
.ae-img img{width:100%;height:100%;object-fit:cover}
.ae-num{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.35rem}
.ae-num span{font-size:.78rem;font-weight:700;border-radius:999px;padding:.15rem .6rem;background:#f1f5f2}
.ae-num .p{background:#fef3c7;color:#92400e}.ae-num .a{background:#dcfce7;color:#11702f}
.ae-acc{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:flex-end}
@media (max-width:760px){.ae-row{grid-template-columns:56px 1fr}.ae-acc{grid-column:1/-1;justify-content:flex-start}}
</style>
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Todos los <span>eventos</span></h1>
            <span class="text-muted fw-semibold">Los eventos de todos los negocios. "Configurar" lo lleva directo al evento.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form class="mb-3 d-flex gap-2" method="get" style="max-width:520px">
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Buscar por evento o negocio">
        <button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    @forelse ($eventos as $ev)
    @php $c = $conteo[$ev->id] ?? null; @endphp
    <div class="lp-box mb-2 ae-row">
        <div class="ae-img">@if ($ev->imagen_url)<img src="{{ $ev->imagen_url }}" alt="">@else<i class="fa-solid fa-ticket fa-lg"></i>@endif</div>
        <div>
            <b>{{ $ev->nombre }}</b>
            <div class="text-muted small">{{ $ev->tienda?->nombre ?? '—' }} @if ($ev->tienda?->codigo)· {{ $ev->tienda->codigo }}@endif · {{ $ev->fecha_evento ? ucfirst($ev->fecha_evento->locale('es')->isoFormat('ddd D [de] MMM YYYY · h:mm a')) : 'Sin fecha' }}</div>
            <div class="ae-num">
                <span>{{ \App\Models\Evento::ESTADOS[$ev->estado] ?? $ev->estado }}</span>
                @if ($c?->pendientes)<span class="p">{{ (int) $c->pendientes }} por aprobar</span>@endif
                <span class="a">{{ (int) ($c->aprobadas ?? 0) }} aprobadas</span>
                <span>{{ (int) ($c->ingresaron ?? 0) }} ingresaron</span>
                <span><i class="fa-regular fa-eye me-1"></i>{{ number_format((int) $ev->visitas, 0, ',', '.') }}</span>
                <span>{{ dinero((float) ($c->recaudo ?? 0)) }}</span>
            </div>
        </div>
        <div class="ae-acc">
            <form method="post" action="{{ route('admin.eventos.abrir', $ev->id) }}">@csrf<input type="hidden" name="a" value="editar"><button class="btn btn-primary"><i class="fa-solid fa-gear me-1"></i>Configurar</button></form>
            <form method="post" action="{{ route('admin.eventos.abrir', $ev->id) }}">@csrf<button class="btn btn-light"><i class="fa-solid fa-users me-1"></i>Compradores</button></form>
            @if ($ev->slug)<a class="btn btn-light" href="{{ route('evento.publico', $ev->slug) }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Página</a>@endif
        </div>
    </div>
    @empty
    <div class="lp-box text-center text-muted">Todavía no hay eventos.</div>
    @endforelse
    {{ $eventos->links() }}
</div>
@endsection
