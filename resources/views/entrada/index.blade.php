@extends('layouts.app')

@section('title', 'Entradas de mercancía')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Inventario</small><h1>Entradas de <span>mercancía</span></h1><span class="text-muted fw-semibold">Lo que llega de sus proveedores suma al inventario y actualiza su costo.</span></div>
        <div class="lp-quick">@can('crear-entradas')<a href="{{ route('entradas.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-truck-ramp-box me-1"></i>Nueva entrada</a>@endcan</div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th>Productos</th><th>Total</th><th>Registró</th><th></th></tr></thead>
                <tbody>
                @forelse ($entradas as $e)
                <tr>
                    <td class="fw-bold">ENT-{{ str_pad($e->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $e->fecha->format('d/m/Y') }}</td>
                    <td>{{ $e->proveedor?->persona?->razon_social ?? '—' }}</td>
                    <td>{{ $e->numero_factura ?? '—' }}</td>
                    <td>{{ $e->items_count }}</td>
                    <td class="fw-bold">@pesos($e->total)</td>
                    <td>{{ $e->user->name ?? '—' }}</td>
                    <td class="text-end"><a href="{{ route('entradas.show', $e) }}" class="btn btn-sm btn-dark">Ver</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="lp-empty">Aún no ha registrado entradas. Cuando le llegue mercancía, regístrela aquí.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $entradas->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
