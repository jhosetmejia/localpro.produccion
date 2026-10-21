@extends('layouts.app')

@section('title', 'Pedido #' . $pedido->numero)

@section('content')
@php
    $e = \App\Models\Pedido::ESTADOS[$pedido->estado] ?? ['', '#475569', '#eef1ef'];
    $u = auth()->user();
    $mesero = $u->es_superadmin || $u->can('pedidos-tomar') || $u->can('pedidos-administrar');
    $cobra = $u->es_superadmin || $u->can('pedidos-cobrar') || $u->can('pedidos-administrar');
    $vivos = $pedido->items->where('estado', '<>', 'anulado');
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-receipt me-1"></i>Pedido {{ $pedido->modo === 'despues' ? '· paga al final' : '· paga antes' }}</small>
            <h1>#{{ $pedido->numero }} <span>{{ $pedido->cliente_nombre }}</span></h1>
            <span class="pd-est" style="color:{{ $e[1] }};background:{{ $e[2] }};font-size:.9rem">{{ $pedido->estado_txt }}</span>
            <span class="text-muted fw-semibold ms-2">@if ($pedido->mesa)Mesa {{ $pedido->mesa }} · @endif{{ $pedido->mesero?->name }} · {{ $pedido->created_at->locale('es')->isoFormat('h:mm a') }}</span></div>
        <div class="lp-quick"><a href="{{ route('pedidos.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Pedidos</a></div>
    </div>
    @include('pedidos._nav')
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="lp-box">
                @if ($pedido->nota)<div class="alert alert-warning py-2"><i class="fa-solid fa-note-sticky me-1"></i>{{ $pedido->nota }}</div>@endif
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Producto</th><th>Estación</th><th>Estado</th><th class="text-end">Valor</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($pedido->items as $i)
                    <tr @if ($i->estado === 'anulado') style="opacity:.45;text-decoration:line-through" @endif>
                        <td><b>{{ cantidad($i->cantidad) }} × {{ $i->nombre }}</b>@if ($i->etiqueta) <small class="text-muted">{{ $i->etiqueta }}</small>@endif
                            @if ($i->nota)<small class="d-block text-danger fw-bold"><i class="fa-solid fa-comment me-1"></i>{{ $i->nota }}</small>@endif
                            @if ($i->ronda > 1)<small class="d-block text-muted">Agregado después (ronda {{ $i->ronda }})</small>@endif</td>
                        <td><small class="fw-bold">{{ $i->estacion?->nombre }}</small></td>
                        <td>@switch($i->estado)
                            @case('pendiente')<span class="pd-est" style="background:{{ $i->enviado_at ? '#dbeafe' : '#fef3c7' }};color:{{ $i->enviado_at ? '#1d4ed8' : '#b45309' }}">{{ $i->enviado_at ? 'Preparando' : 'Espera pago' }}</span>@break
                            @case('listo')<span class="pd-est" style="background:#dcfce7;color:#11702f">Listo</span>@break
                            @case('entregado')<span class="pd-est" style="background:#eef1ef;color:#475569">Entregado</span>@break
                            @default<span class="pd-est" style="background:#fee2e2;color:#b91c1c">Quitado</span>
                        @endswitch</td>
                        <td class="text-end fw-bold">{{ dinero($i->subtotal) }}</td>
                        <td class="text-end">@if (!$pedido->pagado && $i->estado === 'pendiente' && $pedido->abierto)
                            <form method="post" action="{{ route('pedidos.items.quitar', $i) }}" data-confirm="¿Quitar {{ $i->nombre }} del pedido?" data-confirm-ok="Sí, quitar">@csrf<button class="btn btn-sm btn-light" title="Quitar"><i class="fa-solid fa-xmark"></i></button></form>
                        @endif</td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="3">Total</th><th class="text-end" style="font-size:1.2rem">{{ dinero($pedido->total) }}</th><th></th></tr></tfoot>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="lp-box">
                @if ($pedido->pagado)
                <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-1"></i>Pagado {{ $pedido->pagado_at?->locale('es')->isoFormat('h:mm a') }}@if ($pedido->venta) · venta {{ $pedido->venta->numero }}@endif</div>
                @endif
                @if ($mesero && $vivos->where('estado', 'listo')->count())
                <form method="post" action="{{ route('pedidos.entregar', $pedido) }}" class="mb-2">@csrf<button class="btn btn-success btn-lg w-100"><i class="fa-solid fa-hand-holding me-1"></i>Marcar entregado</button></form>
                @endif
                @if ($cobra && $pedido->estado === 'por_cobrar' && !$pedido->pagado)
                <a href="{{ route('pos.index', ['pedido' => $pedido->id]) }}" class="btn btn-primary btn-lg w-100 mb-2"><i class="fa-solid fa-cash-register me-1"></i>Cobrar {{ dinero($pedido->total) }}</a>
                @endif
                @if ($mesero && $pedido->abierto && !$pedido->pagado)
                <a href="{{ route('pedidos.agregar', $pedido) }}" class="btn btn-dark w-100 mb-2"><i class="fa-solid fa-plus me-1"></i>Agregar productos</a>
                @endif
                @if ($mesero && $pedido->modo === 'despues' && !$pedido->pagado && !$pedido->cuenta_pedida && $pedido->abierto)
                <form method="post" action="{{ route('pedidos.cuenta', $pedido) }}" class="mb-2">@csrf<button class="btn btn-warning w-100 fw-bold"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Pedir la cuenta</button></form>
                @endif
                @if ($mesero && $pedido->pagado && !$pedido->abierto)
                <a href="{{ route('pedidos.crear', ['cliente' => $pedido->cliente_nombre, 'mesa' => $pedido->mesa]) }}" class="btn btn-dark w-100 mb-2"><i class="fa-solid fa-plus me-1"></i>Nuevo pedido para este cliente</a>
                @endif
                <a href="{{ route('pedidos.comanda', $pedido) }}" target="_blank" class="btn btn-light w-100 mb-2"><i class="fa-solid fa-print me-1"></i>Imprimir comanda</a>
                @if ($pedido->abierto && !$pedido->pagado)
                <form method="post" action="{{ route('pedidos.anular', $pedido) }}" data-confirm="¿Anular el pedido #{{ $pedido->numero }}?" data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-link text-danger w-100">Anular pedido</button></form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
