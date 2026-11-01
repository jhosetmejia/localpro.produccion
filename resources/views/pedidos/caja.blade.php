@extends('layouts.app')

@section('title', 'Pedidos por cobrar')

@push('css')
<style>
.cj-kpi{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;margin-bottom:1rem}
@media (max-width:700px){.cj-kpi{grid-template-columns:1fr 1fr}}
.cj-kpi div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem}
.cj-kpi div.v{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.cj-kpi b{display:block;font-size:1.5rem;font-weight:900;line-height:1.1}.cj-kpi .v b{color:#a6f34a}
.cj-kpi small{font-weight:700;color:#6b7570}.cj-kpi .v small{color:#b8c4bc}
.cj-row{display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.85rem 1rem;margin-bottom:.55rem}
.cj-row.nuevo{border-color:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,.15)}
.cj-row .t{flex:1 1 240px;min-width:0}
.cj-row .t b{font-size:1.1rem}
.cj-row .t small{display:block;color:#6b7570;font-weight:600}
.cj-row .v{font-weight:900;font-size:1.25rem;min-width:110px;text-align:right}
.cj-cancel{border:1.5px solid #f3c2d6;background:#fff;color:#b0206a;border-radius:999px;padding:.62rem 1rem;font-weight:800;white-space:nowrap}
.cj-cancel:hover{background:#ffe9f3}
.cj-cancel:disabled{opacity:.6}
/* Al cancelar: se pone roja, se va hacia la derecha y la lista se cierra sola */
.cj-row{transition:max-height .35s ease .25s,margin .35s ease .25s,padding .35s ease .25s,border-width .35s ease .25s;max-height:400px;overflow:hidden}
.cj-row.sale{animation:cjSale .45s cubic-bezier(.5,0,.75,0) forwards;max-height:0;margin-bottom:0;padding-top:0;padding-bottom:0;border-width:0}
@keyframes cjSale{0%{transform:none;opacity:1}25%{transform:scale(.98);background:#ffe9f3;border-color:#f3c2d6}100%{transform:translateX(110%) rotate(2deg);opacity:0;background:#ffe9f3}}
.cj-btn{border:0;border-radius:999px;padding:.7rem 1.2rem;font-weight:900;background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;text-decoration:none;white-space:nowrap}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-cash-register me-1"></i>Pedidos</small><h1>Por <span>cobrar</span></h1>
            <span class="text-muted fw-semibold">Llegan con el nombre del cliente. Toque <b>Cobrar</b>: se abre la caja con el pedido cargado y, al pagar, {{ $tienda->pedidos_cobro === 'despues' ? 'queda cerrado' : 'pasa solo a preparar' }}.</span></div>
        @if (auth()->user()->can('pedidos-tomar') || auth()->user()->can('pedidos-administrar'))
        <div class="lp-quick"><a href="{{ route('pedidos.crear') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Tomar pedido</a></div>
        @endif
    </div>
    @include('pedidos._nav')
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (!$caja)<div class="alert alert-warning"><i class="fa-solid fa-lock me-1"></i>Su caja está cerrada. <a href="{{ route('cajas.abrir') }}" class="fw-bold">Ábrala</a> para cobrar.</div>@endif

    <div class="cj-kpi">
        <div class="v"><b id="cjN">{{ $porCobrar->count() }}</b><small>por cobrar</small></div>
        <div><b>{{ $abiertos->count() }}</b><small>preparando o en mesa</small></div>
        <div><b>{{ dinero($hoy->t ?? 0) }}</b><small>{{ (int) ($hoy->n ?? 0) }} pedidos cobrados hoy</small></div>
    </div>

    <h3 class="h5 fw-bold">Por cobrar</h3>
    @forelse ($porCobrar as $p)
    <div class="cj-row {{ $p->created_at->gt(now()->subMinutes(2)) ? 'nuevo' : '' }}" data-por-cobrar>
        <div class="t"><a href="{{ route('pedidos.show', $p) }}" class="text-reset text-decoration-none"><b>#{{ $p->numero }} {{ $p->cliente_nombre }}</b></a>
            <small>@if ($p->mesa)Mesa {{ $p->mesa }} · @endif{{ $p->mesero?->name }} · {{ $p->created_at->locale('es')->diffForHumans() }}@if ($p->cuenta_pedida) · <b class="text-warning">pidió la cuenta</b>@endif</small>
            <small>{{ $p->items->where('estado', '<>', 'anulado')->map(fn ($i) => cantidad($i->cantidad) . ' × ' . $i->nombre)->join(' · ') }}</small></div>
        <div class="v">{{ dinero($p->total) }}</div>
        <button type="button" class="cj-cancel" data-cancelar="{{ route('pedidos.anular', $p) }}" data-titulo="#{{ $p->numero }} {{ $p->cliente_nombre }}"><i class="fa-solid fa-xmark me-1"></i>Cancelar</button>
        <a href="{{ route('pos.index', ['pedido' => $p->id]) }}" class="cj-btn"><i class="fa-solid fa-cash-register me-1"></i>Cobrar</a>
    </div>
    @empty
    <div class="lp-box text-center text-muted fw-semibold py-4" id="cjVacio">No hay pedidos por cobrar. Esta pantalla se actualiza sola.</div>
    @endforelse

    @if ($abiertos->isNotEmpty())
    <h3 class="h5 fw-bold mt-4">En preparación o en la mesa</h3>
    @foreach ($abiertos as $p)
    @php $e = \App\Models\Pedido::ESTADOS[$p->estado]; @endphp
    <div class="cj-row">
        <div class="t"><a href="{{ route('pedidos.show', $p) }}" class="text-reset text-decoration-none"><b>#{{ $p->numero }} {{ $p->cliente_nombre }}</b></a>
            <small>@if ($p->mesa)Mesa {{ $p->mesa }} · @endif{{ $p->mesero?->name }}</small></div>
        <span class="pd-est" style="color:{{ $e[1] }};background:{{ $e[2] }}">{{ $e[0] }}</span>
        <div class="v">{{ dinero($p->total) }}@if ($p->pagado) <i class="fa-solid fa-circle-check text-success" title="Pagado"></i>@endif</div>
        @unless ($p->pagado)
        <button type="button" class="cj-cancel" data-cancelar="{{ route('pedidos.anular', $p) }}" data-titulo="#{{ $p->numero }} {{ $p->cliente_nombre }}"><i class="fa-solid fa-xmark me-1"></i>Cancelar</button>
        @endunless
    </div>
    @endforeach
    @endif
</div>
@endsection

@push('js')
@include('pedidos._sonido')
<script>
(function () {
    var n = {{ $porCobrar->count() }}, ult = {{ (int) \App\Models\Pedido::max('id') }};
    var CSRF = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
    // Cancelar pedido: confirma, lo anula y la fila se va con animación (sin recargar)
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-cancelar]'); if (!b || b.disabled) return;
        var fila = b.closest('.cj-row');
        (window.LP && LP.confirmar ? LP.confirmar({ titulo: '¿Cancelar el pedido ' + b.dataset.titulo + '?', texto: 'Se quita de la lista y de la cocina. No se puede deshacer.', ok: 'Sí, cancelar', cancelar: 'No', peligro: true }) : Promise.resolve(true))
        .then(function (si) {
            if (!si) return;
            b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Cancelando…';
            fetch(b.dataset.cancelar, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) throw new Error(res.d.message || 'No se pudo cancelar.');
                    var cobrar = fila.hasAttribute('data-por-cobrar');
                    if (cobrar) { n = Math.max(0, n - 1); document.getElementById('cjN').textContent = n; }
                    fila.classList.add('sale');
                    setTimeout(function () {
                        var antes = fila.previousElementSibling;
                        fila.remove();
                        if (cobrar && !document.querySelector('[data-por-cobrar]') && antes) {
                            antes.insertAdjacentHTML('afterend', '<div class="lp-box text-center text-muted fw-semibold py-4">No hay pedidos por cobrar. Esta pantalla se actualiza sola.</div>');
                        }
                    }, 750);
                    if (window.LP && LP.toast) LP.toast(res.d.mensaje || 'Pedido cancelado.');
                })
                .catch(function (err) {
                    b.disabled = false; b.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Cancelar';
                    if (window.LP && LP.toast) LP.toast(err.message || 'Sin conexión. Intente de nuevo.', 'error');
                });
        });
    });
    setInterval(function () {
        fetch(@json(route('pedidos.caja.datos')), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.por_cobrar !== n || d.ultimo !== ult) {
                if (d.por_cobrar > n) Pd.sonar(2);
                setTimeout(function () { location.reload(); }, d.por_cobrar > n ? 900 : 0);
            }
        }).catch(function () {});
    }, 7000);
})();
</script>
@endpush
