@extends('layouts.app')

@section('title', 'Etiquetas para mercancía nueva')

@push('css')
<style>
    .en-wrap{max-width:900px;margin:0 auto}
    .en-sec{background:#fff;border:1.5px solid var(--lp-line);border-radius:22px;padding:1.2rem 1.2rem 1.3rem;margin-bottom:14px}
    .en-sec h2{font-size:1.1rem;font-weight:800;margin:0 0 .6rem}
    .en-pasos{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin:.4rem 0 0}
    .en-paso{background:#f4fcf6;border:1.5px solid #cdeed6;border-radius:16px;padding:.8rem .9rem;font-size:.9rem;line-height:1.35}
    .en-paso b{display:block;color:#11702f;margin-bottom:2px}
    .en-paso .n{width:26px;height:26px;border-radius:8px;background:var(--lp-black);color:var(--lp-lime);display:inline-grid;place-items:center;font-weight:800;font-size:.8rem;margin-bottom:6px}
    .en-form{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .en-form label{font-weight:700;font-size:.85rem;display:flex;flex-direction:column;gap:4px}
    .en-form input{width:150px;font-weight:800;font-size:1.1rem}
    .en-rap{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
    .en-rap button{background:#f1f5f2;border:0;border-radius:999px;padding:.35rem .8rem;font-weight:800;font-size:.85rem}
    .en-lote{display:flex;align-items:center;gap:10px;padding:.7rem 0;border-bottom:1px solid var(--lp-line);flex-wrap:wrap}
    .en-lote:last-child{border-bottom:0}
    .en-lote .i{flex:1;min-width:180px}
    .en-lote .i small{display:block;color:#6b7a71}
    .en-bar{height:6px;background:#eef3f0;border-radius:9px;overflow:hidden;margin-top:5px;max-width:240px}
    .en-bar span{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a)}
    .en-lote a{font-weight:800;font-size:.85rem;border-radius:999px;padding:.4rem .9rem;text-decoration:none;border:1.5px solid #dfe6e2;color:#0b0d0c;white-space:nowrap}
    .en-lote a.p{background:var(--lp-black);color:#fff;border-color:var(--lp-black)}
</style>
@endpush

@section('content')
<div class="en-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <h1 class="h4 fw-bold mb-0 me-auto"><i class="fa-solid fa-barcode me-1"></i> Etiquetas para mercancía nueva</h1>
        <a href="{{ route('etiquetas.todas') }}" class="btn btn-light fw-bold" style="border-radius:999px">Etiquetas de productos creados</a>
    </div>

    <div class="en-sec">
        <h2>¿Llegó mercancía o quedó algo sin código?</h2>
        <p class="mb-2 text-muted">Imprima códigos de barras en blanco (sin nombre ni precio), péguelos a toda la mercancía de una vez y créela después, a su ritmo. Al crear el producto escanea la etiqueta y el sistema usa ese código: no tiene que volver a imprimir nada.</p>
        <div class="en-pasos">
            <div class="en-paso"><span class="n">1</span><b>Imprima</b>Elija cuántos códigos necesita y toque Generar.</div>
            <div class="en-paso"><span class="n">2</span><b>Selle la mercancía</b>Pegue una etiqueta en cada prenda o referencia.</div>
            <div class="en-paso"><span class="n">3</span><b>Cree el producto</b>En "Nuevo producto" escanee la etiqueta en cada talla/color.</div>
            <div class="en-paso"><span class="n">4</span><b>Venda y cuente</b>La caja y el conteo la reconocen al escanearla.</div>
        </div>
    </div>

    @can('editar-producto')
    <div class="en-sec">
        <h2>Generar etiquetas nuevas</h2>
        <form method="post" action="{{ route('etiquetas.generar') }}" class="en-form" id="fGen">
            @csrf
            <label>¿Cuántos códigos distintos?
                <input type="number" name="cantidad" id="cantidad" min="1" max="1000" value="{{ old('cantidad', 40) }}" class="form-control" inputmode="numeric" required>
            </label>
            <label>Copias de cada código
                <input type="number" name="copias" min="1" max="50" value="{{ old('copias', 1) }}" class="form-control" inputmode="numeric">
            </label>
            <button class="btn btn-primary btn-lg fw-bold" style="border-radius:999px"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generar e imprimir</button>
        </form>
        <div class="en-rap">
            <small class="text-muted align-self-center me-1">Rápido:</small>
            @foreach ([40, 80, 120, 200] as $n)<button type="button" data-n="{{ $n }}">{{ $n }} ({{ $n / 40 }} {{ $n == 40 ? 'hoja' : 'hojas' }})</button>@endforeach
        </div>
        <p class="small text-muted mt-2 mb-0"><b>¿Una etiqueta por prenda o por referencia?</b> Lo más fácil: <b>un código por cada talla/color</b> y varias copias si hay varias prendas iguales. Si ya pegó códigos distintos a prendas iguales, no pasa nada: al crear el producto escanéelos todos en esa talla/color (quedan como etiquetas extra y sirven igual).</p>
        @error('cantidad')<small class="text-danger d-block mt-2">{{ $message }}</small>@enderror
    </div>
    @endcan

    <div class="en-sec">
        <h2>Lotes impresos <small class="text-muted fw-normal" style="font-size:.85rem">· {{ $libres }} {{ $libres == 1 ? 'etiqueta sin usar' : 'etiquetas sin usar' }}</small></h2>
        @forelse ($lotes as $l)
            @php $pct = $l->total ? round($l->usadas / $l->total * 100) : 0; $quedan = $l->total - $l->usadas; @endphp
            <div class="en-lote">
                <div class="i">
                    <b>{{ \App\Http\Controllers\etiquetaController::nombreLote($l->lote) }}</b>
                    <small>{{ $l->total }} códigos · {{ $l->usadas }} ya en productos · {{ $quedan }} sin usar</small>
                    <div class="en-bar"><span style="width:{{ $pct }}%"></span></div>
                </div>
                @if ($quedan)<a class="p" href="{{ route('etiquetas.lote', $l->lote) }}"><i class="fa-solid fa-print me-1"></i>Reimprimir las {{ $quedan }} sin usar</a>@endif
                <a href="{{ route('etiquetas.lote', ['lote' => $l->lote, 'ver' => 'todas']) }}">Ver todo el lote</a>
            </div>
        @empty
            <p class="text-muted mb-0">Todavía no ha impreso etiquetas para mercancía nueva.</p>
        @endforelse
    </div>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('.en-rap [data-n]').forEach(function (b) {
    b.addEventListener('click', function () { document.getElementById('cantidad').value = b.dataset.n; });
});
</script>
@endpush
