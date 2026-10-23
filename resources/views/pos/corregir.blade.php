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
    <div class="alert alert-info small fw-semibold mb-3">
        <div class="mb-1"><b>¿Qué hago?</b> Deje cada producto como <b>realmente</b> se vendió:</div>
        <ul class="mb-1 ps-3">
            <li><b>Precio mal digitado</b> (ej. se vendió en $150.000 y se registró $125.000): escriba <b>150.000</b> en <b>Precio real</b> y deje la cantidad igual.</li>
            <li><b>Método de pago equivocado</b> (ej. quedó en Efectivo y fue Transferencia): cámbielo abajo en <b>¿Cómo pagó realmente?</b>.</li>
            <li><b>Productos de más</b> (ej. se digitaron 2 y se vendió 1): baje la <b>Cantidad real</b>; el producto vuelve al inventario.</li>
        </ul>
        El reporte de ventas, la ganancia
        @if ($cerrada) y la caja del {{ $venta->caja->abierta_en?->format('d/m/Y') }} ({{ $venta->caja->user->name ?? '' }}) se recalculan solos (si sobró plata por el error, la caja queda cuadrada). @else y la caja se recalculan solos. @endif
        No mueve plata de la caja de hoy.
    </div>

    <form method="post" action="{{ route('pos.corregir.guardar', $venta) }}" data-confirm="¿Corregir la venta {{ $venta->numero }}?" data-confirm-texto="Se recalculan la venta, el inventario, los reportes y la caja de ese día." data-confirm-ok="Sí, corregir">
        @csrf
        <div class="lp-box mb-3">
            <h3>¿Cuánto se vendió de verdad?</h3>
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th class="text-end">Registrado</th><th style="width:110px">Cantidad real</th><th style="width:170px">Precio real</th></tr></thead>
                <tbody>
                @foreach ($venta->items as $i)
                    <tr>
                        <td><b>{{ $i->descripcion }}</b><br><small class="text-muted">{{ trim(($i->talla ? 'Talla ' . $i->talla : '') . ($i->color ? ' · ' . $i->color : '')) }}</small></td>
                        <td class="text-end text-nowrap">{{ cantidad($i->cantidad) }} × @pesos($i->precio)</td>
                        <td><input type="number" class="form-control cv" name="cantidades[{{ $i->id }}]" min="0" step="any" max="{{ (float) $i->cantidad }}" value="{{ (float) $i->cantidad }}" data-c="{{ (float) $i->cantidad }}" data-p="{{ (float) $i->precio }}" title="Solo se puede bajar"></td>
                        <td><div class="input-group"><span class="input-group-text">$</span><input type="text" inputmode="numeric" class="form-control cp" name="precios[{{ $i->id }}]" value="{{ number_format((float) $i->precio, 0, ',', '.') }}" data-p="{{ (float) $i->precio }}" autocomplete="off"></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-between text-muted border-top pt-2"><span>Total registrado</span><span>@pesos($venta->total)</span></div>
            <div class="d-flex justify-content-between fw-bold fs-5"><span>Total real</span><span id="cvTotal">@pesos($venta->total)</span></div>
            <div class="d-flex justify-content-between fw-bold" id="cvDifFila"><span id="cvDifTxt">Sin cambios</span><span id="cvDif"></span></div>
            @if ($venta->descuento > 0)<small class="text-muted">Esta venta tuvo descuento de @pesos($venta->descuento): se mantiene (si el nuevo total es menor, se ajusta).</small>@endif
        </div>

        <div class="lp-box mb-3">
            <h3>Datos de la corrección</h3>
            @if ($venta->pagos->count() > 1)
            <label class="form-label fw-bold small">¿Qué pago se ajusta?</label>
            <select name="pago_id" class="form-select mb-3">
                @foreach ($venta->pagos as $p)<option value="{{ $p->id }}" @selected($p->metodo->es_efectivo ?? false)>{{ $p->metodo->nombre ?? 'Pago' }} · @pesos($p->monto)</option>@endforeach
            </select>
            @else
            <p class="small text-muted mb-2">La diferencia se ajusta en el pago: <b>{{ $venta->pagos->first()->metodo->nombre ?? '—' }}</b>.</p>
            @endif
            <label class="form-label fw-bold small">¿Cómo pagó realmente?</label>
            @foreach ($venta->pagos as $p)
            <div class="d-flex align-items-center gap-2 mb-2">
                @if ($venta->pagos->count() > 1)<span class="small text-muted text-nowrap">@pesos($p->monto):</span>@endif
                <select name="metodos[{{ $p->id }}]" class="form-select" @disabled(($p->moneda ?? null) === 'VES')>
                    @if (!$metodos->contains('id', $p->metodo_pago_id))<option value="">{{ $p->metodo->nombre ?? 'Pago' }}</option>@endif
                    @foreach ($metodos as $m)<option value="{{ $m->id }}" @selected((int) $m->id === (int) $p->metodo_pago_id)>{{ $m->nombre }}</option>@endforeach
                </select>
            </div>
            @endforeach
            <small class="text-muted d-block mb-3">Si cambia Efectivo por Transferencia, la caja de ese día deja de esperar esa plata en efectivo.</small>
            <label class="form-label fw-bold small">Motivo</label>
            <input type="text" name="motivo" class="form-control" maxlength="255" required value="{{ old('motivo') }}" placeholder="Ej.: se vendió en $150.000 por transferencia y se digitó $125.000 en efectivo">
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
            @foreach ($c->detalle['productos'] ?? [] as $d){{ $d['producto'] }}: {{ cantidad($d['antes']) }} → {{ cantidad($d['despues']) }}@if (isset($d['precio_despues']) && abs($d['precio_despues'] - $d['precio']) >= 0.5), precio @pesos($d['precio']) → @pesos($d['precio_despues'])@endif. @endforeach
            @foreach ($c->detalle['metodos'] ?? [] as $d)Pago @pesos($d['monto']): {{ $d['antes'] }} → {{ $d['despues'] }}. @endforeach
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
    var desc = {{ (float) $venta->descuento }};
    var num = function (t) { return parseInt(String(t).replace(/\D/g, ''), 10) || 0; };
    function calc() {
        var sub = 0, antes = 0;
        document.querySelectorAll('.cv').forEach(function (i) {
            var c = parseFloat(i.dataset.c) || 0, v = Math.min(Math.max(parseFloat(i.value) || 0, 0), c);
            var p = num(i.closest('tr').querySelector('.cp').value);
            sub += v * p; antes += c * (parseFloat(i.dataset.p) || 0);
        });
        var total = Math.max(sub - Math.min(desc, sub), 0), reg = Math.max(antes - Math.min(desc, antes), 0), d = total - reg;
        document.getElementById('cvTotal').textContent = '$' + fmt.format(total);
        var txt = document.getElementById('cvDifTxt'), val = document.getElementById('cvDif');
        if (Math.abs(d) < 1) { txt.textContent = 'Sin cambios'; val.textContent = ''; txt.className = val.className = 'text-muted'; }
        else if (d > 0) { txt.textContent = 'Se suma a la venta'; val.textContent = '+ $' + fmt.format(d); txt.className = val.className = 'text-success'; }
        else { txt.textContent = 'Se quita de la venta'; val.textContent = '− $' + fmt.format(-d); txt.className = val.className = 'text-danger'; }
    }
    document.querySelectorAll('.cv').forEach(function (i) { i.addEventListener('input', calc); });
    document.querySelectorAll('.cp').forEach(function (i) {
        i.addEventListener('input', function () {
            var n = num(i.value); i.value = n ? fmt.format(n) : '';
            i.setSelectionRange(i.value.length, i.value.length); calc();
        });
        i.addEventListener('focus', function () { i.select(); });
    });
    calc();
})();
</script>
@endpush
