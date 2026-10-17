@extends('layouts.app')

@section('title', 'Conteo físico')

@push('css')
<style>
.ct-nuevo{display:grid;grid-template-columns:1.2fr 1fr auto;gap:10px;align-items:end}
.ct-nuevo label{font-weight:800;font-size:.8rem;color:var(--lp-muted);margin-bottom:.25rem}
.ct-pasos{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:1rem}
.ct-paso{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem 1.1rem;display:flex;gap:.8rem;align-items:flex-start}
.ct-paso .n{flex:none;width:34px;height:34px;border-radius:12px;display:grid;place-items:center;background:var(--lp-grad);font-weight:900}
.ct-paso:nth-child(2) .n{background:var(--lp-black);color:var(--lp-lime)}
.ct-paso:nth-child(3) .n{background:var(--lp-gold)}
.ct-paso b{display:block;font-size:.95rem}.ct-paso small{color:var(--lp-muted)}
.ct-item{display:flex;gap:1rem;align-items:center;padding:.85rem 0;border-bottom:1px solid #eef3ef;text-decoration:none;color:inherit}
.ct-item:last-child{border-bottom:0}
.ct-item:hover b{color:var(--lp-green-2)}
.ct-ic{flex:none;width:44px;height:44px;border-radius:14px;display:grid;place-items:center;font-size:1.1rem}
.ct-ic.abierto{background:#fff6d6;color:#9a6a00}.ct-ic.aplicado{background:#dcfce7;color:#14753a}.ct-ic.cancelado{background:#eef1ef;color:#8a948e}
.ct-item .tx{flex:1;min-width:0}.ct-item .tx small{display:block;color:var(--lp-muted)}
.ct-prog{height:7px;border-radius:999px;background:#eef3ef;overflow:hidden;margin-top:.35rem;max-width:260px}
.ct-prog i{display:block;height:100%;background:var(--lp-grad)}
.ct-est{font-weight:800;font-size:.75rem;border-radius:999px;padding:.2rem .65rem}
.ct-est.abierto{background:#fff6d6;color:#9a6a00}.ct-est.aplicado{background:#dcfce7;color:#14753a}.ct-est.cancelado{background:#eef1ef;color:#6b7570}
@media (max-width:767px){.ct-nuevo,.ct-pasos{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@include('layouts.partials.alert')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Inventario</small><h1>Conteo <span>físico</span></h1></div>
    </div>
    @include('inventario._tabs')

    <div class="ct-pasos">
        <div class="ct-paso"><div class="n">1</div><div><b>Abra un conteo</b><small>De toda la tienda o solo de una categoría (ej. Chaquetas).</small></div></div>
        <div class="ct-paso"><div class="n">2</div><div><b>Cuente las prendas</b><small>Escanee cada etiqueta con la cámara o escriba la cantidad. Pueden contar varios al tiempo.</small></div></div>
        <div class="ct-paso"><div class="n">3</div><div><b>Revise y ajuste</b><small>Vea qué falta y qué sobra. Con un botón el sistema queda igual a lo físico.</small></div></div>
    </div>

    <div class="lp-box mb-3">
        <h3>Abrir un conteo nuevo</h3>
        <form method="post" action="{{ route('inventario.conteos.store') }}" class="ct-nuevo">
            @csrf
            <div><label for="ctCat" class="d-block">¿Qué va a contar?</label>
                <select name="categoria_id" id="ctCat" class="form-select">
                    <option value="">Toda la tienda</option>
                    @foreach ($categorias as $c)<option value="{{ $c['id'] }}">Solo {{ $c['nombre'] }}</option>@endforeach
                </select></div>
            <div><label for="ctNom" class="d-block">Nombre (opcional)</label>
                <input name="nombre" id="ctNom" class="form-control" maxlength="120" placeholder="Ej. Inventario de fin de mes"></div>
            <button class="btn btn-primary fw-bold"><i class="fa-solid fa-play me-1"></i>Empezar a contar</button>
        </form>
        <small class="text-muted d-block mt-2">El sistema guarda cuántas había en ese momento. Si se vende algo mientras cuentan, no hay problema: al ajustar se respeta lo vendido.</small>
    </div>

    <div class="lp-box">
        <h3>Conteos</h3>
        @forelse ($conteos as $c)
        @php $pct = $c->items_count ? round($c->contados_count / $c->items_count * 100) : 0; @endphp
        <a href="{{ route('inventario.conteos.show', $c) }}" class="ct-item">
            <div class="ct-ic {{ $c->estado }}"><i class="fa-solid {{ $c->estado === 'aplicado' ? 'fa-check' : ($c->estado === 'cancelado' ? 'fa-ban' : 'fa-clipboard-list') }}"></i></div>
            <div class="tx">
                <b>{{ $c->nombre }}</b>
                <small>{{ $c->categoria?->caracteristica?->nombre ? 'Solo ' . $c->categoria->caracteristica->nombre : 'Toda la tienda' }} · {{ $c->created_at->format('d/m/Y h:i a') }}{{ $c->user ? ' · ' . $c->user->name : '' }}</small>
                @if ($c->estado === 'abierto')
                <div class="ct-prog"><i style="width:{{ $pct }}%"></i></div>
                <small>{{ $c->contados_count }} de {{ $c->items_count }} tallas/colores contadas ({{ $pct }} %)</small>
                @elseif ($c->estado === 'aplicado' && $c->resumen)
                <small>{{ $c->resumen['ajustadas'] ?? 0 }} corregidas · faltaban {{ cantidad($c->resumen['faltan'] ?? 0) }} · sobraban {{ cantidad($c->resumen['sobran'] ?? 0) }}</small>
                @endif
            </div>
            <span class="ct-est {{ $c->estado }}">{{ ['abierto' => 'En curso', 'aplicado' => 'Ajustado', 'cancelado' => 'Cancelado'][$c->estado] ?? $c->estado }}</span>
        </a>
        @empty
        <div class="lp-empty">Todavía no ha hecho conteos. Abra el primero arriba 👆</div>
        @endforelse
        {{ $conteos->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
