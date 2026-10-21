@extends('layouts.app')

@section('title', 'Mis pedidos')

@push('css')
<style>
.ms-top{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin-bottom:.8rem}
.ms-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:.7rem}
.ms-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.9rem 1rem;display:flex;flex-direction:column;gap:.4rem;text-decoration:none;color:inherit}
.ms-card.listo{border-color:#16b34a;box-shadow:0 0 0 4px rgba(22,179,74,.18);animation:latir 1.4s ease-in-out infinite}
@keyframes latir{50%{box-shadow:0 0 0 9px rgba(22,179,74,.08)}}
.ms-card .h{display:flex;justify-content:space-between;gap:.5rem;align-items:flex-start}
.ms-card .h b{font-size:1.1rem}
.ms-card ul{margin:0;padding-left:1.1rem;color:#334039;font-weight:600;font-size:.9rem}
.ms-card li.listo{color:#11702f}.ms-card li.entregado{color:#9aa39c;text-decoration:line-through}
.ms-card .f{display:flex;justify-content:space-between;align-items:center;font-weight:800;margin-top:auto}
.ms-vacio{background:#fff;border:1.5px dashed #cfd8d2;border-radius:20px;padding:2rem;text-align:center;color:#6b7570;font-weight:700}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-bell-concierge me-1"></i>Pedidos</small><h1>Mis <span>pedidos</span></h1>
            <span class="text-muted fw-semibold">Tome el pedido, envíelo y espere el aviso: cuando esté listo le suena y vibra. Entregue y márquelo.</span></div>
        <div class="lp-quick"><a href="{{ route('pedidos.crear') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Tomar pedido</a></div>
    </div>
    @include('pedidos._nav')
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="ms-top">
        @if ($todos)<div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="msTodos"><label class="form-check-label fw-bold" for="msTodos">Ver los pedidos de todos los meseros</label></div>@endif
        <small class="text-muted ms-auto" id="msHora"></small>
    </div>
    <div class="ms-grid" id="msLista"><div class="ms-vacio">Cargando…</div></div>
</div>
@endsection

@push('js')
@include('pedidos._sonido')
<script>
(function () {
    try { var aviso = sessionStorage.getItem('lpToast'); if (aviso) { sessionStorage.removeItem('lpToast'); setTimeout(function () { if (window.LP && LP.toast) LP.toast(aviso, 'ok'); }, 300); } } catch (e) {}
    var URL = @json(route('pedidos.mesero.datos')), listos = null, todos = document.getElementById('msTodos');
    var EST = @json(collect(\App\Models\Pedido::ESTADOS)->map(fn ($e) => ['c' => $e[1], 'b' => $e[2]]));
    function pintar(lista) {
        var el = document.getElementById('msLista');
        if (!lista.length) { el.innerHTML = '<div class="ms-vacio"><i class="fa-solid fa-mug-saucer d-block mb-2" style="font-size:1.6rem"></i>No tiene pedidos abiertos. Toque <b>Tomar pedido</b>.</div>'; return; }
        el.innerHTML = lista.map(function (p) {
            var e = EST[p.estado] || { c: '#475569', b: '#eef1ef' };
            return '<a class="ms-card ' + (p.estado === 'listo' ? 'listo' : '') + '" href="' + p.url + '">' +
                '<div class="h"><div><b>#' + p.numero + ' ' + Pd.esc(p.cliente || '') + '</b>' + (p.mesa ? '<div class="text-muted fw-bold small">Mesa ' + Pd.esc(p.mesa) + '</div>' : '') +
                (p.mesero && todos && todos.checked ? '<div class="text-muted small">' + Pd.esc(p.mesero) + '</div>' : '') + '</div>' +
                '<span class="pd-est" style="color:' + e.c + ';background:' + e.b + '">' + Pd.esc(p.estado_txt) + '</span></div>' +
                '<ul>' + p.items.map(function (i) { return '<li class="' + i.e + '">' + Pd.cant(i.c) + ' × ' + Pd.esc(i.n) + '</li>'; }).join('') + '</ul>' +
                '<div class="f"><span>' + p.total_txt + (p.pagado ? ' <i class="fa-solid fa-circle-check text-success" title="Pagado"></i>' : '') + '</span>' +
                (p.minutos ? '<small class="text-muted">' + p.minutos + ' min</small>' : '') + '</div></a>';
        }).join('');
    }
    function cargar() {
        fetch(URL + (todos && todos.checked ? '?todos=1' : ''), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            var ahora = d.pedidos.filter(function (p) { return p.estado === 'listo'; }).map(function (p) { return p.id; });
            if (listos !== null && ahora.some(function (id) { return listos.indexOf(id) < 0; })) {
                Pd.sonar(3);
                if (window.LP && LP.toast) LP.toast('¡Hay un pedido listo para entregar!', 'ok');
            }
            listos = ahora;
            pintar(d.pedidos);
            document.getElementById('msHora').textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit' });
        }).catch(function () {});
    }
    if (todos) todos.addEventListener('change', function () { listos = null; cargar(); });
    cargar(); setInterval(cargar, 6000);
})();
</script>
@endpush
