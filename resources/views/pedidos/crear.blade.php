@extends('layouts.app')

@section('title', $pedido ? 'Agregar al pedido #' . $pedido->numero : 'Tomar pedido')

@push('css')
<style>
.tp{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:1rem;align-items:start}
@media (max-width:900px){.tp{grid-template-columns:1fr}.tp-cart{position:static!important}}
.tp-cats{display:flex;gap:.35rem;overflow-x:auto;padding-bottom:.4rem;margin-bottom:.6rem}
.tp-cats button{flex:none;border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.4rem .85rem;font-weight:800;font-size:.85rem;white-space:nowrap}
.tp-cats button.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.55rem}
.tp-p{border:1.5px solid #e3ebe6;background:#fff;border-radius:16px;padding:.55rem;text-align:left;display:flex;flex-direction:column;gap:.25rem;position:relative;min-height:96px}
.tp-p:active{transform:scale(.97)}
.tp-p img{width:100%;height:78px;object-fit:cover;border-radius:10px}
.tp-p b{font-size:.9rem;line-height:1.15}
.tp-p small{color:#6b7570;font-weight:700}
.tp-p .pr{font-weight:900;color:#11702f;margin-top:auto}
.tp-p .q{position:absolute;top:6px;right:6px;background:#16b34a;color:#fff;border-radius:999px;min-width:26px;height:26px;display:grid;place-items:center;font-weight:900;font-size:.8rem}
.tp-p.ag{opacity:.45}
.tp-cart{position:sticky;top:80px;background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;padding:1rem}
.tp-it{border-bottom:1px solid #eef1ef;padding:.5rem 0}
.tp-it .r{display:flex;align-items:center;gap:.5rem}
.tp-it .r b{flex:1;font-size:.92rem}
.tp-it .r button{width:32px;height:32px;border-radius:10px;border:1.5px solid #e3ebe6;background:#fff;font-weight:900}
.tp-it input{font-size:.82rem;padding:.25rem .5rem;margin-top:.3rem}
.tp-tot{display:flex;justify-content:space-between;font-weight:900;font-size:1.2rem;margin:.7rem 0}
.tp-send{width:100%;border:0;border-radius:16px;padding:.9rem;font-weight:900;font-size:1.05rem;background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
.tp-send:disabled{background:#eef1ef;color:#a3aca7}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-bell-concierge me-1"></i>Pedidos</small>
            <h1>{!! $pedido ? 'Agregar al pedido <span>#' . $pedido->numero . '</span>' : 'Tomar <span>pedido</span>' !!}</h1>
            <span class="text-muted fw-semibold">
                @if ($pedido){{ $pedido->titulo }} · lo nuevo va directo a preparar.
                @elseif ($tienda->pedidos_cobro === 'despues')El pedido va de una a preparar; al final el cliente pide la cuenta.
                @else El pedido pasa a caja con el nombre del cliente; cuando paga, va a preparar.@endif
            </span></div>
        <div class="lp-quick"><a href="{{ $pedido ? route('pedidos.show', $pedido) : route('pedidos.mesero') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="tp">
        <div>
            <input type="search" id="tpQ" class="form-control form-control-lg mb-2" placeholder="Buscar producto…">
            <div class="tp-cats" id="tpCats"></div>
            <div class="tp-grid" id="tpGrid"></div>
            @if (empty($catalogo))<div class="alert alert-warning mt-2">No hay productos activos. Créelos en Productos (platos, bebidas, entradas, paseos…).</div>@endif
        </div>
        <div class="tp-cart">
            @unless ($pedido)
            <div class="row g-2 mb-2">
                <div class="col-7"><label class="form-label small fw-bold mb-1">Cliente</label><input id="tpCli" class="form-control" maxlength="60" placeholder="Nombre" value="{{ $cliente }}"></div>
                <div class="col-5"><label class="form-label small fw-bold mb-1">Mesa</label><input id="tpMesa" class="form-control" maxlength="20" placeholder="Ej. 4" value="{{ $mesa }}"></div>
            </div>
            @endunless
            <div id="tpItems"><div class="text-muted text-center py-3 fw-semibold">Toque los productos para agregarlos.</div></div>
            @unless ($pedido)<input id="tpNota" class="form-control form-control-sm mt-2" maxlength="255" placeholder="Nota del pedido (opcional): para llevar, alergias…">@endunless
            <div class="tp-tot"><span>Total</span><span id="tpTotal">{{ dinero(0) }}</span></div>
            <div class="alert alert-danger py-2 small" id="tpErr" style="display:none"></div>
            <button class="tp-send" id="tpEnviar" disabled><i class="fa-solid fa-paper-plane me-1"></i>{{ $pedido ? 'Agregar al pedido' : ($tienda->pedidos_cobro === 'despues' ? 'Enviar a preparar' : 'Enviar a caja') }}</button>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('pedidos._sonido')
<script>
(function () {
    var CAT = @json($catalogo), PEDIDO = @json($pedido?->id), URL = @json(route('pedidos.store'));
    var M = @json(moneda()), CSRF = document.querySelector('meta[name=csrf-token]').content;
    var cart = [], cat = null, $ = function (id) { return document.getElementById(id); };
    function fmt(n) { var t = Math.abs(Number((n || 0).toFixed(M.decimales))).toFixed(M.decimales).split('.'); t[0] = t[0].replace(/\B(?=(\d{3})+(?!\d))/g, M.miles); return M.simbolo + t.join(M.decimal); }
    var cats = []; CAT.forEach(function (p) { if (cats.indexOf(p.cat) < 0) cats.push(p.cat); });
    $('tpCats').innerHTML = (cats.length > 1 ? '<button class="on" data-c="">Todo</button>' : '') + (cats.length > 1 ? cats.map(function (c) { return '<button data-c="' + Pd.esc(c) + '">' + Pd.esc(c) + '</button>'; }).join('') : '');
    $('tpCats').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        cat = b.dataset.c || null;
        [].forEach.call(this.children, function (x) { x.classList.toggle('on', x === b); });
        grilla();
    });
    $('tpQ').addEventListener('input', grilla);
    function enCarro(id) { return cart.filter(function (i) { return i.id === id; }).reduce(function (a, i) { return a + i.c; }, 0); }
    function grilla() {
        var q = $('tpQ').value.trim().toLowerCase();
        $('tpGrid').innerHTML = CAT.filter(function (p) { return (!cat || p.cat === cat) && (!q || (p.n + ' ' + p.et).toLowerCase().indexOf(q) >= 0); }).map(function (p) {
            var n = enCarro(p.id);
            return '<button class="tp-p' + (p.ag ? ' ag' : '') + '" data-id="' + p.id + '">' + (n ? '<span class="q">' + Pd.cant(n) + '</span>' : '') +
                (p.img ? '<img src="' + p.img + '" alt="" loading="lazy">' : '') + '<b>' + Pd.esc(p.n) + '</b>' + (p.et ? '<small>' + Pd.esc(p.et) + '</small>' : '') +
                (p.ag ? '<small class="text-danger">Agotado</small>' : '') + '<span class="pr">' + p.pt + '</span></button>';
        }).join('') || '<div class="text-muted fw-semibold p-3">No hay productos con ese nombre.</div>';
    }
    $('tpGrid').addEventListener('click', function (e) {
        var b = e.target.closest('.tp-p'); if (!b) return;
        var p = CAT.find(function (x) { return x.id === +b.dataset.id; });
        if (p.ag) { if (window.LP && LP.toast) LP.toast(p.n + ' está agotado.', 'error'); return; }
        var it = cart.find(function (i) { return i.id === p.id && !i.nota; });
        if (it) it.c++; else cart.push({ id: p.id, n: p.n, et: p.et, p: p.p, c: 1, nota: '' });
        pintar(); grilla();
    });
    function pintar() {
        $('tpItems').innerHTML = cart.length ? cart.map(function (i, k) {
            return '<div class="tp-it"><div class="r"><b>' + Pd.esc(i.n) + (i.et ? ' <small class="text-muted">' + Pd.esc(i.et) + '</small>' : '') + '<small class="d-block text-muted">' + fmt(i.p * i.c) + '</small></b>' +
                '<button data-k="' + k + '" data-d="-1">−</button><b style="flex:none;min-width:24px;text-align:center">' + Pd.cant(i.c) + '</b><button data-k="' + k + '" data-d="1">+</button></div>' +
                '<input class="form-control" data-nota="' + k + '" maxlength="160" placeholder="Nota: sin cebolla, término medio…" value="' + Pd.esc(i.nota) + '"></div>';
        }).join('') : '<div class="text-muted text-center py-3 fw-semibold">Toque los productos para agregarlos.</div>';
        $('tpTotal').textContent = fmt(cart.reduce(function (a, i) { return a + i.p * i.c; }, 0));
        $('tpEnviar').disabled = !cart.length;
    }
    $('tpItems').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-k]'); if (!b) return;
        var i = cart[+b.dataset.k]; i.c += +b.dataset.d;
        if (i.c <= 0) cart.splice(+b.dataset.k, 1);
        pintar(); grilla();
    });
    $('tpItems').addEventListener('input', function (e) { if (e.target.dataset.nota !== undefined) cart[+e.target.dataset.nota].nota = e.target.value; });
    $('tpEnviar').addEventListener('click', function () {
        var b = this; if (b.disabled) return;
        $('tpErr').style.display = 'none';
        if (!PEDIDO && !$('tpCli').value.trim() && !$('tpMesa').value.trim()) { $('tpErr').textContent = 'Escriba el nombre del cliente o la mesa.'; $('tpErr').style.display = 'block'; $('tpCli').focus(); return; }
        b.disabled = true; var txt = b.innerHTML; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enviando…';
        fetch(URL, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ pedido_id: PEDIDO, cliente_nombre: PEDIDO ? null : $('tpCli').value.trim(), mesa: PEDIDO ? null : $('tpMesa').value.trim(),
                nota: PEDIDO ? null : $('tpNota').value.trim(), items: cart.map(function (i) { return { variante_id: i.id, cantidad: i.c, nota: i.nota.trim() || null }; }) })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); }).then(function (res) {
            if (!res.ok) { var m = res.d.message || 'No se pudo enviar.'; if (res.d.errors) m = res.d.errors[Object.keys(res.d.errors)[0]][0]; throw new Error(m); }
            cart = []; window.onbeforeunload = null;
            sessionStorage.setItem('lpToast', res.d.mensaje);
            location.href = res.d.url;
        }).catch(function (err) { $('tpErr').textContent = err.message || 'Sin conexión. Intente de nuevo.'; $('tpErr').style.display = 'block'; b.disabled = false; b.innerHTML = txt; });
    });
    window.onbeforeunload = function (e) { if (cart.length) { e.preventDefault(); e.returnValue = ''; } };
    grilla(); pintar();
})();
</script>
@endpush
