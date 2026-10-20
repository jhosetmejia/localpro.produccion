@extends('layouts.app')

@section('title', 'Registrar venta olvidada')

@push('css')
<style>
.vo-res{border:1.5px solid #e3ebe6;border-radius:16px;max-height:280px;overflow:auto;display:none;margin-top:.4rem;background:#fff}
.vo-res.on{display:block}
.vo-res button{display:flex;justify-content:space-between;gap:.6rem;width:100%;border:0;background:none;text-align:left;padding:.55rem .8rem;border-bottom:1px solid #eef2ef}
.vo-res button:hover{background:#f4fcf6}
.vo-res small{color:#6b7570;font-weight:600}
.vo-total{display:flex;justify-content:space-between;font-weight:900;font-size:1.3rem;border-top:1.5px solid #e3ebe6;padding-top:.6rem}
</style>
@endpush

@section('content')
<div class="lp-dash" style="max-width:900px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('pos.ventas') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Ventas</a></small><h1>Registrar venta <span>olvidada</span></h1>
        <span class="text-muted fw-semibold">¿Se vendió algo y no se registró en la caja? Cárguelo aquí con la fecha y en la caja de ese día, aunque ya esté cerrada.</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if ($bloqueo)
    <div class="alert alert-warning fw-bold">{{ $bloqueo }}</div>
    @elseif ($cajas->isEmpty())
    <div class="alert alert-warning fw-bold">No hay cajas de los últimos {{ \App\Http\Controllers\ventaOlvidadaController::DIAS }} días.</div>
    @else
    <form method="post" action="{{ route('pos.olvidada.guardar') }}" id="voForm" data-confirm="¿Registrar esta venta en la caja de ese día?" data-confirm-texto="Se descuenta del inventario y la caja de ese día se vuelve a cuadrar." data-confirm-ok="Sí, registrar">
        @csrf
        <div class="lp-box mb-3">
            <h3>1. ¿De qué día y qué caja?</h3>
            <div class="row g-2">
                <div class="col-md-8">
                    <select name="caja_id" class="form-select" required>
                        @foreach ($cajas as $c)
                        <option value="{{ $c->id }}" @selected(old('caja_id', $sugerida?->id) == $c->id)>{{ $c->abierta_en->translatedFormat('l d/m/Y') }} · {{ $c->user->name ?? '—' }} · {{ $c->estado === 'cerrada' ? 'cerrada ' . $c->cerrada_en?->format('h:i a') : 'abierta' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><input type="time" name="hora" class="form-control" value="{{ old('hora') }}" title="Hora aproximada (opcional)"></div>
            </div>
            <small class="text-muted">La venta queda a nombre del vendedor de esa caja. La hora es opcional.</small>
        </div>

        <div class="lp-box mb-3">
            <h3>2. ¿Qué se vendió?</h3>
            <input type="search" id="voBuscar" class="form-control form-control-lg" placeholder="Buscar producto por nombre, código o código de barras" autocomplete="off">
            <div class="vo-res" id="voRes"></div>
            <table class="table align-middle mt-3">
                <thead><tr><th>Producto</th><th style="width:110px">Cantidad</th><th style="width:150px">Precio</th><th class="text-end">Total</th><th></th></tr></thead>
                <tbody id="voItems"><tr class="vo-vacio"><td colspan="5" class="text-muted text-center">Busque y agregue los productos.</td></tr></tbody>
            </table>
            <div class="vo-total"><span>Total</span><span id="voTotal">0</span></div>
        </div>

        <div class="lp-box mb-3">
            <h3>3. ¿Cómo pagó y por qué se registra después?</h3>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label small fw-bold">Método de pago</label>
                    <select name="metodo_id" class="form-select" required>@foreach ($metodos as $m)<option value="{{ $m->id }}" @selected(old('metodo_id') ? old('metodo_id') == $m->id : $m->es_efectivo)>{{ $m->nombre }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Cliente (opcional)</label><input type="text" name="cliente_nombre" class="form-control" maxlength="80" value="{{ old('cliente_nombre') }}"></div>
                <div class="col-12"><label class="form-label small fw-bold">Motivo</label><input type="text" name="motivo" class="form-control" maxlength="255" required value="{{ old('motivo') }}" placeholder="Ej.: se vendió y no se registró en la caja"></div>
            </div>
            <button class="btn btn-primary btn-lg rounded-pill fw-bold mt-3" id="voGuardar" disabled><i class="fa-solid fa-check me-1"></i>Registrar venta</button>
        </div>
    </form>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var buscar = document.getElementById('voBuscar'); if (!buscar) return;
    var res = document.getElementById('voRes'), cuerpo = document.getElementById('voItems'), URL_B = @json(route('pos.buscar'));
    var fmt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: {{ (int) (moneda()['decimales'] ?? 0) }} }), SIM = @json(moneda()['simbolo'] ?? '$');
    var n = 0, t = null;
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function total() {
        var s = 0, filas = cuerpo.querySelectorAll('tr[data-f]');
        filas.forEach(function (tr) {
            var c = parseFloat(tr.querySelector('.c').value) || 0, p = parseFloat(tr.querySelector('.p').value) || 0;
            tr.querySelector('.st').textContent = SIM + fmt.format(c * p); s += c * p;
        });
        document.getElementById('voTotal').textContent = SIM + fmt.format(s);
        document.getElementById('voGuardar').disabled = !filas.length;
        var v = cuerpo.querySelector('.vo-vacio'); if (v) v.style.display = filas.length ? 'none' : '';
    }
    function agregar(p, v) {
        var i = n++, tr = document.createElement('tr'); tr.dataset.f = 1;
        var det = [v.talla ? 'Talla ' + v.talla : '', v.color || ''].filter(Boolean).join(' · ');
        tr.innerHTML = '<td><b>' + esc(p.nombre) + '</b>' + (det ? '<br><small class="text-muted">' + esc(det) + '</small>' : '') +
            '<input type="hidden" name="items[' + i + '][variante_id]" value="' + v.id + '"></td>' +
            '<td><input type="number" class="form-control c" name="items[' + i + '][cantidad]" min="0.001" step="any" value="1" required></td>' +
            '<td><input type="number" class="form-control p" name="items[' + i + '][precio]" min="0" step="any" value="' + (v.precio || p.precio || 0) + '" required></td>' +
            '<td class="text-end fw-bold st"></td><td><button type="button" class="btn btn-sm btn-light" data-q title="Quitar">✕</button></td>';
        cuerpo.appendChild(tr); total();
        res.classList.remove('on'); buscar.value = ''; buscar.focus();
    }
    cuerpo.addEventListener('input', total);
    cuerpo.addEventListener('click', function (e) { var b = e.target.closest('[data-q]'); if (b) { b.closest('tr').remove(); total(); } });
    buscar.addEventListener('input', function () {
        clearTimeout(t); var q = buscar.value.trim();
        if (q.length < 2) { res.classList.remove('on'); return; }
        t = setTimeout(function () {
            fetch(URL_B + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                res.innerHTML = '';
                (d.productos || []).forEach(function (p) {
                    (p.variantes || []).forEach(function (v) {
                        var b = document.createElement('button'); b.type = 'button';
                        var det = [v.talla ? 'Talla ' + v.talla : '', v.color || ''].filter(Boolean).join(' · ');
                        b.innerHTML = '<span><b>' + esc(p.nombre) + '</b>' + (det ? ' <small>' + esc(det) + '</small>' : '') + '</span><small>' + SIM + fmt.format(v.precio || p.precio || 0) + (p.controla ? ' · ' + v.stock + ' disp.' : '') + '</small>';
                        b.addEventListener('click', function () { agregar(p, v); });
                        res.appendChild(b);
                    });
                });
                if (!res.children.length) res.innerHTML = '<div class="p-3 text-muted small">No encontramos productos con existencias.</div>';
                res.classList.add('on');
            });
        }, 250);
    });
    buscar.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var b = res.querySelector('button'); if (b) b.click(); } });
})();
</script>
@endpush
