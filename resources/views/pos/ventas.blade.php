@extends('layouts.app')

@section('title', 'Ventas POS')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>POS</small><h1>Ventas del <span>punto de venta</span></h1></div>
        <div class="lp-quick">@can('usar-pos')<a href="{{ route('pos.index') }}" class="btn btn-primary"><i class="fa-solid fa-cash-register me-1"></i>Abrir POS</a>@endcan</div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box">
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-9"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por número, cliente o teléfono"></div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button></div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Vendedor</th><th>Cliente</th><th>Pago</th><th>Total</th><th></th></tr></thead>
                <tbody>
                @forelse ($ventas as $v)
                <tr class="{{ $v->estado === 'anulada' ? 'opacity-50' : '' }}">
                    <td class="fw-bold">{{ $v->numero }}
                        @if ($v->documento)<small class="d-block fw-bold" style="color:{{ $v->documento->estado_info[1] }}">{{ $v->tipo_doc === 'fe' ? 'FE' : 'POS-e' }} · {{ $v->documento->estado_info[0] }}</small>@endif
                    </td>
                    <td class="text-nowrap">{{ $v->created_at->format('d/m/Y h:i a') }}</td>
                    <td>{{ $v->user->name ?? '—' }}</td>
                    <td>{{ $v->cliente_nombre ?? 'Mostrador' }}@if($v->cliente_telefono)<br><small class="text-muted">{{ $v->cliente_telefono }}</small>@endif</td>
                    <td><small>{{ $v->pagos->map(fn ($p) => $p->metodo->nombre)->implode(', ') }}</small></td>
                    <td class="fw-bold">@pesos($v->total) @if($v->estado === 'anulada')<span class="badge bg-danger">Anulada</span>@endif @if($v->corregida_en)<span class="badge bg-warning text-dark" title="Corregida el {{ $v->corregida_en->format('d/m/Y h:i a') }}">Corregida</span>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('pos.ticket', $v) }}" target="_blank" class="btn btn-sm btn-dark"><i class="fa-solid fa-receipt"></i></a>
                        @if ($v->estado === 'pagada' && (auth()->user()->es_superadmin || auth()->user()->hasAnyRole(['dueno', 'administrador'])))
                        <a href="{{ route('pos.corregir', $v) }}" class="btn btn-sm btn-light border" title="Corregir venta (error al digitar)"><i class="fa-solid fa-pen-to-square"></i></a>
                        @endif
                        @if ($v->estado === 'pagada')
                        @can('devolver-venta')
                        <a href="{{ route('devoluciones.create', $v) }}" class="btn btn-sm btn-warning" title="Cambio o devolución"><i class="fa-solid fa-right-left"></i></a>
                        @endcan
                        @endif
                        <a href="{{ $v->url_factura }}" target="_blank" class="btn btn-sm btn-info" title="Factura del cliente"><i class="fa-solid fa-file-invoice"></i></a>
                        @if ($v->whatsapp_numero)
                        <a href="https://wa.me/{{ $v->whatsapp_numero }}?text={{ rawurlencode('Hola, aquí está su factura ' . $v->numero . ': ' . $v->url_factura) }}" target="_blank" class="btn btn-sm btn-success"><i class="fa-brands fa-whatsapp"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="lp-empty">Aún no hay ventas en el POS.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $ventas->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
