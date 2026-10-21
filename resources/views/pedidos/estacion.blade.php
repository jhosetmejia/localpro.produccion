@extends('layouts.app')

@section('title', $sel ? $sel->nombre : 'Estaciones')

@push('css')
<style>
.ks-bar{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:.8rem}
.ks-bar a,.ks-bar button{border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.86rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.ks-bar a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.ks-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.75rem;align-items:start}
.ks-card{background:#fff;border:2px solid #e3ebe6;border-radius:20px;overflow:hidden}
.ks-card .h{padding:.6rem .85rem;background:#0b0d0c;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:.5rem}
.ks-card .h b{font-size:1.15rem}.ks-card .h small{color:#b8c4bc;font-weight:700}
.ks-card .t{font-weight:900;border-radius:999px;padding:.15rem .6rem;background:#dcfce7;color:#11702f;font-size:.85rem;white-space:nowrap}
.ks-card.amarillo{border-color:#f59e0b}.ks-card.amarillo .t{background:#fef3c7;color:#b45309}
.ks-card.rojo{border-color:#dc2626}.ks-card.rojo .t{background:#fee2e2;color:#b91c1c;animation:pulso 1s infinite}
@keyframes pulso{50%{opacity:.55}}
.ks-card.hecho{opacity:.55}
.ks-card .nota{background:#fff7ed;color:#9a3412;font-weight:700;font-size:.85rem;padding:.4rem .85rem}
.ks-it{display:flex;gap:.6rem;align-items:flex-start;padding:.55rem .85rem;border-top:1px solid #eef1ef;cursor:pointer;user-select:none}
.ks-it .c{font-weight:900;font-size:1.2rem;min-width:34px}
.ks-it .n{flex:1;font-weight:800;font-size:1.02rem;line-height:1.2}
.ks-it .n small{display:block;color:#6b7570;font-weight:700}
.ks-it .n em{display:block;color:#dc2626;font-style:normal;font-weight:800;font-size:.88rem}
.ks-it .ck{width:30px;height:30px;border-radius:10px;border:2px solid #cfd8d2;display:grid;place-items:center;color:transparent;flex:none}
.ks-it.listo .ck{background:#16b34a;border-color:#16b34a;color:#fff}
.ks-it.listo .n{text-decoration:line-through;color:#9aa39c}
.ks-card .f{display:flex;gap:.4rem;padding:.6rem .85rem;border-top:1px solid #eef1ef}
.ks-card .f button,.ks-card .f a{flex:1;border:0;border-radius:12px;padding:.6rem;font-weight:900;text-align:center;text-decoration:none}
.ks-ok{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
.ks-pr{background:#f1f5f2;color:#151a17;flex:0 0 52px!important}
.ks-vacio{background:#fff;border:1.5px dashed #cfd8d2;border-radius:20px;padding:2.5rem;text-align:center;color:#6b7570;font-weight:800;font-size:1.1rem}
body.ks-full .lp-hello,body.ks-full .pd-nav{display:none}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-fire-burner me-1"></i>Pedidos</small><h1>{!! $sel ? e($sel->nombre) : 'Pantalla de <span>estación</span>' !!}</h1>
            <span class="text-muted fw-semibold">Toque cada producto cuando esté listo (o <b>Todo listo</b>). Suena cuando llega un pedido nuevo. Se actualiza sola.</span></div>
    </div>
    @include('pedidos._nav')
    <div class="ks-bar">
        @if ($estaciones->count() > 1)
        <a href="{{ route('pedidos.estacion') }}" class="{{ $sel ? '' : 'on' }}">Todas</a>
        @foreach ($estaciones as $e)<a href="{{ route('pedidos.estacion', ['e' => $e->id]) }}" class="{{ $sel && $sel->id === $e->id ? 'on' : '' }}"><i class="fa-solid {{ $e->icono ?: 'fa-kitchen-set' }} me-1"></i>{{ $e->nombre }}</a>@endforeach
        @endif
        <span class="ms-auto"></span>
        <label class="form-check form-switch m-0 fw-bold small"><input class="form-check-input" type="checkbox" id="ksPrint"> Imprimir al llegar</label>
        <button type="button" id="ksFull"><i class="fa-solid fa-expand me-1"></i>Pantalla completa</button>
    </div>
    <div class="ks-grid" id="ksLista"><div class="ks-vacio">Cargando…</div></div>
</div>
@endsection

@push('js')
@include('pedidos._sonido')
<script>
(function () {
    var URL = @json(route('pedidos.estacion.datos', $sel ? ['e' => $sel->id] : [])), MARCAR = @json(route('pedidos.estacion.marcar'));
    var CSRF = document.querySelector('meta[name=csrf-token]').content, ultimo = null, datos = [], desfase = 0, EST = @json($sel?->id);
    var pr = document.getElementById('ksPrint'), clave = 'ksPrint' + (EST || '');
    try { pr.checked = localStorage.getItem(clave) === '1'; } catch (e) {}
    pr.addEventListener('change', function () { try { localStorage.setItem(clave, pr.checked ? '1' : '0'); } catch (e) {} });
    document.getElementById('ksFull').addEventListener('click', function () {
        document.body.classList.toggle('ks-full');
        var d = document.documentElement; if (!document.fullscreenElement && d.requestFullscreen) d.requestFullscreen().catch(function () {}); else if (document.exitFullscreen) document.exitFullscreen().catch(function () {});
    });
    function minutos(iso) { return Math.max(0, Math.floor((Date.now() - desfase - new Date(iso).getTime()) / 60000)); }
    function pintar() {
        var el = document.getElementById('ksLista');
        if (!datos.length) { el.innerHTML = '<div class="ks-vacio"><i class="fa-solid fa-mug-hot d-block mb-2" style="font-size:2rem"></i>Sin pedidos pendientes</div>'; return; }
        el.innerHTML = datos.map(function (p) {
            var m = minutos(p.desde), color = p.listo ? 'hecho' : (m >= 20 ? 'rojo' : (m >= 10 ? 'amarillo' : ''));
            return '<div class="ks-card ' + color + '"><div class="h"><div><b>#' + p.numero + ' ' + Pd.esc(p.cliente || '') + '</b><small class="d-block">' +
                (p.mesa ? 'Mesa ' + Pd.esc(p.mesa) + ' · ' : '') + Pd.esc(p.mesero || '') + '</small></div><span class="t">' + (p.listo ? 'Listo' : m + ' min') + '</span></div>' +
                (p.nota ? '<div class="nota"><i class="fa-solid fa-note-sticky me-1"></i>' + Pd.esc(p.nota) + '</div>' : '') +
                p.items.map(function (i) {
                    return '<div class="ks-it ' + i.e + '" data-id="' + i.id + '" data-e="' + i.e + '"><span class="c">' + Pd.cant(i.c) + '</span><div class="n">' + Pd.esc(i.n) +
                        (i.et ? '<small>' + Pd.esc(i.et) + '</small>' : '') + (!EST && i.est ? '<small>' + Pd.esc(i.est) + '</small>' : '') + (i.ronda > 1 ? '<small>Agregado después</small>' : '') +
                        (i.nota ? '<em>⚠ ' + Pd.esc(i.nota) + '</em>' : '') + '</div><span class="ck"><i class="fa-solid fa-check"></i></span></div>';
                }).join('') +
                '<div class="f">' + (p.listo ? '<button class="ks-pr" data-deshacer="' + p.id + '" title="Deshacer"><i class="fa-solid fa-rotate-left"></i></button>' : '<button class="ks-ok" data-todo="' + p.id + '"><i class="fa-solid fa-check-double me-1"></i>Todo listo</button>') +
                '<a class="ks-pr" href="' + p.comanda + (EST ? '?e=' + EST : '') + '" target="_blank" title="Imprimir"><i class="fa-solid fa-print"></i></a></div></div>';
        }).join('');
    }
    function marcar(ids, deshacer) {
        datos.forEach(function (p) { p.items.forEach(function (i) { if (ids.indexOf(i.id) >= 0) i.e = deshacer ? 'pendiente' : 'listo'; }); p.listo = p.items.every(function (i) { return i.e === 'listo'; }); });
        pintar();
        fetch(MARCAR, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ items: ids, deshacer: !!deshacer }) })
            .then(function (r) { if (!r.ok) throw 0; }).catch(function () { if (window.LP && LP.toast) LP.toast('No se pudo guardar. Revise el internet.', 'error'); }).finally(cargar);
    }
    document.getElementById('ksLista').addEventListener('click', function (e) {
        var it = e.target.closest('.ks-it'), todo = e.target.closest('[data-todo]'), des = e.target.closest('[data-deshacer]');
        if (it) marcar([+it.dataset.id], it.dataset.e === 'listo');
        else if (todo || des) {
            var p = datos.find(function (x) { return x.id === +(todo ? todo.dataset.todo : des.dataset.deshacer); });
            marcar(p.items.filter(function (i) { return todo ? i.e === 'pendiente' : i.e === 'listo'; }).map(function (i) { return i.id; }), !!des);
        }
    });
    function cargar() {
        fetch(URL, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            desfase = Date.now() - new Date(d.ahora).getTime();
            if (ultimo !== null && d.ultimo > ultimo) {
                Pd.sonar(2);
                if (pr.checked) d.pedidos.filter(function (p) { return p.items.some(function (i) { return i.id > ultimo && i.e === 'pendiente'; }); })
                    .forEach(function (p) { window.open(p.comanda + '?imprimir=1' + (EST ? '&e=' + EST : ''), '_blank', 'width=380,height=600'); });
            }
            ultimo = Math.max(ultimo || 0, d.ultimo); datos = d.pedidos; pintar();
        }).catch(function () {});
    }
    cargar(); setInterval(cargar, 5000); setInterval(pintar, 30000);
    // Que la pantalla no se apague
    if (navigator.wakeLock) { var pedir = function () { if (document.visibilityState === 'visible') navigator.wakeLock.request('screen').catch(function () {}); }; pedir(); document.addEventListener('visibilitychange', pedir); }
})();
</script>
@endpush
