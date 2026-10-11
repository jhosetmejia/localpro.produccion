@extends('layouts.app')

@section('title', 'Devolución ' . $venta->numero)

@section('content')
<div class="lp-dash" style="max-width:860px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('pos.ventas') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Ventas</a></small><h1>Cambio o <span>devolución</span></h1>
        <span class="text-muted fw-semibold">Venta {{ $venta->numero }} · {{ $venta->created_at->format('d/m/Y h:i a') }} · {{ $venta->cliente_nombre ?: 'Mostrador' }} · @pesos($venta->total)</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('devoluciones.store', $venta) }}" data-confirm="¿Registrar la devolución?" data-confirm-texto="Las prendas devueltas vuelven al inventario." data-confirm-ok="Sí, registrar">
        @csrf
        <div class="lp-box mb-3">
            <h3>¿Qué devuelve el cliente?</h3>
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th class="text-end">Vendido</th><th class="text-end">Precio</th><th style="width:130px">Devuelve</th></tr></thead>
                <tbody>
                @foreach ($venta->items as $i)
                    @php $max = $i->cantidad - ($devuelto[$i->id] ?? 0); @endphp
                    <tr class="{{ $max <= 0 ? 'opacity-50' : '' }}">
                        <td><b>{{ $i->descripcion }}</b><br><small class="text-muted">{{ trim(($i->talla ? 'Talla ' . $i->talla : '') . ($i->color ? ' · ' . $i->color : '')) }}</small></td>
                        <td class="text-end">{{ $i->cantidad }}@if(($devuelto[$i->id] ?? 0) > 0)<br><small class="text-danger">ya devolvió {{ $devuelto[$i->id] }}</small>@endif</td>
                        <td class="text-end">@pesos($i->precio)</td>
                        <td><input type="number" class="form-control dv" name="cantidades[{{ $i->id }}]" min="0" max="{{ max(0, $max) }}" value="0" data-p="{{ (float) $i->precio }}" @disabled($max <= 0)></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($venta->descuento > 0)<small class="text-muted">Esta venta tuvo descuento: se devuelve en proporción a lo que el cliente pagó.</small>@endif
        </div>

        <div class="lp-box">
            <h3>¿Cómo se resuelve?</h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check p-3 border rounded-4 h-100"><input class="form-check-input ms-0 me-2" type="radio" name="tipo" value="cambio" id="tc" checked onchange="document.getElementById('mp').style.display='none'"><label class="form-check-label fw-bold" for="tc"><i class="fa-solid fa-right-left me-1 text-success"></i> Cambio por otra prenda<small class="d-block text-muted fw-normal">Queda saldo a favor con un código para usar en el POS.</small></label></div>
                </div>
                <div class="col-md-6">
                    <div class="form-check p-3 border rounded-4 h-100"><input class="form-check-input ms-0 me-2" type="radio" name="tipo" value="reembolso" id="tr" onchange="document.getElementById('mp').style.display=''"><label class="form-check-label fw-bold" for="tr"><i class="fa-solid fa-money-bill-transfer me-1 text-danger"></i> Devolver el dinero<small class="d-block text-muted fw-normal">Sale de su caja abierta.</small></label></div>
                </div>
                <div class="col-md-6" id="mp" style="display:none">
                    <label class="form-label">¿Cómo le devuelve el dinero?</label>
                    <select name="metodo_pago_id" class="form-select">@foreach ($metodos as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach</select>
                </div>
                <div class="col-md-6"><label class="form-label">Motivo <span class="text-muted fw-normal">(opcional)</span></label><input name="motivo" class="form-control" maxlength="255" placeholder="Ej: no le quedó la talla"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 p-3" style="background:#0b0d0c;color:#fff;border-radius:16px"><b>Valor a devolver</b><b class="fs-4" style="color:#a6f34a" id="vt">@pesos(0)</b></div>
            <button class="btn btn-primary btn-lg w-100 mt-3"><i class="fa-solid fa-check me-1"></i>Registrar</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var M = @json(moneda()), f = {{ $venta->subtotal > 0 ? ($venta->subtotal - $venta->descuento) / $venta->subtotal : 1 }};
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    document.addEventListener('input', function () {
        var t = 0; document.querySelectorAll('.dv').forEach(function (i) { t += (+i.value || 0) * (+i.dataset.p); });
        document.getElementById('vt').textContent = fmt(t * f);
    });
})();
</script>
@endpush
