@extends('layouts.app')

@section('title', 'Nuevo separado')

@push('css')
<style>
.ns{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:1rem}
@media (max-width:960px){.ns{grid-template-columns:minmax(0,1fr)}}
.ns-res{display:none;border:1.5px solid #e3ebe6;border-radius:16px;margin-top:.5rem;max-height:340px;overflow:auto;background:#fff}
.ns-res .p{padding:.6rem .8rem;border-bottom:1px solid #eef3ef}
.ns-res .p b{display:block}
.ns-res .chips{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.35rem}
.ns-res .chips button{border:1.5px solid #cfe9d6;background:#f4fcf6;border-radius:999px;padding:.3rem .7rem;font-weight:800;font-size:.8rem;cursor:pointer}
.ns-res .chips button:hover{background:#16b34a;color:#fff;border-color:#16b34a}
.ns-res .chips button[disabled]{opacity:.4;cursor:not-allowed}
.ns-it{display:flex;gap:.7rem;align-items:center;border:1.5px solid #e3ebe6;border-radius:16px;padding:.6rem .8rem;margin-bottom:.5rem}
.ns-it .t{flex:1;min-width:0}.ns-it .t b{display:block}.ns-it .t small{color:#6b7570;font-weight:600}
.ns-it input{width:70px}
.ns-it .x{border:0;background:#ffe9f3;color:#d61f69;border-radius:10px;width:34px;height:34px;font-weight:900;cursor:pointer}
.ns-tot{background:#0b0d0c;color:#fff;border-radius:20px;padding:1rem 1.1rem;margin:1rem 0}
.ns-tot .l{display:flex;justify-content:space-between;color:#c9d3cc;font-weight:700}
.ns-tot .g{display:flex;justify-content:space-between;font-size:1.5rem;font-weight:900;margin-top:.3rem}
.ns-tot .g span:last-child{color:#2fd15a}
.ns-falta{display:flex;justify-content:space-between;background:#fff5d6;color:#8a6400;border-radius:14px;padding:.7rem .9rem;font-weight:800;margin-top:.6rem}
.ns-cli-res{display:none;border:1.5px solid #e3ebe6;border-radius:14px;margin-top:.4rem;overflow:hidden}
.ns-cli-res button{display:block;width:100%;text-align:left;border:0;background:#fff;padding:.55rem .8rem;border-bottom:1px solid #eef3ef;font-weight:700}
.ns-cli-res button small{display:block;color:#6b7570;font-weight:600}
.ns-cli-res button:hover{background:#f4fcf6}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-box-archive me-1"></i>Separados</small><h1>Nuevo <span>separado</span></h1>
            <span class="text-muted fw-semibold">La prenda sale del inventario disponible y queda guardada para el cliente.</span></div>
        <div class="lp-quick"><a href="{{ route('separados.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Separados</a></div>
    </div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('separados.store') }}" id="fSep" class="ns">
        @csrf
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-shirt me-1"></i> 1. ¿Qué prenda separa?</span></h3>
            <input id="qProd" class="form-control" placeholder="Buscar producto por nombre, código o lector de barras…" autocomplete="off">
            <div class="ns-res" id="resProd"></div>
            <div id="items" class="mt-3"></div>
            <div id="sinItems" class="text-muted small">Aún no ha agregado prendas.</div>
        </div>

        <div class="lp-box">
            <h3><span><i class="fa-solid fa-user me-1"></i> 2. Cliente</span></h3>
            <input type="hidden" name="cliente_id" id="cliId">
            <div class="mb-2"><label class="form-label">Nombre *</label><input name="cliente_nombre" id="cliNom" class="form-control" maxlength="80" required autocomplete="off" placeholder="Escriba para buscar o crear" value="{{ old('cliente_nombre') }}"></div>
            <div class="ns-cli-res" id="cliRes"></div>
            <div class="mb-3"><label class="form-label">WhatsApp</label><input name="cliente_telefono" id="cliTel" class="form-control" maxlength="20" inputmode="tel" placeholder="Para enviarle sus recibos" value="{{ old('cliente_telefono') }}"></div>

            <h3><span><i class="fa-solid fa-coins me-1"></i> 3. Abono</span></h3>
            <div class="row g-2">
                <div class="col-6"><label class="form-label">Descuento</label><input name="descuento" id="desc" data-dinero type="number" min="0" class="form-control" placeholder="0" value="{{ old('descuento') }}"></div>
                <div class="col-6"><label class="form-label">Plazo para pagar</label>
                    <select name="vence_dias" class="form-select" data-nativo>
                        @foreach ([15 => '15 días', 30 => '30 días', 45 => '45 días', 60 => '60 días', 90 => '90 días', 0 => 'Sin fecha'] as $k => $v)<option value="{{ $k }}" @selected($k === 30)>{{ $v }}</option>@endforeach
                    </select></div>
            </div>
            <div class="ns-tot">
                <div class="l"><span>Subtotal</span><span id="tSub">$0</span></div>
                <div class="g"><span>Total</span><span id="tTot">$0</span></div>
            </div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label">¿Cuánto abona hoy? *</label><input name="abono" id="abono" data-dinero type="number" min="0" class="form-control form-control-lg fw-bold" required value="{{ old('abono') }}" placeholder="0"></div>
                <div class="col-6"><label class="form-label">¿Cómo pagó?</label>
                    <select name="metodo_id" class="form-select form-select-lg" data-nativo>@foreach ($metodos as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach</select></div>
            </div>
            <div class="ns-falta"><span>Le queda debiendo</span><span id="tFalta">$0</span></div>
            <div class="mt-3"><label class="form-label">Nota (opcional)</label><input name="nota" class="form-control" maxlength="255" placeholder="Ej. la recoge el sábado, viene la hermana…" value="{{ old('nota') }}"></div>
            <button class="btn btn-primary btn-lg w-100 mt-3" id="btnGuardar"><i class="fa-solid fa-box-archive me-1"></i>Separar y registrar abono</button>
        </div>
    </form>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var URL_BUSCAR = @json(route('pos.buscar')), URL_CLIENTES = @json(route('pos.clientes'));
    var items = [], tb, tc;
    var dinero = function (n) { return LP.formatoDinero ? '$' + LP.formatoDinero(Math.round(n)) : '$' + Math.round(n).toLocaleString('es-CO'); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var num = function (el) { return parseFloat(String(el.value || '0').replace(/[^0-9.]/g, '')) || 0; };

    $('qProd').addEventListener('input', function () {
        clearTimeout(tb); var v = this.value.trim();
        if (v.length < 2) { $('resProd').style.display = 'none'; return; }
        tb = setTimeout(function () {
            fetch(URL_BUSCAR + '?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                var l = d.productos || [];
                $('resProd').innerHTML = l.length ? l.slice(0, 15).map(function (p, pi) {
                    return '<div class="p"><b>' + esc(p.nombre) + '</b><div class="chips">' + p.variantes.map(function (va, vi) {
                        var sin = p.controla && va.stock <= 0;
                        return '<button type="button" data-p="' + pi + '" data-v="' + vi + '"' + (sin ? ' disabled' : '') + '>' + esc(va.etiqueta && va.etiqueta !== 'Única' ? va.etiqueta : 'Agregar') + ' · ' + dinero(va.precio) + (p.controla ? ' · ' + va.stock + ' disp.' : '') + '</button>';
                    }).join('') + '</div></div>';
                }).join('') : '<div class="p text-muted">No encontré ese producto.</div>';
                $('resProd')._l = l; $('resProd').style.display = 'block';
            });
        }, 250);
    });
    $('resProd').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-p]'); if (!b) return;
        var p = $('resProd')._l[+b.dataset.p], va = p.variantes[+b.dataset.v];
        var ya = items.find(function (i) { return i.variante_id === va.id; });
        if (ya) ya.cantidad++; else items.push({ variante_id: va.id, nombre: p.nombre, etiqueta: va.etiqueta !== 'Única' ? va.etiqueta : '', precio: +va.precio, cantidad: 1, max: p.controla ? va.stock : null });
        $('qProd').value = ''; $('resProd').style.display = 'none'; pintar(); $('qProd').focus();
    });
    function pintar() {
        $('items').innerHTML = items.map(function (i, k) {
            return '<div class="ns-it"><div class="t"><b>' + esc(i.nombre) + '</b><small>' + esc(i.etiqueta) + (i.etiqueta ? ' · ' : '') + dinero(i.precio) + ' c/u</small></div>'
                + '<input type="number" min="1" ' + (i.max ? 'max="' + i.max + '"' : '') + ' class="form-control" value="' + i.cantidad + '" data-k="' + k + '">'
                + '<b style="min-width:90px;text-align:right">' + dinero(i.precio * i.cantidad) + '</b><button type="button" class="x" data-x="' + k + '">×</button>'
                + '<input type="hidden" name="items[' + k + '][variante_id]" value="' + i.variante_id + '"><input type="hidden" name="items[' + k + '][cantidad]" value="' + i.cantidad + '"></div>';
        }).join('');
        $('sinItems').style.display = items.length ? 'none' : '';
        totales();
    }
    $('items').addEventListener('input', function (e) { var k = e.target.dataset.k; if (k === undefined) return; items[k].cantidad = Math.max(1, parseInt(e.target.value) || 1); pintar(); });
    $('items').addEventListener('click', function (e) { var k = e.target.dataset.x; if (k === undefined) return; items.splice(+k, 1); pintar(); });
    function totales() {
        var sub = items.reduce(function (a, i) { return a + i.precio * i.cantidad; }, 0);
        var tot = Math.max(0, sub - num($('desc')));
        $('tSub').textContent = dinero(sub); $('tTot').textContent = dinero(tot);
        $('tFalta').textContent = dinero(Math.max(0, tot - num($('abono'))));
        return tot;
    }
    ['desc', 'abono'].forEach(function (id) { $(id).addEventListener('input', totales); $(id).addEventListener('change', totales); });

    // Cliente: buscar entre los registrados
    $('cliNom').addEventListener('input', function () {
        $('cliId').value = ''; clearTimeout(tc); var v = this.value.trim();
        if (v.length < 2) { $('cliRes').style.display = 'none'; return; }
        tc = setTimeout(function () {
            fetch(URL_CLIENTES + '?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
                $('cliRes').innerHTML = l.map(function (c, i) { return '<button type="button" data-i="' + i + '">' + esc(c.nombre) + '<small>' + esc([c.documento ? 'CC ' + c.documento : '', c.telefono || ''].filter(Boolean).join(' · ') || 'Cliente registrado') + '</small></button>'; }).join('');
                $('cliRes')._l = l; $('cliRes').style.display = l.length ? 'block' : 'none';
            });
        }, 250);
    });
    $('cliRes').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]'); if (!b) return;
        var c = $('cliRes')._l[+b.dataset.i];
        $('cliId').value = c.id; $('cliNom').value = c.nombre; if (c.telefono) $('cliTel').value = c.telefono;
        $('cliRes').style.display = 'none';
    });

    $('fSep').addEventListener('submit', function (e) {
        if (!items.length) { e.preventDefault(); LP.toast('Agregue al menos una prenda', 'error'); $('qProd').focus(); return; }
        var tot = totales();
        if (num($('abono')) > tot) { e.preventDefault(); LP.toast('El abono no puede ser mayor que el total', 'error'); return; }
        $('btnGuardar').disabled = true; $('btnGuardar').textContent = 'Guardando…';
    });
})();
</script>
@endsection
