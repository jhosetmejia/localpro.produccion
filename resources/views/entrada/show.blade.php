@extends('layouts.app')

@section('title', 'Entrada')

@section('content')
<div class="lp-dash" style="max-width:900px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('entradas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Entradas</a></small><h1>ENT-{{ str_pad($entrada->id, 5, '0', STR_PAD_LEFT) }}</h1>
        <span class="text-muted fw-semibold">{{ $entrada->fecha->format('d/m/Y') }} · {{ $entrada->proveedor?->persona?->razon_social ?? 'Sin proveedor' }}{{ $entrada->numero_factura ? ' · Factura ' . $entrada->numero_factura : '' }} · Registró {{ $entrada->user->name ?? '' }}</span></div>
        <a href="{{ route('etiquetas.entrada', $entrada) }}" class="btn btn-dark"><i class="fa-solid fa-barcode me-1"></i>Etiquetas de lo que llegó</a>
        <div class="lp-kpi" style="padding:.8rem 1.2rem"><div class="lbl">Total</div><div class="val" style="font-size:1.8rem">@pesos($entrada->total)</div></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <table class="table align-middle">
            <thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo c/u</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            @foreach ($entrada->items as $i)
            <tr><td>{{ $i->descripcion }}</td><td class="text-end">{{ $i->cantidad }}</td><td class="text-end">@pesos($i->costo)</td><td class="text-end fw-bold">@pesos($i->subtotal)</td></tr>
            @endforeach
            </tbody>
        </table>
        @if ($entrada->nota)<p class="text-muted mb-0"><b>Nota:</b> {{ $entrada->nota }}</p>@endif
    </div>
</div>
@endsection
