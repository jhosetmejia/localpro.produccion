@extends('layouts.app')

@section('title', 'Cajas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Caja</small><h1>{{ $todas ? 'Cajas del negocio' : 'Mis cajas' }}</h1></div>
        <div class="lp-quick">@can('usar-pos')<a href="{{ route('cajas.actual') }}" class="btn btn-primary"><i class="fa-solid fa-cash-register me-1"></i>Mi caja actual</a>@endcan</div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th>@if($todas)<th>Vendedor</th>@endif<th>Apertura</th><th>Cierre</th><th>Base</th><th>Ventas</th><th>Diferencia</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($cajas as $c)
                <tr>
                    <td class="fw-bold">{{ $c->id }}</td>
                    @if($todas)<td>{{ $c->user->name }}</td>@endif
                    <td class="text-nowrap">{{ $c->abierta_en->format('d/m h:i a') }}</td>
                    <td class="text-nowrap">{{ $c->cerrada_en?->format('d/m h:i a') ?? '—' }}</td>
                    <td>@pesos($c->monto_inicial)</td>
                    <td>{{ $c->total_ventas !== null ? dinero($c->total_ventas) : '—' }}</td>
                    <td>
                        @if ($c->diferencia === null) —
                        @elseif (abs($c->diferencia) < (moneda()["decimales"] ? 0.01 : 1)) <span class="badge bg-success">Cuadró</span>
                        @else <span class="badge {{ $c->diferencia < 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $c->diferencia < 0 ? 'Faltó' : 'Sobró' }} @pesos(abs($c->diferencia))</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $c->estado === 'abierta' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($c->estado) }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('cajas.show', $c) }}" class="btn btn-sm btn-dark">Ver</a>
                        @if ($c->estado === 'cerrada')<a href="{{ route('cajas.reporte', $c) }}" target="_blank" class="btn btn-sm btn-info"><i class="fa-solid fa-print"></i></a>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="lp-empty">Aún no hay cajas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $cajas->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
