@extends('layouts.app')

@section('title', 'Comisiones')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('admin.red.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Red comercial</a></small><h1>Comisiones</h1>
            <span class="text-muted fw-semibold">Se generan solas cuando un negocio de la red paga su plan</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    @foreach (['nueva' => ['Ventas nuevas · pagar ya', 'La comisión de una venta nueva se puede retirar apenas el negocio paga su plan.', 'fa-bolt'], 'renovacion' => ['Renovaciones · corte mensual', 'Las comisiones de renovación se pagan una vez al mes.', 'fa-calendar-check']] as $tipo => [$titulo, $ayuda, $icono])
    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid {{ $icono }} me-1"></i> {{ $titulo }}</span></h3>
        <p class="small text-muted mb-2">{{ $ayuda }}</p>
        <ul class="lp-list">
            @forelse ($porAsesor[$tipo] ?? [] as $p)
            <li><div class="av"><i class="fa-solid {{ $p->asesor->cargo_icono ?? 'fa-user' }}"></i></div>
                <div class="t"><b><a href="{{ route('admin.red.show', $p->asesor_id) }}" class="text-decoration-none" style="color:inherit">{{ $p->asesor->nombre ?? '—' }}</a> · {{ \App\Support\Moneda::formato($p->total, $p->moneda ?: 'COP', true) }}</b><small>{{ $p->n }} comisión(es){{ $p->asesor?->banco ? ' · Pagar a: ' . $p->asesor->banco : '' }}</small></div>
                <form method="post" action="{{ route('admin.red.pagar') }}" data-confirm="¿Ya le pagó {{ \App\Support\Moneda::formato($p->total, $p->moneda ?: 'COP', true) }} a {{ $p->asesor->nombre ?? '' }}?" data-confirm-ok="Sí, marcar pagado">@csrf<input type="hidden" name="asesor_id" value="{{ $p->asesor_id }}"><input type="hidden" name="tipo" value="{{ $tipo }}"><button class="btn btn-sm btn-dark">Pagado</button></form></li>
            @empty
            <li class="lp-empty">Nada pendiente. ✅</li>
            @endforelse
        </ul>
    </div>
    @endforeach

    <div class="lp-box">
        <h3><span>Detalle</span>
            <form method="get" class="d-flex gap-2">
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">@foreach (['pendiente' => 'Pendientes', 'pagada' => 'Pagadas', 'anulada' => 'Anuladas', 'todas' => 'Todas'] as $k => $v)<option value="{{ $k }}" @selected($estado === $k)>{{ $v }}</option>@endforeach</select>
                <select name="asesor" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Todas las personas</option>@foreach ($asesores as $as)<option value="{{ $as->id }}" @selected((int) $asesor === $as->id)>{{ $as->nombre }}</option>@endforeach</select>
            </form>
        </h3>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Fecha</th><th>Persona</th><th>Negocio</th><th>Concepto</th><th class="text-end">Valor</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($lista as $c)
                <tr>
                    <td class="small">{{ $c->created_at->format('d/m/Y') }}</td>
                    <td>{{ $c->asesor->nombre ?? '—' }}</td>
                    <td>{{ $c->tienda->nombre ?? '—' }}</td>
                    <td class="small">{{ $c->concepto_nombre }} · {{ $c->tipo === 'nueva' ? 'nueva' : 'renovación' }}</td>
                    <td class="text-end fw-bold">{{ $c->monto_texto }}</td>
                    <td><span class="badge {{ $c->estado === 'pagada' ? 'bg-success' : ($c->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($c->estado) }}</span></td>
                    <td>@if ($c->estado === 'pendiente')<form method="post" action="{{ route('admin.red.anular', $c) }}" data-confirm="¿Anular esta comisión?" data-confirm-texto="Úselo si el pago del negocio se devolvió o fue un error." data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-sm btn-light" title="Anular"><i class="fa-solid fa-ban"></i></button></form>@endif</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">Nada por aquí.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $lista->links() }}
    </div>
</div>
@endsection
