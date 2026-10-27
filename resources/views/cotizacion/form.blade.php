@extends('layouts.app')

@php
    $nuevo = !$c->exists;
    $filas = old('items', $items);
    $m = moneda();
    $tiendaLogo = \App\Support\TiendaActual::tienda()?->logo_url;
    $copiaLogo = $copiaLogo ?? null;
    $logoActual = $c->logo_url ?? $copiaLogo?->logo_url;
    $logoModo = old('logo_modo', $logoActual ? 'otro' : 'negocio');
@endphp
@section('title', $nuevo ? 'Nueva cotización' : 'Editar ' . $c->numero)

@push('css')
<style>
.ct-sec{font-size:.8rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#6b7570;margin:1.2rem 0 .6rem}
.ct-sec:first-child{margin-top:0}
.ct-busca{position:relative}
.ct-res{position:absolute;left:0;right:0;top:100%;z-index:30;background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;box-shadow:0 20px 40px -20px rgba(0,0,0,.35);max-height:320px;overflow:auto;display:none;margin-top:4px}
.ct-res button{display:flex;gap:.7rem;align-items:center;width:100%;border:0;background:#fff;padding:.55rem .8rem;text-align:left;border-bottom:1px solid #f1f5f2}
.ct-res button:hover,.ct-res button:focus{background:#f4fcf6}
.ct-res img,.ct-res .ph{width:38px;height:38px;border-radius:10px;object-fit:cover;flex:none;background:#f1f5f2;display:grid;place-items:center;color:#9aa5a0}
.ct-res .tx{flex:1;min-width:0}.ct-res .tx b{display:block;font-size:.92rem}.ct-res .tx small{color:#6b7570}
.ct-res .pr{font-weight:900;white-space:nowrap}
.ct-items{width:100%;border-collapse:separate;border-spacing:0 6px}
.ct-items th{font-size:.72rem;text-transform:uppercase;color:#6b7570;font-weight:800;padding:0 .35rem}
.ct-items td{padding:.2rem .35rem;vertical-align:top;background:#fafdfb}
.ct-items td:first-child{border-radius:12px 0 0 12px}.ct-items td:last-child{border-radius:0 12px 12px 0}
.ct-items .sub{font-weight:900;white-space:nowrap;padding-top:.6rem}
.ct-tag{font-size:.7rem;font-weight:800;border-radius:999px;padding:.12rem .5rem;display:inline-block;margin-top:.25rem}
.ct-tag.ok{background:#e3f9e8;color:#11702f}.ct-tag.poco{background:#fde8e8;color:#9b1c1c}.ct-tag.info{background:#eef1ef;color:#45504a}.ct-tag.cambio{background:#fff5d6;color:#8a6400}
.ct-tot{max-width:340px;margin-left:auto}
.ct-tot div{display:flex;justify-content:space-between;padding:.25rem 0;font-weight:700}
.ct-tot .gran{font-size:1.35rem;font-weight:900;border-top:2px solid #0b0d0c;margin-top:.3rem;padding-top:.5rem}
.ct-logo{display:flex;gap:.8rem;flex-wrap:wrap}
.ct-logo label.op{flex:1;min-width:220px;border:1.5px solid #e3ebe6;border-radius:16px;padding:.7rem .9rem;cursor:pointer;display:flex;gap:.7rem;align-items:center;background:#fff}
.ct-logo label.op:has(input:checked){border-color:#16b34a;background:#f4fcf6}
.ct-logo .prev{width:110px;height:60px;border-radius:10px;background:#f1f5f2;display:grid;place-items:center;flex:none;overflow:hidden;color:#9aa5a0;font-size:.75rem;text-align:center}
.ct-logo .prev img{max-width:100%;max-height:100%;object-fit:contain}
.ct-dispbox{border:1.5px dashed #c9efd2;background:#f4fcf6;border-radius:16px;padding:.7rem .9rem;font-size:.88rem;display:none}
@media(max-width:700px){
  .ct-items thead{display:none}
  .ct-items tr{display:grid;grid-template-columns:1fr 1fr auto;gap:4px;background:#fafdfb;border-radius:14px;padding:6px;margin-bottom:8px}
  .ct-items td{background:none;padding:0 .2rem}
  .ct-items td.d{grid-column:1 / -1}
}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-signature me-1"></i>Cotizaciones</small>
            <h1>@if ($nuevo)Nueva <span>cotización</span>@else Editar <span>{{ $c->numero }}</span>@endif</h1>
            <span class="text-muted fw-semibold">Busque sus productos (el precio sale solo y lo puede cambiar) o agregue líneas libres.</span></div>
        <div class="lp-quick"><a href="{{ $nuevo ? route('cotizaciones.index') : route('cotizaciones.show', $c) }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ $nuevo ? route('cotizaciones.store') : route('cotizaciones.update', $c) }}" class="lp-box lp-form-wide" id="ctForm" enctype="multipart/form-data" novalidate>
        @csrf
        @unless ($nuevo) @method('PUT') @endunless

        <div class="ct-sec"><i class="fa-solid fa-user me-1"></i>Para quién es</div>
        <input type="hidden" name="cliente_id" id="cliId" value="{{ old('cliente_id', $c->cliente_id) }}">
        <div class="row g-2">
            <div class="col-md-6 ct-busca">
                <label class="form-label fw-bold">Nombre del cliente o empresa</label>
                <input name="cliente_nombre" id="cliNom" class="form-control form-control-lg" maxlength="120" required autocomplete="off" placeholder="Escriba el nombre (si ya es cliente, le salen sus datos)" value="{{ old('cliente_nombre', $c->cliente_nombre) }}">
                <div class="ct-res" id="cliRes"></div>
            </div>
            <div class="col-md-3"><label class="form-label fw-bold">Cédula / NIT</label><input name="cliente_documento" id="cliDoc" class="form-control form-control-lg" maxlength="30" value="{{ old('cliente_documento', $c->cliente_documento) }}" data-libre></div>
            <div class="col-md-3"><label class="form-label fw-bold">Celular / WhatsApp</label><input name="cliente_telefono" id="cliTel" type="tel" class="form-control form-control-lg" maxlength="30" value="{{ old('cliente_telefono', $c->cliente_telefono) }}" data-libre></div>
            <div class="col-md-6"><label class="form-label fw-bold">Correo <span class="text-muted fw-normal">(opcional)</span></label><input name="cliente_email" id="cliMail" type="email" class="form-control" maxlength="120" value="{{ old('cliente_email', $c->cliente_email) }}"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Dirección <span class="text-muted fw-normal">(opcional)</span></label><input name="cliente_direccion" id="cliDir" class="form-control" maxlength="150" value="{{ old('cliente_direccion', $c->cliente_direccion) }}"></div>
        </div>

        <div class="ct-sec"><i class="fa-solid fa-calendar-days me-1"></i>Datos de la cotización</div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label fw-bold">Asunto <span class="text-muted fw-normal">(opcional)</span></label><input name="asunto" class="form-control" maxlength="150" placeholder="Ej: Sonido y luces para matrimonio" value="{{ old('asunto', $c->asunto) }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Fecha</label><input type="date" name="fecha" class="form-control" required value="{{ old('fecha', optional($c->fecha)->format('Y-m-d')) }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Válida hasta</label><input type="date" name="vence" class="form-control" value="{{ old('vence', optional($c->vence)->format('Y-m-d')) }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Fecha del alquiler / evento</label><input type="date" name="evento_fecha" id="evDesde" class="form-control" value="{{ old('evento_fecha', optional($c->evento_fecha)->format('Y-m-d')) }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Hasta <span class="text-muted fw-normal">(si es de varios días)</span></label><input type="date" name="evento_hasta" id="evHasta" class="form-control" value="{{ old('evento_hasta', optional($c->evento_hasta)->format('Y-m-d')) }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Hora <span class="text-muted fw-normal">(opcional)</span></label><input type="time" name="evento_hora" class="form-control" value="{{ old('evento_hora', $c->evento_hora ? substr($c->evento_hora, 0, 5) : '') }}"></div>
            <div class="col-6 col-md-3"><label class="form-label fw-bold">Hasta la hora <span class="text-muted fw-normal">(opcional)</span></label><input type="time" name="evento_hora_fin" class="form-control" value="{{ old('evento_hora_fin', $c->evento_hora_fin ? substr($c->evento_hora_fin, 0, 5) : '') }}"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Lugar <span class="text-muted fw-normal">(opcional)</span></label><input name="evento_lugar" class="form-control" maxlength="150" placeholder="Dónde se entrega o se presta el servicio" value="{{ old('evento_lugar', $c->evento_lugar) }}"></div>
        </div>
        <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i>Con la fecha del alquiler le mostramos cuántas unidades tiene libres de cada producto (lo de cotizaciones aceptadas para esas fechas cuenta como ocupado).</small>

        <div class="ct-sec"><i class="fa-solid fa-list me-1"></i>Qué se cotiza</div>
        <div class="d-flex gap-2 flex-wrap mb-2">
            <div class="ct-busca flex-grow-1" style="min-width:240px">
                <input id="prodQ" class="form-control form-control-lg" placeholder="🔍 Buscar producto por nombre o código…" autocomplete="off" data-libre>
                <div class="ct-res" id="prodRes"></div>
            </div>
            <button type="button" class="btn btn-light btn-lg" id="addLibre"><i class="fa-solid fa-pen me-1"></i>Línea libre</button>
        </div>
        <div class="ct-dispbox mb-2" id="dispBox"></div>
        <table class="ct-items">
            <thead><tr><th>Descripción</th><th style="width:110px">Cant.</th><th style="width:160px">Precio unidad</th><th style="width:130px;text-align:right">Subtotal</th><th style="width:44px"></th></tr></thead>
            <tbody id="filas"></tbody>
        </table>
        <p class="text-muted text-center my-3" id="sinItems"><i class="fa-solid fa-arrow-up me-1"></i>Busque un producto o agregue una línea libre.</p>

        <div class="ct-tot mt-3">
            <div><span>Subtotal</span><span id="tSub">0</span></div>
            <div class="align-items-center"><span>Descuento</span><input type="number" name="descuento" id="desc" min="0" step="any" class="form-control form-control-sm text-end" style="max-width:150px" value="{{ old('descuento', $c->descuento ?: '') }}" placeholder="0"></div>
            <div class="gran"><span>Total</span><span id="tTot">0</span></div>
        </div>

        <div class="ct-sec"><i class="fa-solid fa-image me-1"></i>Logo de la cotización</div>
        @if ($copiaLogo && !$c->logo_path)<input type="hidden" name="logo_copiar" value="{{ $copiaLogo->id }}">@endif
        <div class="ct-logo">
            <label class="op">
                <input type="radio" name="logo_modo" value="negocio" class="form-check-input" @checked($logoModo === 'negocio')>
                <span class="prev">@if ($tiendaLogo)<img src="{{ $tiendaLogo }}" alt="">@else Sin logo @endif</span>
                <span><b class="d-block">El de mi negocio</b><small class="text-muted">@if ($tiendaLogo) El que tiene en Mi negocio. @else Aún no tiene logo: súbalo en Mi negocio. @endif</small></span>
            </label>
            <label class="op">
                <input type="radio" name="logo_modo" value="otro" class="form-check-input" id="logoOtro" @checked($logoModo === 'otro')>
                <span class="prev" id="logoPrev">@if ($logoActual)<img src="{{ $logoActual }}" alt="">@else Otro logo @endif</span>
                <span class="flex-grow-1"><b class="d-block">Otro logo solo para esta cotización</b><small class="text-muted d-block mb-1">Por ejemplo, si le hace la cotización a otro negocio. PNG, JPG o WEBP, hasta 2 MB.</small>
                    <input type="file" name="logo" id="logoFile" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm"></span>
            </label>
        </div>

        <div class="ct-sec"><i class="fa-solid fa-file-lines me-1"></i>Condiciones y notas</div>
        <textarea name="condiciones" class="form-control" rows="4" maxlength="2000" placeholder="Forma de pago, qué incluye, tiempos de entrega…">{{ old('condiciones', $c->condiciones) }}</textarea>
        <small class="text-muted">Salen al final del PDF. La próxima cotización arranca con estas mismas.</small>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ $nuevo ? route('cotizaciones.index') : route('cotizaciones.show', $c) }}" class="btn btn-light btn-lg">Cancelar</a>
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $nuevo ? 'Crear cotización' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>

<template id="tplFila">
    <tr>
        <td class="d">
            <input type="hidden" data-k="producto_id"><input type="hidden" data-k="precio_lista">
            <input class="form-control" data-k="descripcion" maxlength="200" required placeholder="Descripción">
            <div data-info></div>
        </td>
        <td><input type="number" class="form-control" data-k="cantidad" min="0.001" step="any" required></td>
        <td><input type="number" class="form-control" data-k="precio" min="0" step="any" required></td>
        <td class="sub text-end" data-sub>0</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger mt-1" data-quitar title="Quitar"><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>
@endsection

@push('js')
<script>
(function () {
    var M = @json($m), excluir = {{ $c->exists ? $c->id : 'null' }};
    var URL_P = @json(route('cotizaciones.productos')), URL_C = @json(route('cotizaciones.clientes')), URL_D = @json(route('cotizaciones.disponibilidad'));
    var filas = document.getElementById('filas'), tpl = document.getElementById('tplFila'), disp = {};
    function fmt(v) {
        var n = Number(v || 0).toFixed(M.decimales), p = n.split('.');
        return M.simbolo + p[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles) + (p[1] ? M.decimal + p[1] : '');
    }
    function cant(v) { return String(Math.round(Number(v) * 1000) / 1000).replace('.', ','); }
    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }

    function renombrar() {
        filas.querySelectorAll('tr').forEach(function (tr, i) {
            tr.querySelectorAll('[data-k]').forEach(function (inp) { inp.name = 'items[' + i + '][' + inp.dataset.k + ']'; });
        });
        document.getElementById('sinItems').style.display = filas.children.length ? 'none' : '';
    }
    function info(tr) {
        var pid = tr.querySelector('[data-k=producto_id]').value, box = tr.querySelector('[data-info]'), h = '';
        var lista = Number(tr.querySelector('[data-k=precio_lista]').value || 0), precio = Number(tr.querySelector('[data-k=precio]').value || 0);
        if (pid && tr.querySelector('[data-k=precio_lista]').value !== '' && Math.abs(lista - precio) > 0.004) h += '<span class="ct-tag cambio me-1">Precio cambiado (lista ' + fmt(lista) + ')</span>';
        var d = pid ? disp[pid] : null;
        if (d) {
            var pide = Number(tr.querySelector('[data-k=cantidad]').value || 0);
            if (d.libre === null) h += '<span class="ct-tag info">Sin control de cantidad</span>';
            else if (pide > d.libre) h += '<span class="ct-tag poco"><i class="fa-solid fa-triangle-exclamation me-1"></i>Solo quedan ' + cant(d.libre) + ' de ' + cant(d.tiene) + (d.ocupado ? ' (' + cant(d.ocupado) + ' ocupados)' : '') + '</span>';
            else h += '<span class="ct-tag ok"><i class="fa-solid fa-check me-1"></i>Disponibles ' + cant(d.libre) + ' de ' + cant(d.tiene) + (d.ocupado ? ' · ' + cant(d.ocupado) + ' ocupados' : '') + '</span>';
            if (d.cotizaciones && d.cotizaciones.length) h += '<small class="d-block text-muted mt-1">Ocupado por: ' + d.cotizaciones.map(function (x) { return esc(x.numero + ' ' + x.cliente + ' (' + cant(x.cantidad) + ', ' + x.fechas + ')'); }).join(' · ') + '</small>';
        }
        box.innerHTML = h;
    }
    function totales() {
        var s = 0;
        filas.querySelectorAll('tr').forEach(function (tr) {
            var v = Number(tr.querySelector('[data-k=cantidad]').value || 0) * Number(tr.querySelector('[data-k=precio]').value || 0);
            tr.querySelector('[data-sub]').textContent = fmt(v); s += v; info(tr);
        });
        var d = Number(document.getElementById('desc').value || 0);
        document.getElementById('tSub').textContent = fmt(s);
        document.getElementById('tTot').textContent = fmt(Math.max(s - d, 0));
    }
    function agregar(f) {
        if (f.producto_id) {
            var ya = Array.from(filas.querySelectorAll('tr')).find(function (tr) { return tr.querySelector('[data-k=producto_id]').value == f.producto_id; });
            if (ya) { var q = ya.querySelector('[data-k=cantidad]'); q.value = Number(q.value || 0) + 1; totales(); q.focus(); return; }
        }
        var tr = tpl.content.firstElementChild.cloneNode(true);
        ['producto_id', 'precio_lista', 'descripcion', 'cantidad', 'precio'].forEach(function (k) { tr.querySelector('[data-k=' + k + ']').value = f[k] == null ? '' : f[k]; });
        if (f.producto_id) tr.querySelector('[data-k=descripcion]').readOnly = false;
        filas.appendChild(tr); renombrar(); totales(); revisar();
        (f.descripcion ? tr.querySelector('[data-k=cantidad]') : tr.querySelector('[data-k=descripcion]')).focus();
    }
    filas.addEventListener('input', totales);
    filas.addEventListener('click', function (e) { var b = e.target.closest('[data-quitar]'); if (b) { b.closest('tr').remove(); renombrar(); totales(); } });
    document.getElementById('desc').addEventListener('input', totales);
    document.getElementById('addLibre').addEventListener('click', function () { agregar({ cantidad: 1, precio: '' }); });
    @json(array_values($filas)).forEach(function (f) { agregar(f); });

    // Buscador con lista desplegable
    function buscador(input, res, url, pintar, elegir) {
        var t, items = [];
        input.addEventListener('input', function () {
            clearTimeout(t);
            var q = input.value.trim();
            if (input.id === 'cliNom') document.getElementById('cliId').value = '';
            if (q.length < (input.id === 'cliNom' ? 2 : 1)) { res.style.display = 'none'; return; }
            t = setTimeout(function () {
                fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
                    items = l; res.innerHTML = l.map(function (x, i) { return '<button type="button" data-i="' + i + '">' + pintar(x) + '</button>'; }).join('');
                    if (!l.length && input.id === 'prodQ') res.innerHTML = '<div class="p-3 text-muted small">No hay productos con ese nombre. Use <b>Línea libre</b>.</div>';
                    res.style.display = res.innerHTML ? 'block' : 'none';
                });
            }, 250);
        });
        res.addEventListener('click', function (e) { var b = e.target.closest('button[data-i]'); if (b) { elegir(items[b.dataset.i]); res.style.display = 'none'; } });
        document.addEventListener('click', function (e) { if (!res.contains(e.target) && e.target !== input) res.style.display = 'none'; });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var b = res.querySelector('button[data-i]'); if (b) b.click(); } });
    }
    buscador(document.getElementById('prodQ'), document.getElementById('prodRes'), URL_P, function (p) {
        return (p.img ? '<img src="' + esc(p.img) + '" alt="">' : '<span class="ph"><i class="fa-solid fa-box"></i></span>')
            + '<span class="tx"><b>' + esc(p.nombre) + '</b><small>' + (p.codigo ? esc(p.codigo) + ' · ' : '') + (p.unidades === null ? 'sin control de cantidad' : 'tiene ' + cant(p.unidades)) + '</small></span><span class="pr">' + fmt(p.precio) + '</span>';
    }, function (p) {
        agregar({ producto_id: p.id, descripcion: p.nombre, cantidad: 1, precio: p.precio, precio_lista: p.precio });
        var q = document.getElementById('prodQ'); q.value = '';
    });
    buscador(document.getElementById('cliNom'), document.getElementById('cliRes'), URL_C, function (c) {
        return '<span class="ph"><i class="fa-solid fa-user"></i></span><span class="tx"><b>' + esc(c.nombre) + '</b><small>' + esc([c.documento, c.telefono].filter(Boolean).join(' · ')) + '</small></span>';
    }, function (c) {
        document.getElementById('cliId').value = c.id; document.getElementById('cliNom').value = c.nombre || '';
        [['cliDoc', 'documento'], ['cliTel', 'telefono'], ['cliMail', 'email'], ['cliDir', 'direccion']].forEach(function (x) { if (c[x[1]]) document.getElementById(x[0]).value = c[x[1]]; });
    });

    // Disponibilidad para las fechas del alquiler
    var tD;
    function revisar() {
        clearTimeout(tD);
        tD = setTimeout(function () {
            var desde = document.getElementById('evDesde').value, hasta = document.getElementById('evHasta').value, box = document.getElementById('dispBox');
            var ids = Array.from(filas.querySelectorAll('[data-k=producto_id]')).map(function (i) { return i.value; }).filter(Boolean);
            if (!desde) { disp = {}; box.style.display = 'none'; totales(); return; }
            var qs = 'desde=' + desde + (hasta ? '&hasta=' + hasta : '') + (excluir ? '&excluir=' + excluir : '') + ids.map(function (i) { return '&productos[]=' + i; }).join('');
            fetch(URL_D + '?' + qs, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
                disp = d.productos || {}; totales();
                var a = d.alquileres || [];
                box.innerHTML = a.length ? '<b><i class="fa-solid fa-calendar-days me-1"></i>Alquileres registrados para esas fechas:</b><ul class="mb-0 mt-1 ps-3">' + a.map(function (x) { return '<li>' + esc(x.numero + ' · ' + x.fecha + ' · ' + x.que + (x.lugar ? ' (' + x.lugar + ')' : '')) + (x.alquilado ? ' <span class="ct-tag info">en manos del cliente</span>' : '') + '</li>'; }).join('') + '</ul>'
                    : '<i class="fa-solid fa-circle-check me-1" style="color:#16b34a"></i>No hay alquileres registrados para esas fechas.';
                box.style.display = '';
            });
        }, 300);
    }
    // Logo: al escoger un archivo se marca "Otro logo" y se ve antes de guardar
    var lf = document.getElementById('logoFile');
    lf.addEventListener('change', function () {
        if (!lf.files[0]) return;
        if (lf.files[0].size > 2 * 1024 * 1024) { alert('El logo no puede pesar más de 2 MB.'); lf.value = ''; return; }
        document.getElementById('logoOtro').checked = true;
        var r = new FileReader();
        r.onload = function (e) { var pv = document.getElementById('logoPrev'); pv.innerHTML = ''; var im = new Image(); im.src = e.target.result; pv.appendChild(im); };
        r.readAsDataURL(lf.files[0]);
    });
    ['evDesde', 'evHasta'].forEach(function (id) { document.getElementById(id).addEventListener('change', revisar); });
    revisar();
})();
</script>
@endpush
