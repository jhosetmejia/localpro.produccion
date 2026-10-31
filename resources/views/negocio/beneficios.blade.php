@extends('layouts.app')

@section('title', 'Comparta y gane')

@push('css')
<style>
.bf-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:1.6rem;display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:1.2rem;margin-bottom:1rem}
@media (max-width:860px){.bf-hero{grid-template-columns:minmax(0,1fr)}}
.bf-hero h2{font-weight:900;font-size:1.6rem;margin:0 0 .4rem}
.bf-hero h2 span{color:#a6f34a}
.bf-hero p{color:#c9d3cc;font-weight:500;margin:0}
.bf-cod{background:#161a18;border:1.5px solid #2a312d;border-radius:22px;padding:1.1rem;text-align:center}
.bf-cod small{color:#9fb0a5;font-weight:700;text-transform:uppercase;letter-spacing:.08em;font-size:.72rem}
.bf-cod b{display:block;font-size:2.2rem;font-weight:900;color:#a6f34a;letter-spacing:.04em;margin:.2rem 0}
.bf-cod .enl{font-size:.85rem;color:#c9d3cc;word-break:break-all}
.bf-btns{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;margin-top:.8rem}
.bf-btns .btn{border-radius:999px;font-weight:800}
.bf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;margin-bottom:1rem}
@media (max-width:900px){.bf-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:480px){.bf-grid{grid-template-columns:minmax(0,1fr)}}
.bf-b{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:1rem}
.bf-b i{font-size:1.4rem;color:#16b34a}
.bf-b b{display:block;margin:.4rem 0 .2rem}
.bf-b small{color:#5b665e;font-weight:500;line-height:1.35;display:block}
.bf-saldo{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.bf-saldo .v{font-size:2rem;font-weight:900;color:#11702f}
</style>
@endpush

@section('content')
@php
    $msg = "¡Hola! 👋 Yo manejo mi negocio con *LocalProo*: ventas, caja, inventario y mi tienda en línea, todo desde el celular.\n\nTe lo recomiendo 💚 Regístrate con mi código *{$tienda->codigo}* y tienes *2 meses gratis*:\n👉 {$enlace}";
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-gift me-1"></i>Beneficios LocalProo</small><h1>Comparta y <span>gane</span></h1>
            <span class="text-muted fw-semibold">Recomiende LocalProo a otros empresarios y reciba beneficios por cada uno que se quede.</span></div>
        <div class="lp-quick"><a href="{{ route('plan.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-gem me-1"></i>Mi plan</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    @include('negocio._gratis', ['tienda' => $tienda, 'rm' => ['invitados' => $estados->values()->all()]])

    <div class="bf-hero">
        <div>
            <h2>Gane <span>{{ $valor }}</span> cada vez que un colega pague su plan anual. ¡De por vida!</h2>
            <p>Comparta su código con otros dueños de negocio. Su colega recibe <b style="color:#fff">2 meses gratis</b> y, cada año que pague su plan anual, a usted se le abona <b style="color:#fff">{{ $valor }}</b> para su propio plan.</p>
        </div>
        <div class="bf-cod">
            <small>El código de su negocio</small>
            <b id="bfCodigo">{{ $tienda->codigo }}</b>
            <div class="enl" id="bfEnlace">{{ $enlace }}</div>
            <div class="bf-btns">
                <a href="https://wa.me/?text={{ rawurlencode($msg) }}" target="_blank" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp</a>
                <button type="button" class="btn btn-light" data-copiar="{{ $enlace }}"><i class="fa-regular fa-copy me-1"></i>Copiar enlace</button>
            </div>
        </div>
    </div>

    <div class="bf-grid">
        <div class="bf-b"><i class="fa-solid fa-coins"></i><b>Abono de por vida</b><small>{{ $valor }} por cada pago anual de cada negocio que invite, todos los años que siga pagando.</small></div>
        <div class="bf-b"><i class="fa-solid fa-tags"></i><b>Descuento en su plan</b><small>Lo que acumule se descuenta solo cuando paga su plan anual. Si alcanza, ¡su plan le sale gratis!</small></div>
        <div class="bf-b"><i class="fa-solid fa-gift"></i><b>Su colega gana</b><small>Su colega empieza con 2 meses gratis para probar todo LocalProo.</small></div>
        <div class="bf-b"><i class="fa-solid fa-rocket"></i><b>Herramientas primero</b><small>Los negocios que recomiendan LocalProo reciben primero las herramientas nuevas y recursos como cursos y plantillas.</small></div>
    </div>

    <div class="lp-box mb-3">
        <div class="bf-saldo">
            <div><small class="text-muted fw-bold">Su saldo para el plan</small><div class="v">{{ $saldoTxt }}</div>
                <small class="text-muted">Se descuenta solo cuando paga su plan anual en <b>Mi plan</b>.</small></div>
            <a href="{{ route('plan.index') }}" class="btn btn-dark rounded-pill fw-bold"><i class="fa-solid fa-gem me-1"></i>Ir a Mi plan</a>
        </div>
    </div>

    <div class="lp-box mb-3">
        <h3><span>Negocios que usted invitó</span><small class="text-muted">{{ $invitados->count() }}</small></h3>
        @forelse ($invitados as $i)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div><b>{{ $i['nombre'] }}</b><small class="d-block text-muted">Desde el {{ $i['desde']->format('d/m/Y') }}</small></div>
            <div class="text-end">@if (!empty($estados[$i['id']]['ganado']))<span class="badge" style="background:#a6f34a;color:#0b0d0c">🎁 +1 mes gratis</span> @endif @if ($i['pago'])<span class="badge bg-success">Pagó su plan anual</span>@else<span class="badge bg-secondary">En prueba gratis</span>@endif
                @if ($i['ganado'] > 0)<small class="d-block fw-bold text-success">+{{ \App\Support\Moneda::formato($i['ganado'], $moneda) }}</small>@endif</div>
        </div>
        @empty
        <p class="text-muted fw-semibold mb-0">Todavía no ha invitado a nadie. Comparta su código por WhatsApp con los negocios que conoce: el vecino, el proveedor, un amigo emprendedor.</p>
        @endforelse
    </div>

    @if ($movimientos->isNotEmpty())
    <div class="lp-box">
        <h3>Movimientos de su saldo</h3>
        @foreach ($movimientos as $m)
        <div class="d-flex justify-content-between py-1 border-bottom small">
            <span>{{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d/m/Y') }} · {{ $m->nota }}</span>
            <b class="{{ $m->valor < 0 ? 'text-danger' : 'text-success' }}">{{ $m->valor < 0 ? '−' : '+' }}{{ \App\Support\Moneda::formato(abs($m->valor), $m->moneda) }}</b>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('js')
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copiar]'); if (!b) return;
    var t = b.dataset.copiar, ok = function () { if (window.LP && LP.toast) LP.toast('Enlace copiado ✅', 'ok'); };
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(ok).catch(function () {});
    else { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); ok(); } catch (x) {} a.remove(); }
});
</script>
@endpush
