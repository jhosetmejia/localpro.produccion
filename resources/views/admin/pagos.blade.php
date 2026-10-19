@extends('layouts.app')

@section('title', 'Pagos de planes')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Pagos de <span>planes</span></h1>
            <span class="text-muted fw-semibold">Recibido en este filtro: <b>{{ $f($total) }}</b></span></div>
        <form method="post" action="{{ route('admin.pagos.verificar') }}">@csrf<button class="btn btn-dark"><i class="fa-solid fa-rotate me-1"></i>Revisar con Wompi</button></form>
    </div>
    <div class="lp-box">
        <form method="get" class="d-flex flex-wrap gap-2 mb-3">
            <select name="estado" class="form-select" style="max-width:200px" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (['aprobado' => 'Aprobados', 'pendiente' => 'Pendientes', 'rechazado' => 'Rechazados'] as $k => $v)<option value="{{ $k }}" @selected($estado === $k)>{{ $v }}</option>@endforeach
            </select>
            <input type="month" name="mes" class="form-control" style="max-width:200px" value="{{ $mes }}" onchange="this.form.submit()">
            @if ($estado || $mes)<a href="{{ route('admin.pagos') }}" class="btn btn-light">Quitar filtros</a>@endif
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Plan</th><th>Método</th><th>Período</th><th class="text-end">Valor</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($lista as $p)
                <tr>
                    <td class="small">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td>@if ($p->tienda)<a href="{{ route('admin.tiendas.show', $p->tienda_id) }}">{{ $p->tienda->nombre }}</a>@else — @endif</td>
                    <td>{{ $p->plan->nombre ?? '—' }}@if ($p->es_prueba) <span class="badge bg-warning text-dark">Prueba</span>@endif</td>
                    <td class="small">{{ ucfirst($p->metodo) }}{{ $p->nota ? ' · ' . $p->nota : '' }}</td>
                    <td class="small">{{ $p->periodo_desde?->format('d/m/Y') }}{{ $p->periodo_hasta ? ' → ' . $p->periodo_hasta->format('d/m/Y') : '' }}</td>
                    <td class="text-end fw-bold">{{ $p->monto_texto }}@if (\App\Support\Moneda::esExtranjera($p->moneda))<br><small class="text-muted fw-normal">{{ $f($p->monto_cop) }} COP</small>@endif</td>
                    <td><span class="badge {{ $p->estado === 'aprobado' ? 'bg-success' : ($p->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($p->estado) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">Sin pagos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $lista->links() }}
    </div>
</div>
@endsection
