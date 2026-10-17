@extends('layouts.app')

@section('title', 'Movimientos de inventario')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('inventario.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Inventario</a></small><h1>Movimientos</h1></div>
    </div>
    @include('inventario._tabs')
    <div class="lp-box">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Prenda</th><th>Tipo</th><th>Cantidad</th><th>Quedó</th><th>Usuario</th><th>Detalle</th></tr></thead>
                <tbody>
                @php $tipos = ['entrada' => ['Entrada', 'bg-success'], 'salida' => ['Salida', 'bg-danger'], 'ajuste' => ['Ajuste', 'bg-warning text-dark'], 'conteo' => ['Conteo físico', 'bg-primary'], 'venta' => ['Venta', 'bg-dark'], 'anulacion' => ['Anulación', 'bg-info text-dark'], 'separado' => ['Separado', 'bg-secondary'], 'sep_cancelado' => ['Separado cancelado', 'bg-info text-dark']]; @endphp
                @forelse ($movimientos as $m)
                    <tr>
                        <td class="text-nowrap">{{ $m->created_at->format('d/m/Y h:i a') }}</td>
                        <td><b>{{ $m->variante?->producto?->nombre ?? '—' }}</b> <small class="text-muted">{{ $m->variante?->etiqueta }}</small></td>
                        <td><span class="badge {{ $tipos[$m->tipo][1] ?? 'bg-secondary' }}">{{ $tipos[$m->tipo][0] ?? $m->tipo }}</span></td>
                        <td class="fw-bold {{ $m->cantidad < 0 ? 'text-danger' : 'text-success' }}">{{ $m->cantidad > 0 ? '+' : '' }}{{ cantidad($m->cantidad) }}</td>
                        <td>{{ $m->stock_resultante }}</td>
                        <td>{{ $m->user?->name ?? '—' }}</td>
                        <td><small>{{ $m->referencia }} {{ $m->nota }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="lp-empty">Aún no hay movimientos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $movimientos->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
