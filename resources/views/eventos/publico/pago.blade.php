@extends('eventos.publico._layout')

@section('titulo', 'Pagar entrada · ' . $evento->nombre)
@section('meta')<meta name="robots" content="noindex, nofollow">@endsection

@section('css')
.paso{display:flex;gap:.8rem;margin:.9rem 0}
.paso .n{width:32px;height:32px;flex:0 0 32px;border-radius:50%;display:grid;place-items:center;background:var(--c);color:#fff;font-weight:800}
.nq{background:#0f0e14;border:1.5px dashed var(--c);border-radius:18px;padding:16px;text-align:center;margin:12px 0}
.nq .num{font-size:2rem;font-weight:800;letter-spacing:.04em}
.nq button{margin-top:8px;background:transparent;border:1px solid var(--line);color:var(--tx);border-radius:999px;padding:.45rem 1rem;font:inherit;font-weight:700;cursor:pointer}
.valor{display:flex;justify-content:space-between;align-items:center;font-weight:800;font-size:1.15rem}
@endsection

@section('content')
<div class="card" style="margin-top:20px">
    <span class="badge b-wait"><i class="fa-solid fa-hourglass-half"></i> {{ $entrada->estado === 'rechazada' ? 'Pago rechazado' : 'Pago pendiente' }}</span>
    <h2 style="margin-top:.7rem">¡Listo, {{ \Illuminate\Support\Str::of($yo->nombre)->explode(' ')->first() }}! Ya quedaste registrado</h2>
    <p class="mu">Ahora haz el pago y envíanos el comprobante para generar tu entrada.</p>
    @if ($entrada->estado === 'rechazada')
    <div class="aviso" style="margin-top:12px">El organizador no pudo confirmar tu pago{{ $entrada->nota ? ': ' . $entrada->nota : '' }}. Escríbele por WhatsApp para revisarlo.</div>
    @endif
</div>

<div class="card">
    <div class="valor"><span>Total a pagar</span><span style="color:var(--c)">{{ $evento->plata($entrada->precio) }}</span></div>

    <div class="paso"><span class="n">1</span><div><b>Envía tu pago a este {{ $evento->pago_metodo }}</b>
        <div class="nq"><div class="mu" style="font-size:.85rem">{{ $evento->pago_metodo }}</div><div class="num" id="nq">{{ $evento->pago_numero }}</div>
            @if ($evento->pago_titular)<div class="mu">A nombre de {{ $evento->pago_titular }}</div>@endif
            <button type="button" id="copiar"><i class="fa-regular fa-copy"></i> Copiar número</button></div>
    </div></div>

    <div class="paso"><span class="n">2</span><div><b>Toma captura del comprobante</b><div class="mu">El pantallazo donde se ve que el pago salió.</div></div></div>

    <div class="paso"><span class="n">3</span><div><b>Envíalo por WhatsApp</b><div class="mu">Toca el botón: se abre el chat con el mensaje listo. Solo adjunta la captura.</div></div></div>

    @if ($evento->whatsapp_link)
    <form method="post" action="{{ route('evento.aviso', $evento->slug) }}" style="margin-top:14px">
        @csrf
        <button class="btn btn-wa"><i class="fa-brands fa-whatsapp" style="font-size:1.4rem"></i> Enviar comprobante de pago</button>
    </form>
    @endif
    @if ($entrada->aviso_pago_at)
    <p class="mu" style="text-align:center;margin-top:10px;font-size:.88rem"><i class="fa-solid fa-check" style="color:var(--ok)"></i> Nos avisaste el {{ $entrada->aviso_pago_at->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') }}.</p>
    @endif
</div>

<div class="card">
    <h2><i class="fa-solid fa-qrcode" style="color:var(--c)"></i> ¿Y después?</h2>
    <p class="mu">Cuando el organizador revise tu comprobante y apruebe el pago, tu entrada con QR aparece en <b style="color:var(--tx)">Mi entrada</b> y te llega un correo a {{ $yo->email }}.</p>
    <a class="btn btn-l" style="margin-top:14px" href="{{ route('mis-entradas.index') }}"><i class="fa-solid fa-ticket"></i> Ir a Mis entradas</a>
</div>
@endsection

@section('js')
<script>
document.getElementById('copiar').addEventListener('click', function () {
    var t = document.getElementById('nq').textContent.replace(/\s/g, ''), b = this;
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { b.innerHTML = '<i class="fa-solid fa-check"></i> Copiado'; })
        .catch(function () { var r = document.createRange(); r.selectNode(document.getElementById('nq')); getSelection().removeAllRanges(); getSelection().addRange(r); });
});
</script>
@endsection
