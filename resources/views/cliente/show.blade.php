@extends('layouts.app')

@section('title', $cliente->persona->razon_social)

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('clientes.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Clientes</a></small><h1>{{ $cliente->persona->razon_social }}</h1>
            <span class="text-muted fw-semibold">{{ $cliente->persona->documento->tipo_documento ?? '' }} {{ str_starts_with($cliente->persona->numero_documento, 'TEL-') ? '' : $cliente->persona->numero_documento }}{{ $cliente->persona->telefono ? ' · ' . $cliente->persona->telefono : '' }}</span></div>
        <div class="lp-quick">
            @if ($cliente->persona->telefono)<a class="btn btn-success" target="_blank" href="https://wa.me/{{ numero_whatsapp($cliente->persona->telefono) }}"><i class="fa-brands fa-whatsapp me-1"></i>Escribirle</a>@endif
            @can('editar-cliente')<a class="btn btn-dark" href="{{ route('clientes.edit', $cliente) }}"><i class="fa-solid fa-pen me-1"></i>Editar</a>@endcan
        </div>
    </div>
    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-bag-shopping"></i></div><div class="lbl">Ha comprado</div><div class="val">@pesos($resumen['total'])</div><div class="sub">{{ $resumen['n'] }} compra(s)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-receipt"></i></div><div class="lbl">Compra promedio</div><div class="val">@pesos($resumen['n'] ? $resumen['total'] / $resumen['n'] : 0)</div><div class="sub">Por visita</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-clock"></i></div><div class="lbl">Última compra</div><div class="val" style="font-size:1.5rem">{{ $resumen['ultima'] ? $resumen['ultima']->diffForHumans() : '—' }}</div><div class="sub">{{ $resumen['ultima']?->format('d/m/Y') }}</div></div>
    </div>
    <div class="lp-box">
        <h3>Compras</h3>
        <ul class="lp-list">
            @forelse ($ventas as $v)
            <li>
                <div class="av"><i class="fa-solid fa-receipt"></i></div>
                <div class="t"><b>{{ $v->numero }} · {{ $v->created_at->format('d/m/Y') }}</b><small>{{ $v->items->map(fn ($i) => cantidad($i->cantidad) . '× ' . $i->descripcion . ($i->talla ? ' ' . $i->talla : ''))->implode(', ') }}</small></div>
                @if ($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif
                <span class="m">@pesos($v->total)</span>
                <a href="{{ route('pos.ticket', $v) }}" target="_blank" class="btn btn-sm btn-dark"><i class="fa-solid fa-receipt"></i></a>
            </li>
            @empty
            <li class="lp-empty">Aún no tiene compras registradas en el POS.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
