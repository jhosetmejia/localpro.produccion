@extends('layouts.app')

@section('title', 'Corregir venta ' . $venta->numero)

@section('content')
@php $cerrada = $venta->caja && $venta->caja->estado === 'cerrada'; @endphp
<div class="lp-dash" style="max-width:860px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('pos.ventas') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Ventas</a></small><h1>Corregir <span>venta</span></h1>
        <span class="text-muted fw-semibold">Venta {{ $venta->numero }} · {{ $venta->created_at->format('d/m/Y h:i a') }} · {{ $venta->user->name ?? '—' }} · @pesos($venta->total)</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if ($bloqueo)
    <div class="alert alert-warning fw-bold">{{ $bloqueo }}</div>
    @else
    <div class="alert alert-info small fw-semibold">
        Úsela cuando en la caja se digitaron productos <b>de más</b>. Baje la cantidad a lo que realmente se vendió:
        los productos vuelven al inventario, el reporte de ventas y la ganancia se recalculan
        @if ($cerrada) y la caja del {{ $venta->caja->abierta_en?->format('d/m/Y') }} ({{ $venta->caja->user->name ?? '' }}) se vuelve a cuadrar sola. @else y la caja lo descuenta. @endif
        No mueve plata de la caja de hoy.
    </div>

    <form method="post" action="{{ route('pos.corregir.guardar', $venta) }}" data-confirm="¿Corregir la venta {{ $venta->numero }}?" data-confirm-texto="Se recalculan la venta, el inventario, los reportes y la caja de ese día." data-confirm-ok="Sí, corregir">
        @csrf
        <div class="lp-box mb-3">
            <h3>¿Cuánto se vendió de verdad?</h3>
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th class="text-end">Registrado</th><th class="text-end">Precio</th><th style="width:140px">Se vendió</th></tr></thead>
                <tbody>
                @foreach ($venta->items as $i)
                    <tr>
                        <td><b>{{ $i->descripcion }}</b><br><small class="text-muted">{{ trim(($i->talla ? 'Talla ' . $i->talla : '') . ($i->color ? ' · ' . $i->color : '')) }}</small></td>
                        <td class="text-end">{{ cantidad($i->cantidad) }}</td>
                        <td class="text-end">@pesos($i->precio)</td>
                        <td><input type="number" class="form-control cv" name="cantidades[{{ $i->id }}]" min="0" step="any" max="{{ (float) $i->cantidad }}" value="{{ (float) $i->cantidad }}" data-c="{{ (float) $i->cantidad }}" data-p="{{ (float) $i->precio }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2"><span>Se quitan</span><span id="cvMenos">$0</span></div>
            @if ($venta->descuento > 0)<small class="text-muted">Esta venta tuvo descuento de @pesos($venta->descuento): se mantiene (si el nuevo total es menor, se ajusta).</small>@endif
        </div>

        <div class="lp-box mb-3">
            <h3>Datos de la corrección</h3>
            @if ($venta->pagos->count() > 1)
            <label class="form-label fw-bold small">¿De qué pago se descuenta?</label>
            <select name="pago_id" class="form-select mb-3">
                @foreach ($venta->pagos as $p)<option value="{{ $p->id }}" @selected($p->metodo->es_efectivo ?? false)>{{ $p->metodo->nombre ?? 'Pago' }} · @pesos($p->monto)</option>@endforeach
            </select>
            @else
            <p class="small text-muted mb-2">Se descuenta del pago: <b>{{ $venta->pagos->first()->metodo->nombre ?? '—' }}</b>.</p>
            @endif
            <label class="form-label fw-bold small">Motivo</label>
            <input type="text" name="motivo" class="form-control" maxlength="255" required value="{{ old('motivo') }}" placeholder="Ej.: se digitaron 2 productos de más">
            <button class="btn btn-primary btn-lg rounded-pill fw-bold mt-3"><i class="fa-solid fa-check me-1"></i>Corregir venta</button>
        </div>
    </form>
    @endif

    @if ($venta->correcciones->isNotEmpty())
    <div class="lp-box">
        <h3>Correcciones anteriores</h3>
        @foreach ($venta->correcciones as $c)
        <div class="border-bottom py-2 small">
            <b>{{ $c->created_at->format('d/m/Y h:i a') }}</b> · {{ $c->user->name ?? '—' }} · @pesos($c->total_antes) → @pesos($c->total_despues)<br>
            @foreach ($c->detalle['productos'] ?? [] as $d){{ $d['producto'] }}: {{ cantidad($d['antes']) }} → {{ cantidad($d['despues']) }}. @endforeach
            <span class="text-muted">Motivo: {{ $c->motivo }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var fmt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
    function calc() {
        var m = 0;
        document.querySelectorAll('.cv').forEach(function (i) {
            var c = parseFloat(i.dataset.c) || 0, v = Math.min(Math.max(parseFloat(i.value) || 0, 0), c);
            m += (c - v) * (parseFloat(i.dataset.p) || 0);
        });
        var e = document.getElementById('cvMenos'); if (e) e.textContent = '$' + fmt.format(m);
    }
    document.querySelectorAll('.cv').forEach(function (i) { i.addEventListener('input', calc); });
    calc();
})();
</script>
@endpush
