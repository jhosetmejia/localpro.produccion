@extends('layouts.app')

@section('title', 'Caja #' . $caja->id)

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>{{ $caja->user->name ?? '' }} · {{ $caja->abierta_en->format('d/m/Y h:i a') }}</small><h1>Caja #{{ $caja->id }} <span>{{ $caja->estado === 'abierta' ? 'abierta' : 'cerrada' }}</span></h1></div>
        <div class="lp-quick">
            @if ($caja->estado === 'abierta' && (int) $caja->user_id === (int) auth()->id())
                <a href="{{ route('pos.index') }}" class="btn btn-primary"><i class="fa-solid fa-cash-register me-1"></i>Ir al POS</a>
                <a href="{{ route('cajas.cerrar') }}" class="btn btn-dark"><i class="fa-solid fa-lock me-1"></i>Cerrar caja</a>
            @elseif ($caja->estado === 'cerrada')
                <a href="{{ route('cajas.reporte', $caja) }}" target="_blank" class="btn btn-dark"><i class="fa-solid fa-print me-1"></i>Imprimir cuadre</a>
            @endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Vendido</div><div class="val">@pesos($totales->sum('total'))</div><div class="sub">{{ $ventas->where('estado', 'pagada')->count() }} ventas</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-money-bill-wave"></i></div><div class="lbl">Efectivo esperado</div><div class="val">@pesos($esperado)</div><div class="sub">Base @pesos($caja->monto_inicial) + efectivo</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-scale-balanced"></i></div><div class="lbl">Diferencia</div><div class="val">{{ $caja->diferencia === null ? '—' : dinero($caja->diferencia) }}</div><div class="sub">{{ $caja->estado === 'abierta' ? 'Se calcula al cerrar' : 'Contó ' . dinero($caja->efectivo_contado) }}</div></div>
    </div>

    @include('caja._movimientos')

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Ventas del turno</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>N.º</th><th>Hora</th><th>Cliente</th><th>Pago</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($ventas as $v)
                    <tr class="{{ $v->estado === 'anulada' ? 'opacity-50' : '' }}">
                        <td class="fw-bold">{{ $v->numero }}</td>
                        <td>{{ $v->created_at->format('h:i a') }}</td>
                        <td>{{ $v->cliente_nombre ?? 'Mostrador' }}</td>
                        <td><small>{{ $v->pagos->map(fn ($p) => $p->metodo->nombre)->implode(', ') }}</small></td>
                        <td class="fw-bold">@pesos($v->total) @if($v->estado === 'anulada')<span class="badge bg-danger">Anulada</span>@endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('pos.ticket', $v) }}" target="_blank" class="btn btn-sm btn-info"><i class="fa-solid fa-receipt"></i></a>
                            @if ($v->estado === 'pagada' && $caja->estado === 'abierta')
                            @can('anular-venta-pos')
                            <form method="post" action="{{ route('pos.anular', $v) }}" class="d-inline" data-confirm="¿Anular la venta {{ $v->numero }}?" data-confirm-texto="Las prendas vuelven al inventario y la venta no suma en la caja." data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button></form>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="lp-empty">Aún no hay ventas en esta caja.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="lp-box">
            <h3>Por método de pago</h3>
            <ul class="lp-list">
                @forelse ($totales as $t)
                <li><div class="av"><i class="fa-solid {{ $t->es_efectivo ? 'fa-money-bill-wave' : 'fa-mobile-screen-button' }}"></i></div><div class="t"><b>{{ $t->nombre }}</b><small>{{ $t->ventas }} venta(s)</small></div><span class="m">@pesos($t->total)</span></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
                @foreach ($reembolsos as $r)
                <li><div class="av" style="color:#b0206a"><i class="fa-solid fa-rotate-left"></i></div><div class="t"><b>Devoluciones en {{ $r->nombre }}</b><small>{{ $r->n }} devolución(es)</small></div><span class="m" style="color:#b0206a">-@pesos($r->total)</span></li>
                @endforeach
                @php $egCaja = $caja->totalEgresos(); @endphp
                @if ($egCaja > 0)
                <li><div class="av" style="color:#b0206a"><i class="fa-solid fa-arrow-trend-down"></i></div><div class="t"><b>Egresos pagados con la caja</b><small>{{ $caja->egresos()->count() }} egreso(s) · <a href="{{ route('egresos.index') }}">ver</a></small></div><span class="m" style="color:#b0206a">-@pesos($egCaja)</span></li>
                @endif

            </ul>
        </div>
    </div>
</div>
@endsection
