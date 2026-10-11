@extends('layouts.app')

@section('title', 'Nueva entrada')

@section('content')
<div class="lp-dash" style="max-width:1000px;margin:0 auto">
    <div class="lp-hello"><div><small><a href="{{ route('entradas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Entradas</a></small><h1>Nueva <span>entrada</span></h1><span class="text-muted fw-semibold">Busque cada producto que llegó, ponga la cantidad y lo que le costó.</span></div></div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('entradas.store') }}" id="fEntrada">
        @csrf
        <div class="lp-box mb-3">
            <div class="row g-3">
                <div class="col-md-5"><label class="form-label">Proveedor <span class="text-muted fw-normal">(opcional)</span></label>
                    <select name="proveedore_id" class="form-select">
                        <option value="">— Sin proveedor —</option>
                        @foreach ($proveedores as $p)<option value="{{ $p->id }}" @selected(old('proveedore_id') == $p->id)>{{ $p->persona->razon_social ?? 'Proveedor ' . $p->id }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->format('Y-m-d')) }}" required></div>
                <div class="col-md-4"><label class="form-label">N.º de factura <span class="text-muted fw-normal">(opcional)</span></label><input name="numero_factura" class="form-control" value="{{ old('numero_factura') }}" maxlength="40"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>Productos que llegaron</h3>
            <div class="position-relative mb-3">
                <input id="buscar" class="form-control form-control-lg" placeholder="Buscar producto o escanear código..." autocomplete="off">
                <div id="resultados" class="lp-box" style="position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:20;display:none;max-height:360px;overflow:auto;padding:.6rem"></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Producto</th><th style="width:130px">Cantidad</th><th style="width:170px">Costo c/u</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                    <tbody id="filas"><tr id="vacio"><td colspan="5" class="lp-empty">Busque arriba y toque la talla/color que llegó.</td></tr></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 p-3" style="background:#0b0d0c;color:#fff;border-radius:16px">
                <b>Total de la entrada</b><b class="fs-4" style="color:#a6f34a" id="total">@pesos(0)</b>
            </div>
            <label class="form-label mt-3">Nota <span class="text-muted fw-normal">(opcional)</span></label>
            <input name="nota" class="form-control" maxlength="255">
            <button class="btn btn-primary btn-lg w-100 mt-3" id="guardar" disabled><i class="fa-solid fa-floppy-disk me-1"></i>Guardar entrada y sumar al inventario</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var URL = @json(route('pos.buscar'));
    var M = @json(moneda());
    var fmt = function (n) { var t = Math.abs(n || 0).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var q = document.getElementById('buscar'), res = document.getElementById('resultados'), filas = document.getElementById('filas');
    var n = 0, t;

    q.addEventListener('input', function () { clearTimeout(t); var v = q.value.trim(); if (!v) { res.style.display = 'none'; return; } t = setTimeout(function () { buscar(v); }, 220); });
    q.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(t); buscar(q.value.trim()); } });
    document.addEventListener('click', function (e) { if (!res.contains(e.target) && e.target !== q) res.style.display = 'none'; });

    function buscar(v) {
        fetch(URL + '?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.exacta) {
                for (var i = 0; i < d.productos.length; i++) for (var j = 0; j < d.productos[i].variantes.length; j++)
                    if (d.productos[i].variantes[j].id === d.exacta) { agregar(d.productos[i], d.productos[i].variantes[j]); q.value = ''; res.style.display = 'none'; return; }
            }
            if (!d.productos.length) { res.innerHTML = '<div class="lp-empty">No se encontró. Cree el producto primero en Productos.</div>'; res.style.display = 'block'; return; }
            res.innerHTML = d.productos.map(function (p, i) {
                return '<div class="mb-2"><b>' + esc(p.nombre) + '</b> <small class="text-muted">' + esc(p.codigo) + '</small><div class="d-flex flex-wrap gap-1 mt-1">' +
                    p.variantes.map(function (v, j) { return '<button type="button" class="btn btn-sm btn-info" data-p="' + i + '" data-v="' + j + '">' + esc(v.etiqueta) + ' <small>(' + v.stock + ')</small></button>'; }).join('') + '</div></div>';
            }).join('');
            res._d = d.productos; res.style.display = 'block';
        });
    }
    res.addEventListener('click', function (e) {
        var b = e.target.closest('button[data-p]'); if (!b) return;
        var p = res._d[+b.dataset.p]; agregar(p, p.variantes[+b.dataset.v]);
    });

    function agregar(p, v) {
        var ex = filas.querySelector('tr[data-id="' + v.id + '"]');
        if (ex) { var c = ex.querySelector('.c'); c.value = (+c.value || 0) + 1; calcular(); return; }
        var vac = document.getElementById('vacio'); if (vac) vac.remove();
        var k = n++;
        var tr = document.createElement('tr'); tr.dataset.id = v.id;
        tr.innerHTML = '<td><b>' + esc(p.nombre) + '</b><br><small class="text-muted">' + esc(v.etiqueta) + '</small><input type="hidden" name="items[' + k + '][variante_id]" value="' + v.id + '"></td>' +
            '<td><input type="number" min="1" class="form-control c" name="items[' + k + '][cantidad]" value="1" required></td>' +
            '<td><input data-dinero type="number" min="0" step="' + (M.decimales ? '0.01' : '1') + '" class="form-control k" name="items[' + k + '][costo]" value="' + (v.costo || 0) + '" required></td>' +
            '<td class="text-end fw-bold s"></td><td class="text-end"><button type="button" class="btn btn-sm btn-danger x"><i class="fa-solid fa-xmark"></i></button></td>';
        filas.appendChild(tr);
        tr.querySelector('.c').focus(); tr.querySelector('.c').select();
        calcular();
    }
    filas.addEventListener('input', calcular);
    filas.addEventListener('click', function (e) { var b = e.target.closest('.x'); if (b) { b.closest('tr').remove(); calcular(); } });

    function calcular() {
        var total = 0, rows = filas.querySelectorAll('tr[data-id]');
        rows.forEach(function (tr) {
            var s = (+tr.querySelector('.c').value || 0) * (+tr.querySelector('.k').value || 0);
            tr.querySelector('.s').textContent = fmt(s); total += s;
        });
        document.getElementById('total').textContent = fmt(total);
        document.getElementById('guardar').disabled = rows.length === 0;
    }
})();
</script>
@endpush
