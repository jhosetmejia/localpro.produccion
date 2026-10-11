@extends('layouts.app')

@section('title', 'Cerrar caja')

@section('content')
<div class="lp-dash" style="max-width:720px;margin:0 auto">
    @include('layouts.partials.lp-flash')
    <div class="lp-hello"><div><small>Caja abierta desde {{ $caja->abierta_en->format('d/m/Y h:i a') }}</small><h1>Cerrar <span>caja</span></h1></div></div>

    <div class="lp-box mb-3">
        <h3>Lo que vendió en este turno</h3>
        <ul class="lp-list">
            <li><div class="av"><i class="fa-solid fa-piggy-bank"></i></div><div class="t"><b>Base inicial</b><small>Efectivo con el que abrió</small></div><span class="m">@pesos($caja->monto_inicial)</span></li>
            @forelse ($totales as $t)
            <li><div class="av"><i class="fa-solid {{ $t->es_efectivo ? 'fa-money-bill-wave' : 'fa-mobile-screen-button' }}"></i></div><div class="t"><b>{{ $t->nombre }}</b><small>{{ $t->ventas }} venta(s)</small></div><span class="m">@pesos($t->total)</span></li>
            @empty
            <li class="lp-empty">No hubo ventas en este turno.</li>
            @endforelse
            @foreach ($reembolsos as $r)
            <li><div class="av" style="color:#b0206a"><i class="fa-solid fa-rotate-left"></i></div><div class="t"><b>Devoluciones en {{ $r->nombre }}</b><small>{{ $r->n }} devolución(es)</small></div><span class="m" style="color:#b0206a">-@pesos($r->total)</span></li>
            @endforeach

        </ul>
        <div class="d-flex justify-content-between align-items-center mt-3 p-3" style="background:var(--lp-black);color:#fff;border-radius:16px">
            <span class="fw-bold">Efectivo que debe haber en caja</span>
            <span class="fs-4 fw-bold" style="color:var(--lp-lime)">@pesos($esperado)</span>
        </div>
    </div>

    <form method="post" action="{{ route('cajas.guardarCierre') }}" class="lp-box" onsubmit="return confirm('¿Cerrar la caja? Después no podrá vender hasta abrir una nueva.')">
        @csrf
        <h3>Cuente el efectivo</h3>
        <label class="form-label">Efectivo contado</label>
        <div class="input-group mb-2">
            <span class="input-group-text fw-bold" style="border-radius:12px 0 0 12px">{{ moneda()['simbolo'] }}</span>
            <input type="number" id="contado" name="efectivo_contado" min="0" step="{{ paso_dinero() }}" class="form-control form-control-lg fw-bold" required autofocus>
        </div>
        <div id="dif" class="fw-bold mb-3"></div>
        <label class="form-label">Nota de cierre (opcional)</label>
        <input name="nota_cierre" class="form-control mb-4" maxlength="255" placeholder="Ej: Se retiraron $200.000 para consignar">
        <button class="btn btn-dark btn-lg w-100"><i class="fa-solid fa-lock me-1"></i>Cerrar caja e imprimir cuadre</button>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var esperado = {{ (float) $esperado }}, inp = document.getElementById('contado'), out = document.getElementById('dif');
    var M = @json(moneda());
    var fmt = function (n) {
        var t = Math.abs(n).toFixed(M.decimales).split('.');
        t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles);
        return M.simbolo + t.join(M.decimal);
    };
    inp.addEventListener('input', function () {
        if (inp.value === '') { out.textContent = ''; return; }
        var d = parseFloat(inp.value) - esperado;
        out.style.color = Math.abs(d) < (M.decimales ? 0.005 : 1) ? '#11702f' : (d < 0 ? '#b0206a' : '#8a6400');
        out.textContent = Math.abs(d) < (M.decimales ? 0.005 : 1) ? '✔ La caja cuadra exacto' : (d < 0 ? 'Faltan ' + fmt(d) : 'Sobran ' + fmt(d));
    });
})();
</script>
@endpush
